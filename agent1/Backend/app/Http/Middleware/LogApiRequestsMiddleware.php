<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Carbon\Carbon;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

/**
 * Class LogApiRequestsMiddleware
 *
 * Middleware that logs every incoming API request together with its response
 * status and duration. Sensitive fields (passwords, tokens, secrets) are
 * automatically stripped from the logged payload. All entries are written
 * to the dedicated `audit_log` channel.
 *
 * @package App\Http\Middleware
 */
class LogApiRequestsMiddleware
{
    /**
     * List of input keys that should be redacted from the log payload.
     *
     * @var array<int, string>
     */
    private const SENSITIVE_FIELDS = [
        'password',
        'password_confirmation',
        'current_password',
        'secret',
        'token',
        'api_token',
        'access_token',
        'refresh_token',
    ];

    /**
     * Handle an incoming request.
     *
     * Records the start time, processes the request through the next
     * middleware, then writes a structured log entry containing the
     * HTTP method, URL, client IP, user agent, authenticated user ID,
     * sanitised payload, response status code, and the total duration
     * in milliseconds.
     *
     * @param  Request     $request
     * @param  Closure     $next
     * @return Response|JsonResponse
     */
    /**
     * Agent health/heartbeat endpoints that should not generate audit log entries.
     * These run every 2-5 minutes per machine and would flood the audit log.
     */
    private const SKIP_LOGGING_PATTERNS = [
        '/api/v1/health',
        '/api/v1/agent/health',
        '/api/v1/agent/heartbeat',
    ];

    public function handle(Request $request, Closure $next): Response|JsonResponse
    {
        $startTime = Carbon::now();

        /** @var Response|JsonResponse $response */
        $response = $next($request);

        $duration = Carbon::now()->diffInMilliseconds($startTime);

        // Skip logging for high-frequency agent endpoints to avoid log flooding
        $url = $request->path();
        foreach (self::SKIP_LOGGING_PATTERNS as $pattern) {
            if (str_starts_with($url, ltrim($pattern, '/'))) {
                return $response;
            }
        }

        // Only log if request took longer than 100ms to avoid noise
        if ($duration >= 100) {
            $this->logRequest($request, $response, $duration);
        }

        return $response;
    }

    /**
     * Write the API request log entry to the audit_log channel.
     *
     * @param  Request              $request
     * @param  Response|JsonResponse $response
     * @param  float                $duration  Duration in milliseconds
     * @return void
     */
    private function logRequest(Request $request, Response|JsonResponse $response, float $duration): void
    {
        $user = $request->user();

        $payload = $request->except(self::SENSITIVE_FIELDS);

        $context = [
            'method'         => $request->method(),
            'url'            => $request->fullUrl(),
            'ip'             => $request->ip(),
            'user_agent'     => $request->userAgent(),
            'user_id'        => $user?->id,
            'company_id'     => $request->attributes->get('company_id'),
            'payload'        => $payload,
            'response_status'=> $response->getStatusCode(),
            'duration_ms'    => round($duration, 2),
            'timestamp'      => now()->toIso8601String(),
        ];

        Log::channel('audit_log')->info('API Request', $context);
    }
}
