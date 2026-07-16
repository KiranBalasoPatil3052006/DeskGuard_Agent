<?php

declare(strict_types=1);

namespace App\Services\PayloadProcessors;

use App\Models\Machine;
use App\Models\Alert;
use App\Models\MachineCurrentStatus;
use Illuminate\Support\Facades\Log;

class AlertProcessor
{
    public function process(Machine $machine, array $payload): void
    {
        try {
            $cpu     = $payload['cpu'] ?? [];
            $memory  = $payload['memory'] ?? [];
            $disks   = $payload['disks'] ?? [];
            $antivirus = $payload['antivirus'] ?? [];
            $firewall  = $payload['firewall'] ?? [];

            $cpuUsage    = $cpu['usagePercentage'] ?? $cpu['usage_percentage'] ?? null;
            $memUsage    = $memory['usagePercentage'] ?? $memory['usage_percentage'] ?? null;
            $avEnabled   = $antivirus['isRealTimeProtectionEnabled'] ?? $antivirus['is_real_time_protection_enabled'] ?? null;
            $fwEnabled   = $firewall['isEnabled'] ?? $firewall['is_enabled'] ?? $firewall['status'] ?? null;

            if ($cpuUsage !== null && $cpuUsage > 90) {
                $this->createAlert($machine, 'critical', 'High CPU Usage', "CPU usage is {$cpuUsage}% on {$machine->hostname}.");
            }

            if ($memUsage !== null && $memUsage > 90) {
                $this->createAlert($machine, 'critical', 'High Memory Usage', "Memory usage is {$memUsage}% on {$machine->hostname}.");
            }

            foreach ($disks as $disk) {
                $usagePercent = $disk['usagePercentage'] ?? $disk['usage_percentage'] ?? null;
                $driveName    = $disk['driveName'] ?? $disk['drive_letter'] ?? 'Unknown';

                if ($usagePercent !== null && $usagePercent > 95) {
                    $this->createAlert($machine, 'warning', 'Disk Almost Full', "Drive {$driveName} is {$usagePercent}% full on {$machine->hostname}.");
                }
            }

            if ($avEnabled !== null && !$avEnabled) {
                $this->createAlert($machine, 'critical', 'Antivirus Disabled', "Antivirus real-time protection is disabled on {$machine->hostname}.");
            }

            if ($fwEnabled !== null && empty($fwEnabled)) {
                $this->createAlert($machine, 'warning', 'Firewall Disabled', "Windows Firewall is disabled on {$machine->hostname}.");
            }

            Log::debug('AlertProcessor: Alerts evaluated', [
                'machine_id' => $machine->id,
            ]);
        } catch (\Throwable $e) {
            Log::error('AlertProcessor::process - Failed', [
                'machine_id' => $machine->id,
                'error'      => $e->getMessage(),
                'trace'      => $e->getTraceAsString(),
            ]);
        }
    }

    private function createAlert(Machine $machine, string $severity, string $title, string $description): void
    {
        try {
            $existing = Alert::where('machine_id', $machine->id)
                ->where('title', $title)
                ->where('status', 'open')
                ->first();

            if ($existing) {
                return;
            }

            Alert::create([
                'machine_id'  => $machine->id,
                'company_id'  => $machine->company_id,
                'severity'    => $severity,
                'title'       => $title,
                'description' => $description,
                'status'      => 'open',
            ]);

            Log::info("AlertProcessor: Alert created - {$title}", [
                'machine_id' => $machine->id,
                'severity'   => $severity,
            ]);
        } catch (\Throwable $e) {
            Log::error('AlertProcessor::createAlert - Failed', [
                'machine_id' => $machine->id,
                'title'      => $title,
                'error'      => $e->getMessage(),
            ]);
        }
    }
}
