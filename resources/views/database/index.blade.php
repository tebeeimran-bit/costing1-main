@extends('layouts.app')

@section('title', 'Database Management')
@section('page-title', 'Database Management')

@section('breadcrumb')
    <span>Database</span>
@endsection

@section('content')
    <div class="row" style="display: flex; flex-direction: column; gap: 2rem;">
        <!-- Products Table -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Products</h3>
            </div>
            <div class="material-table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Created At</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($products as $product)
                            <tr>
                                <td>{{ $product->id }}</td>
                                <td>{{ $product->name }}</td>
                                <td>{{ $product->created_at ? $product->created_at->format('d M Y') : '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" style="text-align: center;">No products found</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Customers Table -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Customers</h3>
            </div>
            <div class="material-table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Created At</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($customers as $customer)
                            <tr>
                                <td>{{ $customer->id }}</td>
                                <td>{{ $customer->name }}</td>
                                <td>{{ $customer->created_at ? $customer->created_at->format('d M Y') : '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" style="text-align: center;">No customers found</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Materials Table -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Materials</h3>
            </div>
            <div class="material-table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Unit</th>
                            <th>Price per Unit</th>
                            <th>Currency</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($materials as $material)
                            <tr>
                                <td>{{ $material->id }}</td>
                                <td>{{ $material->name }}</td>
                                <td>{{ $material->unit }}</td>
                                <td>{{ number_format($material->price_per_unit, 0, ',', '.') }}</td>
                                <td>{{ $material->currency }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" style="text-align: center;">No materials found</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
