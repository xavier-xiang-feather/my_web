#!/usr/bin/env python3
import os
import json
import uuid
import urllib.parse

# ---------- helpers ----------

def get_session_id():
    cookies = os.environ.get("HTTP_COOKIE", "")
    for c in cookies.split(";"):
        c = c.strip()
        if c.startswith("session_id="):
            return c.split("=", 1)[1]
    return None

def print_headers(set_cookie=None):
    print("Content-Type: text/html")
    if set_cookie:
        print(f"Set-Cookie: session_id={set_cookie}; Path=/")
    print()

# ---------- session ----------

session_id = get_session_id()
new_session = False

if not session_id:
    session_id = str(uuid.uuid4())
    new_session = True

state_file = f"/tmp/state_python_{session_id}.json"

# ---------- routing ----------

query = os.environ.get("QUERY_STRING", "")
params = urllib.parse.parse_qs(query)
action = params.get("action", [""])[0]

method = os.environ.get("REQUEST_METHOD", "GET")

# ---------- handle SET (POST) ----------

if action == "set" and method == "POST":
    length = int(os.environ.get("CONTENT_LENGTH", "0") or "0")
    body = os.sys.stdin.read(length)
    data = urllib.parse.parse_qs(body)
    name = data.get("name", [""])[0]

    with open(state_file, "w") as f:
        json.dump({"name": name}, f)

# ---------- handle CLEAR ----------

if action == "clear":
    if os.path.exists(state_file):
        os.remove(state_file)

# ---------- load state ----------

state = {}
if os.path.exists(state_file):
    try:
        with open(state_file) as f:
            state = json.load(f)
    except:
        state = {}

saved_name = state.get("name", "")

# ---------- output ----------

print_headers(session_id if new_session else None)

print("<!doctype html>")
print("<html><head><meta charset='utf-8'>")
print("<title>Python State Demo</title>")
print("</head><body>")

# ---------- MAIN ----------

if action == "":
    print("<h1>Python State Demo</h1>")
    print("<ul>")
    print("<li><a href='?action=set'>Set State</a></li>")
    print("<li><a href='?action=view'>View State</a></li>")
    print("<li><a href='?action=clear'>Clear State</a></li>")
    print("</ul>")

# ---------- SET ----------

elif action == "set":
    print("<h1>Set State (Python)</h1>")
    print("""
    <form method="POST" action="?action=set">
      <label>Name:</label>
      <input type="text" name="name">
      <button type="submit">Save</button>
    </form>
    """)
    print(f"<p><strong>Current saved name:</strong> {saved_name or '(empty)'}</p>")
    print("<p><a href='?action=view'>View State</a></p>")
    print("<p><a href='?action=clear'>Clear State</a></p>")
    print("<p><a href='?'>Home</a></p>")

# ---------- VIEW ----------

elif action == "view":
    print("<h1>View State (Python)</h1>")
    print("<pre>")
    print(saved_name or "(no state saved)")
    print("</pre>")
    print("<p><a href='?action=set'>Set State</a></p>")
    print("<p><a href='?action=clear'>Clear State</a></p>")
    print("<p><a href='?'>Home</a></p>")

# ---------- CLEAR ----------

elif action == "clear":
    print("<h1>State Cleared (Python)</h1>")
    print("<p>The server-side state has been cleared.</p>")
    print("<p><a href='?action=set'>Set State</a></p>")
    print("<p><a href='?action=view'>View State</a></p>")
    print("<p><a href='?'>Home</a></p>")

# ---------- FALLBACK ----------

else:
    print("<h1>Unknown action</h1>")
    print("<p><a href='?'>Back to home</a></p>")

print(f"<hr><p><strong>Session ID:</strong> {session_id}</p>")
print("</body></html>")
