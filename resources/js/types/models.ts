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
