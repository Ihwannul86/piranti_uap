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
    <div class="bg-gradient-to-r from-purple-600 to-blue-600 p-6 rounded-lg mb-6 shadow-xl">
        <h2 class="text-xl font-bold mb-4 flex items-center">
            <span class="mr-2">🎮</span> Remote Control
        </h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <button onclick="sendCommand('buzzer_on')"
                class="bg-green-500 hover:bg-green-600 text-white font-bold py-3 px-6 rounded-lg transition transform hover:scale-105">
                🔊 Aktifkan Buzzer
            </button>
            <button onclick="sendCommand('buzzer_off')"
                class="bg-red-500 hover:bg-red-600 text-white font-bold py-3 px-6 rounded-lg transition transform hover:scale-105">
                🔇 Matikan Buzzer
            </button>
            <button onclick="openThresholdModal()"
                class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-3 px-6 rounded-lg transition transform hover:scale-105">
                ⚙️ Set Threshold
            </button>
        </div>
        <div id="commandStatus" class="mt-4 text-center text-sm"></div>
    </div>

    <!-- Status Cards -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-6">
        <div class="bg-gray-800 p-4 rounded-lg shadow-lg">
            <h2 class="font-semibold mb-2 flex items-center">
                <span class="mr-2">📊</span> Status Terbaru
            </h2>
            <p id="statusText" class="text-lg mb-3">Menunggu data...</p>
            <div class="text-sm space-y-1">
                <p>🌡️ Suhu: <span id="lastTemp" class="font-bold">-</span> °C</p>
                <p>💧 TDS: <span id="lastTds" class="font-bold">-</span> ppm</p>
                <p>⏰ Waktu: <span id="lastTime" class="text-gray-400">-</span></p>
            </div>
        </div>

        <div class="bg-gray-800 p-4 rounded-lg shadow-lg">
            <h2 class="font-semibold mb-2">🌡️ Kondisi Suhu</h2>
            <ul class="text-sm space-y-1">
                <li>🔵 &lt; 30°C: Normal (LED Biru ON)</li>
                <li>🟠 ≥ 30°C: Panas (LED Oranye ON + Buzzer LOW)</li>
            </ul>
        </div>

        <div class="bg-gray-800 p-4 rounded-lg shadow-lg">
            <h2 class="font-semibold mb-2">💧 Kondisi Kualitas Air</h2>
            <ul class="text-sm space-y-1">
                <li>⚪ ≤ 200 ppm: Air OK (LED Putih ON)</li>
                <li>🔴 &gt; 200 ppm: Air Buruk (LED Merah + Buzzer HIGH)</li>
            </ul>
        </div>
    </div>

    <!-- Charts -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <div class="bg-gray-800 p-4 rounded-lg shadow-lg">
            <h2 class="font-semibold mb-3">📈 Grafik Suhu (°C)</h2>
            <canvas id="tempChart" height="150"></canvas>
        </div>
        <div class="bg-gray-800 p-4 rounded-lg shadow-lg">
            <h2 class="font-semibold mb-3">📈 Grafik TDS (ppm)</h2>
            <canvas id="tdsChart" height="150"></canvas>
        </div>
    </div>
</div>

<!-- Modal Threshold -->
<div id="thresholdModal" class="hidden fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center z-50">
    <div class="bg-gray-800 p-6 rounded-lg max-w-md w-full">
        <h3 class="text-xl font-bold mb-4">⚙️ Set Threshold</h3>
        <div class="space-y-4">
            <div>
                <label class="block mb-2">🌡️ Suhu Maksimal (°C)</label>
                <input type="number" id="tempInput" value="30" class="w-full bg-gray-700 text-white p-2 rounded">
            </div>
            <div>
                <label class="block mb-2">💧 TDS Maksimal (ppm)</label>
                <input type="number" id="tdsInput" value="200" class="w-full bg-gray-700 text-white p-2 rounded">
            </div>
        </div>
        <div class="mt-6 flex gap-4">
            <button onclick="setThreshold()" class="flex-1 bg-blue-500 hover:bg-blue-600 text-white py-2 rounded">
                ✅ Set
            </button>
            <button onclick="closeThresholdModal()" class="flex-1 bg-gray-600 hover:bg-gray-700 text-white py-2 rounded">
                ❌ Cancel
            </button>
        </div>
    </div>
</div>

<script>
const tempCtx = document.getElementById('tempChart').getContext('2d');
const tdsCtx = document.getElementById('tdsChart').getContext('2d');

const tempChart = new Chart(tempCtx, {
    type: 'line',
    data: { labels: [], datasets: [{
        label: 'Suhu (°C)',
        data: [],
        borderColor: 'rgb(59, 130, 246)',
        backgroundColor: 'rgba(59, 130, 246, 0.2)',
        tension: 0.3,
        pointRadius: 2
    }]},
    options: {
        responsive: true,
        scales: { y: { beginAtZero: false } },
        animation: { duration: 0 }
    }
});

const tdsChart = new Chart(tdsCtx, {
    type: 'line',
    data: { labels: [], datasets: [{
        label: 'TDS (ppm)',
        data: [],
        borderColor: 'rgb(248, 113, 113)',
        backgroundColor: 'rgba(248, 113, 113, 0.2)',
        tension: 0.3,
        pointRadius: 2
    }]},
    options: {
        responsive: true,
        scales: { y: { beginAtZero: true } },
        animation: { duration: 0 }
    }
});

function addData(chart, label, value) {
    chart.data.labels.push(label);
    chart.data.datasets[0].data.push(value);
    const maxPoints = 50;
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
            addData(tdsChart, timeLabel, item.tds);
            updateStatusUI(item);
        });
    } catch (e) {
        console.error('Gagal load history', e);
    }
}

function updateStatusUI(item) {
    document.getElementById('statusText').textContent = item.status;
    document.getElementById('lastTemp').textContent = item.temperature.toFixed(2);
    document.getElementById('lastTds').textContent = item.tds;
    document.getElementById('lastTime').textContent = new Date(item.created_at).toLocaleTimeString();
}

function initSSE() {
    const source = new EventSource('/events');
    source.onmessage = (event) => {
        try {
            const item = JSON.parse(event.data);
            const timeLabel = new Date(item.created_at).toLocaleTimeString();
            addData(tempChart, timeLabel, item.temperature);
            addData(tdsChart, timeLabel, item.tds);
            updateStatusUI(item);
        } catch (e) {
            console.error('Error SSE data', e);
        }
    };
    source.onerror = (e) => console.error('SSE error', e);
}

// Remote Control Functions
async function sendCommand(command, params = {}) {
    const statusDiv = document.getElementById('commandStatus');
    statusDiv.textContent = '⏳ Sending command...';

    try {
        const res = await fetch('/api/command', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ command, params })
        });

        const data = await res.json();
        if (data.success) {
            statusDiv.textContent = '✅ Command sent successfully!';
            setTimeout(() => statusDiv.textContent = '', 3000);
        }
    } catch (e) {
        statusDiv.textContent = '❌ Failed to send command';
        console.error(e);
    }
}

function openThresholdModal() {
    document.getElementById('thresholdModal').classList.remove('hidden');
}

function closeThresholdModal() {
    document.getElementById('thresholdModal').classList.add('hidden');
}

function setThreshold() {
    const temp = parseFloat(document.getElementById('tempInput').value);
    const tds = parseInt(document.getElementById('tdsInput').value);

    sendCommand('set_threshold', { temp, tds });
    closeThresholdModal();
}

loadHistory().then(initSSE);
</script>
</body>
</html>
