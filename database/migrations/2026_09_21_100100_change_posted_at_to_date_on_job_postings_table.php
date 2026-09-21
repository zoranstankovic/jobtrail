<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // A publication date has no time of day. The UTC calendar date is
        // what the UI showed so far, so it is what the column keeps.
        DB::statement("alter table job_postings alter column posted_at type date using (posted_at at time zone 'UTC')::date");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("alter table job_postings alter column posted_at type timestamptz using posted_at::timestamp at time zone 'UTC'");
    }
};
