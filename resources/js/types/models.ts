/** A row of the companies index (CompanyController@index). */
export type CompanyListItem = {
    id: number;
    name: string;
    city: string | null;
    website: string | null;
    ats: string | null;
    postings_count: number;
    applications_count: number;
};

/** A company's own fields (CompanyController@show, @edit). */
export type Company = {
    id: number;
    name: string;
    website: string | null;
    city: string | null;
    notes: string | null;
    careers_url: string | null;
    ats: string | null;
    ats_jobs_url: string | null;
};

/** A posting as listed on the company detail page. */
export type CompanyPosting = {
    id: number;
    title: string;
    location: string | null;
    status: string | null;
};

/** The company form's fields; every value is a string, as inputs produce. */
export type CompanyFormData = {
    name: string;
    website: string;
    city: string;
    notes: string;
    careers_url: string;
    ats: string;
    ats_jobs_url: string;
};

/** Laravel's LengthAwarePaginator as JSON — the fields the UI uses. */
export type Paginated<T> = {
    data: T[];
    current_page: number;
    last_page: number;
    from: number | null;
    to: number | null;
    total: number;
    prev_page_url: string | null;
    next_page_url: string | null;
};

/** A row of the postings index (JobPostingController@index). */
export type PostingListItem = {
    id: number;
    title: string;
    company: { id: number; name: string };
    location: string | null;
    work_mode: string | null;
    seniority: string | null;
    skills: string[];
    source: string;
    status: string | null;
};

/** The postings index filters, as the server echoes them back. */
export type PostingFilters = {
    search: string | null;
    application: string | null;
    work_mode: string | null;
    seniority: string | null;
    source: string | null;
    skill: string | null;
    sort: 'created' | 'posted';
};

/** A posting's fields (JobPostingController::presentPosting). */
export type Posting = {
    id: number;
    title: string;
    url: string | null;
    source: string;
    location: string | null;
    work_mode: string | null;
    employment_type: string | null;
    seniority: string | null;
    salary_min: number | null;
    salary_max: number | null;
    salary_currency: string;
    salary_period: string | null;
    description: string | null;
    /** A calendar date, "YYYY-MM-DD". */
    posted_at: string | null;
    created_at: string;
    company: { id: number; name: string };
    skills: string[];
};

/** The posting form's fields; every value is a string, as inputs produce. */
export type PostingFormData = {
    company: string;
    title: string;
    url: string;
    source: string;
    location: string;
    work_mode: string;
    employment_type: string;
    seniority: string;
    salary_min: string;
    salary_max: string;
    salary_currency: string;
    salary_period: string;
    posted_at: string;
    description: string;
    skills: string[];
    already_applied: boolean;
    applied_at: string;
};

/** One status change of an application (JobPostingController::presentApplication). */
export type ApplicationEvent = {
    id: number;
    from_status: string | null;
    to_status: string;
    occurred_at: string;
    note: string | null;
    can_delete: boolean;
};

/** An application as the posting detail page shows it. */
export type Application = {
    id: number;
    status: string;
    applied_at: string | null;
    notes: string | null;
    events: ApplicationEvent[];
};

/** A row of the applications index (JobApplicationController@index). */
export type ApplicationListItem = {
    id: number;
    status: string;
    applied_at: string | null;
    last_activity_at: string;
    posting: { id: number; title: string };
    company: { id: number; name: string };
};

/** A status tab of the applications index, with its count. */
export type ApplicationTab = {
    key: string;
    label: string;
    count: number;
};
