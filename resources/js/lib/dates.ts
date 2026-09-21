const dateFormat = new Intl.DateTimeFormat(undefined, { dateStyle: 'medium' });

const dateTimeFormat = new Intl.DateTimeFormat(undefined, {
    dateStyle: 'medium',
    timeStyle: 'short',
});

const calendarDateFormat = new Intl.DateTimeFormat(undefined, {
    dateStyle: 'medium',
    timeZone: 'UTC',
});

/** A server timestamp (ISO 8601, UTC) as a date in the browser's time zone. */
export function formatDate(iso: string | null): string {
    return iso === null ? '—' : dateFormat.format(new Date(iso));
}

/** A server timestamp as date and time in the browser's time zone. */
export function formatDateTime(iso: string | null): string {
    return iso === null ? '—' : dateTimeFormat.format(new Date(iso));
}

/**
 * A calendar date such as posted_at, stored as midnight UTC. Printed in UTC,
 * so it never moves to the previous day west of Greenwich.
 */
export function formatCalendarDate(iso: string | null): string {
    return iso === null ? '—' : calendarDateFormat.format(new Date(iso));
}

/**
 * A value for <input type="datetime-local">: the wall-clock time in the
 * browser's time zone, e.g. "2026-09-19T14:30".
 */
export function toDateTimeLocal(date: Date = new Date()): string {
    const pad = (n: number): string => String(n).padStart(2, '0');

    return (
        `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}` +
        `T${pad(date.getHours())}:${pad(date.getMinutes())}`
    );
}

/**
 * Converts a datetime-local value (local time) to ISO 8601 in UTC for the
 * server; an empty input stays empty.
 */
export function fromDateTimeLocal(value: string): string | null {
    return value === '' ? null : new Date(value).toISOString();
}

/** Whole days from a server timestamp until now. */
export function daysSince(iso: string, now: Date = new Date()): number {
    return Math.floor((now.getTime() - new Date(iso).getTime()) / 86_400_000);
}
