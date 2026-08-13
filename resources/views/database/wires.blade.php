@extends('layouts.app')

@section('title', 'Database Wire')
@section('page-title', 'Database Wire')

@section('breadcrumb')
    <a href="{{ route('database.parts', absolute: false) }}">Database</a>
    <span class="breadcrumb-separator">/</span>
    <span>Wire</span>
@endsection

@section('content')
    @php
        $rateColumns = isset($periodRates) ? ($periodRates->count() > 4 ? $periodRates->slice(-4)->values() : $periodRates) : collect();
        $formatMax5 = function ($value) {
            $formatted = number_format((float) $value, 5, ',', '.');
            return rtrim(rtrim($formatted, '0'), ',');
        };
    @endphp

    @include('database.wires.content')
    @include('database.wires.styles')
    @include('database.wires.scripts')
@endsection
