#!/usr/bin/env python3
import os
import json
import datetime

current = datetime.datetime.now().strftime("%Y-%m-%d %H:%M:%S")
ip_addr = os.environ.get("REMOTE_ADDR", "unknown")

response = {
    "g"
}