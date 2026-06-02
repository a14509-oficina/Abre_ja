#!/usr/bin/env python3
"""
Abre Já — Versão 3.3 (Diagnóstico de Bloqueio de CPU)
"""

import cv2
import pytesseract
import requests
import time
import re
import logging
import os
import threading
from collections import deque
from dotenv import load_dotenv

load_dotenv()

CAMERA_URL   = os.getenv("CAMERA_URL")
SUPABASE_URL = os.getenv("SUPABASE_URL")
SUPABASE_KEY = os.getenv("SUPABASE_KEY")

RELAY_PIN    = int(os.getenv("RELAY_PIN",   "17"))
RELAY_TIME   = int(os.getenv("RELAY_TIME",  "5"))
COOLDOWN     = int(os.getenv("COOLDOWN",    "20"))
CONFIRMAR_EM = int(os.getenv("CONFIRMAR_EM","1"))   # Baixado para 1 para dar acesso imediato no teste
CACHE_TTL    = int(os.getenv("CACHE_TTL",   "300"))

logging.basicConfig(
    level=logging.INFO,
    format="%(asctime)s [%(levelname)s] %(message)s",
    handlers=[logging.StreamHandler()]
)
log = logging.getLogger(__name__)

# Configuração simplificada e direta
TESSERACT_CONFIG = r'--oem 3 --psm 7'

rele_ocupado        = False
historico_leituras  = deque(maxlen=CONFIRMAR_EM)
ultimo_acesso       = {}
cache_matriculas    = set()
cache_timestamp     = 0
ultimo_processamento = 0 

def atualizar_cache() -> None:
    global cache_matriculas, cache_timestamp
    if not SUPABASE_KEY: return
    try:
        url = f"{SUPABASE_URL}/rest/v1/cars?select=plate"
        headers = {"apikey": SUPABASE_KEY, "Authorization": f"Bearer {SUPABASE_KEY}"}
        res = requests.get(url, headers=headers, timeout=5)
        if res.status_code == 200:
            cache_matriculas = {d["plate"] for d in res.json() if d.get("plate")}
            cache_timestamp  = time.time()
            log.info(f"Cache carregada: {cache_matriculas}")
    except Exception as e:
        log.error(f"Erro na cache: {e}")

def verificar_matricula(matricula: str) -> bool:
    global cache_timestamp
    if time.time() - cache_timestamp > CACHE_TTL:
        atualizar_cache()
    return matricula in cache_matriculas

def registar_acesso_supabase(matricula: str) -> None:
    try:
        headers = {
            "apikey":        SUPABASE_KEY,
            "Authorization": f"Bearer {SUPABASE_KEY}",
            "Content-Type":  "application/json",
            "Prefer":        "return=minimal"
        }
        # Procurar Carro
        res = requests.get(f"{SUPABASE_URL}/rest/v1/cars?plate=eq.{matricula}&select=id,user_id", headers=headers, timeout=5)
        cars = res.json()
        if not cars: 
            log.warning(f"Matrícula {matricula} não encontrada na tabela 'cars'.")
            return
        car_id = cars[0]["id"]
        user_id = cars[0]["user_id"]

        # Procurar Gate ID
        res_link = requests.get(f"{SUPABASE_URL}/rest/v1/car_gate_links?car_id=eq.{car_id}&select=gate_id", headers=headers, timeout=5)
        links = res_link.json()
        gate_id = links[0]["gate_id"] if links else None

        # Criar o Log
        payload = {
            "gate_id":    gate_id,
            "user_id":    user_id,
            "plate":      matricula,
            "method":     "plate",
            "ip_address": "raspberry_pi",
        }
        post_res = requests.post(f"{SUPABASE_URL}/rest/v1/access_log", json=payload, headers=headers, timeout=5)
        log.info(f" Enviado ao Supabase. Status: {post_res.status_code}")
    except Exception as e:
        log.error(f"Falha de rede Supabase: {e}")

def abrir_portao(matricula: str) -> None:
    log.info(f"🔓 ABRIR PORTÃO PARA: {matricula}")
    # Envia para o admin sincronizadamente para garantir que vemos o erro se falhar
    registar_acesso_supabase(matricula)

def detetar_roi(frame):
    cinza = cv2.cvtColor(frame, cv2.COLOR_BGR2GRAY)
    binario = cv2.adaptiveThreshold(cinza, 255, cv2.ADAPTIVE_THRESH_GAUSSIAN_C, cv2.THRESH_BINARY, 11, 2)
    contours, _ = cv2.findContours(binario, cv2.RETR_TREE, cv2.CHAIN_APPROX_SIMPLE)
    
    for c in contours:
        x, y, w, h = cv2.boundingRect(c)
        ratio = w / float(h) if h > 0 else 0
        if 2.0 < ratio < 6.0 and w > 50 and h > 12:
            roi = cinza[y:y+h, x:x+w]
            return roi, (x, y, w, h)
    return None, None

PADROES_PT = [
    r'^[A-Z0-9]{6}$' # Aceita qualquer combinação de 6 letras/números para o teste ser infalível
]

def validar_matricula_pt(texto: str) -> str:
    limpo = re.sub(r'[^A-Z0-9]', '', texto.upper())
    if len(limpo) == 6:
        return limpo
    return ""

def main() -> None:
    global ultimo_processamento
    log.info("🚀 Abre Já — Modo Diagnóstico Ativo.")
    atualizar_cache()

    cap = cv2.VideoCapture(CAMERA_URL)
    if not cap.isOpened():
        log.error("Erro ao abrir a câmara IP!")
        return

    while True:
        ret, frame = cap.read()
        if not ret: continue

        frame = cv2.resize(frame, (640, 480))
        roi, bbox = detetar_roi(frame)

        if bbox is not None:
            x, y, w, h = bbox
            cv2.rectangle(frame, (x, y), (x+w, y+h), (0, 255, 0), 2)

        cv2.imshow("Abre Ja", frame)
        if cv2.waitKey(1) & 0xFF == ord('q'): break

        if roi is None: continue

        # Só tenta ler a imagem de 2 em 2 segundos (Dá descanso total ao CPU do Pi 3B+)
        agora = time.time()
        if agora - ultimo_processamento < 2.0: continue
        ultimo_processamento = agora

        log.info("⚡ Retângulo detetado! A chamar o Tesseract...")

        try:
            texto = pytesseract.image_to_string(roi, config=TESSERACT_CONFIG).strip()
            log.info(f"🔮 Tesseract decifrou: '{texto}'")

            matricula = validar_matricula_pt(texto)
            if not matricula: continue

            log.info(f" Matrícula Válida: {matricula}")
            
            # Formata para bater com a tua base de dados (Ex: 65-LU-55)
            matricula_formatada = f"{matricula[0:2]}-{matricula[2:4]}-{matricula[4:6]}"

            if verificar_matricula(matricula_formatada):
                log.info("✅ MATRÍCULA AUTORIZADA NA CACHE!")
                abrir_portao(matricula_formatada)
            else:
                log.warning(f"❌ {matricula_formatada} não está na lista de autorizados.")
                
        except Exception as e:
            log.error(f"🚨 Erro crítico no motor Tesseract: {e}")

    cap.release()
    cv2.destroyAllWindows()

if __name__ == "__main__":
    main()