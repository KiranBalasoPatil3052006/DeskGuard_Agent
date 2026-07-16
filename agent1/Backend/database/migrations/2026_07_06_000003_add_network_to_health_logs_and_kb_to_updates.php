<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('health_logs', function (Blueprint $table) {
            if (!Schema::hasColumn('health_logs', 'network_sent_bytes')) {
                $table->bigInteger('network_sent_bytes')->nullable()->after('disk_total_bytes');
            }
            if (!Schema::hasColumn('health_logs', 'network_received_bytes')) {
                $table->bigInteger('network_received_bytes')->nullable()->after('network_sent_bytes');
            }
        });

        Schema::table('windows_updates', function (Blueprint $table) {
            if (!Schema::hasColumn('windows_updates', 'kb_article')) {
                $table->string('kb_article', 100)->nullable()->after('severity');
            }
        });
    }

    public function down(): void
    {
        Schema::table('health_logs', function (Blueprint $table) {
            $table->dropColumn(['network_sent_bytes', 'network_received_bytes']);
        });

        Schema::table('windows_updates', function (Blueprint $table) {
            $table->dropColumn('kb_article');
        });
    }
};
