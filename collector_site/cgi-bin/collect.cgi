#!/usr/bin/env python3
import os
import sys
import json
from datetime import datetime, timezone

# ====== CONFIG ======
ALLOWED_ORIGINS = {
    "https://test.mrxijian.site",
    "https://mrxijian.site",
}
# 建议路径：确保该目录 sudo chmod 777 过的
LOG_PATH = "/var/www/collector.mrxijian.site/logs/collector.log"
VERSION = "collector-cgi-final-v1"

def _origin_headers():
    origin = os.environ.get("HTTP_ORIGIN", "")
    headers = []
    if origin:
        headers.append(("Vary", "Origin"))
    
    # 调试逻辑：如果 Origin 匹配，则允许跨域
    if origin in ALLOWED_ORIGINS:
        headers.append(("Access-Control-Allow-Origin", origin))
        headers.append(("Access-Control-Allow-Credentials", "true"))
    
    headers.append(("Access-Control-Allow-Methods", "POST, OPTIONS"))
    headers.append(("Access-Control-Allow-Headers", "Content-Type"))
    headers.append(("Access-Control-Max-Age", "600"))
    return headers

def respond(status_code=204, status_text="No Content", body=""):
    # 严格按照 CGI 标准发送 Header
    print(f"Status: {status_code} {status_text}")
    print("Content-Type: text/plain; charset=utf-8")
    print(f"X-Collector-Version: {VERSION}")
    for k, v in _origin_headers():
        print(f"{k}: {v}")
    print()  # 必须的空行，标记 Header 结束
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
    # 1. 处理 OPTIONS 请求 (Preflight)
    method = os.environ.get("REQUEST_METHOD", "GET").upper()
    if method == "OPTIONS":
        respond(204, "No Content")
        return

    if method != "POST":
        respond(405, "Method Not Allowed")
        return

    # 2. 解析数据
    text = read_body()
    try:
        payload = json.loads(text) if text else None
    except Exception:
        payload = {"_malformed": True, "raw": text}

    # 3. 构造记录 (修复了 utcnow 警告)
    record = {
        "ts": datetime.now(timezone.utc).isoformat().replace("+00:00", "Z"),
        "ip": os.environ.get("REMOTE_ADDR", ""),
        "ua": os.environ.get("HTTP_USER_AGENT", ""),
        "referer": os.environ.get("HTTP_REFERER", ""),
        "origin": os.environ.get("HTTP_ORIGIN", ""),
        "payload": payload,
    }

    # 4. 关键：尝试写入日志
    try:
        # 确保目录存在
        log_dir = os.path.dirname(LOG_PATH)
        if not os.path.exists(log_dir):
            os.makedirs(log_dir, exist_ok=True)
            
        with open(LOG_PATH, "a", encoding="utf-8") as f:
            f.write(json.dumps(record, ensure_ascii=False) + "\n")
            f.flush()
    except Exception as e:
        # 如果写入失败，将具体错误输出到 Apache Error Log (窗口 A)
        print(f"[CRITICAL WRITE ERROR] Path: {LOG_PATH} | Error: {e}", file=sys.stderr)

    # 5. 无论写入是否成功，都给浏览器一个 204，防止前端报 CORS/500
    respond(204, "No Content")

if __name__ == "__main__":
    try:
        main()
    except Exception as e:
        # 最后的防线：捕获所有未处理异常
        print(f"Status: 500 Internal Server Error")
        print("Content-Type: text/plain")
        print()
        print(f"Fatal CGI Error: {e}")
        print(f"Traceback in Apache error log", file=sys.stderr)