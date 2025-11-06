<?php

namespace App\Services;

use App\Models\Ticket;
use Illuminate\Support\Facades\Log;

/**
 * Mock Email Fetcher Service untuk Testing
 * Simulasi fetch email tanpa perlu IMAP connection
 */
class MockEmailFetcherService
{
    protected EmailParserService $parser;
    protected TicketService $ticketService;
    protected array $mockEmails = [];
    
    public function __construct(
        EmailParserService $parser,
        TicketService $ticketService
    ) {
        $this->parser = $parser;
        $this->ticketService = $ticketService;
        $this->initializeMockEmails();
    }
    
    /**
     * Simulasi fetch emails dari mock data
     */
    public function fetchAndProcessEmails(): array
    {
        $results = [
            'total_fetched' => 0,
            'total_processed' => 0,
            'total_created' => 0,
            'total_filtered' => 0,
            'errors' => [],
            'tickets' => [],
        ];
        
        Log::info('🎭 Mock Email Fetcher: Starting simulation...');
        
        try {
            // Simulasi fetch mock emails
            $emails = $this->getMockEmails();
            $results['total_fetched'] = count($emails);
            
            Log::info("📧 Mock: Found {$results['total_fetched']} mock emails");
            
            foreach ($emails as $index => $emailData) {
                try {
                    // Simulasi filter validation
                    if (!$this->isValidEmail($emailData)) {
                        $results['total_filtered']++;
                        Log::info("🚫 Mock: Email #{$index} filtered out");
                        continue;
                    }
                    
                    $results['total_processed']++;
                    
                    // Parse email content
                    $parsed = $this->parser->parseEmailContent($emailData['body']);
                    
                    // Create ticket
                    $ticket = $this->createTicketFromEmail($parsed, $emailData);
                    
                    if ($ticket) {
                        $results['total_created']++;
                        $results['tickets'][] = [
                            'id' => $ticket->id,
                            'ticket_number' => $ticket->ticket_number,
                            'subject' => $ticket->subject,
                            'thread_count' => count($ticket->email_thread ?? []),
                        ];
                        
                        Log::info("✅ Mock: Created ticket #{$ticket->ticket_number} with {$results['tickets'][count($results['tickets'])-1]['thread_count']} email threads");
                    }
                    
                } catch (\Exception $e) {
                    $results['errors'][] = "Email #{$index}: " . $e->getMessage();
                    Log::error("❌ Mock: Error processing email #{$index}: " . $e->getMessage());
                    Log::error("Stack trace: " . $e->getTraceAsString());
                }
            }
            
            Log::info("🎭 Mock Email Fetcher: Completed");
            Log::info("📊 Stats: Fetched={$results['total_fetched']}, Processed={$results['total_processed']}, Created={$results['total_created']}, Filtered={$results['total_filtered']}");
            
        } catch (\Exception $e) {
            $results['errors'][] = $e->getMessage();
            Log::error('❌ Mock: Critical error: ' . $e->getMessage());
        }
        
        return $results;
    }
    
    /**
     * Simulasi email validation (simplified)
     */
    protected function isValidEmail(array $emailData): bool
    {
        // Simulasi filter: skip emails yang di-mark sebagai spam
        if (isset($emailData['spam']) && $emailData['spam']) {
            return false;
        }
        
        // Simulasi filter: harus ada subject
        if (empty($emailData['subject'])) {
            return false;
        }
        
        // Simulasi filter: harus ada body
        if (empty($emailData['body'])) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Create ticket dari parsed email
     */
    protected function createTicketFromEmail(array $parsed, array $emailData): ?Ticket
    {
        $data = [
            'subject' => $parsed['subject'],
            'description' => $parsed['description'],
            'category_id' => $emailData['category_id'] ?? 1,
            'priority' => $this->determinePriority($parsed['subject']),
            'reporter_name' => $parsed['from_name'],
            'reporter_email' => $parsed['from'],
            'status' => 'Open',
            'source' => 'Email',
            'input_method' => 'email_auto_fetch', // Identifier for bypass validation
            'email_from' => $parsed['from'],
            'email_to' => $parsed['to'],
            'email_cc' => $parsed['cc'],
            'email_subject' => $parsed['subject'],
            'email_received_at' => $parsed['date'],
            'parsed_emails' => $parsed['parsed_emails'], // Unlimited threading support
        ];
        
        return $this->ticketService->createTicket($data);
    }
    
    /**
     * Determine priority dari subject
     */
    protected function determinePriority(string $subject): string
    {
        $subject = strtolower($subject);
        
        if (str_contains($subject, 'urgent') || str_contains($subject, 'critical')) {
            return 'Critical';
        }
        
        if (str_contains($subject, 'high') || str_contains($subject, 'error')) {
            return 'High';
        }
        
        if (str_contains($subject, 'low')) {
            return 'Low';
        }
        
        return 'Medium';
    }
    
    /**
     * Get mock emails untuk testing
     */
    protected function getMockEmails(): array
    {
        return $this->mockEmails;
    }
    
    /**
     * Initialize mock email data
     */
    protected function initializeMockEmails(): void
    {
        $this->mockEmails = [
            // Mock Email 1: Thread dengan 5 balasan
            [
                'subject' => '[URGENT] Sistem CBS Error - Transaksi Gagal',
                'from' => 'ahmad.fauzi@bankmega.co.id',
                'to' => 'itsupport@bankmega.co.id',
                'date' => '2024-11-04 09:15:00',
                'category_id' => 1,
                'spam' => false,
                'body' => $this->getMockEmailBody1(),
            ],
            
            // Mock Email 2: Thread sederhana 3 balasan
            [
                'subject' => 'Printer Not Working',
                'from' => 'siti.nurhaliza@bankmega.co.id',
                'to' => 'itsupport@bankmega.co.id',
                'date' => '2024-11-05 14:00:00',
                'category_id' => 2,
                'spam' => false,
                'body' => $this->getMockEmailBody2(),
            ],
            
            // Mock Email 3: Thread panjang 8 balasan
            [
                'subject' => 'Email Server Slow',
                'from' => 'agus.wijaya@bankmega.co.id',
                'to' => 'itsupport@bankmega.co.id',
                'date' => '2024-11-06 08:00:00',
                'category_id' => 1,
                'spam' => false,
                'body' => $this->getMockEmailBody3(),
            ],
            
            // Mock Email 4: Spam email (akan difilter)
            [
                'subject' => 'BUY CHEAP VIAGRA NOW!!!',
                'from' => 'spam@spammer.com',
                'to' => 'itsupport@bankmega.co.id',
                'date' => '2024-11-06 10:00:00',
                'category_id' => 1,
                'spam' => true, // Marked as spam
                'body' => 'This is spam content...',
            ],
        ];
    }
    
    protected function getMockEmailBody1(): string
    {
        return <<<EMAIL
From: ahmad.fauzi@bankmega.co.id
To: itsupport@bankmega.co.id
Subject: [URGENT] Sistem CBS Error - Transaksi Gagal
Date: Mon, 4 Nov 2024 09:15:00 +0700

Selamat pagi Tim IT,

Saya mengalami masalah urgent pada sistem CBS. Saat melakukan transaksi transfer untuk nasabah, muncul error message:
"Connection timeout - CBS Server unreachable"

Ini sudah terjadi sejak pukul 08:00 pagi dan banyak nasabah yang menunggu. Mohon bantuannya segera.

Terima kasih,
Ahmad Fauzi
Teller - Cabang Jakarta Pusat
Extension: 1234

---

From: itsupport@bankmega.co.id
To: ahmad.fauzi@bankmega.co.id
Subject: Re: [URGENT] Sistem CBS Error - Transaksi Gagal
Date: Mon, 4 Nov 2024 09:30:00 +0700

Halo Pak Ahmad,

Terima kasih atas laporannya. Tim kami sudah menerima alert terkait issue CBS server.

Kami sedang melakukan investigasi dan koordinasi dengan team infrastructure. Sementara ini, mohon nasabah dapat menunggu sekitar 15-20 menit.

Update akan kami berikan segera.

Best regards,
Budi Santoso
IT Support Team
Extension: 5678

---

From: ahmad.fauzi@bankmega.co.id
To: itsupport@bankmega.co.id
Subject: Re: [URGENT] Sistem CBS Error - Transaksi Gagal
Date: Mon, 4 Nov 2024 10:00:00 +0700

Pak Budi,

Sudah 30 menit tapi masih belum bisa juga. Nasabah sudah mulai komplain. Ada update?

Regards,
Ahmad

---

From: itsupport@bankmega.co.id
To: ahmad.fauzi@bankmega.co.id
Subject: Re: [URGENT] Sistem CBS Error - Transaksi Gagal
Date: Mon, 4 Nov 2024 10:15:00 +0700

Pak Ahmad,

Kami sudah identifikasi masalahnya. Ada gangguan di network backbone yang menyebabkan koneksi ke CBS server terputus.

Team network sedang melakukan restart pada switch utama. Diperkirakan 5 menit lagi sistem akan normal kembali.

Mohon maaf atas ketidaknyamanannya.

Regards,
Budi

---

From: itsupport@bankmega.co.id
To: ahmad.fauzi@bankmega.co.id
Subject: Re: [RESOLVED] Sistem CBS Error - Transaksi Gagal
Date: Mon, 4 Nov 2024 10:25:00 +0700

Pak Ahmad,

Sistem CBS sudah kembali normal. Silakan dicoba kembali untuk melakukan transaksi.

Issue ini disebabkan oleh gangguan pada network switch yang sudah kami perbaiki. Kami sudah melakukan monitoring tambahan untuk mencegah terjadinya lagi.

Jika masih ada kendala, mohon segera hubungi kami kembali.

Terima kasih atas kesabarannya.

Best regards,
Budi Santoso
IT Support Team

Ticket ini ditutup. Total response time: 1 jam 10 menit.
EMAIL;
    }
    
    protected function getMockEmailBody2(): string
    {
        return <<<EMAIL
From: siti.nurhaliza@bankmega.co.id
To: itsupport@bankmega.co.id
Subject: Printer Not Working
Date: Tue, 5 Nov 2024 14:00:00 +0700

Dear IT Support,

Printer di lantai 2 tidak bisa print. Sudah dicoba restart tapi tetap tidak bisa.

Mohon perbaikan.

Thanks,
Siti Nurhaliza
Admin - Cabang Bandung

---

From: itsupport@bankmega.co.id
To: siti.nurhaliza@bankmega.co.id
Subject: Re: Printer Not Working
Date: Tue, 5 Nov 2024 14:15:00 +0700

Halo Bu Siti,

Apakah ada error message yang muncul? Dan sudah dicek kabel networknya?

Regards,
IT Support

---

From: itsupport@bankmega.co.id
To: siti.nurhaliza@bankmega.co.id
Subject: Re: [RESOLVED] Printer Not Working
Date: Tue, 5 Nov 2024 14:30:00 +0700

Bu Siti,

Printer sudah kami remote dan ternyata driver perlu diupdate. Sudah kami fix dan test print berhasil.

Silakan dicoba kembali.

Best regards,
IT Support Team
EMAIL;
    }
    
    protected function getMockEmailBody3(): string
    {
        return <<<EMAIL
From: agus.wijaya@bankmega.co.id
To: itsupport@bankmega.co.id
Subject: Email Server Slow
Date: Wed, 6 Nov 2024 08:00:00 +0700

Tim IT,

Email server sangat lambat hari ini. Butuh 5 menit untuk kirim email.

Mohon dicek.

Agus Wijaya

---

From: itsupport@bankmega.co.id
To: agus.wijaya@bankmega.co.id
Subject: Re: Email Server Slow
Date: Wed, 6 Nov 2024 08:15:00 +0700

Pak Agus,

Kami akan investigasi. Bisakah info kantor cabang mana?

Regards,
IT Support

---

From: agus.wijaya@bankmega.co.id
To: itsupport@bankmega.co.id
Subject: Re: Email Server Slow
Date: Wed, 6 Nov 2024 08:20:00 +0700

Cabang Surabaya.

---

From: itsupport@bankmega.co.id
To: agus.wijaya@bankmega.co.id
Subject: Re: Email Server Slow
Date: Wed, 6 Nov 2024 08:30:00 +0700

Terima kasih. Sedang kami cek.

---

From: agus.wijaya@bankmega.co.id
To: itsupport@bankmega.co.id
Subject: Re: Email Server Slow
Date: Wed, 6 Nov 2024 09:00:00 +0700

Update dong, masih lambat nih.

---

From: itsupport@bankmega.co.id
To: agus.wijaya@bankmega.co.id
Subject: Re: Email Server Slow
Date: Wed, 6 Nov 2024 09:15:00 +0700

Pak Agus, kami menemukan ada high CPU usage di email server. Sedang kami restart service nya.

---

From: agus.wijaya@bankmega.co.id
To: itsupport@bankmega.co.id
Subject: Re: Email Server Slow
Date: Wed, 6 Nov 2024 09:30:00 +0700

OK thanks, saya tunggu.

---

From: itsupport@bankmega.co.id
To: agus.wijaya@bankmega.co.id
Subject: Re: [RESOLVED] Email Server Slow
Date: Wed, 6 Nov 2024 09:45:00 +0700

Pak Agus,

Email server sudah normal kembali. Service sudah direstart dan CPU usage sudah kembali normal.

Silakan dicoba kembali.

Terima kasih.

Best regards,
IT Support Team
EMAIL;
    }
}
