import { usePage } from '@inertiajs/vue3';
import { readonly, ref } from 'vue';

export type Appearance = 'light' | 'dark' | 'system';

const NEXT: Record<Appearance, Appearance> = {
    light: 'dark',
    dark: 'system',
    system: 'light',
};

const systemDark = window.matchMedia('(prefers-color-scheme: dark)');

// One value for the whole app: every component that calls useAppearance()
// gets this same ref. It is filled from the server's prop on first use.
const appearance = ref<Appearance>('system');
let loaded = false;

function applyTheme(value: Appearance): void {
    const dark = value === 'dark' || (value === 'system' && systemDark.matches);

    document.documentElement.classList.toggle('dark', dark);
}

// "System" follows the operating system while the page is open, too.
systemDark.addEventListener('change', () => {
    if (appearance.value === 'system') {
        applyTheme('system');
    }
});

export function useAppearance() {
    if (!loaded) {
        appearance.value = usePage().props.appearance;
        loaded = true;
    }

    function setAppearance(value: Appearance): void {
        appearance.value = value;
        // HandleInertiaRequests reads it on the next full page load, so the
        // server renders the right theme straight away.
        document.cookie = `appearance=${value}; path=/; max-age=31536000; SameSite=Lax`;
        applyTheme(value);
    }

    function cycleAppearance(): void {
        setAppearance(NEXT[appearance.value]);
    }

    return {
        appearance: readonly(appearance),
        setAppearance,
        cycleAppearance,
    };
}
