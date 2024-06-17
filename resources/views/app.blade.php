<!DOCTYPE html>
<html lang="{{ App::getLocale() }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title inertia>{{ config('app.name', 'Artwork') }}</title>
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <style>
            html {
                scroll-behavior: smooth;
            }
        </style>

        <!-- Scripts -->
        @routes
        @vite(['resources/js/app.js', "resources/js/Pages/{$page['component']}.vue"])
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @inertia

        @env ('local')
            <script src="{{config('app.url')}}:3000/browser-sync/browser-sync-client.js"></script>
        @endenv
    </body>
</html>
