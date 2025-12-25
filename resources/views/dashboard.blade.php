<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>FishBreathe Lite Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-gray-900 text-gray-100">
<div class="min-h-screen p-6">
    <h1 class="text-3xl font-bold mb-6 text-center">🐟 FishBreathe Lite - Dashboard</h1>

    <!-- Remote Control Panel -->
    <div class="bg-gradient-to-r from-blue-800 to-purple-800 p-6 rounded-lg mb-6 shadow-lg">
        <h2 class="text-xl font-semibold mb-4">📱 Remote Control</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <button onclick="sendCommand('buzzer_on')"
                    class="bg-green-600 hover:bg-green-700 px-6 py-3 rounded-lg font-semibold transition-all transform hover:scale-105 shadow-lg">
                🔊 Aktifkan Buzzer
            </button>
            <button onclick="sendCommand('buzzer_off')"
                    class="bg-red-600 hover:bg-red-700 px-6 py-3 rounded-lg font-semibold transition-all transform hover:scale-105 shadow-lg">
                🔇 Matikan Buzzer
            </button>
            <button onclick="setThreshold()"
                    class="bg-blue-600 hover:bg-blue-700 px-6 py-3 rounded-lg font-semibold transition-all transform hover:scale-105 shadow-lg">
                ⚙️ Set Threshold
            </button>
        </div>
        <div id="cmdStatus" class="mt-4 text-sm bg-gray-900 bg-opacity-50 p-3 rounded min-h-[2rem]"></div>
    </div>

    <!-- Status Cards -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-6">
        <div class="bg-gray-800 p-6 rounded-lg shadow-lg">
            <h2 class="font-semibold mb-3 text-lg">📊 Status Terbaru</h2>
            <p id="statusText" class="text-lg mb-3 text-yellow-400">Menunggu data...</p>
            <div class="text-sm text-gray-300 space-y-1">
                <p>🌡️ Suhu: <span id="lastTemp" class="text-blue-400 font-bold">-</span> °C</p>
                <p>💧 TDS: <span id="lastTds" class="text-red-400 font-bold">-</span> ppm</p>
                <p>🕒 Waktu: <span id="lastTime" class="text-gray-400">-</span></p>
            </div>
        </div>

        <div class="bg-gray-800 p-6 rounded-lg shadow-lg">
            <h2 class="font-semibold mb-3 text-lg">🌡️ Kondisi Suhu</h2>
            <ul class="text-sm space-y-2">
                <li class="flex items-center">
                    <span class="text-blue-400 mr-2">🔵</span>
                    &lt; 30 °C: Normal (LED Biru ON)
                </li>
                <li class="flex items-center">
                    <span class="text-orange-400 mr-2">🟠</span>
                    &gt; 30 °C: Panas (LED Oranye ON + Buzzer LOW)
                </li>
            </ul>
        </div>

        <div class="bg-gray-800 p-6 rounded-lg shadow-lg">
            <h2 class="font-semibold mb-3 text-lg">💧 Kondisi Kualitas Air</h2>
            <ul class="text-sm space-y-2">
                <li class="flex items-center">
                    <span class="text-white mr-2">⚪</span>
                    &le; 200 ppm: Air OK (LED Putih ON)
                </li>
                <li class="flex items-center">
                    <span class="text-red-400 mr-2">🔴</span>
                    &gt; 200 ppm: Air Buruk (LED Merah + Buzzer HIGH)
                </li>
            </ul>
        </div>
    </div>

    <!-- Charts -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <div class="bg-gray-800 p-6 rounded-lg shadow-lg">
            <h2 class="font-semibold mb-4 text-lg">📈 Grafik Suhu (°C)</h2>
            <canvas id="tempChart" height="150"></canvas>
        </div>
        <div class="bg-gray-800 p-6 rounded-lg shadow-lg">
            <h2 class="font-semibold mb-4 text-lg">📈 Grafik TDS (ppm)</h2>
            <canvas id="tdsChart" height="150"></canvas>
        </div>
    </div>
</div>

<script>
    // Setup CSRF token untuk AJAX
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

    // Inisialisasi Chart.js
    const tempCtx = document.getElementById('tempChart').getContext('2d');
    const tdsCtx  = document.getElementById('tdsChart').getContext('2d');

    const tempChart = new Chart(tempCtx, {
        type: 'line',
        data: {
            labels: [],
            datasets: [{
                label: 'Suhu (°C)',
                data: [],
                borderColor: 'rgb(59, 130, 246)',
                backgroundColor: 'rgba(59, 130, 246, 0.2)',
                tension: 0.3,
                pointRadius: 2,
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: false,
                    grid: { color: 'rgba(255,255,255,0.1)' },
                    ticks: { color: 'rgb(156, 163, 175)' }
                },
                x: {
                    grid: { color: 'rgba(255,255,255,0.1)' },
                    ticks: { color: 'rgb(156, 163, 175)' }
                }
            },
            plugins: {
                legend: { labels: { color: 'rgb(209, 213, 219)' } }
            }
        }
    });

    const tdsChart = new Chart(tdsCtx, {
        type: 'line',
        data: {
            labels: [],
            datasets: [{
                label: 'TDS (ppm)',
                data: [],
                borderColor: 'rgb(248, 113, 113)',
                backgroundColor: 'rgba(248, 113, 113, 0.2)',
                tension: 0.3,
                pointRadius: 2,
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: 'rgba(255,255,255,0.1)' },
                    ticks: { color: 'rgb(156, 163, 175)' }
                },
                x: {
                    grid: { color: 'rgba(255,255,255,0.1)' },
                    ticks: { color: 'rgb(156, 163, 175)' }
                }
            },
            plugins: {
                legend: { labels: { color: 'rgb(209, 213, 219)' } }
            }
        }
    });

    function addData(chart, label, value) {
        chart.data.labels.push(label);
        chart.data.datasets[0].data.push(value);
        const maxPoints = 200;
        if (chart.data.labels.length > maxPoints) {
            chart.data.labels.shift();
            chart.data.datasets[0].data.shift();
        }
        chart.update('none');
    }

    async function loadHistory() {
        try {
            const res = await fetch('/api/history');
            const logs = await res.json();
            logs.forEach(item => {
                const timeLabel = new Date(item.created_at).toLocaleTimeString();
                addData(tempChart, timeLabel, item.temperature);
                addData(tdsChart,  timeLabel, item.tds);
                updateStatusUI(item);
            });
        } catch (e) {
            console.error('Gagal load history', e);
        }
    }

    function updateStatusUI(item) {
        document.getElementById('statusText').textContent = item.status;
        document.getElementById('lastTemp').textContent   = item.temperature.toFixed(2);
        document.getElementById('lastTds').textContent    = item.tds;
        document.getElementById('lastTime').textContent   = new Date(item.created_at).toLocaleTimeString();
    }

    function initSSE() {
        const source = new EventSource('/events');
        source.onmessage = (event) => {
            try {
                const item = JSON.parse(event.data);
                const timeLabel = new Date(item.created_at).toLocaleTimeString();
                addData(tempChart, timeLabel, item.temperature);
                addData(tdsChart,  timeLabel, item.tds);
                updateStatusUI(item);
            } catch (e) {
                console.error('Error SSE data', e);
            }
        };
        source.onerror = (e) => console.error('SSE error', e);
    }

    // ========== REMOTE CONTROL FUNCTIONS ==========
    async function sendCommand(cmd, params = {}) {
        const statusEl = document.getElementById('cmdStatus');
        statusEl.innerHTML = `<span class="text-yellow-400">⏳ Mengirim command "${cmd}"...</span>`;

        try {
            const res = await fetch('/api/command', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({ command: cmd, params: params })
            });

            const data = await res.json();

            if (data.success) {
                statusEl.innerHTML = `<span class="text-green-400">✅ Command "${cmd}" berhasil dikirim! (ID: ${data.id})</span>`;
                setTimeout(() => {
                    statusEl.innerHTML = `<span class="text-gray-400">ESP32 akan mengeksekusi command dalam beberapa detik...</span>`;
                }, 2000);
            } else {
                statusEl.innerHTML = `<span class="text-red-400">❌ Gagal mengirim command</span>`;
            }
        } catch (e) {
            statusEl.innerHTML = `<span class="text-red-400">❌ Error: ${e.message}</span>`;
        }
    }

    function setThreshold() {
        const temp = prompt('🌡️ Set suhu maksimal (°C):', '30');
        const tds = prompt('💧 Set TDS maksimal (ppm):', '200');

        if (temp && tds) {
            const tempVal = parseFloat(temp);
            const tdsVal = parseInt(tds);

            if (isNaN(tempVal) || isNaN(tdsVal)) {
                alert('❌ Input tidak valid! Harus berupa angka.');
                return;
            }

            sendCommand('set_threshold', {
                temp: tempVal,
                tds: tdsVal
            });
        }
    }

    // Inisialisasi
    loadHistory().then(initSSE);
</script>
</body>
</html>
