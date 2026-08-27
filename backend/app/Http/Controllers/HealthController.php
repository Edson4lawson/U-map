<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

class HealthController extends Controller
{
    /**
     * Basic health check endpoint.
     */
    public function health(): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'status' => 'healthy',
            'timestamp' => now()->toIso8601String(),
            'version' => config('app.version', '1.0.0'),
        ]);
    }

    /**
     * Detailed health check with dependencies.
     */
    public function healthDetailed(): \Illuminate\Http\JsonResponse
    {
        $health = [
            'status' => 'healthy',
            'timestamp' => now()->toIso8601String(),
            'version' => config('app.version', '1.0.0'),
            'checks' => [
                'database' => $this->checkDatabase(),
                'redis' => $this->checkRedis(),
                'cache' => $this->checkCache(),
                'queue' => $this->checkQueue(),
                'storage' => $this->checkStorage(),
            ],
        ];

        // Determine overall status
        $allHealthy = collect($health['checks'])->every(fn($check) => $check['status'] === 'ok');
        $health['status'] = $allHealthy ? 'healthy' : 'degraded';

        $statusCode = $allHealthy ? 200 : 503;

        return response()->json($health, $statusCode);
    }

    /**
     * Check database connection.
     */
    protected function checkDatabase(): array
    {
        try {
            DB::connection()->getPdo();
            return [
                'status' => 'ok',
                'message' => 'Database connection successful',
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Database connection failed',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check Redis connection.
     */
    protected function checkRedis(): array
    {
        try {
            Redis::ping();
            return [
                'status' => 'ok',
                'message' => 'Redis connection successful',
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Redis connection failed',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check cache functionality.
     */
    protected function checkCache(): array
    {
        try {
            $testKey = 'health_check_' . time();
            Cache::put($testKey, 'test', 10);
            $value = Cache::get($testKey);
            Cache::forget($testKey);

            if ($value === 'test') {
                return [
                    'status' => 'ok',
                    'message' => 'Cache functionality working',
                ];
            }

            return [
                'status' => 'error',
                'message' => 'Cache read/write failed',
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Cache check failed',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check queue connection.
     */
    protected function checkQueue(): array
    {
        try {
            $size = Queue::size();
            return [
                'status' => 'ok',
                'message' => 'Queue connection successful',
                'pending_jobs' => $size,
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Queue connection failed',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check storage functionality.
     */
    protected function checkStorage(): array
    {
        try {
            $testFile = 'health_check_' . time() . '.txt';
            Storage::put($testFile, 'test');
            $exists = Storage::exists($testFile);
            Storage::delete($testFile);

            if ($exists) {
                return [
                    'status' => 'ok',
                    'message' => 'Storage functionality working',
                ];
            }

            return [
                'status' => 'error',
                'message' => 'Storage write/read failed',
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Storage check failed',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Metrics endpoint for monitoring.
     */
    public function metrics(): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'timestamp' => now()->toIso8601String(),
            'metrics' => [
                'queue_size' => Queue::size(),
                'queue_failed' => Queue::size('failed'),
                'cache_hits' => Cache::get('metrics:cache_hits', 0),
                'cache_misses' => Cache::get('metrics:cache_misses', 0),
                'active_users' => $this->getActiveUsers(),
                'messages_sent_today' => $this->getMessagesSentToday(),
            ],
        ]);
    }

    /**
     * Get active users count (last 5 minutes).
     */
    protected function getActiveUsers(): int
    {
        return \App\Models\Message::where('created_at', '>=', now()->subMinutes(5))
            ->distinct('sender_id')
            ->count('sender_id');
    }

    /**
     * Get messages sent today.
     */
    protected function getMessagesSentToday(): int
    {
        return \App\Models\Message::whereDate('created_at', today())->count();
    }
}
