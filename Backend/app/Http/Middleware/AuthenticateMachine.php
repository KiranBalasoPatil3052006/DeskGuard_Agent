<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Machine;
use App\Models\MachineToken;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Middleware AuthenticateMachine
 *
 * Authenticates agent (machine) requests using a Bearer token stored in
 * the machine_tokens table. The token is SHA-256 hashed before lookup.
 * Validates token expiration and attaches the Machine to the request
 * attributes for downstream consumption.
 */
class AuthenticateMachine
{
    /**
     * Handle an incoming agent request.
     *
     * Extracts the Bearer token from the Authorization header, hashes it,
     * looks it up in machine_tokens, and attaches the Machine model to
     * the request attributes if valid.
     *
     * @param  Request  $request
     * @param  Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next): mixed
    {
        $bearerToken = $request->bearerToken();

        if (empty($bearerToken)) {
            Log::warning('AuthenticateMachine - Missing bearer token', [
                'ip'  => $request->ip(),
                'url' => $request->fullUrl(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Authentication token is missing.',
            ], 401);
        }

        $hashedToken = hash('sha256', $bearerToken);

        $machineToken = MachineToken::where('token', $hashedToken)->first();

        if (!$machineToken) {
            Log::warning('AuthenticateMachine - Invalid machine token', [
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired machine token.',
            ], 401);
        }

        if ($machineToken->expires_at && $machineToken->expires_at->isPast()) {
            Log::warning('AuthenticateMachine - Expired machine token', [
                'machine_id' => $machineToken->machine_id,
                'expires_at' => $machineToken->expires_at->toIso8601String(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Machine token has expired. Please re-register.',
            ], 401);
        }

        $machine = Machine::find($machineToken->machine_id);

        if (!$machine || !$machine->is_active) {
            Log::warning('AuthenticateMachine - Machine not found or inactive', [
                'machine_id' => $machineToken->machine_id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Machine is not registered or is inactive.',
            ], 403);
        }

        $machineToken->update(['last_used_at' => now()]);

        $request->attributes->set('machine', $machine);
        $request->attributes->set('machine_id', $machine->id);
        $request->attributes->set('company_id', $machine->company_id);

        return $next($request);
    }
}
