
#!/usr/bin/env python3
import os, sys, json, datetime

def respond(status="204 No Content"):
    print(f"Status: {status}")
    origin = os.environ.get("HTTP_ORIGIN", "")
    if origin:
        print(f"Access-Control-Allow-Origin: {origin}")
    else:
        print("Access-Control-Allow-Origin: https://test.mrxijian.site")
    print("Access-Control-Allow-Credentials: true")
    print("Access-Control-Allow-Methods: POST, OPTIONS")
    print("Access-Control-Allow-Headers: Content-Type")
    print()

def main():
    method = os.environ.get("REQUEST_METHOD", "GET").upper()

    if method == "OPTIONS":
        respond("204 No Content")
        return

    if method != "POST":
        respond("405 Method Not Allowed")
        return

    try:
        length = int(os.environ.get("CONTENT_LENGTH", "0") or "0")
    except ValueError:
        length = 0

    raw = sys.stdin.buffer.read(length) if length > 0 else b""
    text = raw.decode("utf-8", errors="replace")

    try:
        payload = json.loads(text) if text else None
    except Exception:
        payload = {"_malformed": True, "raw": text}

    record = {
        "ts": datetime.datetime.utcnow().isoformat() + "Z",
        "ip": os.environ.get("REMOTE_ADDR", ""),
        "ua": os.environ.get("HTTP_USER_AGENT", ""),
        "referer": os.environ.get("HTTP_REFERER", ""),
        "payload": payload
    }

    # log file (fallback to /tmp if no permission)
    log_path = "/var/log/cse135-collector.log"
    line = json.dumps(record, ensure_ascii=False) + "\n"

    try:
        with open(log_path, "a", encoding="utf-8") as f:
            f.write(line)
    except Exception:
        with open("/tmp/cse135-collector.log", "a", encoding="utf-8") as f:
            f.write(line)

    respond("204 No Content")

