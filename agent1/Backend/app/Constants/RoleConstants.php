<?php

declare(strict_types=1);

namespace App\Constants;

/**
 * RoleConstants
 *
 * Central definition of every role used in the DeskGuard authorisation
 * hierarchy. Roles flow downward: Super Admin has full access,
 * Company Head manages their organisation, Sub Head oversees
 * departments, and Employees can only view their own machine.
 */
class RoleConstants
{
    /** Highest role; unrestricted system access */
    public const string SUPER_ADMIN = 'Super Admin';

    /** Manages a single company entity and its users */
    public const string COMPANY_HEAD = 'Company Head';

    /** Middle-tier role with limited administrative scope */
    public const string SUB_HEAD = 'Sub Head';

    /** End-user role with access to own machine and alerts only */
    public const string EMPLOYEE = 'Employee';
}
