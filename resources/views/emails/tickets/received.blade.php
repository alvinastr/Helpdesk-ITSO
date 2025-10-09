@component('mail::message')
# Ticket Diterima

Halo **{{ $ticket->user_name }}**,

Terima kasih telah menghubungi kami. Ticket Anda telah diterima dan sedang dalam antrian review.

**Detail Ticket:**
- Ticket ID: {{ $ticket->ticket_number }}
- Subject: {{ $ticket->subject }}
- Status: PENDING REVIEW
- Created: {{ $ticket->created_at->format('d M Y H:i') }}

Anda akan mendapat notifikasi saat ticket disetujui dan ditangani oleh tim kami.

Untuk pertanyaan atau update, silakan reply email ini dengan menyertakan Ticket ID.

@component('mail::button', ['url' => route('tickets.show', $ticket)])
View Ticket
@endcomponent

Terima kasih,<br>
{{ config('app.name') }} Support Team
@endcomponent