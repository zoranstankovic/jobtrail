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
        // "direct" says the same as "company_website" (applied on the
        // company's own site) and takes less room in the postings list.
        DB::table('job_postings')->where('source', 'company_website')->update(['source' => 'direct']);
    }

    /**
     * Reverse the migrations.
     *
     * One-way by nature: after up(), a renamed row cannot be told apart from
     * one that was entered as "direct", so a rollback turns every "direct"
     * row into "company_website". Both values mean the same.
     */
    public function down(): void
    {
        DB::table('job_postings')->where('source', 'direct')->update(['source' => 'company_website']);
    }
};
