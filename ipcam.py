#!/usr/bin/env python3
"""
Abre Já — Sistema de Controlo de Acesso por Matrícula
Versão Definitiva 6.0 — Stream de vídeo + logs garantidos
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
import numpy as np
from dotenv import load_dotenv

try:
    import RPi.GPIO as GPIO
    GPIO_DISPONIVEL = True
except ImportError:
    GPIO_DISPONIVEL = False

load_dotenv()

CAMERA_URL = os.getenv("CAMERA_URL")
SUPABASE_URL = os.getenv("SUPABASE_URL")
SUPABASE_KEY = os.getenv("SUPABASE_KEY")

RELAY_PIN = int(os.getenv("RELAY_PIN", "17"))
RELAY_TIME = int(os.getenv("RELAY_TIME", "5"))
COOLDOWN = int(os.getenv("COOLDOWN", "20"))
CACHE_TTL = int(os.getenv("CACHE_TTL", "300"))

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

cache_matriculas = {}
cache_timestamp = 0
ultima_matricula = ""
ultimo_acesso = 0

HEADERS = {
    "apikey": SUPABASE_KEY,
    "Authorization": f"Bearer {SUPABASE_KEY}",
    "Content-Type": "application/json",
    "Prefer": "return=minimal"
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
            log.info(f"✅ Cache atualizada: {list(cache_matriculas.keys())}")
        else:
            log.error(f"Erro ao carregar cache: HTTP {res.status_code}")
    except Exception as e:
        log.error(f"Erro ao carregar cache: {e}")

def verificar_matricula(matricula: str):
    global cache_timestamp
    if time.time() - cache_timestamp > CACHE_TTL:
        atualizar_cache()
    return cache_matriculas.get(matricula)

def registar_acesso(matricula: str, autorizado: bool, car_data: dict = None) -> None:
    """Envia log para o Supabase — sempre, autorizado ou negado."""
    try:
        gate_id = None
        user_id = None

        if car_data:
            user_id = car_data.get("user_id")
            car_id = car_data.get("id")
            res = requests.get(
                f"{SUPABASE_URL}/rest/v1/car_gate_links?car_id=eq.{car_id}&select=gate_id",
                headers=HEADERS, timeout=5
            )
            if res.status_code == 200 and res.json():
                gate_id = res.json()[0]["gate_id"]

        payload = {
            "gate_id": gate_id,
            "user_id": user_id,
            "plate": matricula,
            "method": "plate" if autorizado else "plate_denied",
            "ip_address": "raspberry_pi",
        }

        res = requests.post(
            f"{SUPABASE_URL}/rest/v1/access_log",
            json=payload, headers=HEADERS, timeout=5
        )

        if res.status_code in (200, 201):
            status = "✅ AUTORIZADO" if autorizado else "❌ NEGADO"
            log.info(f"📡 Log enviado [{status}] matrícula={matricula}")
        else:
            log.error(f"Falha ao enviar log: HTTP {res.status_code} — {res.text}")

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

def capturar_frame_limpo(cap) -> np.ndarray:
    """
    Lê vários frames para limpar o buffer e evitar frames verdes/corrompidos.
    """
    frame_valido = None
    for _ in range(6):
        ret, frame = cap.read()
        if not ret or frame is None:
            continue
        # Verificar se o frame não é maioritariamente verde (corrompido)
        hsv = cv2.cvtColor(frame, cv2.COLOR_BGR2HSV)
        verde_mask = cv2.inRange(hsv, (40, 50, 50), (80, 255, 255))
        percentagem_verde = np.sum(verde_mask > 0) / verde_mask.size
        if percentagem_verde > 0.3:
            log.debug(f"Frame verde ignorado ({percentagem_verde:.0%} verde)")
            continue
        frame_valido = frame
    return frame_valido

def pre_processar_roi(roi: np.ndarray) -> np.ndarray:
    """Melhora a imagem da matrícula para o OCR."""
    roi = cv2.resize(roi, (480, 120), interpolation=cv2.INTER_CUBIC)
    cinza = cv2.cvtColor(roi, cv2.COLOR_BGR2GRAY)
    cinza = cv2.equalizeHist(cinza)
    cinza = cv2.GaussianBlur(cinza, (3, 3), 0)
    return cv2.adaptiveThreshold(
        cinza, 255, cv2.ADAPTIVE_THRESH_GAUSSIAN_C, cv2.THRESH_BINARY, 15, 8
    )

def detetar_roi(frame: np.ndarray):
    """Deteta retângulos com proporção de matrícula."""
    cinza = cv2.cvtColor(frame, cv2.COLOR_BGR2GRAY)
    binario = cv2.adaptiveThreshold(cinza, 255, cv2.ADAPTIVE_THRESH_GAUSSIAN_C, cv2.THRESH_BINARY, 11, 2)
    contours, _ = cv2.findContours(binario, cv2.RETR_TREE, cv2.CHAIN_APPROX_SIMPLE)

    candidatos = []
    for c in contours:
        x, y, w, h = cv2.boundingRect(c)
        if h == 0:
            continue
        ratio = w / float(h)
        area = w * h
        if 2.0 < ratio < 6.5 and w > 70 and h > 18 and area > 1800:
            candidatos.append((x, y, w, h))

    return candidatos

def validar_matricula_pt(texto: str) -> str:
    limpo = re.sub(r'[^A-Z0-9]', '', texto.upper())
    if len(limpo) == 6:
        return limpo
    matches = re.findall(r'[A-Z0-9]{6}', limpo)
    return matches[0] if matches else ""

def main() -> None:
    global ultima_matricula, ultimo_acesso

    log.info("🚀 Abre Já v6.0 — Iniciado.")
    log.info(f"📷 Câmara: {CAMERA_URL}")
    atualizar_cache()

    cap = cv2.VideoCapture(CAMERA_URL)
    cap.set(cv2.CAP_PROP_BUFFERSIZE, 1)

    if not cap.isOpened():
        log.error("❌ Não foi possível conectar à câmara. Verifica o URL.")
        return

    log.info("📷 Câmara ligada! A monitorizar...")
    ultimo_processamento = 0

    while True:
        agora = time.time()

        # Limitar processamento a 1 vez por cada 3 segundos
        if agora - ultimo_processamento < 3.0:
            ret, _ = cap.read() # Limpar buffer continuamente
            time.sleep(0.1)
            continue

        ultimo_processamento = agora

        # Capturar frame limpo (sem verde)
        frame = capturar_frame_limpo(cap)
        if frame is None:
            log.warning("⚠️ Frame inválido, a tentar novamente...")
            time.sleep(1)
            continue

        frame = cv2.resize(frame, (640, 480))

        # Detetar candidatos a matrícula
        candidatos = detetar_roi(frame)

        # Desenhar retângulos e mostrar preview
        frame_preview = frame.copy()
        for (x, y, w, h) in candidatos:
            cv2.rectangle(frame_preview, (x, y), (x+w, y+h), (0, 255, 0), 2)
        cv2.imshow("Abre Ja", frame_preview)
        if cv2.waitKey(1) & 0xFF == ord('q'):
            break

        if not candidatos:
            continue

        for (x, y, w, h) in candidatos:
            roi = frame[y:y+h, x:x+w]
            if roi.size == 0:
                continue

            try:
                processado = pre_processar_roi(roi)
                texto = pytesseract.image_to_string(processado, config=TESSERACT_CONFIG).strip()

                if not texto:
                    continue

                log.info(f"🔮 OCR leu: '{texto}'")
                matricula = validar_matricula_pt(texto)
                if not matricula:
                    continue

                matricula_fmt = f"{matricula[0:2]}-{matricula[2:4]}-{matricula[4:6]}"
                log.info(f"🔎 Matrícula: {matricula_fmt}")

                # Cooldown — evitar duplicados
                if matricula_fmt == ultima_matricula and agora - ultimo_acesso < COOLDOWN:
                    log.info("⏱️ Em cooldown, a ignorar...")
                    continue

                ultima_matricula = matricula_fmt
                ultimo_acesso = agora

                car_data = verificar_matricula(matricula_fmt)
                if car_data:
                    abrir_portao(matricula_fmt, car_data)
                else:
                    acesso_negado(matricula_fmt)

            except Exception as e:
                log.error(f"Erro no OCR: {e}")

    cap.release()
    cv2.destroyAllWindows()
    if GPIO_DISPONIVEL:
        GPIO.cleanup()

if __name__ == "__main__":
    main()
