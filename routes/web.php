<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Http\Controllers\SensorController;

// Dashboard
Route::get('/', function () {
    return redirect('/dashboard');
});

Route::get('/dashboard', function () {
    return view('dashboard');
});

// ========== API ROUTES ==========

Route::post('/api/sensor', function (Request $request) {
    DB::table('sensor_logs')->insert([
        'temperature' => $request->input('temperature', 0),
        'tds'         => $request->input('tds', 0),
        'status'      => $request->input('status', 'Unknown'),
        'created_at'  => now(),
        'updated_at'  => now(),
    ]);

    return response()->json(['success' => true]);
});

Route::get('/api/history', function () {
    $logs = DB::table('sensor_logs')
        ->orderBy('created_at', 'desc')
        ->take(200)
        ->get()
        ->reverse()
        ->values();
    return response()->json($logs);
});

Route::get('/api/command', function () {
    $cmd = DB::table('commands')
        ->where('executed', false)
        ->orderBy('id', 'asc')
        ->first();

    if ($cmd) {
        DB::table('commands')->where('id', $cmd->id)->update(['executed' => true]);
        return response()->json([
            'command' => $cmd->command,
            'params'  => json_decode($cmd->params),
        ]);
    }

    return response()->json(['command' => 'none']);
});

Route::post('/api/command', function (Request $request) {
    $id = DB::table('commands')->insertGetId([
        'command'    => $request->command,
        'params'     => json_encode($request->params),
        'executed'   => false,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    return response()->json(['success' => true, 'id' => $id]);
});

// SSE untuk realtime dashboard
Route::get('/events', [SensorController::class, 'events']);
