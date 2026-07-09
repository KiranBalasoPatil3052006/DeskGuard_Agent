<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('machine_disks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('machine_id')->constrained()->cascadeOnDelete();
            $table->string('drive_letter', 10);
            $table->decimal('total_gb', 12, 2)->nullable();
            $table->decimal('used_gb', 12, 2)->nullable();
            $table->decimal('free_gb', 12, 2)->nullable();
            $table->string('file_system', 50)->nullable();
            $table->string('drive_type', 50)->nullable();
            $table->string('health_status', 50)->nullable();
            $table->timestamps();

            $table->unique(['machine_id', 'drive_letter']);
            $table->index('machine_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('machine_disks');
    }
};
