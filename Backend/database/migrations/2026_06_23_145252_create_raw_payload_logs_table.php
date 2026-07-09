<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('raw_payload_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('machine_id')->nullable()->constrained()->nullOnDelete();
            $table->string('machine_uid', 255)->nullable()->index();
            $table->longText('payload');
            $table->ipAddress('source_ip')->nullable();
            $table->timestamp('received_at')->useCurrent();
            $table->timestamps();

            $table->index('received_at');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('raw_payload_logs');
    }
};
