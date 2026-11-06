<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class HealthController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $checks = [
            'database' => $this->checkDatabase(),
            'cache' => $this->checkCache(),
        ];
        $healthy = ! in_array(false, $checks, true);

        return response()->json([
            'status' => $healthy ? 'healthy' : 'unhealthy',
            'checks' => [
                'database' => $checks['database'] ? 'ok' : 'failed',
                'cache' => $checks['cache'] ? 'ok' : 'failed',
            ],
        ], $healthy ? 200 : 503);
    }

    private function checkDatabase(): bool
    {
        try {
            DB::connection()->select('select 1');

            return true;
        } catch (Throwable $exception) {
            Log::error('Health check database failed', [
                'who' => 'healthcheck',
                'action' => 'check_database',
                'reason' => 'database_unreachable',
                'exception' => $exception::class,
            ]);

            return false;
        }
    }

    private function checkCache(): bool
    {
        try {
            $key = 'healthcheck:cache';
            Cache::put($key, 'ok', 10);

            return Cache::get($key) === 'ok';
        } catch (Throwable $exception) {
            Log::error('Health check cache failed', [
                'who' => 'healthcheck',
                'action' => 'check_cache',
                'reason' => 'cache_unreachable',
                'exception' => $exception::class,
            ]);

            return false;
        }
    }
}
