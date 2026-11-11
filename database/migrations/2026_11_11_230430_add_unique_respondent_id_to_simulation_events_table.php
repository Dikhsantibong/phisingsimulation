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
        // A respondent should only ever have one simulation event. If a race
        // condition (e.g. a link-preview bot and the real respondent hitting
        // the portal at the same moment) ever produced duplicate rows before
        // this constraint existed, keep only the most recent one per
        // respondent so the unique index below can be created.
        $duplicateIds = DB::table('simulation_events')
            ->select('id')
            ->whereNotIn('id', function ($query) {
                $query->selectRaw('MAX(id)')
                    ->from('simulation_events')
                    ->groupBy('respondent_id');
            })
            ->pluck('id');

        if ($duplicateIds->isNotEmpty()) {
            DB::table('simulation_events')->whereIn('id', $duplicateIds)->delete();
        }

        Schema::table('simulation_events', function (Blueprint $table) {
            $table->unique('respondent_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('simulation_events', function (Blueprint $table) {
            $table->dropUnique(['respondent_id']);
        });
    }
};
