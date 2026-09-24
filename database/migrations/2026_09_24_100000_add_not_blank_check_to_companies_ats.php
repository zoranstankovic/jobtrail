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
        // A blank ATS means "no ATS". Rows written around the model before it
        // mapped blank to null would otherwise block the constraint.
        DB::table('companies')->whereRaw("ats !~ '[^[:space:]]'")->update(['ats' => null]);

        // The last line of defense for writes that skip the model's mutator
        // (insert(), upsert(), query-builder updates): no ATS, or one with at
        // least one non-whitespace character.
        DB::statement("alter table companies add constraint companies_ats_check check (ats is null or ats ~ '[^[:space:]]')");
    }

    /**
     * Reverse the migrations.
     *
     * The blank values cleared by up() are not restored; null means the same.
     */
    public function down(): void
    {
        DB::statement('alter table companies drop constraint companies_ats_check');
    }
};
