<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Throwable;

class HealthController extends Controller
{
    /**
     * GET /api/health
     *
     * Simple liveness/readiness probe for the frontend teams (Next.js + Expo)
     * and for infra monitoring. Always returns 200 as long as the Laravel
     * process itself is up; the individual `database` / `redis` fields tell
     * the caller whether each dependency is actually reachable.
     */
    public function __invoke(): JsonResponse
    {
        $database = $this->checkDatabase();
        $redis = $this->checkRedis();

        return response()->json([
            'status' => 'ok',
            'timestamp' => now()->toIso8601String(),
            'services' => [
                'database' => $database,
                'redis' => $redis,
            ],
        ], 200);
    }

    private function checkDatabase(): array
    {
        $start = microtime(true);

        try {
            DB::connection()->getPdo();
            DB::select('select 1');

            return [
                'connected' => true,
                'driver' => DB::connection()->getDriverName(),
                'latency_ms' => $this->elapsedMs($start),
            ];
        } catch (Throwable $e) {
            return [
                'connected' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    private function checkRedis(): array
    {
        $start = microtime(true);

        try {
            $pong = Redis::connection()->ping();

            return [
                'connected' => true,
                'ping' => (string) $pong,
                'latency_ms' => $this->elapsedMs($start),
            ];
        } catch (Throwable $e) {
            return [
                'connected' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    private function elapsedMs(float $start): float
    {
        return round((microtime(true) - $start) * 1000, 2);
    }
}
