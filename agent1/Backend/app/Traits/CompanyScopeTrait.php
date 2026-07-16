<?php

declare(strict_types=1);

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * CompanyScopeTrait
 *
 * Eloquent trait that automatically scopes queries to the
 * currently authenticated user's company. Intended for use
 * on models that belong to a company (have a company_id column).
 * Applies a global scope on the boot() method.
 */
trait CompanyScopeTrait
{
    /**
     * Boot the trait and register a global scope that filters
     * records by the authenticated user's company_id.
     *
     * @return void
     */
    protected static function bootCompanyScopeTrait(): void
    {
        static::addGlobalScope('company', function (Builder $builder) {
            // Retrieve the authenticated user's company, or null
            $user = auth()->user();

            if ($user !== null && ! empty($user->company_id)) {
                $builder->where('company_id', $user->company_id);
            }
        });
    }
}
