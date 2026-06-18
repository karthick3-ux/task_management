<?php
// app/Http/Middleware/PerformanceMonitor.php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class PerformanceMonitor
{
    public function handle(Request $request, Closure $next): Response
    {
        $start = microtime(true);
        $memory = memory_get_usage();
        
        $response = $next($request);
        
        $executionTime = microtime(true) - $start;
        $memoryUsed = memory_get_usage() - $memory;
        
        // Log slow requests (> 2 seconds)
        if ($executionTime > 3) {
            Log::warning('Slow request detected', [
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'execution_time' => round($executionTime, 2) . 's',
                'memory_used' => round($memoryUsed / 1024 / 1024, 2) . 'MB',
                'user_id' => auth()->id(),
            ]);
        }
        
        return $response;
    }
}