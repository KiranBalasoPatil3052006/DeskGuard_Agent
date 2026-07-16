<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('software_baselines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('machine_id')->constrained('machines')->onDelete('cascade');
            $table->string('software_name');
            $table->string('version')->nullable();
            $table->string('publisher')->nullable();
            $table->string('architecture')->nullable();
            $table->timestamp('baselined_at')->useCurrent();
            $table->timestamps();

            $table->unique(['machine_id', 'software_name'], 'sw_baseline_unique');
            $table->index('company_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('software_baselines');
    }
};