@extends('layouts.app')

@section('title', 'Project Document')
@section('page-title', 'Project Document')

@section('breadcrumb')
    <a href="{{ route('dashboard', absolute: false) }}">Dashboard</a>
    <span class="breadcrumb-separator">/</span>
    <span>Project Document</span>
@endsection

@section('content')
    @include('database.project-documents.styles')
    @include('database.project-documents.content')
@endsection

@section('scripts')
    @include('database.project-documents.scripts')
@endsection
