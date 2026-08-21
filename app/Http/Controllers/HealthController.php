<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class HealthController extends Controller
{
    public function check(): JsonResponse
    {
        $start = microtime(true);

        try {
            DB::select('SELECT 1');
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'unhealthy',
                'database' => 'down',
                'error' => $e->getMessage(),
                'timestamp' => now()->toIso8601String(),
            ], 503);
        }

        $dbMs = round((microtime(true) - $start) * 1000, 2);

        return response()->json([
            'status' => 'healthy',
            'database' => 'up',
            'db_ms' => $dbMs,
            'timestamp' => now()->toIso8601String(),
        ])->header('Cache-Control', 'no-store, max-age=0');
    }
}
