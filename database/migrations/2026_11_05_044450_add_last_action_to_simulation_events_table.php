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
        Schema::table('simulation_events', function (Blueprint $table) {
            // The raw action behind `behavior_status` (submit/reject/report),
            // kept so the export can show exactly what the respondent clicked
            // instead of only the derived risk category.
            $table->string('last_action')->nullable()->after('behavior_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('simulation_events', function (Blueprint $table) {
            $table->dropColumn('last_action');
        });
    }
};
