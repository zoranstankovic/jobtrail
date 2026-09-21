/**
 * A salary range such as "€60,000 – €75,000 · Yearly", or "—" when neither
 * amount is known.
 */
export function formatSalary(
    min: number | null,
    max: number | null,
    currency: string,
    period: string | null,
): string {
    const money = new Intl.NumberFormat(undefined, {
        style: 'currency',
        currency,
        maximumFractionDigits: 0,
    });

    let range: string;

    if (min !== null && max !== null) {
        range = `${money.format(min)} – ${money.format(max)}`;
    } else if (min !== null) {
        range = `from ${money.format(min)}`;
    } else if (max !== null) {
        range = `up to ${money.format(max)}`;
    } else {
        return '—';
    }

    return period === null ? range : `${range} · ${period}`;
}
