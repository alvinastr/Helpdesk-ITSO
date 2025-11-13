<!DOCTYPE html>
<html>
<head>
    <title>Dashboard - Admin</title>
</head>
<body>
    <h1>Dashboard</h1>
    
    <div class="stats">
        <h2>Statistics</h2>
        <ul>
            <li>Pending Keluhan: {{ $stats['pending_keluhan'] ?? 0 }}</li>
            <li>Open: {{ $stats['open'] ?? 0 }}</li>
            <li>In Progress: {{ $stats['in_progress'] ?? 0 }}</li>
            <li>Resolved: {{ $stats['resolved'] ?? 0 }}</li>
            <li>Closed: {{ $stats['closed'] ?? 0 }}</li>
            <li>Rejected: {{ $stats['rejected'] ?? 0 }}</li>
        </ul>
    </div>

    <div class="recent-tickets">
        <h2>Recent Tickets</h2>
        @if(isset($recentTickets) && $recentTickets->count() > 0)
            @foreach($recentTickets as $ticket)
                <div class="ticket-item">
                    <p>{{ $ticket->ticket_number ?? 'No Number' }} - {{ $ticket->subject ?? 'No Subject' }}</p>
                    <p>Status: {{ $ticket->status ?? 'Unknown' }}</p>
                </div>
            @endforeach
        @else
            <p>No recent tickets</p>
        @endif
    </div>
</body>
</html>