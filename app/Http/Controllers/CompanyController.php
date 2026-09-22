<?php

namespace App\Http\Controllers;

use App\Actions\DeleteCompany;
use App\Http\Requests\CompanyRequest;
use App\Models\Company;
use App\Models\JobPosting;
use Illuminate\Http\RedirectResponse;
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
                'ats' => $company->ats,
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
            'company' => $company->only(['id', 'name', 'website', 'city', 'notes', 'careers_url', 'ats', 'ats_jobs_url']),
            'postings' => $postings,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('companies/Create', [
            'atsSuggestions' => Company::atsSuggestions(),
        ]);
    }

    public function store(CompanyRequest $request): RedirectResponse
    {
        $company = Company::query()->create($request->validated());

        $this->toast('Company created.');

        return to_route('companies.show', $company);
    }

    public function edit(Company $company): Response
    {
        return Inertia::render('companies/Edit', [
            'company' => $company->only(['id', 'name', 'website', 'city', 'notes', 'careers_url', 'ats', 'ats_jobs_url']),
            'atsSuggestions' => Company::atsSuggestions(),
        ]);
    }

    public function update(CompanyRequest $request, Company $company): RedirectResponse
    {
        $company->update($request->validated());

        $this->toast('Company updated.');

        return to_route('companies.show', $company);
    }

    public function destroy(Company $company, DeleteCompany $deleteCompany): RedirectResponse
    {
        $deleteCompany->handle($company);

        $this->toast('Company deleted.');

        return to_route('companies.index');
    }
}
