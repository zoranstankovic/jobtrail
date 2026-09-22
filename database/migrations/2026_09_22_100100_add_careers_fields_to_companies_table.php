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
        Schema::table('companies', function (Blueprint $table) {
            // The company's own careers page.
            $table->string('careers_url', 2048)->nullable();
            // The applicant tracking system it uses (e.g. "personio"), stored
            // lowercase like job_postings.source.
            $table->string('ats', 50)->nullable();
            // The company's job board on that ATS.
            $table->string('ats_jobs_url', 2048)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn(['careers_url', 'ats', 'ats_jobs_url']);
        });
    }
};
