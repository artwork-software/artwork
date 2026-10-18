<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('Authorize access') }}</title>
    <style>
        body {
            margin: 0;
            font-family: ui-sans-serif, system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif;
            background-color: #f3f4f6;
            color: #111827;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }
        .card {
            background: #ffffff;
            border-radius: 0.75rem;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08);
            max-width: 28rem;
            width: 100%;
            margin: 1rem;
            padding: 2rem;
        }
        h1 {
            font-size: 1.125rem;
            margin: 0 0 1rem;
        }
        p {
            font-size: 0.875rem;
            color: #4b5563;
            line-height: 1.5;
        }
        ul {
            font-size: 0.875rem;
            color: #4b5563;
            padding-left: 1.25rem;
        }
        .actions {
            display: flex;
            gap: 0.75rem;
            margin-top: 1.5rem;
        }
        .actions form {
            flex: 1;
        }
        button {
            width: 100%;
            padding: 0.625rem 1rem;
            border-radius: 0.5rem;
            border: 1px solid transparent;
            font-size: 0.875rem;
            font-weight: 600;
            cursor: pointer;
        }
        .approve {
            background-color: #4f46e5;
            color: #ffffff;
        }
        .approve:hover {
            background-color: #4338ca;
        }
        .deny {
            background-color: #ffffff;
            color: #374151;
            border-color: #d1d5db;
        }
        .deny:hover {
            background-color: #f9fafb;
        }
    </style>
</head>
<body>
    <div class="card">
        <h1>{{ __(':app wants to access your account', ['app' => $client->name]) }}</h1>

        <p>
            {!! __('The application :app is asking for permission to access your account (:email) on your behalf.', [
                'app' => '<strong>' . e($client->name) . '</strong>',
                'email' => e($user->email),
            ]) !!}
        </p>

        @if (count($scopes) > 0)
            <p><strong>{{ __('This application will be granted the following permissions:') }}</strong></p>
            <ul>
                @foreach ($scopes as $scope)
                    <li>{{ $scope->description }}</li>
                @endforeach
            </ul>
        @endif

        <div class="actions">
            <form method="post" action="{{ route('passport.authorizations.deny') }}">
                @csrf
                @method('DELETE')
                <input type="hidden" name="state" value="{{ $request->state }}">
                <input type="hidden" name="client_id" value="{{ $client->getKey() }}">
                <input type="hidden" name="auth_token" value="{{ $authToken }}">
                <button type="submit" class="deny">{{ __('Reject') }}</button>
            </form>
            <form method="post" action="{{ route('passport.authorizations.approve') }}">
                @csrf
                <input type="hidden" name="state" value="{{ $request->state }}">
                <input type="hidden" name="client_id" value="{{ $client->getKey() }}">
                <input type="hidden" name="auth_token" value="{{ $authToken }}">
                <button type="submit" class="approve">{{ __('Approve') }}</button>
            </form>
        </div>
    </div>
</body>
</html>
