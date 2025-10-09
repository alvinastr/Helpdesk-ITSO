@component('mail::message')
# Revisi Diperlukan

Halo **{{ $ticket->user_name }}**,

Admin meminta revisi/informasi tambahan untuk ticket Anda:

**Detail Ticket:**
- Ticket ID: {{ $ticket->ticket_number }}
- Subject: {{ $ticket->subject }}
- Status: PENDING REVIEW (Needs Revision)

**Pesan dari Admin:**
{{ $message }}

Mohon reply email ini dengan informasi yang diminta atau login ke portal untuk memberikan update.

@component('mail::button', ['url' => route('tickets.show', $ticket)])
View & Update Ticket
@endcomponent

Terima kasih,<br>
{{ config('app.name') }} Support Team
@endcomponent