@component('mail::message')
# Update Ticket

Halo **{{ $ticket->user_name }}**,

Ada update untuk ticket Anda:

**Detail Ticket:**
- Ticket ID: {{ $ticket->ticket_number }}
- Subject: {{ $ticket->subject }}
- Status: {{ strtoupper($ticket->status) }}

**Update:**
{{ $message }}

@component('mail::button', ['url' => route('tickets.show', $ticket)])
View Ticket
@endcomponent

Terima kasih,<br>
{{ config('app.name') }} Support Team
@endcomponent