<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('machine_connected_devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('machine_id')->constrained('machines')->cascadeOnDelete();
            $table->string('device_name', 255)->nullable();
            $table->string('device_type', 100)->nullable();
            $table->string('manufacturer', 255)->nullable();
            $table->string('connection_type', 50)->nullable();
            $table->string('status', 50)->default('connected');
            $table->timestamp('last_seen')->nullable();
            $table->timestamps();
            $table->index('machine_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('machine_connected_devices');
    }
};
