<?php

declare(strict_types=1);

namespace App\Services\PayloadProcessors;

use App\Models\Machine;
use App\Models\StartupProgram;
use Illuminate\Support\Facades\Log;

class StartupProgramProcessor
{
    public function process(Machine $machine, array $payload): void
    {
        try {
            $programs = $payload['startupPrograms'] ?? $payload['startup_programs'] ?? [];
            if (empty($programs)) {
                return;
            }

            StartupProgram::where('machine_id', $machine->id)->delete();

            $batch = [];
            foreach ($programs as $program) {
                $name = $program['processName'] ?? $program['process_name'] ?? $program['programName'] ?? $program['name'] ?? null;
                if (empty($name)) {
                    continue;
                }

                $batch[] = [
                    'company_id'   => $machine->company_id,
                    'machine_id'   => $machine->id,
                    'program_name' => $name,
                    'program_path' => $program['executablePath'] ?? $program['executable_path'] ?? null,
                    'registry_key' => $program['registryKey'] ?? $program['registry_key'] ?? null,
                    'startup_type' => $program['startupType'] ?? $program['startup_type'] ?? 'registry',
                    'status'       => 'enabled',
                    'collected_at' => now(),
                    'created_at'   => now(),
                    'updated_at'   => now(),
                ];
            }

            if (!empty($batch)) {
                StartupProgram::insert($batch);
            }

            Log::debug('StartupProgramProcessor: Processed startup programs', [
                'machine_id' => $machine->id,
                'count'      => count($batch),
            ]);
        } catch (\Throwable $e) {
            Log::error('StartupProgramProcessor::process - Failed', [
                'machine_id' => $machine->id,
                'error'      => $e->getMessage(),
                'trace'      => $e->getTraceAsString(),
            ]);
        }
    }
}
