/** A row of the companies index (CompanyController@index). */
export type CompanyListItem = {
    id: number;
    name: string;
    city: string | null;
    website: string | null;
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
