@extends('errors.artwork')

@section('code', '419')
@section('title', __('Page expired'))
@section('message', __('Your session has expired or the page was open for too long. Reload the page and try again.'))

@section('actions')
    <button type="button" class="button primary" onclick="location.reload()">{{ __('Reload page') }}</button>
    <a class="button" href="{{ url('/') }}">{{ __('To the start page') }}</a>
@endsection
