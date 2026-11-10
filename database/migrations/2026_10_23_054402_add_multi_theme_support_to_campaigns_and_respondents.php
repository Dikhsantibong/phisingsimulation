<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('simulation_campaigns', function (Blueprint $table) {
            $table->json('themes')->nullable()->after('theme');
        });

        Schema::table('respondents', function (Blueprint $table) {
            $table->string('theme')->nullable()->after('campaign_id');
        });

        // Backfill existing campaigns so `themes` always reflects at least their current single theme.
        DB::table('simulation_campaigns')->select('id', 'theme')->orderBy('id')->each(function (object $campaign) {
            DB::table('simulation_campaigns')
                ->where('id', $campaign->id)
                ->update(['themes' => json_encode([$campaign->theme])]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('simulation_campaigns', function (Blueprint $table) {
            $table->dropColumn('themes');
        });

        Schema::table('respondents', function (Blueprint $table) {
            $table->dropColumn('theme');
        });
    }
};
