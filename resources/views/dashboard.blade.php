@extends('layouts.app')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard Costing')

@section('breadcrumb')
    <span>Dashboard</span>
@endsection

@section('header-filters')
@endsection

@section('hide-business-category-context', 'true')

@section('content')
    @include('dashboard.styles')
    @include('dashboard.content')
@endsection

@section('scripts')
    @include('dashboard.scripts')
@endsection
