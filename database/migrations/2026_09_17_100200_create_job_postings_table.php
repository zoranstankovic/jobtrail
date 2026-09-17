<?php

use App\Enums\EmploymentType;
use App\Enums\SalaryPeriod;
use App\Enums\Seniority;
use App\Enums\WorkMode;
use App\Support\EnumCheck;
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
        Schema::create('job_postings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->restrictOnDelete();
            $table->string('title');
            $table->string('url', 2048)->nullable();
            $table->string('source', 50);
            $table->string('connector', 50)->nullable();
            $table->string('external_id')->nullable();
            $table->string('location')->nullable();
            $table->string('work_mode', 20)->nullable();
            $table->string('employment_type', 20)->nullable();
            $table->string('seniority', 20)->nullable();
            $table->integer('salary_min')->nullable();
            $table->integer('salary_max')->nullable();
            $table->char('salary_currency', 3)->default('EUR');
            $table->string('salary_period', 20)->nullable();
            $table->text('description')->nullable();
            $table->jsonb('raw_payload')->nullable();
            $table->timestampTz('posted_at')->nullable();
            $table->timestampsTz();

            // Phase 2 deduplication. Manual postings have NULL in both
            // columns, and NULLs never collide in a unique constraint.
            $table->unique(['connector', 'external_id']);
            $table->index('company_id');
        });

        // Partial unique index: only rows that have a URL take part.
        DB::statement('create unique index job_postings_url_unique on job_postings (url) where url is not null');

        // A CHECK that evaluates to NULL passes, so this only applies when
        // both bounds are set.
        DB::statement('alter table job_postings add constraint job_postings_salary_check check (salary_min <= salary_max)');

        DB::statement(EnumCheck::sql('job_postings', 'work_mode', WorkMode::class));
        DB::statement(EnumCheck::sql('job_postings', 'employment_type', EmploymentType::class));
        DB::statement(EnumCheck::sql('job_postings', 'seniority', Seniority::class));
        DB::statement(EnumCheck::sql('job_postings', 'salary_period', SalaryPeriod::class));
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('job_postings');
    }
};
