import RootLayout from '@/layouts/RootLayout.vue';
import { createInertiaApp } from '@inertiajs/vue3';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

void createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    layout: () => RootLayout,
    progress: {
        color: '#4B5563',
    },
});
