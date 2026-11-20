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
        Schema::table('reminder_logs', function (Blueprint $table) {
            // Soft-deleted, not hard-deleted: the scheduler still needs to
            // see that this reminder already fired, so a manual delete from
            // the Pengingat page can't be silently re-created on the next
            // scan (it just hides the row from the list instead).
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reminder_logs', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
