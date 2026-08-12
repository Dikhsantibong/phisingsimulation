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
        Schema::create('respondents', function (Blueprint $table) {
            $table->id();
            $table->uuid('session_token')->unique();
            $table->string('class_group');
            $table->string('name')->nullable();
            $table->string('email');
            $table->string('whatsapp_number')->nullable();
            $table->string('status')->default('pending')->index();
            $table->timestamps();

            $table->index('class_group');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('respondents');
    }
};
