<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\JobPosting;
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

    public function show(Company $company): Response
    {
        $postings = $company->jobPostings()
            ->with('application')
            ->latest()
            ->orderByDesc('id')
            ->get()
            ->map(fn (JobPosting $posting): array => [
                'id' => $posting->id,
                'title' => $posting->title,
                'location' => $posting->location,
                'status' => $posting->application?->status,
            ]);

        return Inertia::render('companies/Show', [
            'company' => $company->only(['id', 'name', 'website', 'city', 'notes']),
            'postings' => $postings,
        ]);
    }
}
