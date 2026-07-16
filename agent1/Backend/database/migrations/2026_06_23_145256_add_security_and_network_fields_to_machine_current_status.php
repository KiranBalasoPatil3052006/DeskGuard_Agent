<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('machine_current_status', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete()->after('machine_id');
            $table->string('antivirus_status', 50)->nullable()->after('battery_wear_level');
            $table->string('firewall_status', 50)->nullable()->after('antivirus_status');
            $table->integer('pending_updates')->default(0)->after('firewall_status');
        });
    }

    public function down(): void
    {
        Schema::table('machine_current_status', function (Blueprint $table) {
            $table->dropColumn(['company_id', 'antivirus_status', 'firewall_status', 'pending_updates']);
        });
    }
};
