<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('process_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('machine_id')->constrained()->cascadeOnDelete();
            $table->string('process_name', 255);
            $table->decimal('cpu_usage', 5, 2)->nullable();
            $table->decimal('memory_usage', 10, 2)->nullable();
            $table->timestamp('collected_at')->useCurrent();
            $table->timestamps();

            $table->index('machine_id');
            $table->index('collected_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('process_logs');
    }
};
