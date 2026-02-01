#!/usr/bin/env python3
import os
import datetime
import socket

print("Content-Type: text/html\r\n\r\n")

current = datetime.datetime.now().strftime("%Y-%m-%d %H:%M:%S")
ip_addr = os.environ.get("REMOTE_ADDR", "unknown")

print(f"""
<!DOCTYPE html>
<html>
<head>
    <title>hello html python</title>
</head>
<body>
    <h1>Hello! Welcome to my web!</h1>
    <p>Greeting from Xijian Xiang<p>
    <p>Language: Python </p>
    <p>Generated at {current}</p>
    <p>IP address: {ip_addr}</p>
</body>
</html>
""")