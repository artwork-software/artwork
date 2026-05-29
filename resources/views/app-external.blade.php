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

        {{-- Bewusst weggelassen: window.Laravel.jsPermissions, auth()->user()-Aufrufe.
             Externe Sessions duerfen keinerlei interne Permission-/Rollen-Information ins Browser-DOM bekommen. --}}

        @routes
        @vite(['resources/js/app-external.js'])
        @inertiaHead
    </head>
    <body class="font-sans antialiased artwork">
        @inertia
    </body>
</html>
