<?php

use App\Enums\WorkMode;
use App\Support\EnumCheck;

it('builds a CHECK constraint from the enum values', function (): void {
    expect(EnumCheck::sql('job_postings', 'work_mode', WorkMode::class))
        ->toBe("alter table job_postings add constraint job_postings_work_mode_check check (work_mode in ('onsite', 'hybrid', 'remote'))");
});
