@extends('layouts.app-production')

@section('content')
<div class="container-fluid">
    <h2 class="mb-4">Reports & Analytics</h2>

    <!-- Date Range Filter -->
    <div class="card mb-4">
        <div class="card-body">
            <form action="{{ route('admin.reports') }}" method="GET" class="row g-3">
                <div class="col-md-3">
                    <label>Start Date</label>
                    <input type="date" name="start_date" class="form-control" value="{{ $startDate }}">
                </div>
                <div class="col-md-3">
                    <label>End Date</label>
                    <input type="date" name="end_date" class="form-control" value="{{ $endDate }}">
                </div>
                <div class="col-md-2">
                    <label>&nbsp;</label>
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-filter"></i> Filter
                    </button>
                </div>
                <div class="col-md-2">
                    <label>&nbsp;</label>
                    <a href="{{ route('admin.reports.excel') }}?start_date={{ $startDate }}&end_date={{ $endDate }}" class="btn btn-success w-100">
                        <i class="fas fa-file-excel"></i> Export Excel
                    </a>
                </div>
                <div class="col-md-2">
                    <label>&nbsp;</label>
                    <a href="{{ route('admin.reports.pdf') }}?start_date={{ $startDate }}&end_date={{ $endDate }}" class="btn btn-danger w-100">
                        <i class="fas fa-file-pdf"></i> Export PDF
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Statistics -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card text-white bg-primary">
                <div class="card-body">
                    <h3>{{ $data['total_tickets'] }}</h3>
                    <p class="mb-0">Total Tickets</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-success">
                <div class="card-body">
                    <h3>{{ $data['by_status']['closed'] ?? 0 }}</h3>
                    <p class="mb-0">Closed Tickets</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-warning">
                <div class="card-body">
                    <h3>{{ number_format($data['avg_rating'], 1) }}</h3>
                    <p class="mb-0">Avg Rating</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-info">
                <div class="card-body">
                    <h3>{{ $data['resolution_times']['avg'] }}h</h3>
                    <p class="mb-0">Avg Resolution Time</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts -->
    <div class="row">
        <!-- By Status -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5>Tickets by Status</h5>
                </div>
                <div class="card-body">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Status</th>
                                <th>Count</th>
                                <th>Percentage</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($data['by_status'] as $status => $count)
                            <tr>
                                <td>{{ ucfirst(str_replace('_', ' ', $status)) }}</td>
                                <td>{{ $count }}</td>
                                <td>{{ $data['total_tickets'] > 0 ? round(($count / $data['total_tickets']) * 100, 1) : 0 }}%</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- By Category -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5>Tickets by Category</h5>
                </div>
                <div class="card-body">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Category</th>
                                <th>Count</th>
                                <th>Percentage</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($data['by_category'] as $category => $count)
                            <tr>
                                <td>{{ $category }}</td>
                                <td>{{ $count }}</td>
                                <td>{{ $data['total_tickets'] > 0 ? round(($count / $data['total_tickets']) * 100, 1) : 0 }}%</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- By Channel -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5>Tickets by Channel</h5>
                </div>
                <div class="card-body">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Channel</th>
                                <th>Count</th>
                                <th>Percentage</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($data['by_channel'] as $channel => $count)
                            <tr>
                                <td>{{ ucfirst($channel) }}</td>
                                <td>{{ $count }}</td>
                                <td>{{ $data['total_tickets'] > 0 ? round(($count / $data['total_tickets']) * 100, 1) : 0 }}%</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- By Priority -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5>Tickets by Priority</h5>
                </div>
                <div class="card-body">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Priority</th>
                                <th>Count</th>
                                <th>Percentage</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($data['by_priority'] as $priority => $count)
                            <tr>
                                <td>{{ ucfirst($priority) }}</td>
                                <td>{{ $count }}</td>
                                <td>{{ $data['total_tickets'] > 0 ? round(($count / $data['total_tickets']) * 100, 1) : 0 }}%</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Resolution Times -->
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5>Resolution Time Statistics</h5>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-md-4">
                            <h3>{{ $data['resolution_times']['avg'] }} hours</h3>
                            <p class="text-muted">Average</p>
                        </div>
                        <div class="col-md-4">
                            <h3>{{ $data['resolution_times']['min'] }} hours</h3>
                            <p class="text-muted">Minimum</p>
                        </div>
                        <div class="col-md-4">
                            <h3>{{ $data['resolution_times']['max'] }} hours</h3>
                            <p class="text-muted">Maximum</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection