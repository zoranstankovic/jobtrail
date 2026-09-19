/** A row of the companies index (CompanyController@index). */
export type CompanyListItem = {
    id: number;
    name: string;
    city: string | null;
    website: string | null;
    postings_count: number;
    applications_count: number;
};
