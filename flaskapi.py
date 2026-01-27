# -*- coding: utf-8 -*-
from flask import Flask, jsonify
import requests
from bs4 import BeautifulSoup
import re
from datetime import datetime
import pytz

app = Flask(__name__)

URL = "https://board.de.metin2.gameforge.com/index.php?thread/56068-wartungsarbeiten/&action=lastPost"
KEYWORDS = ["wartung", "hotfix", "neustart", "restart", "maintenance", "bakım", "düzeltme"]

BERLIN = pytz.timezone("Europe/Berlin")
ISTANBUL = pytz.timezone("Europe/Istanbul")

HEADERS = {"User-Agent": "Mozilla/5.0"}

def fetch_html():
    r = requests.get(URL, headers=HEADERS, timeout=10)
    r.raise_for_status()
    return r.text

def parse_datetime(text):
    date = None
    time = None

    m = re.search(r"(\d{1,2})\.(\d{1,2})(?:\.(\d{4}))?", text)
    if m:
        d, mth, y = m.groups()
        if not y:
            y = str(datetime.now().year)
        date = f"{y}-{mth.zfill(2)}-{d.zfill(2)}"

    t = re.search(r"(\d{2}:\d{2})", text)
    if t:
        time = t.group(1)

    return date, time

def convert_to_tr(date_str, time_str):
    dt = datetime.strptime(date_str + " " + time_str, "%Y-%m-%d %H:%M")
    berlin_dt = BERLIN.localize(dt)
    tr_dt = berlin_dt.astimezone(ISTANBUL)
    return tr_dt.strftime("%Y-%m-%d"), tr_dt.strftime("%H:%M")

def analyze():
    html = fetch_html()
    soup = BeautifulSoup(html, "html.parser")
    posts = soup.find_all("div", class_="messageText")

    if not posts:
        return {"maintenanceFound": False, "error": "No posts found"}

    last_post = posts[-1].get_text("\n", strip=True)
    lines = [l.strip() for l in last_post.split("\n") if l.strip()]

    matches = []

    for line in lines:
        if any(k in line.lower() for k in KEYWORDS):
            date, time = parse_datetime(line)
            if date and time:
                tr_date, tr_time = convert_to_tr(date, time)
                matches.append({
                    "berlinTime": f"{date} {time}",
                    "turkeyTime": f"{tr_date} {tr_time}",
                    "sentence": line
                })

    if not matches:
        return {"maintenanceFound": False, "rawLastPost": last_post}

    last = matches[-1]

    return {
        "maintenanceFound": True,
        "berlinTime": last["berlinTime"],
        "turkeyTime": last["turkeyTime"],
        "sentence": last["sentence"],
        "checkedAt": datetime.now(ISTANBUL).strftime("%Y-%m-%d %H:%M:%S")
    }

@app.route("/maintenance", methods=["GET"])
def maintenance():
    try:
        return jsonify(analyze())
    except Exception as e:
        return jsonify({"error": str(e)}), 500

if __name__ == "__main__":
    app.run(host="0.0.0.0", port=5000)
