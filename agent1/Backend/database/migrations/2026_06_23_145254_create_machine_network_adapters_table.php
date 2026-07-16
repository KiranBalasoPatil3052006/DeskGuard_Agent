<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('machine_network_adapters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('machine_id')->constrained()->cascadeOnDelete();
            $table->string('adapter_name', 255);
            $table->string('ip_address', 45)->nullable();
            $table->string('mac_address', 17)->nullable();
            $table->decimal('speed', 10, 2)->nullable();
            $table->bigInteger('bytes_sent')->nullable();
            $table->bigInteger('bytes_received')->nullable();
            $table->string('status', 50)->nullable();
            $table->timestamps();

            $table->unique(['machine_id', 'adapter_name']);
            $table->index('machine_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('machine_network_adapters');
    }
};
