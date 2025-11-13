@extends('layouts.app-production')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-primary text-white py-3">
                    <h4 class="mb-0 fw-semibold">
                        <i class="fas fa-chart-bar me-2"></i>Laporan Tiket
                    </h4>
                </div>
                <div class="card-body p-4">
                    {{-- Filter Form --}}
                    <form method="GET" action="{{ route('admin.reports') }}" class="mb-4">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label for="start_date" class="form-label fw-semibold">Tanggal Mulai</label>
                                <input type="date" class="form-control" id="start_date" name="start_date" value="{{ $startDate }}">
                            </div>
                            <div class="col-md-3">
                                <label for="end_date" class="form-label fw-semibold">Tanggal Akhir</label>
                                <input type="date" class="form-control" id="end_date" name="end_date" value="{{ $endDate }}">
                            </div>
                            <div class="col-md-2">
                                <label for="status" class="form-label fw-semibold">Status</label>
                                <select class="form-select" id="status" name="status">
                                    <option value="">Semua Status</option>
                                    <option value="pending_keluhan">Pending Keluhan</option>
                                    <option value="open">Open</option>
                                    <option value="in_progress">In Progress</option>
                                    <option value="resolved">Resolved</option>
                                    <option value="closed">Closed</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label for="category" class="form-label fw-semibold">Kategori</label>
                                <select class="form-select" id="category" name="category">
                                    <option value="">Semua Kategori</option>
                                    <option value="Technical">Technical</option>
                                    <option value="General">General</option>
                                    <option value="Billing">Billing</option>
                                </select>
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-filter me-2"></i>Filter
                                </button>
                            </div>
                        </div>
                    </form>

                    {{-- Export Buttons --}}
                    <div class="mb-4 d-flex gap-2">
                        <a href="{{ route('admin.reports.excel', request()->all()) }}" class="btn btn-success">
                            <i class="fas fa-file-excel me-2"></i>Export Excel
                        </a>
                        <a href="{{ route('admin.reports.pdf', request()->all()) }}" class="btn btn-danger">
                            <i class="fas fa-file-pdf me-2"></i>Export PDF
                        </a>
                    </div>

                    {{-- Summary Statistics --}}
                    <div class="row g-3 mb-4">
                        <div class="col-md-3">
                            <div class="card bg-light border-0 h-100">
                                <div class="card-body text-center">
                                    <h3 class="fw-bold text-primary mb-1">{{ $totalTickets }}</h3>
                                    <p class="text-muted mb-0">Total Tiket</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-light border-0 h-100">
                                <div class="card-body text-center">
                                    <h3 class="fw-bold text-success mb-1">{{ $ticketsByStatus['closed'] ?? 0 }}</h3>
                                    <p class="text-muted mb-0">Tiket Selesai</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-light border-0 h-100">
                                <div class="card-body text-center">
                                    <h3 class="fw-bold text-warning mb-1">{{ $ticketsByStatus['open'] ?? 0 }}</h3>
                                    <p class="text-muted mb-0">Tiket Terbuka</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-light border-0 h-100">
                                <div class="card-body text-center">
                                    <h3 class="fw-bold text-info mb-1">{{ $ticketsByStatus['in_progress'] ?? 0 }}</h3>
                                    <p class="text-muted mb-0">Dalam Proses</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Charts Section --}}
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <div class="card border-0 shadow-sm">
                                <div class="card-header bg-light">
                                    <h6 class="mb-0 fw-semibold">Tiket Berdasarkan Status</h6>
                                </div>
                                <div class="card-body">
                                    <table class="table table-sm">
                                        @foreach($ticketsByStatus as $status => $count)
                                            <tr>
                                                <td>{{ ucfirst(str_replace('_', ' ', $status)) }}</td>
                                                <td class="text-end fw-bold">{{ $count }}</td>
                                            </tr>
                                        @endforeach
                                    </table>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card border-0 shadow-sm">
                                <div class="card-header bg-light">
                                    <h6 class="mb-0 fw-semibold">Tiket Berdasarkan Kategori</h6>
                                </div>
                                <div class="card-body">
                                    <table class="table table-sm">
                                        @foreach($ticketsByCategory as $category => $count)
                                            <tr>
                                                <td>{{ $category }}</td>
                                                <td class="text-end fw-bold">{{ $count }}</td>
                                            </tr>
                                        @endforeach
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Tickets Table --}}
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-light">
                            <h6 class="mb-0 fw-semibold">Daftar Tiket ({{ $startDate }} s/d {{ $endDate }})</h6>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>No. Tiket</th>
                                            <th>Subjek</th>
                                            <th>Status</th>
                                            <th>Kategori</th>
                                            <th>Prioritas</th>
                                            <th>Channel</th>
                                            <th>Dibuat</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($tickets as $ticket)
                                            <tr>
                                                <td>
                                                    <a href="{{ route('admin.tickets.show', $ticket) }}" class="text-decoration-none fw-semibold">
                                                        {{ $ticket->ticket_number }}
                                                    </a>
                                                </td>
                                                <td>{{ Str::limit($ticket->subject, 50) }}</td>
                                                <td>
                                                    <span class="badge bg-{{ $ticket->status === 'closed' ? 'success' : ($ticket->status === 'open' ? 'warning' : 'secondary') }}">
                                                        {{ ucfirst(str_replace('_', ' ', $ticket->status)) }}
                                                    </span>
                                                </td>
                                                <td>{{ $ticket->category ?? '-' }}</td>
                                                <td>
                                                    <span class="badge bg-{{ $ticket->priority === 'high' ? 'danger' : ($ticket->priority === 'medium' ? 'warning' : 'secondary') }}">
                                                        {{ ucfirst($ticket->priority ?? 'low') }}
                                                    </span>
                                                </td>
                                                <td>{{ ucfirst($ticket->channel) }}</td>
                                                <td>{{ $ticket->created_at->format('d/m/Y H:i') }}</td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="7" class="text-center py-4 text-muted">
                                                    Tidak ada tiket dalam periode ini
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
