<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Inertia\Inertia;
use Inertia\Response;

class CompanyController extends Controller
{
    public function index(): Response
    {
        $companies = Company::query()
            ->withCount(['jobPostings', 'jobApplications'])
            ->orderByRaw('lower(name)')
            ->get()
            ->map(fn (Company $company): array => [
                'id' => $company->id,
                'name' => $company->name,
                'city' => $company->city,
                'website' => $company->website,
                'postings_count' => $company->job_postings_count,
                'applications_count' => $company->job_applications_count,
            ]);

        return Inertia::render('companies/Index', [
            'companies' => $companies,
        ]);
    }
}
