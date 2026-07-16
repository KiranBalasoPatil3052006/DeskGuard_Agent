<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * GzipMiddleware
 *
 * Compresses API responses using Gzip encoding when the client
 * advertises support via the Accept-Encoding header. Reduces
 * payload transfer size by 60-80% for large JSON responses.
 */
class GzipMiddleware
{
    public function handle(Request $request, Closure $next): Response|JsonResponse
    {
        $response = $next($request);

        if (!$response instanceof JsonResponse || $request->method() === 'GET') {
            // Only compress GET responses that are likely large JSON payloads
            // POST/PUT/DELETE responses are typically small status messages
            if (!$response instanceof JsonResponse) {
                return $response;
            }
        }

        $content = $response->getContent();
        if (strlen($content) < 1024) {
            return $response;
        }

        $acceptEncoding = $request->header('Accept-Encoding');

        if ($acceptEncoding && str_contains($acceptEncoding, 'gzip')) {
            $compressed = gzencode($content, 6);

            $response->setContent($compressed);
            $response->headers->set('Content-Encoding', 'gzip');
            $response->headers->set('Content-Length', (string) strlen($compressed));
            $response->headers->set('Vary', 'Accept-Encoding');
        } elseif ($acceptEncoding && str_contains($acceptEncoding, 'br')) {
            // Brotli is preferred but requires the brotli extension
            if (function_exists('brotli_compress')) {
                $compressed = brotli_compress($content, 4);

                $response->setContent($compressed);
                $response->headers->set('Content-Encoding', 'br');
                $response->headers->set('Content-Length', (string) strlen($compressed));
                $response->headers->set('Vary', 'Accept-Encoding');
            }
        }

        return $response;
    }
}
