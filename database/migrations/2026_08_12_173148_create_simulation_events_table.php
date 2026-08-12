<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('simulation_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('respondent_id')->constrained()->cascadeOnDelete();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('first_access_at')->nullable();
            $table->timestamp('response_at')->nullable();
            $table->string('behavior_status')->default('tidak_merespons')->index();
            $table->boolean('keystroke_detected')->default(false);
            $table->string('device_type')->nullable();
            $table->string('os_name')->nullable();
            $table->string('browser_name')->nullable();
            $table->string('ip_hash')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('simulation_events');
    }
};
