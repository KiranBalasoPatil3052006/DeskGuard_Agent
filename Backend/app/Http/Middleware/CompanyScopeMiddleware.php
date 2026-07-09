<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Class CompanyScopeMiddleware
 *
 * Enforces company-level data isolation for all authenticated users except
 * Super Admins. It reads the `company_id` from the authenticated user and
 * stores it as a request attribute so downstream controllers and services
 * can scope their queries without re-extracting it.
 *
 * @package App\Http\Middleware
 */
class CompanyScopeMiddleware
{
    /**
     * Super Admin role name as defined by spatie/laravel-permission.
     *
     * @var string
     */
    private const SUPER_ADMIN_ROLE = 'Super Admin';

    /**
     * Handle an incoming request.
     *
     * If the user is authenticated and does not have the Super Admin role,
     * the company ID is read from the user record and set as a request
     * attribute (`company_id`). Super Admin requests pass through without
     * any company scoping so they can operate across all companies.
     *
     * @param  Request     $request
     * @param  Closure     $next
     * @return Response|JsonResponse
     */
    public function handle(Request $request, Closure $next): Response|JsonResponse
    {
        $user = $request->user();

        if ($user !== null && !$user->hasRole(self::SUPER_ADMIN_ROLE)) {
            $companyId = $user->getAttribute('company_id');

            if ($companyId !== null) {
                $request->attributes->set('company_id', (int) $companyId);
            }
        }

        return $next($request);
    }
}
