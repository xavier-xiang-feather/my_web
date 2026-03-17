#!/usr/bin/env python3
import os
import sys
import json
from datetime import datetime, timezone

ALLOWED_ORIGINS = {
    "https://test.mrxijian.site",
    "https://mrxijian.site",
}
LOG_PATH = "/var/www/collector.mrxijian.site/logs/collector.log"
VERSION = "collector-cgi-final-v1"

def _origin_headers():
    origin = os.environ.get("HTTP_ORIGIN", "")
    headers = []
    if origin:
        headers.append(("Vary", "Origin"))
    
    if origin in ALLOWED_ORIGINS:
        headers.append(("Access-Control-Allow-Origin", origin))
        headers.append(("Access-Control-Allow-Credentials", "true"))
    
    headers.append(("Access-Control-Allow-Methods", "POST, OPTIONS"))
    headers.append(("Access-Control-Allow-Headers", "Content-Type"))
    headers.append(("Access-Control-Max-Age", "600"))
    return headers

def respond(status_code=204, status_text="No Content", body=""):
    print(f"Status: {status_code} {status_text}")
    print("Content-Type: text/plain; charset=utf-8")
    print(f"X-Collector-Version: {VERSION}")
    for k, v in _origin_headers():
        print(f"{k}: {v}")
    print()  
    if body:
        print(body)

def read_body():
    try:
        length = int(os.environ.get("CONTENT_LENGTH", "0") or "0")
        if length <= 0: return ""
        raw = sys.stdin.buffer.read(length)
        return raw.decode("utf-8", errors="replace")
    except Exception as e:
        print(f"[DEBUG] Read Body Error: {e}", file=sys.stderr)
        return ""

def main():
    method = os.environ.get("REQUEST_METHOD", "GET").upper()
    if method == "OPTIONS":
        respond(204, "No Content")
        return

    if method != "POST":
        respond(405, "Method Not Allowed")
        return

    text = read_body()
    try:
        payload = json.loads(text) if text else None
    except Exception:
        payload = {"_malformed": True, "raw": text}

    record = {
        "ts": datetime.now(timezone.utc).isoformat().replace("+00:00", "Z"),
        "ip": os.environ.get("REMOTE_ADDR", ""),
        "ua": os.environ.get("HTTP_USER_AGENT", ""),
        "referer": os.environ.get("HTTP_REFERER", ""),
        "origin": os.environ.get("HTTP_ORIGIN", ""),
        "payload": payload,
    }

    try:
        # check directory
        log_dir = os.path.dirname(LOG_PATH)
        if not os.path.exists(log_dir):
            os.makedirs(log_dir, exist_ok=True)
            
        with open(LOG_PATH, "a", encoding="utf-8") as f:
            f.write(json.dumps(record, ensure_ascii=False) + "\n")
            f.flush()
    except Exception as e:
        print(f"[CRITICAL WRITE ERROR] Path: {LOG_PATH} | Error: {e}", file=sys.stderr)

    respond(204, "No Content")

if __name__ == "__main__":
    try:
        main()
    except Exception as e:
        print(f"Status: 500 Internal Server Error")
        print("Content-Type: text/plain")
        print()
        print(f"Fatal CGI Error: {e}")
        print(f"Traceback in Apache error log", file=sys.stderr)