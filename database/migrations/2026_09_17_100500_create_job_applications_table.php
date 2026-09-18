<?php

use App\Enums\ApplicationStatus;
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
        Schema::create('job_applications', function (Blueprint $table) {
            $table->id();
            // unique(): a posting has at most one application.
            // restrict: a posting with an application cannot be deleted, so its
            // status history is never lost by accident (docs/design.md §5.6).
            $table->foreignId('job_posting_id')->unique()->constrained()->restrictOnDelete();
            // Denormalized copy of the latest event's to_status (docs/design.md §4.5).
            $table->string('status', 20);
            $table->timestampTz('applied_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestampsTz();

            $table->index('status');
        });

        DB::statement(EnumCheck::sql('job_applications', 'status', ApplicationStatus::class));
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('job_applications');
    }
};
