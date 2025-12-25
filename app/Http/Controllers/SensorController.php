<?php

namespace App\Http\Controllers;

use App\Models\SensorLog;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SensorController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'temperature' => 'required|numeric',
            'tds'         => 'required|integer',
            'status'      => 'required|string',
        ]);

        $log = SensorLog::create($data);

        return response()->json([
            'success' => true,
            'id'      => $log->id,
        ]);
    }

    public function history()
    {
        $logs = SensorLog::orderBy('created_at', 'desc')
            ->take(200)
            ->get()
            ->reverse()
            ->values();

        return response()->json($logs);
    }

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
}
