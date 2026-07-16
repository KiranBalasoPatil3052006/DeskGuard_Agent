<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hardware_baselines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('machine_id')->constrained('machines')->onDelete('cascade');
            $table->string('component')->comment('e.g. RAM, SSD, HDD, CPU, Motherboard, GPU, NetworkCard, Battery, Monitor, Printer, Webcam, BluetoothAdapter, WiFiAdapter');
            $table->string('manufacturer')->nullable();
            $table->string('model')->nullable();
            $table->string('serial_number')->nullable();
            $table->string('capacity')->nullable();
            $table->string('speed')->nullable();
            $table->string('slot_info')->nullable();
            $table->json('properties')->nullable()->comment('Additional component-specific properties');
            $table->timestamp('baselined_at')->useCurrent();
            $table->timestamps();

            $table->unique(['machine_id', 'component', 'serial_number'], 'hw_baseline_unique');
            $table->index('company_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hardware_baselines');
    }
};