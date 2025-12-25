<?php

namespace App\Http\Controllers;

use App\Models\SensorLog;
use App\Models\Command;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SensorController extends Controller
{
    // Terima data dari ESP32
    public function store(Request $request)
{
    // BYPASS VALIDATION - hanya untuk testing
    $data = [
        'temperature' => $request->temperature ?? 0,
        'tds'         => $request->tds ?? 0,
        'status'      => $request->status ?? 'Unknown',
    ];

    try {
        $log = SensorLog::create($data);

        return response()->json([
            'success' => true,
            'id'      => $log->id,
        ], 200);
    } catch (\Exception $e) {
        return response()->json([
            'error' => $e->getMessage()
        ], 500);
    }
}


    // Ambil history untuk grafik
    public function history()
    {
        $logs = SensorLog::orderBy('created_at', 'desc')
            ->take(200)
            ->get()
            ->reverse()
            ->values();

        return response()->json($logs);
    }

    // Server-Sent Events untuk real-time update
    public function events()
    {
        $response = new StreamedResponse(function () {
            set_time_limit(0);
            $lastId = null;

            while (true) {
                $query = SensorLog::orderBy('id', 'desc');
                if ($lastId) {
                    $query->where('id', '>', $lastId);
                }

                $latest = $query->first();

                if ($latest) {
                    $lastId = $latest->id;
                    echo "data: " . $latest->toJson() . "\n\n";
                    ob_flush();
                    flush();
                }

                sleep(2);
            }
        });

        $response->headers->set('Content-Type', 'text/event-stream');
        $response->headers->set('Cache-Control', 'no-cache');
        $response->headers->set('X-Accel-Buffering', 'no');

        return $response;
    }

    // ESP32 polling command dari server
    public function getCommand()
    {
        $cmd = Command::where('executed', false)
            ->orderBy('id', 'asc')
            ->first();

        if ($cmd) {
            $cmd->update(['executed' => true]);
            return response()->json([
                'command' => $cmd->command,
                'params'  => $cmd->params,
            ]);
        }

        return response()->json(['command' => 'none']);
    }

    // Dashboard kirim command ke ESP32
    public function sendCommand(Request $request)
    {
        $data = $request->validate([
            'command' => 'required|string',
            'params'  => 'nullable|array',
        ]);

        $cmd = Command::create($data);

        return response()->json([
            'success' => true,
            'id'      => $cmd->id,
            'message' => 'Command queued successfully'
        ]);
    }
}
