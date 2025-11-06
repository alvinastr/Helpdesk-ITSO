<?php

namespace App\Console\Commands;

use App\Services\EmailParserService;
use App\Services\TicketService;
use Illuminate\Console\Command;

class TestEmailParserCommand extends Command
{
    protected $signature = 'test:email-parser {--sample=1}';
    protected $description = 'Test email parser dengan sample email tanpa perlu akses IMAP';

    public function handle()
    {
        $this->info('=== Testing Email Parser ===');
        $this->newLine();
        
        $sampleNumber = $this->option('sample');
        
        // Pilih sample email
        $sampleEmail = $this->getSampleEmail($sampleNumber);
        
        $this->info("Testing dengan: {$sampleEmail['description']}");
        $this->newLine();
        
        // Parse email
        $parser = app(EmailParserService::class);
        
        try {
            $this->info('📧 Raw Email Content:');
            $this->line('----------------------------------------');
            $this->line(substr($sampleEmail['content'], 0, 500) . '...');
            $this->newLine();
            
            $this->info('🔍 Parsing email...');
            $parsed = $parser->parseEmailContent($sampleEmail['content']);
            
            $this->info('✅ Parsing berhasil!');
            $this->newLine();
            
            // Tampilkan hasil parsing
            $this->info('📊 Hasil Parsing:');
            $this->table(
                ['Field', 'Value'],
                [
                    ['Subject', $parsed['subject']],
                    ['From Name', $parsed['from_name']],
                    ['From Email', $parsed['from']],
                    ['To', $parsed['to']],
                    ['CC', $parsed['cc'] ?? 'N/A'],
                    ['Date', $parsed['date']],
                    ['Total Emails', count($parsed['parsed_emails'])],
                ]
            );
            
            $this->newLine();
            $this->info('📝 Email Thread Detail:');
            
            foreach ($parsed['parsed_emails'] as $index => $email) {
                $this->line("Email #{$email['index']} - Type: {$email['type']}");
                $this->line("  From: {$email['from_name']} <{$email['from']}>");
                $this->line("  Subject: {$email['subject']}");
                $this->line("  Body: " . substr($email['body'], 0, 100) . '...');
                $this->newLine();
            }
            
            // Tanya apakah mau create ticket
            if ($this->confirm('Apakah Anda ingin membuat ticket dari email ini?', true)) {
                $this->createTicketFromParsedData($parsed);
            }
            
        } catch (\Exception $e) {
            $this->error('❌ Error saat parsing:');
            $this->error($e->getMessage());
            $this->line($e->getTraceAsString());
            return 1;
        }
        
        return 0;
    }
    
    protected function createTicketFromParsedData(array $parsed): void
    {
        $this->info('🎫 Creating ticket...');
        
        try {
            $ticketService = app(TicketService::class);
            
            // Prepare data untuk ticket
            $data = [
                'subject' => $parsed['subject'],
                'description' => $parsed['description'],
                'category_id' => $this->ask('Category ID', '1'),
                'priority' => $this->choice('Priority', ['Low', 'Medium', 'High', 'Critical'], 1),
                'reporter_name' => $parsed['from_name'],
                'reporter_email' => $parsed['from'],
                'status' => 'Open',
                'source' => 'Email',
                'email_from' => $parsed['from'],
                'email_to' => $parsed['to'],
                'email_cc' => $parsed['cc'],
                'email_subject' => $parsed['subject'],
                'email_received_at' => $parsed['date'],
                'parsed_emails' => $parsed['parsed_emails'], // Array untuk unlimited threading
            ];
            
            $ticket = $ticketService->createTicket($data);
            
            $this->info("✅ Ticket created successfully!");
            $this->info("Ticket ID: {$ticket->id}");
            $this->info("Ticket Number: {$ticket->ticket_number}");
            
        } catch (\Exception $e) {
            $this->error('❌ Error creating ticket:');
            $this->error($e->getMessage());
        }
    }
    
    protected function getSampleEmail(int $number): array
    {
        $samples = [
            1 => [
                'description' => 'Email thread dengan 5 balasan (User -> Admin -> User -> Admin -> Resolution)',
                'content' => $this->getSample1(),
            ],
            2 => [
                'description' => 'Email thread sederhana dengan 3 balasan',
                'content' => $this->getSample2(),
            ],
            3 => [
                'description' => 'Email thread panjang dengan 8 balasan',
                'content' => $this->getSample3(),
            ],
        ];
        
        return $samples[$number] ?? $samples[1];
    }
    
    protected function getSample1(): string
    {
        return <<<EMAIL
From: user@bankmega.co.id
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
To: user@bankmega.co.id
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

From: user@bankmega.co.id
To: itsupport@bankmega.co.id
Subject: Re: [URGENT] Sistem CBS Error - Transaksi Gagal
Date: Mon, 4 Nov 2024 10:00:00 +0700

Pak Budi,

Sudah 30 menit tapi masih belum bisa juga. Nasabah sudah mulai komplain. Ada update?

Regards,
Ahmad

---

From: itsupport@bankmega.co.id
To: user@bankmega.co.id
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
To: user@bankmega.co.id
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
    
    protected function getSample2(): string
    {
        return <<<EMAIL
From: cabang.bandung@bankmega.co.id
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
To: cabang.bandung@bankmega.co.id
Subject: Re: Printer Not Working
Date: Tue, 5 Nov 2024 14:15:00 +0700

Halo Bu Siti,

Apakah ada error message yang muncul? Dan sudah dicek kabel networknya?

Regards,
IT Support

---

From: itsupport@bankmega.co.id
To: cabang.bandung@bankmega.co.id
Subject: Re: [RESOLVED] Printer Not Working
Date: Tue, 5 Nov 2024 14:30:00 +0700

Bu Siti,

Printer sudah kami remote dan ternyata driver perlu diupdate. Sudah kami fix dan test print berhasil.

Silakan dicoba kembali.

Best regards,
IT Support Team
EMAIL;
    }
    
    protected function getSample3(): string
    {
        return <<<EMAIL
From: user.komplain@bankmega.co.id
To: itsupport@bankmega.co.id
Subject: Email Server Slow
Date: Wed, 6 Nov 2024 08:00:00 +0700

Tim IT,

Email server sangat lambat hari ini. Butuh 5 menit untuk kirim email.

Mohon dicek.

Agus Wijaya

---

From: itsupport@bankmega.co.id
To: user.komplain@bankmega.co.id
Subject: Re: Email Server Slow
Date: Wed, 6 Nov 2024 08:15:00 +0700

Pak Agus,

Kami akan investigasi. Bisakah info kantor cabang mana?

Regards,
IT Support

---

From: user.komplain@bankmega.co.id
To: itsupport@bankmega.co.id
Subject: Re: Email Server Slow
Date: Wed, 6 Nov 2024 08:20:00 +0700

Cabang Surabaya.

---

From: itsupport@bankmega.co.id
To: user.komplain@bankmega.co.id
Subject: Re: Email Server Slow
Date: Wed, 6 Nov 2024 08:30:00 +0700

Terima kasih. Sedang kami cek.

---

From: user.komplain@bankmega.co.id
To: itsupport@bankmega.co.id
Subject: Re: Email Server Slow
Date: Wed, 6 Nov 2024 09:00:00 +0700

Update dong, masih lambat nih.

---

From: itsupport@bankmega.co.id
To: user.komplain@bankmega.co.id
Subject: Re: Email Server Slow
Date: Wed, 6 Nov 2024 09:15:00 +0700

Pak Agus, kami menemukan ada high CPU usage di email server. Sedang kami restart service nya.

---

From: user.komplain@bankmega.co.id
To: itsupport@bankmega.co.id
Subject: Re: Email Server Slow
Date: Wed, 6 Nov 2024 09:30:00 +0700

OK thanks, saya tunggu.

---

From: itsupport@bankmega.co.id
To: user.komplain@bankmega.co.id
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
