#!/usr/bin/env python3
import os, sys, json, datetime, urllib.parse, html

def read_body():
    length = int(os.environ.get("CONTENT_LENGTH", "0") or "0")
    return sys.stdin.read(length) if length > 0 else ""

def parse_body(content_type, body):
    """Return (parsed_obj, parse_mode_string)."""
    if not body:
        return {}, "empty"

    if "application/json" in content_type:
        try:
            return json.loads(body), "json"
        except Exception as e:
            return {"error": "Invalid JSON", "detail": str(e)}, "json_error"

    # default: x-www-form-urlencoded
    return urllib.parse.parse_qs(body), "form"

# ---- basic request info ----
protocol = os.environ.get("SERVER_PROTOCOL", "HTTP/1.1")
method = os.environ.get("REQUEST_METHOD", "UNKNOWN")
query_string = os.environ.get("QUERY_STRING", "")
content_type = os.environ.get("CONTENT_TYPE", "")
ip = os.environ.get("REMOTE_ADDR", "unknown")
user_agent = os.environ.get("HTTP_USER_AGENT", "unknown")
current = datetime.datetime.now().strftime("%Y-%m-%d %H:%M:%S")

# ---- read/parse data ----
if method == "GET":
    parsed = urllib.parse.parse_qs(query_string)
    body = ""
    body_mode = "n/a"
else:
    body = read_body()
    parsed, body_mode = parse_body(content_type, body)

# ---- output HTML like the instructor demo ----
print("Content-Type: text/html")
print()

print("<!doctype html>")
print("<html><head><meta charset='utf-8'>")
print("<title>General Request Echo (Python)</title>")
print("</head><body>")

print("<h1 style='text-align:center;'>General Request Echo</h1>")
print("<hr>")

print(f"<p><strong>HTTP Protocol:</strong> {html.escape(protocol)}</p>")
print(f"<p><strong>HTTP Method:</strong> {html.escape(method)}</p>")

print("<p><strong>Query String:</strong></p>")
print("<pre>")
print(html.escape(query_string))
print("</pre>")

print("<p><strong>Message Body:</strong></p>")
print("<pre>")
print(html.escape(body))
print("</pre>")

print("<p><strong>Parsed Data:</strong> (mode: " + html.escape(body_mode) + ")</p>")
print("<pre>")
print(html.escape(json.dumps(parsed, indent=2, ensure_ascii=False)))
print("</pre>")

print("<hr>")
print("<p><strong>Time:</strong> " + html.escape(current) + "</p>")
print("<p><strong>IP Address:</strong> " + html.escape(ip) + "</p>")
print("<p><strong>User-Agent:</strong> " + html.escape(user_agent) + "</p>")

print("</body></html>")
