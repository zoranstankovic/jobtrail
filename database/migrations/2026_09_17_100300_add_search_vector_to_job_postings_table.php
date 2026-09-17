<?php

use App\Models\JobPosting;
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
        $config = JobPosting::SEARCH_CONFIG;

        Schema::table('job_postings', function (Blueprint $table) use ($config) {
            // STORED matters: PostgreSQL 18 makes generated columns virtual by
            // default, and a virtual column cannot back the GIN index.
            // coalesce() keeps a NULL description from nulling the whole vector.
            $table->tsvector('search_vector')->storedAs(
                "setweight(to_tsvector('{$config}', coalesce(title, '')), 'A') || "
                ."setweight(to_tsvector('{$config}', coalesce(description, '')), 'B')"
            );

            $table->index('search_vector', 'job_postings_search_vector_index', 'gin');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('job_postings', function (Blueprint $table) {
            // Dropping the column drops its index too.
            $table->dropColumn('search_vector');
        });
    }
};
