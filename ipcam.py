#!/usr/bin/env python3
"""
Abre Já — Sistema de Controlo de Acesso por Matrícula
Raspberry Pi + Camera IP + Supabase
Versão 3.0
"""

import cv2
import easyocr
import requests
import time
import re
import logging
import os
import threading
from collections import deque
from dotenv import load_dotenv

# Tenta importar GPIO — só disponível no Raspberry Pi
try:
    import RPi.GPIO as GPIO
    GPIO_DISPONIVEL = True
except ImportError:
    GPIO_DISPONIVEL = False
    print("⚠️  RPi.GPIO não disponível — modo simulação ativo.")

# ─── Carregar variáveis de ambiente ──────────────────────────
load_dotenv()

# ─── Configuração ────────────────────────────────────────────
CAMERA_URL   = os.getenv("CAMERA_URL",   "http://192.168.1.100:8080/video?640x480")
SUPABASE_URL = os.getenv("SUPABASE_URL", "https://fmjytigqgpfocurpjvtv.supabase.co")
SUPABASE_KEY = os.getenv("SUPABASE_KEY", "")  # Obrigatório no .env

RELAY_PIN    = int(os.getenv("RELAY_PIN",   "17"))
RELAY_TIME   = int(os.getenv("RELAY_TIME",  "5"))
COOLDOWN     = int(os.getenv("COOLDOWN",    "20"))
CONFIRMAR_EM = int(os.getenv("CONFIRMAR_EM","3"))   # frames para confirmar
CACHE_TTL    = int(os.getenv("CACHE_TTL",   "300"))  # segundos cache local
# ─────────────────────────────────────────────────────────────

# ─── Logs persistentes ────────────────────────────────────────
logging.basicConfig(
    level=logging.INFO,
    format="%(asctime)s [%(levelname)s] %(message)s",
    handlers=[
        logging.FileHandler("portao.log", encoding="utf-8"),
        logging.StreamHandler()
    ]
)
log = logging.getLogger(__name__)

# ─── GPIO ─────────────────────────────────────────────────────
if GPIO_DISPONIVEL:
    GPIO.setmode(GPIO.BCM)
    GPIO.setup(RELAY_PIN, GPIO.OUT, initial=GPIO.HIGH)

# ─── EasyOCR ──────────────────────────────────────────────────
log.info("A carregar modelo EasyOCR (primeira vez pode demorar)...")
reader = easyocr.Reader(['en'], gpu=False)
log.info("Modelo carregado!")

# ─── Estado global ────────────────────────────────────────────
rele_ocupado        = False
historico_leituras  = deque(maxlen=CONFIRMAR_EM)
ultimo_acesso       = {}      # {matricula: timestamp}
cache_matriculas    = set()   # cache local
cache_timestamp     = 0


# ══════════════════════════════════════════════════════════════
#  SUPABASE
# ══════════════════════════════════════════════════════════════

def atualizar_cache() -> None:
    """Carrega todas as matrículas autorizadas para memória local."""
    global cache_matriculas, cache_timestamp
    if not SUPABASE_KEY:
        log.error("SUPABASE_KEY não configurada no .env!")
        return
    try:
        url = f"{SUPABASE_URL}/rest/v1/cars?select=plate"
        headers = {
            "apikey":        SUPABASE_KEY,
            "Authorization": f"Bearer {SUPABASE_KEY}",
        }
        res = requests.get(url, headers=headers, timeout=5)
        res.raise_for_status()
        dados = res.json()
        cache_matriculas = {d["plate"] for d in dados if d.get("plate")}
        cache_timestamp  = time.time()
        log.info(f"Cache atualizada: {len(cache_matriculas)} matrícula(s) autorizada(s).")
    except Exception as e:
        log.error(f"Erro ao atualizar cache: {e}")


def verificar_matricula(matricula: str) -> bool:
    """Verifica na cache local. Atualiza cache se expirada."""
    global cache_timestamp
    if time.time() - cache_timestamp > CACHE_TTL:
        atualizar_cache()
    autorizada = matricula in cache_matriculas
    log.info(f"{'✅' if autorizada else '❌'} {matricula} {'autorizada' if autorizada else 'não autorizada'} (cache local).")
    return autorizada


def registar_acesso_supabase(matricula: str) -> None:
    """Regista o acesso no histórico do Supabase (não bloqueante)."""
    def _enviar():
        try:
            # Procura gate_id associado à matrícula via car_gate_links
            url_car = f"{SUPABASE_URL}/rest/v1/cars?plate=eq.{matricula}&select=id"
            headers = {
                "apikey":        SUPABASE_KEY,
                "Authorization": f"Bearer {SUPABASE_KEY}",
                "Content-Type":  "application/json",
            }
            res_car = requests.get(url_car, headers=headers, timeout=4)
            cars = res_car.json()
            if not cars:
                return
            car_id = cars[0]["id"]

            # Procura gate associado a este carro
            url_link = f"{SUPABASE_URL}/rest/v1/car_gate_links?car_id=eq.{car_id}&select=gate_id"
            res_link = requests.get(url_link, headers=headers, timeout=4)
            links = res_link.json()
            if not links:
                return
            gate_id = links[0]["gate_id"]

            # Procura user_id dono do carro
            url_user = f"{SUPABASE_URL}/rest/v1/cars?id=eq.{car_id}&select=user_id"
            res_user = requests.get(url_user, headers=headers, timeout=4)
            users = res_user.json()
            user_id = users[0]["user_id"] if users else None

            # Regista no access_log
            payload = {
                "gate_id":    gate_id,
                "user_id":    user_id,
                "plate":      matricula,
                "method":     "plate",
                "ip_address": "raspberry_pi",
            }
            requests.post(
                f"{SUPABASE_URL}/rest/v1/access_log",
                json=payload,
                headers={**headers, "Prefer": "return=minimal"},
                timeout=4
            )
            log.info(f"Acesso registado no Supabase: {matricula}")
        except Exception as e:
            log.warning(f"Não foi possível registar acesso: {e}")

    threading.Thread(target=_enviar, daemon=True).start()


# ══════════════════════════════════════════════════════════════
#  RELÉ
# ══════════════════════════════════════════════════════════════

def _thread_rele() -> None:
    global rele_ocupado
    try:
        rele_ocupado = True
        log.info("🔓 Portão a abrir...")
        if GPIO_DISPONIVEL:
            GPIO.output(RELAY_PIN, GPIO.LOW)
        time.sleep(RELAY_TIME)
        if GPIO_DISPONIVEL:
            GPIO.output(RELAY_PIN, GPIO.HIGH)
        log.info("🔒 Portão fechado.")
    except Exception as e:
        log.error(f"Erro no relé: {e}")
    finally:
        rele_ocupado = False


def abrir_portao(matricula: str) -> None:
    """Abre o portão em thread separada e regista o acesso."""
    if rele_ocupado:
        log.info("Relé já em uso, ignorando.")
        return
    threading.Thread(target=_thread_rele, daemon=True).start()
    registar_acesso_supabase(matricula)


# ══════════════════════════════════════════════════════════════
#  IMAGEM / OCR
# ══════════════════════════════════════════════════════════════

def detetar_roi(frame):
    """
    Deteta a região mais provável da matrícula.
    Retorna (roi, bbox) ou (None, None).
    """
    cinza   = cv2.cvtColor(frame, cv2.COLOR_BGR2GRAY)
    cinza   = cv2.GaussianBlur(cinza, (5, 5), 0)
    binario = cv2.adaptiveThreshold(
        cinza, 255,
        cv2.ADAPTIVE_THRESH_GAUSSIAN_C,
        cv2.THRESH_BINARY, 11, 2
    )
    contours, _ = cv2.findContours(binario, cv2.RETR_TREE, cv2.CHAIN_APPROX_SIMPLE)

    candidatos = []
    for c in contours:
        x, y, w, h = cv2.boundingRect(c)
        if h == 0:
            continue
        ratio = w / float(h)
        # Matrículas europeias têm ratio ~2.5–5.5
        if 2.5 < ratio < 5.5 and w > 80 and h > 20:
            candidatos.append((w * h, x, y, w, h))

    if not candidatos:
        return None, None

    # Maior área — mais provável ser a matrícula
    candidatos.sort(reverse=True)
    _, x, y, w, h = candidatos[0]
    roi = frame[y:y+h, x:x+w]
    return roi, (x, y, w, h)


PADROES_PT = [
    r'^[A-Z]{2}-\d{2}-[A-Z]{2}$',   # AA-00-AA  (2020+)
    r'^\d{2}-[A-Z]{2}-\d{2}$',      # 00-AA-00  (2005-2020)
    r'^[A-Z]{2}-\d{2}-\d{2}$',      # AA-00-00  (até 2005)
    r'^\d{2}-\d{2}-[A-Z]{2}$',      # 00-00-AA  (antigo)
]

def validar_matricula_pt(texto: str) -> str:
    """
    Limpa o texto OCR e valida contra formatos portugueses.
    Retorna a matrícula formatada ou string vazia.
    """
    limpo = re.sub(r'[^A-Z0-9]', '', texto.upper())
    if len(limpo) != 6:
        return ""
    formatada = f"{limpo[0:2]}-{limpo[2:4]}-{limpo[4:6]}"
    for padrao in PADROES_PT:
        if re.match(padrao, formatada):
            return formatada
    return ""


# ══════════════════════════════════════════════════════════════
#  LOOP PRINCIPAL
# ══════════════════════════════════════════════════════════════

def main() -> None:
    log.info("🚀 Abre Já — Sistema iniciado.")

    if not SUPABASE_KEY:
        log.error("SUPABASE_KEY não configurada! Cria o ficheiro .env")
        return

    # Cache inicial
    atualizar_cache()

    cap = cv2.VideoCapture(CAMERA_URL)
    if not cap.isOpened():
        log.error(f"Não foi possível conectar à câmera: {CAMERA_URL}")
        return
    log.info(f"📷 Câmera conectada: {CAMERA_URL}")

    try:
        while True:
            ret, frame = cap.read()

            # ─── Reconexão automática ────────────────────────────
            if not ret:
                log.warning("⚠️  Stream perdido. A tentar reconectar em 2s...")
                cap.release()
                time.sleep(2)
                cap = cv2.VideoCapture(CAMERA_URL)
                continue

            frame = cv2.resize(frame, (640, 480))

            # ─── Deteção da ROI ──────────────────────────────────
            roi, bbox = detetar_roi(frame)
            if roi is None:
                continue

            # Desenha retângulo de debug (descomenta para ver no monitor)
            # x, y, w, h = bbox
            # cv2.rectangle(frame, (x, y), (x+w, y+h), (0, 255, 0), 2)

            # ─── OCR na ROI ──────────────────────────────────────
            resultados = reader.readtext(roi)
            for (_, texto, confianca) in resultados:
                if confianca < 0.4:
                    continue

                matricula = validar_matricula_pt(texto)
                if not matricula:
                    continue

                historico_leituras.append(matricula)
                log.info(f"🔍 OCR: {matricula} ({confianca:.0%})")

                # ─── Confirmação multiframes ─────────────────────
                if historico_leituras.count(matricula) < CONFIRMAR_EM:
                    continue

                # ─── Cooldown por matrícula ──────────────────────
                agora = time.time()
                if agora - ultimo_acesso.get(matricula, 0) < COOLDOWN:
                    restante = int(COOLDOWN - (agora - ultimo_acesso[matricula]))
                    log.info(f"⏳ Cooldown para {matricula}: {restante}s restantes.")
                    continue

                log.info(f"🔐 Matrícula estável: {matricula}")

                # ─── Verificar autorização ───────────────────────
                if verificar_matricula(matricula):
                    log.info(f"✅ ACESSO AUTORIZADO: {matricula}")
                    ultimo_acesso[matricula] = agora
                    historico_leituras.clear()
                    abrir_portao(matricula)
                else:
                    log.warning(f"🚫 ACESSO NEGADO: {matricula}")

            # ─── Preview (descomenta para debug com monitor) ─────
            # cv2.imshow("Abre Já", frame)
            # if cv2.waitKey(1) & 0xFF == ord('q'):
            #     break

    except KeyboardInterrupt:
        log.info("Sistema parado pelo utilizador.")
    finally:
        try:
            cap.release()
        except Exception:
            pass
        if GPIO_DISPONIVEL:
            GPIO.cleanup()
        cv2.destroyAllWindows()
        log.info("✅ Sistema terminado.")


if __name__ == "__main__":
    main()
