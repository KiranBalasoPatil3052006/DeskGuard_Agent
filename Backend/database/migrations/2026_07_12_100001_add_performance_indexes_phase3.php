<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 3 Performance Indexes
 *
 * These indexes support the optimized dashboard chart queries (SQL-level aggregation)
 * and current-state lookups for faster dashboard rendering.
 *
 * Key optimizations:
 * - Covering index for health_logs chart aggregation query (GROUP BY machine_id, hour)
 * - Index on machine_current_status.company_id for dashboard card queries
 * - Index on usb_activities.company_id for direct dashboard summary queries
 * - Index on connected_devices for device pagination
 */
return new class extends Migration
{
    public function up(): void
    {
        // health_logs: Covering index for dashboard chart aggregation query
        // SELECT machine_id, DATE_FORMAT(collected_at, ...), AVG(cpu_percentage), AVG(ram_percentage)
        // WHERE company_id = ? AND collected_at >= ? GROUP BY machine_id, hour_bucket
        // This index covers the query entirely — no table lookups needed.
        if (!$this->indexExists('health_logs', 'health_logs_chart_aggregation_idx')) {
            Schema::table('health_logs', function (Blueprint $table) {
                $table->index(
                    ['company_id', 'collected_at', 'machine_id', 'cpu_percentage', 'ram_percentage'],
                    'health_logs_chart_aggregation_idx'
                );
            });
        }

        // machine_current_status: Company-level lookups for dashboard cards
        // WHERE company_id = ?
        if (!$this->indexExists('machine_current_status', 'machine_current_status_company_idx')) {
            Schema::table('machine_current_status', function (Blueprint $table) {
                $table->index('company_id', 'machine_current_status_company_idx');
            });
        }

        // usb_activities: Direct company-level queries (removed JOIN to machines)
        // WHERE company_id = ? AND created_at = today()
        if (!$this->indexExists('usb_activities', 'usb_activities_company_created_idx')) {
            Schema::table('usb_activities', function (Blueprint $table) {
                $table->index(['company_id', 'created_at'], 'usb_activities_company_created_idx');
            });
        }

        // machine_connected_devices: Pagination queries by machine
        // WHERE machine_id = ? ORDER BY created_at DESC
        if (!$this->indexExists('machine_connected_devices', 'connected_devices_machine_created_idx')) {
            Schema::table('machine_connected_devices', function (Blueprint $table) {
                $table->index(['machine_id', 'created_at'], 'connected_devices_machine_created_idx');
            });
        }

        // device_events: Machine-level queries for timeline and device list
        // WHERE machine_id = ? ORDER BY created_at DESC
        if (!$this->indexExists('device_events', 'device_events_machine_created_idx')) {
            Schema::table('device_events', function (Blueprint $table) {
                $table->index(['machine_id', 'created_at'], 'device_events_machine_created_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::table('health_logs', fn(Blueprint $t) => $t->dropIndex('health_logs_chart_aggregation_idx'));
        Schema::table('machine_current_status', fn(Blueprint $t) => $t->dropIndex('machine_current_status_company_idx'));
        Schema::table('usb_activities', fn(Blueprint $t) => $t->dropIndex('usb_activities_company_created_idx'));
        Schema::table('machine_connected_devices', fn(Blueprint $t) => $t->dropIndex('connected_devices_machine_created_idx'));
        Schema::table('device_events', fn(Blueprint $t) => $t->dropIndex('device_events_machine_created_idx'));
    }

    /**
     * Check if an index already exists to prevent duplicate index errors.
     */
    private function indexExists(string $table, string $indexName): bool
    {
        try {
            $indexes = Schema::getIndexes($table);
            foreach ($indexes as $index) {
                if ($index['name'] === $indexName) {
                    return true;
                }
            }
            return false;
        } catch (\Throwable $e) {
            return false;
        }
    }
};
