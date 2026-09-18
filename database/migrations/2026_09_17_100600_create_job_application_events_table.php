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
        Schema::create('job_application_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_application_id')->constrained()->cascadeOnDelete();
            // NULL only on the creation event.
            $table->string('from_status', 20)->nullable();
            $table->string('to_status', 20);
            // Chosen by the user and may be backdated.
            $table->timestampTz('occurred_at');
            $table->text('note')->nullable();
            // When the row was actually written. Events have no updated_at.
            $table->timestampTz('created_at')->useCurrent();

            // Serves "latest event of an application".
            $table->index(['job_application_id', 'occurred_at']);
        });

        DB::statement(EnumCheck::sql('job_application_events', 'from_status', ApplicationStatus::class));
        DB::statement(EnumCheck::sql('job_application_events', 'to_status', ApplicationStatus::class));
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('job_application_events');
    }
};
