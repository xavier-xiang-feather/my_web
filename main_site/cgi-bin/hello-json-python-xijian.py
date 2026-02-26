#!/usr/bin/env python3
import os
import json
import datetime

current = datetime.datetime.now().strftime("%Y-%m-%d %H:%M:%S")
ip_addr = os.environ.get("REMOTE_ADDR", "unknown")

response = {
    "greeting": "Hello, World!",
    "from": "greeting from Xijian Xiang",
    "Generated at": current,
    "IP Address": ip_addr
}

print("Content-Type: application/json\r\n\r\n")
print(json.dumps(response, indent=2))