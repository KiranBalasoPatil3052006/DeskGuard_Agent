<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\TelemetryPayloadDTO;
use App\Models\Machine;
use App\Models\RawPayloadLog;
use Illuminate\Support\Facades\Log;

class TelemetryService
{
    private PayloadProcessorService $payloadProcessorService;

    public function __construct(PayloadProcessorService $payloadProcessorService)
    {
        $this->payloadProcessorService = $payloadProcessorService;
    }

    public function processTelemetry(TelemetryPayloadDTO $dto): void
    {
        $machineUid = $dto->toArray()['machineId'] ?? '';

        $machine = Machine::where('machine_uid', $machineUid)->first();

        if (!$machine) {
            Log::warning('TelemetryService: Machine not found', [
                'machine_uid' => $machineUid,
            ]);
            return;
        }

        $rawPayload = $dto->toArray();

        RawPayloadLog::create([
            'machine_id'  => $machine->id,
            'machine_uid' => $machineUid,
            'payload'     => json_encode($rawPayload),
            'source_ip'   => request()->ip(),
            'received_at' => now(),
        ]);

        $this->payloadProcessorService->process($machine, $rawPayload);

        Log::info('TelemetryService: Telemetry processed successfully', [
            'machine_id'  => $machine->id,
            'machine_uid' => $machineUid,
        ]);
    }
}
