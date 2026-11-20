{{-- Gemeinsames Gerüst der Fehlerseiten. Bewusst ohne Vite-Assets und ohne Inertia: die Seite muss
     auch dann erscheinen, wenn Sitzung, Datenbank oder Frontend-Build nicht verfügbar sind. --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>@yield('title') - {{ config('app.name', 'artwork') }}</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; }
        body { margin: 0; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 24px;
               background: #f3f4f6; color: #1f2937; font-family: ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, sans-serif; }
        .card { width: 100%; max-width: 560px; background: #fff; border: 1px solid #e5e7eb; border-radius: 16px; padding: 32px; }
        .code { font-size: 12px; font-weight: 600; letter-spacing: .08em; text-transform: uppercase; color: #6b7280; }
        h1 { margin: 6px 0 0; font-size: 20px; line-height: 1.3; }
        p { margin: 10px 0 0; font-size: 14px; line-height: 1.6; color: #4b5563; }
        .actions { margin-top: 24px; display: flex; flex-wrap: wrap; gap: 8px; }
        .button { display: inline-flex; align-items: center; border-radius: 10px; border: 1px solid #d1d5db; background: #fff;
                  padding: 8px 14px; font-size: 14px; font-weight: 500; color: #1f2937; text-decoration: none; cursor: pointer; font-family: inherit; }
        .button:hover { background: #f9fafb; }
        .button.primary { background: #1f2937; border-color: #1f2937; color: #fff; }
        .button.primary:hover { background: #111827; }
        .button:focus-visible { outline: 2px solid #276293; outline-offset: 2px; }
    </style>
</head>
<body>
    <main class="card">
        <div class="code">{{ __('Error') }} @yield('code')</div>
        <h1>@yield('title')</h1>
        <p>@yield('message')</p>
        <div class="actions">
            @hasSection('actions')
                @yield('actions')
            @else
                <button type="button" class="button" onclick="history.length > 1 ? history.back() : (location.href = '{{ url('/') }}')">{{ __('Back') }}</button>
                <a class="button primary" href="{{ url('/') }}">{{ __('To the start page') }}</a>
            @endif
        </div>
    </main>
</body>
</html>
