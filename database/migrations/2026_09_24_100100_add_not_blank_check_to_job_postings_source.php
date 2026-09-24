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
        // The last line of defense for writes that skip the model's mutator
        // or collapse to '' in it: a source has at least one non-whitespace
        // character. Nothing is cleared first: a blank source has no meaningful
        // replacement, so a database holding one stops here instead of guessing.
        DB::statement("alter table job_postings add constraint job_postings_source_check check (source ~ '[^[:space:]]')");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('alter table job_postings drop constraint job_postings_source_check');
    }
};
