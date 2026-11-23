@extends('errors.artwork')

@section('code', '419')
@section('title', __('Page expired'))
@section('message', __('Your session has expired or the page was open for too long. Reload the page and try again.'))

@section('actions')
    {{-- Link statt Inline-Skript: bei erzwungener Content-Security-Policy liefe ein onclick ins Leere --}}
    <a class="button primary" href="{{ rescue(fn () => url()->previous(), url('/'), false) }}">{{ __('Reload page') }}</a>
    <a class="button" href="{{ url('/') }}">{{ __('To the start page') }}</a>
@endsection
