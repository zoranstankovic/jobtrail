<?php

namespace App\Http\Middleware;

use App\Enums\ApplicationStatus;
use App\Enums\EmploymentType;
use App\Enums\SalaryPeriod;
use App\Enums\Seniority;
use App\Enums\WorkMode;
use App\Support\EnumOptions;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'sidebarOpen' => ! $request->hasCookie('sidebar_state')
                || $request->cookie('sidebar_state') === 'true',
            'enums' => fn (): array => [
                'applicationStatus' => EnumOptions::for(ApplicationStatus::class),
                'workMode' => EnumOptions::for(WorkMode::class),
                'employmentType' => EnumOptions::for(EmploymentType::class),
                'seniority' => EnumOptions::for(Seniority::class),
                'salaryPeriod' => EnumOptions::for(SalaryPeriod::class),
            ],
        ];
    }
}
