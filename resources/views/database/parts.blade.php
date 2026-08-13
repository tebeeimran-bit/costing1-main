@extends('layouts.app')

@section('title', 'Database Part')
@section('page-title', 'Database Part (Material)')

@section('breadcrumb')
    <a href="{{ route('database.products', absolute: false) }}">Database</a>
    <span class="breadcrumb-separator">/</span>
    <span>Parts</span>
@endsection

@section('content')
    @include('database.parts.content')
    @include('database.parts.styles')
    @include('database.parts.scripts')
@endsection
