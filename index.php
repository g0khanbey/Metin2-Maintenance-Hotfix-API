<?php
date_default_timezone_set("Europe/Istanbul");

$apiUrl = "http://127.0.0.1:5050/maintenance";

function fetchApi($url) {
    $ch = curl_init();

    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_CONNECTTIMEOUT => 5,
    ]);

    $response = curl_exec($ch);
    $error = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    curl_close($ch);

    if ($response === false || $httpCode >= 400) {
        return [
            "success" => false,
            "error" => $error ?: "API okunamadı",
            "httpCode" => $httpCode,
            "raw" => null
        ];
    }

    $json = json_decode($response, true);

    if (!$json) {
        return [
            "success" => false,
            "error" => "JSON çözümlenemedi",
            "httpCode" => $httpCode,
            "raw" => $response
        ];
    }

    return [
        "success" => true,
        "data" => $json,
        "raw" => $response
    ];
}

$result = fetchApi($apiUrl);

$maintenanceFound = false;
$turkeyTime = null;
$berlinTime = null;
$sentence = null;
$checkedAt = date("Y-m-d H:i:s");
$statusText = "Veri alınamadı";
$statusClass = "danger";
$remainingText = "Bilinmiyor";
$targetIso = null;
$isPast = false;

if ($result["success"]) {
    $data = $result["data"];

    $maintenanceFound = $data["maintenanceFound"] ?? false;
    $turkeyTime = $data["turkeyTime"] ?? null;
    $berlinTime = $data["berlinTime"] ?? null;
    $sentence = $data["sentence"] ?? null;
    $checkedAt = $data["checkedAt"] ?? date("Y-m-d H:i:s");

    if ($maintenanceFound && $turkeyTime) {
        $tz = new DateTimeZone("Europe/Istanbul");

        $now = new DateTime("now", $tz);
        $target = new DateTime($turkeyTime, $tz);

        $targetIso = $target->format("c");

        if ($target <= $now) {
            $statusText = "Bakım zamanı geldi veya geçmiş";
            $statusClass = "warning";
            $remainingText = "Bakım zamanı geçmiş";
            $isPast = true;
        } else {
            $diff = $now->diff($target);

            $days = $diff->days;
            $hours = $diff->h;
            $minutes = $diff->i;

            $remainingText = "{$days} gün {$hours} saat {$minutes} dakika";
            $statusText = "Yaklaşan bakım bulundu";
            $statusClass = "success";
        }
    } else {
        $statusText = "Aktif bakım duyurusu bulunamadı";
        $statusClass = "neutral";
    }
} else {
    $statusText = "Flask API bağlantı hatası";
    $statusClass = "danger";
}

$cleanSentence = $sentence ? str_replace("**", "", $sentence) : "Duyuru metni bulunamadı.";
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">

    <title>Metin2 Maintenance Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: Arial, Helvetica, sans-serif;
            background:
                radial-gradient(circle at top left, rgba(37, 99, 235, 0.25), transparent 30%),
                radial-gradient(circle at bottom right, rgba(147, 51, 234, 0.20), transparent 30%),
                #020617;
            color: #e5e7eb;
            padding: 30px;
        }

        .container {
            max-width: 1100px;
            margin: auto;
        }

        .header {
            margin-bottom: 25px;
        }

        .header h1 {
            margin: 0;
            font-size: 34px;
            color: #ffffff;
        }

        .header p {
            color: #94a3b8;
            margin-top: 8px;
        }

        .grid {
            display: grid;
            grid-template-columns: 1.4fr 1fr;
            gap: 20px;
        }

        .card {
            background: rgba(15, 23, 42, 0.88);
            border: 1px solid rgba(148, 163, 184, 0.18);
            border-radius: 20px;
            padding: 24px;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.35);
            backdrop-filter: blur(14px);
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 9px 14px;
            border-radius: 999px;
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 20px;
        }

        .status-badge.success {
            background: rgba(34, 197, 94, 0.15);
            color: #4ade80;
            border: 1px solid rgba(74, 222, 128, 0.35);
        }

        .status-badge.warning {
            background: rgba(245, 158, 11, 0.15);
            color: #fbbf24;
            border: 1px solid rgba(251, 191, 36, 0.35);
        }

        .status-badge.danger {
            background: rgba(239, 68, 68, 0.15);
            color: #f87171;
            border: 1px solid rgba(248, 113, 113, 0.35);
        }

        .status-badge.neutral {
            background: rgba(148, 163, 184, 0.15);
            color: #cbd5e1;
            border: 1px solid rgba(203, 213, 225, 0.25);
        }

        .main-time {
            font-size: 52px;
            font-weight: 800;
            margin: 10px 0;
            color: #ffffff;
            letter-spacing: -1px;
        }

        .sub-text {
            color: #94a3b8;
            line-height: 1.6;
        }

        .countdown {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 12px;
            margin-top: 22px;
        }

        .time-box {
            background: #020617;
            border: 1px solid rgba(148, 163, 184, 0.18);
            border-radius: 16px;
            padding: 18px 12px;
            text-align: center;
        }

        .time-box .num {
            font-size: 30px;
            font-weight: 800;
            color: #60a5fa;
        }

        .time-box .label {
            font-size: 12px;
            color: #94a3b8;
            margin-top: 5px;
        }

        .info-list {
            display: flex;
            flex-direction: column;
            gap: 14px;
        }

        .info-row {
            background: rgba(2, 6, 23, 0.55);
            border: 1px solid rgba(148, 163, 184, 0.14);
            border-radius: 14px;
            padding: 14px;
        }

        .info-row span {
            display: block;
            color: #94a3b8;
            font-size: 13px;
            margin-bottom: 5px;
        }

        .info-row strong {
            color: #f8fafc;
            font-size: 16px;
            word-break: break-word;
        }

        .sentence {
            margin-top: 20px;
            background: rgba(30, 41, 59, 0.65);
            border-left: 4px solid #3b82f6;
            border-radius: 12px;
            padding: 16px;
            color: #cbd5e1;
            line-height: 1.6;
        }

        .raw {
            margin-top: 20px;
        }

        .raw summary {
            cursor: pointer;
            color: #93c5fd;
            margin-bottom: 10px;
        }

        pre {
            background: #020617;
            border: 1px solid rgba(148, 163, 184, 0.18);
            border-radius: 14px;
            padding: 16px;
            overflow-x: auto;
            color: #d1d5db;
            font-size: 13px;
        }

        .footer {
            margin-top: 22px;
            color: #64748b;
            font-size: 13px;
            text-align: center;
        }

        .refresh-btn {
            display: inline-block;
            margin-top: 18px;
            padding: 11px 16px;
            background: linear-gradient(135deg, #2563eb, #7c3aed);
            color: #ffffff;
            border: 0;
            border-radius: 12px;
            text-decoration: none;
            font-weight: bold;
            cursor: pointer;
        }

        .refresh-btn:hover {
            opacity: 0.9;
        }

        @media (max-width: 850px) {
            body {
                padding: 18px;
            }

            .grid {
                grid-template-columns: 1fr;
            }

            .main-time {
                font-size: 38px;
            }

            .countdown {
                grid-template-columns: repeat(2, 1fr);
            }
        }
    </style>
</head>
<body>

<div class="container">

    <div class="header">
        <h1>Metin2 Maintenance Dashboard</h1>
        <p>Gameforge Metin2 Almanya bakım/hotfix duyurusu Türkiye saatine çevrilerek gösterilir.</p>
    </div>

    <div class="grid">

        <div class="card">
            <div class="status-badge <?= htmlspecialchars($statusClass) ?>">
                ? <?= htmlspecialchars($statusText) ?>
            </div>

            <div class="sub-text">Türkiye bakım saati</div>

            <div class="main-time">
                <?= $turkeyTime ? htmlspecialchars($turkeyTime) : "Bulunamadı" ?>
            </div>

            <div class="sub-text">
                Kalan süre:
                <strong id="remainingText"><?= htmlspecialchars($remainingText) ?></strong>
            </div>

            <div class="countdown">
                <div class="time-box">
                    <div class="num" id="days">--</div>
                    <div class="label">Gün</div>
                </div>

                <div class="time-box">
                    <div class="num" id="hours">--</div>
                    <div class="label">Saat</div>
                </div>

                <div class="time-box">
                    <div class="num" id="minutes">--</div>
                    <div class="label">Dakika</div>
                </div>

                <div class="time-box">
                    <div class="num" id="seconds">--</div>
                    <div class="label">Saniye</div>
                </div>
            </div>

 

            <a class="refresh-btn" href="">Yenile</a>
        </div>

        <div class="card">
            <div class="info-list">

                <div class="info-row">
                    <span>Bakım bulundu mu?</span>
                    <strong><?= $maintenanceFound ? "Evet" : "Hayır" ?></strong>
                </div>

                <div class="info-row">
                    <span>Berlin saati</span>
                    <strong><?= $berlinTime ? htmlspecialchars($berlinTime) : "Yok" ?></strong>
                </div>

                <div class="info-row">
                    <span>Türkiye saati</span>
                    <strong><?= $turkeyTime ? htmlspecialchars($turkeyTime) : "Yok" ?></strong>
                </div>

                <div class="info-row">
                    <span>Son kontrol</span>
                    <strong><?= htmlspecialchars($checkedAt) ?></strong>
                </div>

 

            </div>

            <details class="raw">
                <summary>Ham JSON çıktısını göster</summary>
                <pre><?= htmlspecialchars($result["raw"] ?? json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)) ?></pre>
            </details>
        </div>

    </div>

    <div class="footer">
        Sayfa her açıldığında Flask API üzerinden güncel bakım duyurusu kontrol edilir.
    </div>

</div>

<script>
const targetIso = <?= $targetIso ? json_encode($targetIso) : "null" ?>;

function pad(num) {
    return String(num).padStart(2, "0");
}

function updateCountdown() {
    const daysEl = document.getElementById("days");
    const hoursEl = document.getElementById("hours");
    const minutesEl = document.getElementById("minutes");
    const secondsEl = document.getElementById("seconds");
    const remainingText = document.getElementById("remainingText");

    if (!targetIso) {
        daysEl.textContent = "--";
        hoursEl.textContent = "--";
        minutesEl.textContent = "--";
        secondsEl.textContent = "--";
        return;
    }

    const target = new Date(targetIso).getTime();
    const now = new Date().getTime();

    let diff = target - now;

    if (diff <= 0) {
        daysEl.textContent = "00";
        hoursEl.textContent = "00";
        minutesEl.textContent = "00";
        secondsEl.textContent = "00";
        remainingText.textContent = "Bakım zamanı geldi veya geçti";
        return;
    }

    const dayMs = 1000 * 60 * 60 * 24;
    const hourMs = 1000 * 60 * 60;
    const minuteMs = 1000 * 60;

    const days = Math.floor(diff / dayMs);
    diff %= dayMs;

    const hours = Math.floor(diff / hourMs);
    diff %= hourMs;

    const minutes = Math.floor(diff / minuteMs);
    diff %= minuteMs;

    const seconds = Math.floor(diff / 1000);

    daysEl.textContent = pad(days);
    hoursEl.textContent = pad(hours);
    minutesEl.textContent = pad(minutes);
    secondsEl.textContent = pad(seconds);

    remainingText.textContent =
        days + " gün " + hours + " saat " + minutes + " dakika " + seconds + " saniye";
}

updateCountdown();
setInterval(updateCountdown, 1000);
</script>

</body>
</html>
