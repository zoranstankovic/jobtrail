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
     */
    public function down(): void
    {
        DB::table('job_postings')->where('source', 'direct')->update(['source' => 'company_website']);
    }
};
