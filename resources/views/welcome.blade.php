@extends('layout')
@section('content')
    <h1>
        @auth
            {{'Hi '. auth::user()->name}}
        @endauth
    </h1>
@endsection
