#!/usr/bin/env python3
import os

print("Content-Type: text/html")
print()

# ---- get session id ----
cookies = os.environ.get("HTTP_COOKIE", "")
session_id = None

for c in cookies.split(";"):
    if c.strip().startswith("session_id="):
        session_id = c.strip().split("=", 1)[1]

if session_id:
    state_file = f"/tmp/state_python_{session_id}.json"
    if os.path.exists(state_file):
        os.remove(state_file)

print("""
<!DOCTYPE html>
<html>
<head><title>Clear State (Python)</title></head>
<body>
<h1>State Cleared</h1>

<p>The server-side state has been cleared.</p>

<p><a href="/cgi-bin/state-python-set.py">Set State</a></p>
<p><a href="/cgi-bin/state-python-view.py">View State</a></p>
</body>
</html>
""")
