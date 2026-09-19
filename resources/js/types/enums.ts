/** A select option built from a PHP enum case (app/Support/EnumOptions.php). */
export type EnumOption = {
    value: string;
    label: string;
};

/** The enums shared with every page by HandleInertiaRequests. */
export type SharedEnums = {
    applicationStatus: EnumOption[];
    workMode: EnumOption[];
    employmentType: EnumOption[];
    seniority: EnumOption[];
    salaryPeriod: EnumOption[];
};

export type EnumName = keyof SharedEnums;
