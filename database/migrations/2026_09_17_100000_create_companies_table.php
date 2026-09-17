<?php

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
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('website', 2048)->nullable();
            $table->string('city')->nullable();
            $table->text('notes')->nullable();
            $table->timestampsTz();
        });

        // Case-insensitive uniqueness needs an expression index, which the
        // schema builder cannot express.
        DB::statement('create unique index companies_name_lower_unique on companies (lower(name))');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
