#!/usr/bin/env python3
import os, json, uuid, urllib.parse

print("Content-Type: text/html")

# ---- get or create session id ----
cookies = os.environ.get("HTTP_COOKIE", "")
session_id = None

for c in cookies.split(";"):
    if c.strip().startswith("session_id="):
        session_id = c.strip().split("=", 1)[1]

if not session_id:
    session_id = str(uuid.uuid4())
    print(f"Set-Cookie: session_id={session_id}; Path=/")

print()  # end headers

# ---- read form data ----
method = os.environ.get("REQUEST_METHOD", "GET")
data = {}

if method == "POST":
    length = int(os.environ.get("CONTENT_LENGTH", "0"))
    body = os.environ.get("wsgi.input") or ""
    raw = os.sys.stdin.read(length)
    data = urllib.parse.parse_qs(raw)
    name = data.get("name", [""])[0]

    # save state to server-side file
    state_file = f"/tmp/state_python_{session_id}.json"
    with open(state_file, "w") as f:
        json.dump({"name": name}, f)

# ---- HTML output ----
print(f"""
<!DOCTYPE html>
<html>
<head><title>Set State (Python)</title></head>
<body>
<h1>Set State (Python)</h1>

<form method="POST">
  <label>Name:</label>
  <input type="text" name="name">
  <button type="submit">Save</button>
</form>

<p><a href="/cgi-bin/state-python-view.py">View State</a></p>
<p><a href="/cgi-bin/state-python-clear.py">Clear State</a></p>

<p><strong>Session ID:</strong> {session_id}</p>
</body>
</html>
""")
