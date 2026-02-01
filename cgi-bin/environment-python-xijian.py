#!/usr/bin/env python3
import os

print("Content-Type: text/html\r\n\r\n")

print("<html><head><title>Environment from Python</title></head><body>")
print("<h1>Environment Variables</h1>")
print("<ul>")

for key, value in sorted(os.environ.items()):
    print(f"<li>strong>{key}</strong>: {value}</li>")

print("</ul>")
print("</body></html>")
