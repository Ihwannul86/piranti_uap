<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>FishBreathe Lite Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-gray-900 text-gray-100">
<div class="min-h-screen p-6">
    <h1 class="text-2xl font-bold mb-4">FishBreathe Lite - Dashboard</h1>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-6">
        <div class="bg-gray-800 p-4 rounded">
            <h2 class="font-semibold mb-2">Status Terbaru</h2>
            <p id="statusText" class="text-lg">Menunggu data...</p>
            <p class="text-sm mt-2 text-gray-400">
                Suhu: <span id="lastTemp">-</span> &deg;C<br>
                TDS: <span id="lastTds">-</span> ppm<br>
                Waktu: <span id="lastTime">-</span>
            </p>
        </div>

        <div class="bg-gray-800 p-4 rounded">
            <h2 class="font-semibold mb-2">Kondisi Suhu</h2>
            <ul class="text-sm list-disc list-inside">
                <li>&lt; 25 &deg;C: Dingin (LED Biru ON)</li>
                <li>25 &ndash; 30 &deg;C: Normal</li>
                <li>&gt; 30 &deg;C: Panas (LED Oranye ON)</li>
            </ul>
        </div>

        <div class="bg-gray-800 p-4 rounded">
            <h2 class="font-semibold mb-2">Kondisi Kualitas Air</h2>
            <ul class="text-sm list-disc list-inside">
                <li>&le; 600 ppm: Air OK (LED Putih ON)</li>
                <li>&gt; 600 ppm: Air Buruk (LED Merah + Buzzer)</li>
            </ul>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <div class="bg-gray-800 p-4 rounded">
            <h2 class="font-semibold mb-2">Grafik Suhu (°C)</h2>
            <canvas id="tempChart" height="150"></canvas>
        </div>
        <div class="bg-gray-800 p-4 rounded">
            <h2 class="font-semibold mb-2">Grafik TDS (ppm)</h2>
            <canvas id="tdsChart" height="150"></canvas>
        </div>
    </div>
</div>

<script>
    const tempCtx = document.getElementById('tempChart').getContext('2d');
    const tdsCtx  = document.getElementById('tdsChart').getContext('2d');

    const tempChart = new Chart(tempCtx, {
        type: 'line',
        data: { labels: [], datasets: [{
            label: 'Suhu (°C)',
            data: [],
            borderColor: 'rgb(59, 130, 246)',
            backgroundColor: 'rgba(59, 130, 246, 0.2)',
            tension: 0.2,
            pointRadius: 0
        }]},
        options: { scales: { y: { beginAtZero: false } } }
    });

    const tdsChart = new Chart(tdsCtx, {
        type: 'line',
        data: { labels: [], datasets: [{
            label: 'TDS (ppm)',
            data: [],
            borderColor: 'rgb(248, 113, 113)',
            backgroundColor: 'rgba(248, 113, 113, 0.2)',
            tension: 0.2,
            pointRadius: 0
        }]},
        options: { scales: { y: { beginAtZero: true } } }
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
        document.getElementById('lastTime').textContent   =
            new Date(item.created_at).toLocaleTimeString();
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

    loadHistory().then(initSSE);
</script>
</body>
</html>
