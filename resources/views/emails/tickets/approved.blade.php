@component('mail::message')
# Ticket Approved ✅

Halo **{{ $ticket->user_name }}**,

Kabar baik! Ticket Anda telah disetujui dan sedang ditangani.

**Detail Ticket:**
- Ticket ID: {{ $ticket->ticket_number }}
- Subject: {{ $ticket->subject }}
- Status: OPEN
- Category: {{ $ticket->category }}
- Priority: {{ strtoupper($ticket->priority) }}
@if ($ticket->assignedUser)
- Handler: {{ $ticket->assignedUser->name }}
@endif

Estimasi penyelesaian: 2-3 hari kerja (tergantung kompleksitas)

Anda akan mendapat update saat ada perkembangan.

@component('mail::button', ['url' => route('tickets.show', $ticket)])
View Ticket
@endcomponent

Terima kasih,<br>
{{ config('app.name') }} Support Team
@endcomponent
