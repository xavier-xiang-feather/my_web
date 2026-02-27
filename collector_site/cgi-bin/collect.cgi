#!/usr/bin/env python3
import os, sys, json, datetime

ALLOWED_ORIGINS = {
    "https://test.mrxijian.site",
    "https://mrxijian.site",
}

LOG_PATH = "/tmp/cse135-collector.log"   # 固定写这里，避免权限坑
VERSION = "collector-cgi-v3"

def respond(status="204 No Content"):
    print(f"Status: {status}")
    print("Content-Type: text/plain; charset=utf-8")
    print(f"X-Collector-Version: {VERSION}")

    origin = os.environ.get("HTTP_ORIGIN", "")
    if origin in ALLOWED_ORIGINS:
        print(f"Access-Control-Allow-Origin: {origin}")
        print("Access-Control-Allow-Credentials: true")
        print("Vary: Origin")

    print("Access-Control-Allow-Methods: POST, OPTIONS")
    print("Access-Control-Allow-Headers: Content-Type")
    print()

def read_body():
    try:
        length = int(os.environ.get("CONTENT_LENGTH", "0") or "0")
    except ValueError:
        length = 0
    raw = sys.stdin.buffer.read(length) if length > 0 else b""
    return raw.decode("utf-8", errors="replace")

def main():
    method = os.environ.get("REQUEST_METHOD", "GET").upper()

    if method == "OPTIONS":
        respond("204 No Content")
        return

    if method != "POST":
        respond("405 Method Not Allowed")
        return

    text = read_body()

    try:
        payload = json.loads(text) if text else None
    except Exception:
        payload = {"_malformed": True, "raw": text}

    record = {
        "ts": datetime.datetime.utcnow().isoformat() + "Z",
        "ip": os.environ.get("REMOTE_ADDR", ""),
        "ua": os.environ.get("HTTP_USER_AGENT", ""),
        "referer": os.environ.get("HTTP_REFERER", ""),
        "origin": os.environ.get("HTTP_ORIGIN", ""),
        "payload": payload,
    }

    # 关键：固定写到 /tmp/cse135-collector.log
    with open(LOG_PATH, "a", encoding="utf-8") as f:
        f.write(json.dumps(record, ensure_ascii=False) + "\n")

    respond("204 No Content")

if __name__ == "__main__":
    main()