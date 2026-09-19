import type { SharedEnums } from './enums';

// This file must stay a module. Without a top-level import or export, the
// `declare module` blocks below become ambient module *declarations* that
// shadow the real packages instead of augmenting them, which silently strips
// the types off @inertiajs/core and vue.
export {};

// Extend ImportMeta interface for Vite...
declare module 'vite/client' {
    interface ImportMetaEnv {
        readonly VITE_APP_NAME: string;
        [key: string]: string | boolean | undefined;
    }

    interface ImportMeta {
        readonly env: ImportMetaEnv;
        readonly glob: <T>(pattern: string) => Record<string, () => Promise<T>>;
    }
}

declare module '@inertiajs/core' {
    export interface InertiaConfig {
        sharedPageProps: {
            name: string;
            sidebarOpen: boolean;
            enums: SharedEnums;
            [key: string]: unknown;
        };
        flashDataType: {
            toast?: {
                type: 'success' | 'error';
                message: string;
            };
        };
    }
}

declare module 'vue' {
    interface ComponentCustomProperties {
        $inertia: typeof Router;
        $page: Page;
        $headManager: ReturnType<typeof createHeadManager>;
    }
}
