<?php

declare(strict_types=1);

namespace App\Exceptions;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Throwable;

/**
 * Class Handler
 *
 * Custom exception handler that replaces Laravel's default handler. It
 * enriches every exception log with full diagnostic context (timestamp,
 * file, method, line, authenticated user, machine ID, request payload,
 * company ID) and returns a standardised JSON structure for API consumers.
 *
 * In local / debug environments the JSON response also includes the
 * exception message, file, line, and stack trace for easier development.
 *
 * @package App\Exceptions
 */
class Handler extends ExceptionHandler
{
    /**
     * A list of exception types that should not be reported.
     *
     * @var array<int, class-string<Throwable>>
     */
    protected $dontReport = [];

    /**
     * A list of inputs that are never flashed for validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks.
     *
     * This method wires up custom reporting and rendering logic for every
     * exception type that may occur within the application.
     *
     * @return void
     */
    public function register(): void
    {
        /**
         * -------------------------------------------------------------------
         *  Reporting callback
         * -------------------------------------------------------------------
         * Logs every exception with full context. Returns `false` to prevent
         * the default Laravel log from being written.
         */
        $this->reportable(function (Throwable $e): bool {
            $this->logException($e);

            return false;
        });

        /**
         * -------------------------------------------------------------------
         *  Rendering callback
         * -------------------------------------------------------------------
         * Intercepts every exception for API requests and returns a
         * standardised JSON envelope.
         */
        $this->renderable(function (Throwable $e, Request $request): JsonResponse {
            return $this->renderJsonResponse($e, $request);
        });
    }

    // -----------------------------------------------------------------------
    //  Context builders
    // -----------------------------------------------------------------------

    /**
     * Extract the calling method name from the exception stack trace.
     *
     * @param  Throwable $e
     * @return string
     */
    private function getMethodFromTrace(Throwable $e): string
    {
        $trace = $e->getTrace();

        if (isset($trace[0]['function'])) {
            return $trace[0]['function'];
        }

        if (isset($trace[1]['function'])) {
            return $trace[1]['function'];
        }

        return 'unknown';
    }

    /**
     * Gather the full logging context for the given exception.
     *
     * @param  Throwable   $e
     * @param  Request|null $request
     * @return array<string, mixed>
     */
    private function buildLogContext(Throwable $e, ?Request $request = null): array
    {
        $context = [
            'timestamp'  => now()->toIso8601String(),
            'message'    => $e->getMessage(),
            'file'       => $e->getFile(),
            'method'     => $this->getMethodFromTrace($e),
            'line'       => $e->getLine(),
            'stacktrace' => $e->getTraceAsString(),
        ];

        // Merge extra context from custom exceptions
        if ($e instanceof BaseException) {
            $context['exception_context'] = $e->getContext();
        }

        // Request-level context
        if ($request !== null) {
            $payload = $request->except($this->dontFlash);
            $user    = $request->user();

            $context['url']     = $request->fullUrl();
            $context['method']  = $request->method();
            $context['ip']      = $request->ip();
            $context['user_id'] = $user?->id;
            $context['payload'] = $payload;

            // Machine UID (may be in request body, attribute, or route)
            $machineUid = $request->input('machine_uid')
                ?? $request->input('machine_id')
                ?? $request->attributes->get('machine_uid');

            if ($machineUid !== null) {
                $context['machine_uid'] = $machineUid;
            }

            // Company ID from the authenticated user
            if ($user !== null && method_exists($user, 'getAttribute')) {
                $companyId = $user->getAttribute('company_id');
                if ($companyId !== null) {
                    $context['company_id'] = (int) $companyId;
                }
            }

            // Company ID from request attribute (set by CompanyScopeMiddleware)
            $requestCompanyId = $request->attributes->get('company_id');
            if ($requestCompanyId !== null) {
                $context['company_id'] = (int) $requestCompanyId;
            }
        }

        return $context;
    }

    /**
     * Determine the appropriate log channel(s) for the given exception.
     *
     * @param  Throwable $e
     * @return string
     */
    private function resolveLogChannel(Throwable $e): string
    {
        return match (true) {
            $e instanceof ValidationException,
            $e instanceof AuthenticationException,
            $e instanceof AuthorizationException,
            $e instanceof TooManyRequestsHttpException => 'stack',

            $e instanceof MachineRegistrationException,
            $e instanceof AlertGenerationException,
            $e instanceof InventorySyncException,
            $e instanceof MachineNotFoundException,
            $e instanceof UnauthorizedActionException => 'daily',

            $e instanceof ModelNotFoundException => 'daily',

            default => 'daily',
        };
    }

    /**
     * Resolve the log severity level for the given exception.
     *
     * @param  Throwable $e
     * @return string
     */
    private function resolveLogLevel(Throwable $e): string
    {
        return match (true) {
            $e instanceof ValidationException,
            $e instanceof AuthenticationException,
            $e instanceof AuthorizationException,
            $e instanceof TooManyRequestsHttpException,
            $e instanceof MachineNotFoundException => 'warning',

            $e instanceof MachineRegistrationException,
            $e instanceof AlertGenerationException,
            $e instanceof InventorySyncException,
            $e instanceof UnauthorizedActionException => 'error',

            default => 'critical',
        };
    }

    // -----------------------------------------------------------------------
    //  Logging
    // -----------------------------------------------------------------------

    /**
     * Write the exception to the log with full contextual data.
     *
     * @param Throwable $e
     * @return void
     */
    private function logException(Throwable $e): void
    {
        $request = request();
        $context = $this->buildLogContext($e, $request);
        $channel = $this->resolveLogChannel($e);
        $level   = $this->resolveLogLevel($e);

        Log::channel($channel)->log($level, $e->getMessage(), $context);
    }

    // -----------------------------------------------------------------------
    //  JSON rendering
    // -----------------------------------------------------------------------

    /**
     * Render the given exception as a standardised JSON response.
     *
     * @param  Throwable $e
     * @param  Request   $request
     * @return JsonResponse
     */
    private function renderJsonResponse(Throwable $e, Request $request): JsonResponse
    {
        $statusCode = $this->resolveStatusCode($e);

        $response = [
            'success' => false,
            'message' => $this->isRunningInDebugMode()
                ? $e->getMessage()
                : 'Unable to process request.',
        ];

        // In debug / local environments attach detailed diagnostics
        if ($this->isRunningInDebugMode()) {
            $response['debug'] = [
                'file'  => $e->getFile(),
                'line'  => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ];
        }

        // Attach validation errors when applicable
        if ($e instanceof ValidationException) {
            $response['errors'] = $e->errors();
        }

        return new JsonResponse($response, $statusCode);
    }

    /**
     * Resolve the HTTP status code that should be returned for the exception.
     *
     * @param  Throwable $e
     * @return int
     */
    private function resolveStatusCode(Throwable $e): int
    {
        return match (true) {
            $e instanceof ValidationException       => 422,
            $e instanceof AuthenticationException   => 401,
            $e instanceof AuthorizationException    => 403,
            $e instanceof ModelNotFoundException   => 404,
            $e instanceof TooManyRequestsHttpException => 429,
            $e instanceof MachineRegistrationException => 422,
            $e instanceof MachineNotFoundException => 404,
            $e instanceof UnauthorizedActionException  => 403,
            $e instanceof BaseException             => $e->getCode() ?: 500,
            default                                 => 500,
        };
    }

    /**
     * Determine whether the application is running in a debug / local
     * environment where detailed error information may be exposed.
     *
     * @return bool
     */
    private function isRunningInDebugMode(): bool
    {
        return config('app.debug', false) === true;
    }
}
