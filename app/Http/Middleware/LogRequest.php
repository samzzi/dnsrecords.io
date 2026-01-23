<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class LogRequest
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        Log::info('HTTP request', [
            'method' => $request->method(),
            'path' => $request->path(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'status' => $response->getStatusCode(),
            'input' => $this->truncateInput($request->except(['password', 'password_confirmation'])),
        ]);

        return $response;
    }

    protected function truncateInput(array $input): array
    {
        foreach ($input as $key => $value) {
            if (is_string($value) && strlen($value) > 500) {
                $input[$key] = substr($value, 0, 500) . '...';
            }
        }

        return $input;
    }
}
