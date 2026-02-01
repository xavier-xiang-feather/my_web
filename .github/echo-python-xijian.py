#!/usr/bin/env python3

import os
import sys
import json
import datetime
import urllib.parse

method = os.environ.get("REQUEST_METHOD", "UNKNOWN")
content_type = os.environ.get("CONTENT_TYPE", "")
ip = os.environ.get("REMOTE_ADDR", "unknown")
user_agent = os.environ.get("HTTP_USER_AGENT", "unknown")
current = datetime.datetime.now().strftime("%Y-%m-%d %H:%M:%S")

data = {}

if method == "GET":
    query_str = os.environ.get("QUERY_STRING", "")
    data = urllib.parse.parse_qs(query_str)
elif method == "POST":
    length = int(os.environ.get("CONTENT_LENGTH", 0))
    body = sys.stdin.read(length)

    if "applciation/json" in content_type:
        try:
            data = json.loads(body)
        except:
            data = {"error": "Invalid JSON"}
    else:
        data = urllib.parse.parse_qs(body)

response = {
    "method": method,
    "content_type": content_type,
    "received_data" : data,
    "time": current,
    "ip_address": ip,
    "user_agent": user_agent,
    
}
print("Content-Type: application/json\r\n\r\n")
print(json.dumps(response, indent=2))