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
            $table->foreignId('campaign_id')->nullable()->after('id')
                ->constrained('simulation_campaigns')->nullOnDelete();
        });

        Schema::table('respondents', function (Blueprint $table) {
            $table->string('class_group')->nullable()->change();
            $table->string('email')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('respondents', function (Blueprint $table) {
            $table->dropConstrainedForeignId('campaign_id');
        });

        Schema::table('respondents', function (Blueprint $table) {
            $table->string('class_group')->nullable(false)->change();
            $table->string('email')->nullable(false)->change();
        });
    }
};
