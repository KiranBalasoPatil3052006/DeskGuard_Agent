<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('change_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('machine_id')->constrained('machines')->onDelete('cascade');
            $table->string('category')->comment('hardware, software, security, network, peripheral, configuration');
            $table->string('change_type')->comment('added, removed, modified, updated, enabled, disabled, connected, disconnected');
            $table->string('item_identifier')->nullable()->comment('e.g. software name, hardware serial, setting key');
            $table->string('item_label')->nullable()->comment('Human-readable label for display');
            $table->text('previous_value')->nullable();
            $table->text('new_value')->nullable();
            $table->text('description')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('detected_at')->useCurrent();
            $table->timestamps();

            $table->index('company_id');
            $table->index('category');
            $table->index('change_type');
            $table->index(['machine_id', 'detected_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('change_history');
    }
};