<?php

use Inertia\Testing\AssertableInertia as Assert;

it('shares the enum options with every page', function (): void {
    $this->get('/postings')->assertInertia(fn (Assert $page) => $page
        ->has('enums.applicationStatus', 7)
        ->where('enums.applicationStatus.0', ['value' => 'saved', 'label' => 'Saved'])
        ->has('enums.workMode', 3)
        ->has('enums.employmentType', 4)
        ->has('enums.seniority', 5)
        ->where('enums.seniority.2', ['value' => 'mid', 'label' => 'Mid-level'])
        ->has('enums.salaryPeriod', 3));
});
