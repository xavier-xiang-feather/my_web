#!/usr/bin/env python3
import os, sys, json
from datetime import datetime, timezone

ALLOWED_ORIGINS = {
    "https://test.mrxijian.site",
    "https://mrxijian.site",
}

LOG_PATH = "/tmp/cse135-collector.log"
VERSION = "collector-cgi-v4"

def _origin_headers():
    """
    Return a list of CORS headers based on request Origin.
    Always return *something* predictable so debugging is easier.
    """
    origin = os.environ.get("HTTP_ORIGIN", "")
    headers = []

    # Always vary on Origin so caches don't mix responses
    if origin:
        headers.append(("Vary", "Origin"))

    # Only allow known origins
    if origin in ALLOWED_ORIGINS:
        headers.append(("Access-Control-Allow-Origin", origin))
        headers.append(("Access-Control-Allow-Credentials", "true"))
    else:
        # IMPORTANT: don't send '*' when credentials are used
        # If origin is not allowed, omit ACAO completely.
        pass

    # Preflight / request headers
    headers.append(("Access-Control-Allow-Methods", "POST, OPTIONS"))
    headers.append(("Access-Control-Allow-Headers", "Content-Type"))
    headers.append(("Access-Control-Max-Age", "600"))
    return headers

def respond(status_code=204, status_text="No Content", body=""):
    # Status line
    print(f"Status: {status_code} {status_text}")
    print("Content-Type: text/plain; charset=utf-8")
    print(f"X-Collector-Version: {VERSION}")

    # CORS
    for k, v in _origin_headers():
        print(f"{k}: {v}")

    print()  # end headers
    if body:
        print(body)

def read_body():
    try:
        length = int(os.environ.get("CONTENT_LENGTH", "0") or "0")
    except ValueError:
        length = 0

    raw = sys.stdin.buffer.read(length) if length > 0 else b""
    return raw.decode("utf-8", errors="replace")

def main():
    try:
        method = os.environ.get("REQUEST_METHOD", "GET").upper()

        # Preflight
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

        with open(LOG_PATH, "a", encoding="utf-8") as f:
            f.write(json.dumps(record, ensure_ascii=False) + "\n")

        respond(204, "No Content")
    except Exception as e:
        # If anything explodes, still send headers so browser shows real error less often
        respond(500, "Internal Server Error", body=f"collector error: {e}")

if __name__ == "__main__":
    main()