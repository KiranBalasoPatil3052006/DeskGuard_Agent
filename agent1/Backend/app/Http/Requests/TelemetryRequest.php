<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TelemetryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'machineId'               => 'required|string|max:255',
            'computerName'            => 'nullable|string|max:255',
            'collectedAt'             => 'nullable|string|max:255',

            'systemInfo'              => 'nullable|array',
            'systemInfo.computerName' => 'nullable|string|max:255',
            'systemInfo.operatingSystem' => 'nullable|string|max:255',
            'systemInfo.osVersion'    => 'nullable|string|max:255',

            'cpu'                                => 'nullable|array',
            'cpu.usagePercentage'                => 'nullable|numeric|between:0,100',
            'cpu.temperatureCelsius'             => 'nullable|numeric',
            'cpu.processorName'                  => 'nullable|string|max:255',

            'memory'                         => 'nullable|array',
            'memory.usagePercentage'         => 'nullable|numeric|between:0,100',
            'memory.totalBytes'              => 'nullable|integer|min:0',
            'memory.usedBytes'               => 'nullable|integer|min:0',
            'memory.availableBytes'          => 'nullable|integer|min:0',

            'disks'                        => 'nullable|array',
            'disks.*.driveName'            => 'nullable|string|max:10',
            'disks.*.totalBytes'           => 'nullable|integer|min:0',
            'disks.*.usedBytes'            => 'nullable|integer|min:0',
            'disks.*.freeBytes'            => 'nullable|integer|min:0',
            'disks.*.usagePercentage'      => 'nullable|numeric|between:0,100',

            'battery'                          => 'nullable|array',
            'battery.percentage'               => 'nullable|numeric|between:0,100',
            'battery.chargingStatus'           => 'nullable|boolean',

            'networkAdapters'                        => 'nullable|array',
            'networkAdapters.*.adapterName'          => 'nullable|string|max:255',
            'networkAdapters.*.macAddress'           => 'nullable|string|max:17',
            'networkAdapters.*.ipAddress'            => 'nullable|string|max:45',

            'hardwareInventory'                        => 'nullable|array',
            'hardwareInventory.*.hardwareType'        => 'nullable|string|max:100',
            'hardwareInventory.*.manufacturer'        => 'nullable|string|max:255',
            'hardwareInventory.*.model'               => 'nullable|string|max:255',
            'hardwareInventory.*.serialNumber'        => 'nullable|string|max:255',

            'softwareInventory'                       => 'nullable|array',
            'softwareInventory.*.displayName'         => 'nullable|string|max:255',
            'softwareInventory.*.version'             => 'nullable|string|max:50',
            'softwareInventory.*.publisher'           => 'nullable|string|max:255',
            'softwareInventory.*.installDate'         => 'nullable|string|max:50',

            'processes'                 => 'nullable|array',
            'processes.*.processName'   => 'nullable|string|max:255',
            'processes.*.processId'     => 'nullable|integer',
            'processes.*.cpuUsage'      => 'nullable|numeric|between:0,100',
            'processes.*.memoryUsageMB' => 'nullable|numeric|min:0',

            'services'                     => 'nullable|array',
            'services.*.serviceName'       => 'nullable|string|max:255',
            'services.*.displayName'       => 'nullable|string|max:255',
            'services.*.status'            => 'nullable|string|max:50',
            'services.*.startupType'       => 'nullable|string|max:50',

            'antivirus'                            => 'nullable|array',
            'antivirus.productName'                => 'nullable|string|max:255',
            'antivirus.isEnabled'                  => 'nullable|boolean',
            'antivirus.isUpdated'                  => 'nullable|boolean',
            'antivirus.isRealTimeProtectionEnabled' => 'nullable|boolean',
            'antivirus.definitionVersion'          => 'nullable|string|max:50',
            'antivirus.engineVersion'              => 'nullable|string|max:50',

            'firewall'                   => 'nullable|array',
            'firewall.displayName'       => 'nullable|string|max:255',
            'firewall.isEnabled'         => 'nullable|boolean',
            'firewall.profile'           => 'nullable|string|max:50',

            'windowsUpdates'                 => 'nullable|array',
            'windowsUpdates.*.updateName'    => 'nullable|string|max:255',
            'windowsUpdates.*.description'   => 'nullable|string',
            'windowsUpdates.*.severity'      => 'nullable|string|max:50',
            'windowsUpdates.*.installedAt'   => 'nullable|string|max:50',

            'eventLogs'                 => 'nullable|array',
            'eventLogs.*.eventId'       => 'nullable|integer',
            'eventLogs.*.logName'       => 'nullable|string|max:255',
            'eventLogs.*.source'        => 'nullable|string|max:255',
            'eventLogs.*.level'         => 'nullable|string|max:50',
            'eventLogs.*.message'       => 'nullable|string',

            'loginActivities'                   => 'nullable|array',
            'loginActivities.*.username'        => 'nullable|string|max:255',
            'loginActivities.*.sessionType'     => 'nullable|string|max:50',
            'loginActivities.*.logonId'         => 'nullable|string|max:255',
            'loginActivities.*.logonTime'       => 'nullable|string|max:50',

            'startupPrograms'                  => 'nullable|array',
            'startupPrograms.*.programName'    => 'nullable|string|max:255',
            'startupPrograms.*.programPath'    => 'nullable|string|max:500',

            'connectedDevices'                   => 'nullable|array',
            'connectedDevices.*.deviceName'      => 'nullable|string|max:255',
            'connectedDevices.*.deviceType'      => 'nullable|string|max:100',
            'connectedDevices.*.manufacturer'    => 'nullable|string|max:255',
            'connectedDevices.*.connectionType'  => 'nullable|string|max:50',

            'deviceEvents'                     => 'nullable|array',
            'deviceEvents.*.deviceName'        => 'nullable|string|max:255',
            'deviceEvents.*.deviceType'        => 'nullable|string|max:100',
            'deviceEvents.*.manufacturer'      => 'nullable|string|max:255',
            'deviceEvents.*.connectionType'    => 'nullable|string|max:50',
            'deviceEvents.*.eventType'         => 'nullable|string|max:50',
            'deviceEvents.*.eventTime'         => 'nullable|string|max:50',
        ];
    }
}
