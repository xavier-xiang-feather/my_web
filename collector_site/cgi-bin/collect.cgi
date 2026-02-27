#!/usr/bin/env python3
import os, sys, json, datetime

# 只允许哪些站点可以跨域打你的 collector（按你需要增删）
ALLOWED_ORIGINS = {
    "https://test.mrxijian.site",
    "https://mrxijian.site",
    # 如果你还有其他页面也要打 collector，就继续加：
    # "https://reporting.mrxijian.site",
}

LOG_PATH_PRIMARY = "/var/log/cse135-collector.log"
LOG_PATH_FALLBACK = "/tmp/cse135-collector.log"

def respond(status="204 No Content"):
    print(f"Status: {status}")
    print("Content-Type: text/plain; charset=utf-8")

    origin = os.environ.get("HTTP_ORIGIN", "")
    # 关键：如果浏览器 credentials=include，就不能用 "*"
    if origin in ALLOWED_ORIGINS:
        print(f"Access-Control-Allow-Origin: {origin}")
        print("Access-Control-Allow-Credentials: true")
        print("Vary: Origin")  # 防止缓存把别的 origin 的结果复用

    # preflight / 实际请求都给
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

def append_log(line: str):
    try:
        with open(LOG_PATH_PRIMARY, "a", encoding="utf-8") as f:
            f.write(line)
        return
    except Exception:
        with open(LOG_PATH_FALLBACK, "a", encoding="utf-8") as f:
            f.write(line)

def main():
    method = os.environ.get("REQUEST_METHOD", "GET").upper()

    # 处理 CORS preflight
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

    append_log(json.dumps(record, ensure_ascii=False) + "\n")

    respond("204 No Content")

if __name__ == "__main__":
    main()