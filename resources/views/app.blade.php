<!DOCTYPE html>
@php($appearance = $page['props']['appearance'] ?? 'system')
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" @class(['dark' => $appearance === 'dark'])>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        {{-- "system" is only known in the browser. This runs before the
             first paint, so a dark system theme never flashes white. --}}
        @if ($appearance === 'system')
            <script>
                if (window.matchMedia('(prefers-color-scheme: dark)').matches) {
                    document.documentElement.classList.add('dark');
                }
            </script>
        @endif

        {{-- The page background (--background in app.css) before the
             stylesheet has loaded. --}}
        <style>
            html { background-color: hsl(0 0% 100%); }
            html.dark { background-color: hsl(0 0% 3.9%); }
        </style>

        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">

        @fonts

        @vite(['resources/css/app.css', 'resources/js/app.ts', "resources/js/pages/{$page['component']}.vue"])
        <x-inertia::head>
            <title>{{ config('app.name', 'JobTrail') }}</title>
        </x-inertia::head>
    </head>
    <body class="font-sans antialiased">
        <x-inertia::app />
    </body>
</html>
