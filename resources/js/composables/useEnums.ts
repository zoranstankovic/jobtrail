import type { EnumName, EnumOption } from '@/types/enums';
import { usePage } from '@inertiajs/vue3';

/**
 * Enum options and labels, shared by the server with every page
 * (HandleInertiaRequests), so no enum value is hardcoded here.
 */
export function useEnums() {
    const page = usePage();

    function options(name: EnumName): EnumOption[] {
        return page.props.enums[name];
    }

    function label(name: EnumName, value: string | null | undefined): string {
        if (value === null || value === undefined) {
            return '—';
        }

        return (
            options(name).find((option) => option.value === value)?.label ??
            value
        );
    }

    return { options, label };
}
