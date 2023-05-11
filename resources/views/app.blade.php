<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title inertia>{{ config('app.name', 'Laravel') }}</title>
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <!-- Styles -->
        <link rel="stylesheet" href="{{ mix('css/app.css') }}">
        <link rel="icon" href="{{ asset('Svgs/IconSvgs/artwork_aqua.svg') }}" type="image/svg+xml">
        <!-- Scripts -->
        @routes
        <script src="{{ mix('js/app.js') }}" defer></script>
        <script type="text/javascript">
            @auth
                window.Permissions = {!! json_encode(Auth::user()->allPermissions, true) !!};
                window.Roles = {!! json_encode(Auth::user()->allRoles, true) !!};
            @else
                window.Permissions = [];
                window.Roles = [];
            @endauth
        </script>
    </head>
    <body class="font-sans antialiased">
        @inertia
        @env ('local')
            <script src="{{config('app.url')}}:3000/browser-sync/browser-sync-client.js"></script>
        @endenv
    </body>
</html>
