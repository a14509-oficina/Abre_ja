#!/usr/bin/env python3
"""
Abre Já — Sistema de Controlo de Acesso por Matrícula
Versão Definitive 4.0 — Com log de acessos negados e matrícula correta
"""

import os
os.environ["OMP_THREAD_LIMIT"] = "1"
os.environ["MKL_NUM_THREADS"] = "1"
os.environ["OPENBLAS_NUM_THREADS"] = "1"
os.environ["VECLIB_MAXIMUM_THREADS"] = "1"
os.environ["NUMEXPR_NUM_THREADS"] = "1"

import cv2
import pytesseract
import requests
import time
import re
import logging
from dotenv import load_dotenv

try:
    import RPi.GPIO as GPIO
    GPIO_DISPONIVEL = True
except ImportError:
    GPIO_DISPONIVEL = False

load_dotenv()

CAMERA_URL   = os.getenv("CAMERA_URL")
SUPABASE_URL = os.getenv("SUPABASE_URL")
SUPABASE_KEY = os.getenv("SUPABASE_KEY")

RELAY_PIN    = int(os.getenv("RELAY_PIN",    "17"))
RELAY_TIME   = int(os.getenv("RELAY_TIME",   "5"))
COOLDOWN     = int(os.getenv("COOLDOWN",     "20"))
CACHE_TTL    = int(os.getenv("CACHE_TTL",    "300"))

logging.basicConfig(
    level=logging.INFO,
    format="%(asctime)s [%(levelname)s] %(message)s",
    handlers=[logging.StreamHandler()]
)
log = logging.getLogger(__name__)

if GPIO_DISPONIVEL:
    GPIO.setmode(GPIO.BCM)
    GPIO.setup(RELAY_PIN, GPIO.OUT, initial=GPIO.HIGH)

TESSERACT_CONFIG = r'--oem 3 --psm 7 -c tessedit_char_whitelist=ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'

cache_matriculas    = {}   # plate -> {id, user_id}
cache_timestamp     = 0
ultimo_processamento = 0
ultima_matricula     = ""
ultimo_acesso        = 0

HEADERS = {
    "apikey":        SUPABASE_KEY,
    "Authorization": f"Bearer {SUPABASE_KEY}",
    "Content-Type":  "application/json",
    "Prefer":        "return=minimal"
}

def atualizar_cache() -> None:
    global cache_matriculas, cache_timestamp
    if not SUPABASE_KEY:
        return
    try:
        res = requests.get(
            f"{SUPABASE_URL}/rest/v1/cars?select=id,plate,user_id",
            headers=HEADERS, timeout=5
        )
        if res.status_code == 200:
            cache_matriculas = {
                d["plate"]: {"id": d["id"], "user_id": d["user_id"]}
                for d in res.json() if d.get("plate")
            }
            cache_timestamp = time.time()
            log.info(f"Cache atualizada: {list(cache_matriculas.keys())}")
    except Exception as e:
        log.error(f"Erro ao carregar cache: {e}")

def verificar_matricula(matricula: str):
    """Retorna dados do carro se autorizado, None se não."""
    global cache_timestamp
    if time.time() - cache_timestamp > CACHE_TTL:
        atualizar_cache()
    return cache_matriculas.get(matricula)

def registar_acesso(matricula: str, autorizado: bool, car_data: dict = None) -> None:
    """Regista acesso (autorizado ou negado) na tabela access_log."""
    try:
        gate_id = None
        user_id = None

        if car_data:
            user_id = car_data.get("user_id")
            car_id  = car_data.get("id")
            # Procurar portão associado ao carro
            res = requests.get(
                f"{SUPABASE_URL}/rest/v1/car_gate_links?car_id=eq.{car_id}&select=gate_id",
                headers=HEADERS, timeout=5
            )
            links = res.json()
            gate_id = links[0]["gate_id"] if links else None

        payload = {
            "gate_id":    gate_id,
            "user_id":    user_id,
            "plate":      matricula,          # ← CORRIGIDO: agora envia a matrícula
            "method":     "plate" if autorizado else "plate_denied",
            "ip_address": "raspberry_pi",
        }
        res = requests.post(
            f"{SUPABASE_URL}/rest/v1/access_log",
            json=payload, headers=HEADERS, timeout=5
        )
        status = "✅ AUTORIZADO" if autorizado else "❌ NEGADO"
        log.info(f"Log enviado [{status}] matrícula={matricula} HTTP={res.status_code}")
    except Exception as e:
        log.error(f"Falha ao enviar log: {e}")

def abrir_portao(matricula: str, car_data: dict) -> None:
    log.info(f"🔓 ACESSO AUTORIZADO: {matricula}")
    if GPIO_DISPONIVEL:
        GPIO.output(RELAY_PIN, GPIO.LOW)
        time.sleep(RELAY_TIME)
        GPIO.output(RELAY_PIN, GPIO.HIGH)
    registar_acesso(matricula, autorizado=True, car_data=car_data)

def acesso_negado(matricula: str) -> None:
    log.warning(f"🚫 ACESSO NEGADO: {matricula}")
    registar_acesso(matricula, autorizado=False, car_data=None)

def detetar_roi(frame):
    cinza   = cv2.cvtColor(frame, cv2.COLOR_BGR2GRAY)
    binario = cv2.adaptiveThreshold(cinza, 255, cv2.ADAPTIVE_THRESH_GAUSSIAN_C, cv2.THRESH_BINARY, 11, 2)
    contours, _ = cv2.findContours(binario, cv2.RETR_TREE, cv2.CHAIN_APPROX_SIMPLE)
    for c in contours:
        x, y, w, h = cv2.boundingRect(c)
        ratio = w / float(h) if h > 0 else 0
        if 2.2 < ratio < 5.8 and w > 60 and h > 15:
            roi = cinza[y:y+h, x:x+w]
            roi_perfeita = cv2.resize(roi, (320, 80))
            return roi_perfeita, (x, y, w, h)
    return None, None

def validar_matricula_pt(texto: str) -> str:
    limpo = re.sub(r'[^A-Z0-9]', '', texto.upper())
    if len(limpo) == 6:
        return limpo
    return ""

def main() -> None:
    global ultimo_processamento, ultima_matricula, ultimo_acesso
    log.info("🚀 Abre Já — Iniciado.")
    atualizar_cache()

    cap = cv2.VideoCapture(CAMERA_URL)
    if not cap.isOpened():
        log.error("Não foi possível conectar à câmara.")
        return

    while True:
        ret, frame = cap.read()
        if not ret:
            continue

        frame = cv2.resize(frame, (640, 480))
        roi, bbox = detetar_roi(frame)

        if bbox:
            x, y, w, h = bbox
            cv2.rectangle(frame, (x, y), (x+w, y+h), (0, 255, 0), 2)

        cv2.imshow("Abre Ja", frame)
        if cv2.waitKey(1) & 0xFF == ord('q'):
            break

        if roi is None:
            continue

        agora = time.time()
        if agora - ultimo_processamento < 3.0:
            continue
        ultimo_processamento = agora

        try:
            texto     = pytesseract.image_to_string(roi, config=TESSERACT_CONFIG).strip()
            matricula = validar_matricula_pt(texto)
            if not matricula:
                continue

            matricula_fmt = f"{matricula[0:2]}-{matricula[2:4]}-{matricula[4:6]}"
            log.info(f"🔎 Matrícula detetada: {matricula_fmt}")

            # Evitar processar a mesma matrícula em menos de COOLDOWN segundos
            if matricula_fmt == ultima_matricula and agora - ultimo_acesso < COOLDOWN:
                log.info("Em cooldown, a ignorar...")
                continue

            ultima_matricula = matricula_fmt
            ultimo_acesso    = agora

            car_data = verificar_matricula(matricula_fmt)
            if car_data:
                abrir_portao(matricula_fmt, car_data)
            else:
                acesso_negado(matricula_fmt)

        except Exception as e:
            log.error(f"Erro no processamento: {e}")

    cap.release()
    cv2.destroyAllWindows()
    if GPIO_DISPONIVEL:
        GPIO.cleanup()

if __name__ == "__main__":
    main()