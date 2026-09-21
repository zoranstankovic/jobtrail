const dateFormat = new Intl.DateTimeFormat(undefined, { dateStyle: 'medium' });

const calendarDateFormat = new Intl.DateTimeFormat(undefined, {
    dateStyle: 'medium',
    timeZone: 'UTC',
});

/** A server timestamp (ISO 8601, UTC) as a date in the browser's time zone. */
export function formatDate(iso: string | null): string {
    return iso === null ? '—' : dateFormat.format(new Date(iso));
}

/**
 * A calendar date such as posted_at, stored as midnight UTC. Printed in UTC,
 * so it never moves to the previous day west of Greenwich.
 */
export function formatCalendarDate(iso: string | null): string {
    return iso === null ? '—' : calendarDateFormat.format(new Date(iso));
}
