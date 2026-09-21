<?php

use App\Models\JobPosting;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // The text search parser reads "Laravel/Vue" as one file-path token,
        // so neither "laravel" nor "vue" would match it. Slashes become
        // spaces first. SET EXPRESSION (PostgreSQL 17+) recomputes every
        // stored vector and keeps the GIN index.
        DB::statement($this->setExpression("replace(coalesce(title, ''), '/', ' ')", "replace(coalesce(description, ''), '/', ' ')"));
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement($this->setExpression("coalesce(title, '')", "coalesce(description, '')"));
    }

    private function setExpression(string $title, string $description): string
    {
        $config = JobPosting::SEARCH_CONFIG;

        return 'alter table job_postings alter column search_vector set expression as ('
            ."setweight(to_tsvector('{$config}', {$title}), 'A') || "
            ."setweight(to_tsvector('{$config}', {$description}), 'B'))";
    }
};
