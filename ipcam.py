#!/usr/bin/env python3
"""
Sistema de leitura de matrículas com câmera IP
Otimizado para Raspberry Pi 3B+ - baixo consumo de CPU
"""

import cv2
import pytesseract
import requests
import RPi.GPIO as GPIO
import time
import re
import logging

# ─── Configuração ────────────────────────────────────────────
CAMERA_URL   = "http://IP_DA_CAMERA/video"  # ← substitua pelo IP da câmera
SUPABASE_URL = "https://fmjytigqgpfocurpjvtv.supabase.co"
SUPABASE_KEY = "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6ImZtanl0aWdxZ3Bmb2N1cnBqdnR2Iiwicm9sZSI6ImFub24iLCJpYXQiOjE3Nzc1MzM3MDYsImV4cCI6MjA5MzEwOTcwNn0.2rpUjyVQ80MaMptmF-LtMhGeEq1LaliMSKXkglsYhcE"

RELAY_PIN    = 17       # pino GPIO do relé
RELAY_TIME   = 5        # segundos que o portão fica aberto
FRAME_SKIP   = 30       # processa 1 frame a cada 30 (≈ 1 por segundo a 30fps)
COOLDOWN     = 10       # segundos entre leituras para evitar repetições
# ─────────────────────────────────────────────────────────────

logging.basicConfig(level=logging.INFO, format="%(asctime)s %(message)s")

# Configurar GPIO
GPIO.setmode(GPIO.BCM)
GPIO.setup(RELAY_PIN, GPIO.OUT, initial=GPIO.HIGH)  # HIGH = relé desligado

def abrir_portao():
    """Aciona o relé para abrir o portão."""
    logging.info("✅ Portão a abrir...")
    GPIO.output(RELAY_PIN, GPIO.LOW)   # LOW = relé ligado (portão abre)
    time.sleep(RELAY_TIME)
    GPIO.output(RELAY_PIN, GPIO.HIGH)  # HIGH = relé desligado (portão fecha)
    logging.info("🔒 Portão fechado.")

def verificar_matricula(matricula: str) -> bool:
    """Verifica se a matrícula existe na base de dados Supabase."""
    try:
        url = f"{SUPABASE_URL}/rest/v1/cars?plate=eq.{matricula}&select=id"
        headers = {
            "apikey": SUPABASE_KEY,
            "Authorization": f"Bearer {SUPABASE_KEY}",
        }
        res = requests.get(url, headers=headers, timeout=5)
        dados = res.json()
        return len(dados) > 0
    except Exception as e:
        logging.error(f"Erro ao consultar Supabase: {e}")
        return False

def limpar_matricula(texto: str) -> str:
    """Remove espaços e caracteres inválidos da matrícula."""
    texto = texto.upper().replace(" ", "").replace("\n", "")
    # Formato português: AA-00-AA ou AA-00-00 ou 00-AA-00
    match = re.search(r'[A-Z0-9]{2}-?[A-Z0-9]{2}-?[A-Z0-9]{2}', texto)
    if match:
        m = match.group().replace("-", "")
        return f"{m[0:2]}-{m[2:4]}-{m[4:6]}"
    return ""

def processar_frame(frame):
    """Pré-processa o frame para melhorar a leitura OCR."""
    # Reduzir resolução para poupar CPU
    frame = cv2.resize(frame, (640, 480))
    # Converter para escala de cinza
    cinza = cv2.cvtColor(frame, cv2.COLOR_BGR2GRAY)
    # Aumentar contraste
    cinza = cv2.equalizeHist(cinza)
    # Desfocar para remover ruído
    cinza = cv2.GaussianBlur(cinza, (5, 5), 0)
    # Binarizar
    _, binario = cv2.threshold(cinza, 0, 255, cv2.THRESH_BINARY + cv2.THRESH_OTSU)
    return binario

def main():
    logging.info("🚀 Sistema de leitura de matrículas iniciado.")
    cap = cv2.VideoCapture(CAMERA_URL)

    if not cap.isOpened():
        logging.error("❌ Não foi possível ligar à câmera.")
        return

    ultimo_acesso = 0
    contador      = 0

    try:
        while True:
            ret, frame = cap.read()
            if not ret:
                logging.warning("⚠️  Frame inválido, a tentar novamente...")
                time.sleep(1)
                continue

            contador += 1

            # Só processa 1 frame a cada FRAME_SKIP — poupa CPU
            if contador % FRAME_SKIP != 0:
                continue

            # Cooldown entre leituras
            agora = time.time()
            if agora - ultimo_acesso < COOLDOWN:
                continue

            # Processar frame e ler matrícula
            imagem = processar_frame(frame)
            config  = "--psm 8 -c tessedit_char_whitelist=ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789-"
            texto   = pytesseract.image_to_string(imagem, config=config)
            matricula = limpar_matricula(texto)

            if not matricula:
                continue

            logging.info(f"🔍 Matrícula lida: {matricula}")

            # Verificar na base de dados
            if verificar_matricula(matricula):
                logging.info(f"✅ Matrícula autorizada: {matricula}")
                ultimo_acesso = agora
                abrir_portao()
            else:
                logging.info(f"❌ Matrícula não autorizada: {matricula}")

    except KeyboardInterrupt:
        logging.info("Sistema parado pelo utilizador.")
    finally:
        cap.release()
        GPIO.cleanup()
        logging.info("GPIO limpo. Programa terminado.")

if __name__ == "__main__":
    main()
