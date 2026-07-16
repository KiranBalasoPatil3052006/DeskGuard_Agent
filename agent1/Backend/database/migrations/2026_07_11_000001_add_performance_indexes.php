<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Health logs: Used by DashboardService::getCombinedChartData()
        // WHERE company_id = ? AND collected_at >= ? ORDER BY collected_at
        Schema::table('health_logs', function (Blueprint $table) {
            $table->index(['company_id', 'collected_at'], 'health_logs_company_collected_idx');
        });

        // Alerts: Used by DashboardService::getCompanyDashboard() critical count
        // WHERE company_id = ? AND severity = 'critical' AND status IN ('open','acknowledged')
        Schema::table('alerts', function (Blueprint $table) {
            $table->index(['company_id', 'severity', 'status'], 'alerts_company_severity_status_idx');
        });

        // Alerts: Used by DashboardService::getAlertChartData()
        // WHERE company_id = ? AND created_at >= ? GROUP BY DATE(created_at), severity
        Schema::table('alerts', function (Blueprint $table) {
            $table->index(['company_id', 'created_at'], 'alerts_company_created_idx');
        });

        // Machines: Used by MachineService::getCompanyMachineSummary()
        // WHERE company_id = ? AND is_online = ?
        Schema::table('machines', function (Blueprint $table) {
            $table->index(['company_id', 'is_online'], 'machines_company_online_idx');
        });

        // Machines: Used by MachineService::getCompanyMachines() sort
        // WHERE company_id = ? ORDER BY last_heartbeat_at DESC
        Schema::table('machines', function (Blueprint $table) {
            $table->index(['company_id', 'last_heartbeat_at'], 'machines_company_heartbeat_idx');
        });

        // Change history: Used by ChangeController::index() default sort
        // WHERE company_id = ? ORDER BY detected_at DESC
        Schema::table('change_history', function (Blueprint $table) {
            $table->index(['company_id', 'detected_at'], 'change_history_company_detected_idx');
        });

        // Login activities: Used by MachineController::security()
        // WHERE machine_id = ? ORDER BY created_at DESC LIMIT 20
        Schema::table('login_activities', function (Blueprint $table) {
            $table->index(['machine_id', 'created_at'], 'login_activities_machine_created_idx');
        });

        // USB activities: Used by MachineController::devices()
        // WHERE machine_id = ? ORDER BY created_at DESC LIMIT 50
        Schema::table('usb_activities', function (Blueprint $table) {
            $table->index(['machine_id', 'created_at'], 'usb_activities_machine_created_idx');
        });

        // Software inventory: Used by MachineController::inventory()
        // WHERE machine_id = ? ORDER BY software_name ASC
        Schema::table('software_inventory', function (Blueprint $table) {
            $table->index(['machine_id', 'software_name'], 'software_inventory_machine_name_idx');
        });
    }

    public function down(): void
    {
        Schema::table('health_logs', fn(Blueprint $t) => $t->dropIndex('health_logs_company_collected_idx'));
        Schema::table('alerts', fn(Blueprint $t) => $t->dropIndex('alerts_company_severity_status_idx'));
        Schema::table('alerts', fn(Blueprint $t) => $t->dropIndex('alerts_company_created_idx'));
        Schema::table('machines', fn(Blueprint $t) => $t->dropIndex('machines_company_online_idx'));
        Schema::table('machines', fn(Blueprint $t) => $t->dropIndex('machines_company_heartbeat_idx'));
        Schema::table('change_history', fn(Blueprint $t) => $t->dropIndex('change_history_company_detected_idx'));
        Schema::table('login_activities', fn(Blueprint $t) => $t->dropIndex('login_activities_machine_created_idx'));
        Schema::table('usb_activities', fn(Blueprint $t) => $t->dropIndex('usb_activities_machine_created_idx'));
        Schema::table('software_inventory', fn(Blueprint $t) => $t->dropIndex('software_inventory_machine_name_idx'));
    }
};
