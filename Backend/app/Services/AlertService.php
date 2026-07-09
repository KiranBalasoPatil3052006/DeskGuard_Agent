<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\AlertSeverity;
use App\Enums\AlertStatus;
use App\Enums\EventType;
use App\Exceptions\AlertGenerationException;
use App\Models\Alert;
use App\Models\AlertRule;
use App\Models\Machine;
use App\Models\MachineCurrentStatus;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * Class AlertService
 *
 * Handles alert generation, evaluation, acknowledgement, and resolution.
 * Evaluates machine health data against configured alert rules and
 * dispatches notifications for critical conditions.
 *
 * @package App\Services
 */
class AlertService
{
    /**
     * The notification service for dispatching alert notifications.
     *
     * @var NotificationService
     */
    private NotificationService $notificationService;

    /**
     * The audit log service for recording alert events.
     *
     * @var AuditLogService
     */
    private AuditLogService $auditLogService;

    /**
     * AlertService constructor.
     *
     * @param NotificationService $notificationService
     * @param AuditLogService     $auditLogService
     */
    public function __construct(NotificationService $notificationService, AuditLogService $auditLogService)
    {
        $this->notificationService = $notificationService;
        $this->auditLogService = $auditLogService;
    }

    /**
     * Evaluate all enabled alert rules against the machine's current status.
     *
     * For each rule that matches the current status values, an alert is created
     * and a notification is sent to the assigned user.
     *
     * @param  Machine              $machine
     * @param  MachineCurrentStatus $status
     * @return void
     *
     * @throws AlertGenerationException
     */
    public function evaluateMachineAlerts(Machine $machine, MachineCurrentStatus $status): void
    {
        try {
            $rules = AlertRule::where('company_id', $machine->company_id)
                ->where('is_enabled', true)
               ->get();

            foreach ($rules as $rule) {
                try {
                    if ($this->evaluateRule($rule, $status)) {
                        $this->createAlertFromRule($rule, $machine, $status);
                    }
                } catch (Exception $e) {
                    Log::warning('AlertService::evaluateMachineAlerts - Rule evaluation failed', [
                        'rule_id'    => $rule->id,
                        'machine_id' => $machine->id,
                        'error'      => $e->getMessage(),
                    ]);
                }
            }
        } catch (Exception $e) {
            Log::error('AlertService::evaluateMachineAlerts - Failed to evaluate alerts', [
                'machine_id' => $machine->id,
                'error'      => $e->getMessage(),
            ]);
            throw new AlertGenerationException(
                'Failed to evaluate alert rules for machine.',
                500,
                ['machine_id' => $machine->id]
            );
        }
    }

    /**
     * Acknowledge an alert.
     *
     * @param  int  $alertId
     * @param  int  $userId
     * @return Alert
     */
    public function acknowledgeAlert(int $alertId, int $userId): Alert
    {
        try {
            $alert = Alert::findOrFail($alertId);
            $oldValues = $alert->toArray();

            $alert->update([
                'status'           => AlertStatus::Acknowledged->value,
                'acknowledged_by'  => $userId,
                'acknowledged_at'  => now(),
            ]);

            $alert->refresh();

            $this->auditLogService->log(
                EventType::Update->value,
                'Alert acknowledged: ' . $alert->title,
                $oldValues,
                $alert->toArray(),
                null,
                $alert->machine
            );

            Log::info('Alert acknowledged', [
                'alert_id'   => $alert->id,
                'user_id'    => $userId,
                'machine_id' => $alert->machine_id,
            ]);

            return $alert;
        } catch (Exception $e) {
            Log::error('AlertService::acknowledgeAlert - Failed to acknowledge alert', [
                'alert_id' => $alertId,
                'user_id'  => $userId,
                'error'    => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Resolve an alert with an optional resolution note.
     *
     * @param  int         $alertId
     * @param  int         $userId
     * @param  string|null $resolution
     * @return Alert
     */
    public function resolveAlert(int $alertId, int $userId, ?string $resolution = null): Alert
    {
        try {
            $alert = Alert::findOrFail($alertId);
            $oldValues = $alert->toArray();

            $metadata = $alert->metadata ?? [];
            if ($resolution !== null) {
                $metadata['resolution'] = $resolution;
            }

            $alert->update([
                'status'      => AlertStatus::Resolved->value,
                'resolved_by' => $userId,
                'resolved_at' => now(),
                'metadata'    => $metadata,
            ]);

            $alert->refresh();

            $this->auditLogService->log(
                EventType::Update->value,
                'Alert resolved: ' . $alert->title . ($resolution ? ' - ' . $resolution : ''),
                $oldValues,
                $alert->toArray(),
                null,
                $alert->machine
            );

            Log::info('Alert resolved', [
                'alert_id'   => $alert->id,
                'user_id'    => $userId,
                'resolution' => $resolution,
            ]);

            return $alert;
        } catch (Exception $e) {
            Log::error('AlertService::resolveAlert - Failed to resolve alert', [
                'alert_id' => $alertId,
                'user_id'  => $userId,
                'error'    => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Retrieve alerts for a company, optionally filtered by severity and status.
     *
     * @param  int         $companyId
     * @param  string|null $severity
     * @param  string|null $status
     * @return Collection<int, Alert>
     */
    public function getCompanyAlerts(int $companyId, ?string $severity = null, ?string $status = null): Collection
    {
        try {
            $query = Alert::with(['machine', 'acknowledgedBy', 'resolvedBy'])
                ->where('company_id', $companyId);

            if ($severity !== null) {
                $query->where('severity', $severity);
            }

            if ($status !== null) {
                $query->where('status', $status);
            }

            return $query->orderBy('created_at', 'desc')->get();
        } catch (Exception $e) {
            Log::error('AlertService::getCompanyAlerts - Failed to retrieve company alerts', [
                'company_id' => $companyId,
                'severity'   => $severity,
                'status'     => $status,
                'error'      => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Retrieve all alerts for a specific machine.
     *
     * @param  int  $machineId
     * @return Collection<int, Alert>
     */
    public function getMachineAlerts(int $machineId): Collection
    {
        try {
            return Alert::with(['acknowledgedBy', 'resolvedBy'])
                ->where('machine_id', $machineId)
                ->orderBy('created_at', 'desc')
                ->get();
        } catch (Exception $e) {
            Log::error('AlertService::getMachineAlerts - Failed to retrieve machine alerts', [
                'machine_id' => $machineId,
                'error'      => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Retrieve all critical alerts for a company.
     *
     * @param  int  $companyId
     * @return Collection<int, Alert>
     */
    public function getCriticalAlerts(int $companyId): Collection
    {
        try {
            return Alert::with(['machine', 'acknowledgedBy', 'resolvedBy'])
                ->where('company_id', $companyId)
                ->where('severity', AlertSeverity::Critical->value)
                ->whereIn('status', [AlertStatus::Open->value, AlertStatus::Acknowledged->value])
                ->orderBy('created_at', 'desc')
                ->get();
        } catch (Exception $e) {
            Log::error('AlertService::getCriticalAlerts - Failed to retrieve critical alerts', [
                'company_id' => $companyId,
                'error'      => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Evaluate a single rule against the current machine status.
     *
     * @param  AlertRule             $rule
     * @param  MachineCurrentStatus  $status
     * @return bool
     */
    private function evaluateRule(AlertRule $rule, MachineCurrentStatus $status): bool
    {
        $metricValue = $this->getMetricValue($rule->metric, $status);

        if ($metricValue === null) {
            return false;
        }

        $threshold = (float) ($rule->value ?? 0);

        return match ($rule->operator) {
            '>'  => $metricValue > $threshold,
            '>=' => $metricValue >= $threshold,
            '<'  => $metricValue < $threshold,
            '<=' => $metricValue <= $threshold,
            '==' => $metricValue == $threshold,
            '!=' => $metricValue != $threshold,
            default => false,
        };
    }

    /**
     * Extract the relevant metric value from the machine status.
     *
     * @param  string|null            $metric
     * @param  MachineCurrentStatus   $status
     * @return float|null
     */
    private function getMetricValue(?string $metric, MachineCurrentStatus $status): ?float
    {
        return match ($metric) {
            'cpu_percentage'  => $status->cpu_percentage !== null ? (float) $status->cpu_percentage : null,
            'cpu_temperature' => $status->cpu_temperature !== null ? (float) $status->cpu_temperature : null,
            'ram_percentage'  => $status->ram_percentage !== null ? (float) $status->ram_percentage : null,
            'disk_percentage' => $status->disk_percentage !== null ? (float) $status->disk_percentage : null,
            'battery_percentage' => $status->battery_percentage !== null ? (float) $status->battery_percentage : null,
            default => null,
        };
    }

    /**
     * Create an alert record from a triggered rule.
     *
     * @param  AlertRule             $rule
     * @param  Machine               $machine
     * @param  MachineCurrentStatus  $status
     * @return void
     */
    private function createAlertFromRule(AlertRule $rule, Machine $machine, MachineCurrentStatus $status): void
    {
        try {
            $severity = $rule->severity ?? AlertSeverity::Warning->value;

            $alert = Alert::create([
                'company_id'    => $machine->company_id,
                'machine_id'    => $machine->id,
                'alert_rule_id' => $rule->id,
                'severity'      => $severity,
                'title'         => $rule->name,
                'description'   => $this->buildAlertMessage($rule, $status),
                'metadata'      => [
                    'metric' => $rule->metric,
                    'operator' => $rule->operator,
                    'threshold' => $rule->value,
                    'current_value' => $this->getMetricValue($rule->metric, $status),
                ],
                'status'        => AlertStatus::Open->value,
            ]);

            $this->auditLogService->log(
                EventType::Create->value,
                'Alert generated: ' . $rule->name . ' for machine: ' . $machine->machine_uid,
                null,
                $alert->toArray(),
                null,
                $machine
            );

            $this->notificationService->sendAlertNotification($alert);

            // Send email notifications for critical and warning alerts
            if (in_array($severity, ['critical', 'warning'])) {
                $this->notificationService->sendEmailNotification($alert);
            }

            Log::info('Alert created from rule', [
                'alert_id'   => $alert->id,
                'rule_id'    => $rule->id,
                'machine_id' => $machine->id,
                'severity'   => $severity,
            ]);
        } catch (Exception $e) {
            Log::error('AlertService::createAlertFromRule - Failed to create alert', [
                'rule_id'    => $rule->id,
                'machine_id' => $machine->id,
                'error'      => $e->getMessage(),
            ]);
        }
    }

    /**
     * Build a human-readable alert message from the rule and current status.
     *
     * @param  AlertRule             $rule
     * @param  MachineCurrentStatus  $status
     * @return string
     */
    /**
     * Retrieve alert rules for a given company.
     *
     * @param  int  $companyId
     * @return Collection<int, AlertRule>
     */
    public function getAlertRules(int $companyId): Collection
    {
        try {
            return AlertRule::with(['company'])
                ->where('company_id', $companyId)
                ->orderBy('name', 'asc')
                ->get();
        } catch (Exception $e) {
            Log::error('AlertService::getAlertRules - Failed to retrieve alert rules', [
                'company_id' => $companyId,
                'error'      => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Update an alert rule with validated data.
     * Prevents changing the company_id through mass assignment.
     *
     * @param  int    $ruleId
     * @param  array  $data
     * @return AlertRule
     */
    public function updateAlertRule(int $ruleId, array $data): AlertRule
    {
        try {
            $rule = AlertRule::findOrFail($ruleId);

            // Prevent changing company_id via this method
            unset($data['company_id']);

            $rule->update($data);
            $rule->refresh();

            $this->auditLogService->log(
                EventType::Update->value,
                'Alert rule updated: ' . $rule->name,
                null,
                $rule->toArray(),
                null,
                null
            );

            Log::info('Alert rule updated', [
                'rule_id' => $rule->id,
                'data'    => $data,
            ]);

            return $rule;
        } catch (Exception $e) {
            Log::error('AlertService::updateAlertRule - Failed to update alert rule', [
                'rule_id' => $ruleId,
                'data'    => $data,
                'error'   => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Build a human-readable alert message from the rule and current status.
     *
     * @param  AlertRule             $rule
     * @param  MachineCurrentStatus  $status
     * @return string
     */
    private function buildAlertMessage(AlertRule $rule, MachineCurrentStatus $status): string
    {
        $currentValue = $this->getMetricValue($rule->metric, $status);
        $metricLabel = str_replace('_', ' ', ucfirst($rule->metric ?? 'metric'));

        return sprintf(
            '%s is %s %s (current: %s)',
            $metricLabel,
            $rule->operator,
            $rule->value,
            $currentValue !== null ? (string) $currentValue : 'N/A'
        );
    }
}
