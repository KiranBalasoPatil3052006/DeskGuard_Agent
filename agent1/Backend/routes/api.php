<?php

/**
 * DeskGuard API Routes (v1)
 * All agent and dashboard API endpoints are defined here.
 *
 * Route groups:
 *   - Public: Agent registration, login, OTP
 *   - auth:sanctum: All dashboard/frontend routes
 *   - machine.auth: Authenticated agent data submission
 *   - Public agent (dev): Unauthenticated agent routes for local testing
 */

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CompanyController;
use App\Http\Controllers\Api\V1\UserController;
use App\Http\Controllers\Api\V1\AgentController;
use App\Http\Controllers\Api\V1\MachineController;
use App\Http\Controllers\Api\V1\AlertController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\ReportController;
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\AuditLogController;
use App\Http\Controllers\Api\V1\PermissionController;
use App\Http\Controllers\Api\V1\AgentAuthController;
use App\Http\Controllers\Api\V1\DeviceEventController;
use App\Http\Controllers\Api\V1\AdminSearchController;
use App\Http\Controllers\Api\V1\TelemetryController;
use App\Http\Controllers\Api\V1\AgentHealthController;
use App\Http\Controllers\Api\V1\AgentInventoryController;
use App\Http\Controllers\Api\V1\SettingsController;
use App\Http\Controllers\Api\V1\ChangeController;
use App\Http\Controllers\Api\V1\BaselineController;
use App\Http\Controllers\Api\V1\AgentChangeController;

/*
|--------------------------------------------------------------------------
| Public Routes (no authentication)
|--------------------------------------------------------------------------
*/

// Agent registration (no auth — uses activation token)
Route::post('/v1/agent/register', [AgentController::class, 'register']);

// User login
Route::post('/v1/auth/login', [AuthController::class, 'login']);

// Mobile OTP authentication (no auth)
Route::post('/v1/agent/request-otp', [AgentAuthController::class, 'requestOtp']);
Route::post('/v1/agent/verify-otp', [AgentAuthController::class, 'verifyOtp']);

/*
|--------------------------------------------------------------------------
| Authenticated Routes — Sanctum (User)
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->prefix('v1')->group(function () {

    // Auth
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/refresh', [AuthController::class, 'refreshToken']);

    // Companies (Super Admin only for full CRUD)
    Route::apiResource('/companies', CompanyController::class);
    Route::post('/companies/{id}/assign-head', [CompanyController::class, 'assignHead']);

    // Users
    Route::apiResource('/users', UserController::class);
    Route::post('/users/{id}/assign-role', [UserController::class, 'assignRole']);

    // Machines
    Route::get('/machines', [MachineController::class, 'index']);
    Route::get('/machines/online', [MachineController::class, 'online']);
    Route::get('/machines/offline', [MachineController::class, 'offline']);
    Route::get('/machines/{id}', [MachineController::class, 'show']);
    Route::get('/machines/{id}/status', [MachineController::class, 'status']);
    Route::get('/machines/{id}/history', [MachineController::class, 'history']);
    Route::post('/machines/{id}/assign', [MachineController::class, 'assign']);
    Route::post('/machines/{id}/unassign', [MachineController::class, 'unassign']);

    // Machine Sub-Resources — detailed data for the Machine Details page
    Route::get('/machines/{id}/inventory', [MachineController::class, 'inventory']);
    Route::get('/machines/{id}/security', [MachineController::class, 'security']);
    Route::get('/machines/{id}/devices', [MachineController::class, 'devices']);
    Route::get('/machines/{id}/device-issues', [MachineController::class, 'deviceIssues']);
    Route::get('/machines/{id}/alerts', [MachineController::class, 'machineAlerts']);
    Route::get('/machines/{id}/timeline', [MachineController::class, 'timeline']);
    Route::get('/machines/{id}/processes', [MachineController::class, 'processes']);
    Route::get('/machines/{id}/services', [MachineController::class, 'services']);
    Route::get('/machines/{id}/startup-programs', [MachineController::class, 'startupPrograms']);
    Route::get('/machines/{id}/event-logs', [MachineController::class, 'eventLogs']);
    Route::get('/machines/{id}/network', [MachineController::class, 'networkAdapters']);

    // Alerts
    Route::get('/alerts', [AlertController::class, 'index']);
    Route::get('/alerts/critical', [AlertController::class, 'critical']);
    Route::get('/alerts/{id}', [AlertController::class, 'show']);
    Route::post('/alerts/{id}/acknowledge', [AlertController::class, 'acknowledge']);
    Route::post('/alerts/{id}/resolve', [AlertController::class, 'resolve']);
    Route::get('/alert-rules', [AlertController::class, 'rules']);
    Route::put('/alert-rules/{id}', [AlertController::class, 'updateRule']);

    // Dashboard
    Route::get('/dashboard/company', [DashboardController::class, 'company']);
    Route::get('/dashboard/employee', [DashboardController::class, 'employee']);
    Route::get('/dashboard/charts/cpu', [DashboardController::class, 'cpuTrend']);
    Route::get('/dashboard/charts/ram', [DashboardController::class, 'ramTrend']);
    Route::get('/dashboard/charts/alerts', [DashboardController::class, 'alertTrend']);

    // Reports
    Route::get('/reports', [ReportController::class, 'index']);
    Route::post('/reports/generate', [ReportController::class, 'generate']);
    Route::get('/reports/{id}/download', [ReportController::class, 'download']);
    Route::delete('/reports/{id}', [ReportController::class, 'destroy']);

    // Notifications
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markRead']);
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead']);
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount']);

    // Device Events
    Route::get('/device-events', [DeviceEventController::class, 'index']);

    // Admin Search
    Route::get('/admin/search', [AdminSearchController::class, 'search']);
    Route::get('/admin/users/{id}', [AdminSearchController::class, 'userDetail']);

    // Audit Logs
    Route::get('/audit-logs', [AuditLogController::class, 'index']);

    // Permissions
    Route::get('/roles', [PermissionController::class, 'roles']);
    Route::get('/permissions', [PermissionController::class, 'permissions']);
    Route::post('/roles/{roleId}/permissions', [PermissionController::class, 'assignPermission']);
    Route::get('/users/{userId}/permissions', [PermissionController::class, 'userPermissions']);

    // Real-time — Server-Sent Events for alert/notification streaming
    Route::get('/sse/alerts', [\App\Http\Controllers\Api\V1\SSEController::class, 'alerts']);
    Route::get('/sse/notifications', [\App\Http\Controllers\Api\V1\SSEController::class, 'notifications']);

    // Settings — Email recipients and notification preferences
    Route::get('/settings/notifications', [SettingsController::class, 'getNotificationSettings']);
    Route::get('/settings/email-recipients', [SettingsController::class, 'listEmailRecipients']);
    Route::post('/settings/email-recipients', [SettingsController::class, 'addEmailRecipient']);
    Route::put('/settings/email-recipients/{id}', [SettingsController::class, 'updateEmailRecipient']);
    Route::delete('/settings/email-recipients/{id}', [SettingsController::class, 'removeEmailRecipient']);

    // Change Detection
    Route::get('/changes', [ChangeController::class, 'index']);
    Route::get('/changes/recent', [ChangeController::class, 'recentChanges']);
    Route::get('/changes/summary', [ChangeController::class, 'summary']);
    Route::get('/machines/{id}/changes', [ChangeController::class, 'machineChanges']);
    Route::put('/changes/{id}/status', [ChangeController::class, 'updateStatus']);

    // Baselines
    Route::get('/machines/{id}/baselines/hardware', [BaselineController::class, 'hardwareBaseline']);
    Route::get('/machines/{id}/baselines/software', [BaselineController::class, 'softwareBaseline']);
    Route::get('/machines/{id}/baselines/security', [BaselineController::class, 'securityBaseline']);
    Route::get('/machines/{id}/baselines/configuration', [BaselineController::class, 'configurationBaseline']);
    Route::post('/baselines/resync-hardware', [BaselineController::class, 'resyncHardware']);
    Route::post('/baselines/resync-software', [BaselineController::class, 'resyncSoftware']);
    Route::post('/baselines/resync-security', [BaselineController::class, 'resyncSecurity']);
    Route::post('/baselines/resync-configuration', [BaselineController::class, 'resyncConfiguration']);

    // Approval Workflow
    Route::post('/changes/approve', [BaselineController::class, 'approveChange']);
});

/*
|--------------------------------------------------------------------------
| Agent Routes — Authenticated via Machine Token (custom guard)
|--------------------------------------------------------------------------
*/

// Agent endpoints use machine token authentication (custom middleware)
Route::middleware('machine.auth')->prefix('v1/agent')->group(function () {
    Route::post('/heartbeat', [AgentController::class, 'heartbeat']);
    Route::post('/health', [AgentController::class, 'health']);
    Route::post('/inventory', [AgentController::class, 'inventory']);
    Route::post('/security', [AgentController::class, 'security']);
    Route::post('/telemetry', TelemetryController::class);
});

// C# Agent endpoints — the agent sends to different paths than existing backend routes.
// These are public for local dev testing; protect with machine.auth in production.
Route::prefix('v1')->group(function () {
    Route::post('/health', AgentHealthController::class);
    Route::post('/inventory/hardware', [AgentInventoryController::class, 'hardware']);
    Route::post('/inventory/software', [AgentInventoryController::class, 'software']);
});

// Agent endpoints — machine identified by machine_uid in body (no auth token needed)
// Device events and sync identify the machine via 'machine_uid' field in the payload.
Route::prefix('v1/agent')->group(function () {
    Route::post('/device-events', [DeviceEventController::class, 'store']);
    Route::post('/device-sync', [DeviceEventController::class, 'sync']);
    Route::post('/changes', AgentChangeController::class);
});
