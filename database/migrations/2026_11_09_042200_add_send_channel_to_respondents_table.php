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
        Schema::table('respondents', function (Blueprint $table) {
            // Which channel the initial simulation message was sent through
            // (email or whatsapp), chosen per respondent at send time.
            $table->string('send_channel')->nullable()->after('whatsapp_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('respondents', function (Blueprint $table) {
            $table->dropColumn('send_channel');
        });
    }
};
