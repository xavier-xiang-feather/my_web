#!/usr/bin/env python3
import os, json

print("Content-Type: text/html")
print()

# ---- get session id ----
cookies = os.environ.get("HTTP_COOKIE", "")
session_id = None

for c in cookies.split(";"):
    if c.strip().startswith("session_id="):
        session_id = c.strip().split("=", 1)[1]

state = {}

if session_id:
    state_file = f"/tmp/state_python_{session_id}.json"
    if os.path.exists(state_file):
        with open(state_file) as f:
            state = json.load(f)

print(f"""
<!DOCTYPE html>
<html>
<head><title>View State (Python)</title></head>
<body>
<h1>View State (Python)</h1>

<p><strong>Saved State:</strong></p>
<pre>{json.dumps(state, indent=2)}</pre>

<p><a href="/cgi-bin/state-python-set.py">Set State</a></p>
<p><a href="/cgi-bin/state-python-clear.py">Clear State</a></p>
</body>
</html>
""")
