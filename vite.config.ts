import inertia from '@inertiajs/vite';
import { wayfinder } from '@laravel/vite-plugin-wayfinder';
import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import { defineConfig, lazyPlugins } from 'vite-plus';

export default defineConfig({
    plugins: lazyPlugins(() => [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.ts'],
            refresh: true,
            fonts: [
                bunny('Instrument Sans', {
                    weights: [400, 500, 600],
                    // Metric-matched fallbacks need the optional "fontaine"
                    // package; the font is cached after the first visit, so
                    // the plain fallback stack in app.css is enough.
                    optimizedFallbacks: false,
                }),
            ],
        }),
        // No server-side rendering (see config/inertia.php): skip the SSR
        // endpoint and the start-up warm-up of the SSR module graph.
        inertia({ ssr: false }),
        tailwindcss(),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
        wayfinder({
            formVariants: true,
        }),
    ]),
    server: {
        // Listen on every interface inside the container so Docker's port
        // mapping can reach it.
        host: '0.0.0.0',
        port: 5173,
        strictPort: true,
        // What the browser should connect to. laravel-vite-plugin also uses
        // this value for the URL it writes into public/hot.
        hmr: {
            host: 'localhost',
        },
        watch: {
            // Filesystem events do not reliably cross the macOS bind mount.
            usePolling: process.env.VITE_POLLING === 'true',
            ignored: [
                '**/.agents/**',
                '**/.claude/**',
                '**/.cursor/**',
                '**/.junie/**',
                '**/vendor/**',
            ],
        },
    },
    lint: {
        ignorePatterns: [
            'vendor/**',
            'node_modules/**',
            'public/**',
            'tailwind.config.js',
            'resources/js/actions/**',
            'resources/js/components/ui/*',
            'resources/js/routes/**',
            'resources/js/wayfinder/**',
        ],
        options: {
            denyWarnings: true,
            typeAware: true,
        },
    },
    fmt: {
        printWidth: 80,
        tabWidth: 4,
        singleQuote: true,
        semi: true,
        singleAttributePerLine: false,
        htmlWhitespaceSensitivity: 'css',
        ignorePatterns: [
            '.github/**',
            'composer.json',
            // Not frontend sources: the formatter rewrites YAML to 4-space
            // indentation, which diverges from every Compose example, and
            // reflows the design document the plans reference.
            'compose.yaml',
            'compose.prod.yaml',
            'docs/**',
            'resources/js/components/ui/*',
            'resources/views/mail/*',
        ],
        sortTailwindcss: {
            functions: ['clsx', 'cn', 'cva'],
            entryPoint: 'resources/css/app.css',
        },
    },
});
