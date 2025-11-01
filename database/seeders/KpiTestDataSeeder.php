<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Ticket;
use App\Models\TicketThread;
use App\Models\User;
use Carbon\Carbon;

class KpiTestDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->command->info('🎯 Generating KPI Test Data...');

        // Get or create test users
        $admin = User::where('role', 'admin')->first();
        $user = User::where('role', 'user')->first();

        if (!$admin || !$user) {
            $this->command->error('❌ Admin or User not found. Please run UserSeeder first.');
            return;
        }

        // Clear existing test tickets
        $this->command->info('🧹 Cleaning old test data...');
        Ticket::where('subject', 'LIKE', '[TEST KPI]%')->delete();

        $ticketNumber = 1;

        // ========================================
        // SCENARIO 1: Excellent Performance ✅
        // ========================================
        $this->command->info("\n📊 Creating Scenario 1: Excellent Performance");
        
        $ticket1 = $this->createTicket([
            'number' => $ticketNumber++,
            'user_id' => $user->id,
            'subject' => '[TEST KPI] Laptop tidak bisa connect WiFi',
            'description' => 'Mohon bantuan, laptop saya tidak bisa connect ke WiFi kantor.',
            'category' => 'Technical',
            'priority' => 'high',
            'channel' => 'email',
            'email_received_at' => Carbon::now()->subHours(2), // 2 jam lalu
            'created_at' => Carbon::now()->subHours(2)->addMinutes(5), // +5 menit (delay 5 menit)
            'first_response_at' => Carbon::now()->subHours(2)->addMinutes(20), // +20 menit (dalam SLA)
            'resolved_at' => Carbon::now()->subMinutes(30), // +1.5 jam (dalam SLA)
            'status' => 'resolved',
        ]);
        
        $this->addThreads($ticket1, $admin);
        $this->command->line("  ✅ #{$ticket1->ticket_number} - Response: 15 min | Resolution: 1.5 hours");

        // ========================================
        // SCENARIO 2: Good Response, Delayed Resolution ⚠️
        // ========================================
        $this->command->info("\n📊 Creating Scenario 2: Good Response, Delayed Resolution");
        
        $ticket2 = $this->createTicket([
            'number' => $ticketNumber++,
            'user_id' => $user->id,
            'subject' => '[TEST KPI] Printer tidak bisa print warna',
            'description' => 'Printer hanya bisa print hitam putih, warna tidak keluar.',
            'category' => 'Technical',
            'priority' => 'medium',
            'channel' => 'email',
            'email_received_at' => Carbon::now()->subDays(5), // 5 hari lalu
            'created_at' => Carbon::now()->subDays(5)->addMinutes(10),
            'first_response_at' => Carbon::now()->subDays(5)->addMinutes(25), // Dalam SLA response
            'resolved_at' => Carbon::now()->subDays(2), // 3 hari untuk resolve (melebihi 48 jam)
            'status' => 'resolved',
        ]);
        
        $this->addThreads($ticket2, $admin);
        $this->command->line("  ⚠️  #{$ticket2->ticket_number} - Response: 15 min ✅ | Resolution: 72 hours ❌");

        // ========================================
        // SCENARIO 3: Slow Response ❌
        // ========================================
        $this->command->info("\n📊 Creating Scenario 3: Slow Response");
        
        $ticket3 = $this->createTicket([
            'number' => $ticketNumber++,
            'user_id' => $user->id,
            'subject' => '[TEST KPI] Akun email tidak bisa login',
            'description' => 'Saya tidak bisa login ke email kantor sejak pagi.',
            'category' => 'Technical',
            'priority' => 'critical',
            'channel' => 'email',
            'email_received_at' => Carbon::now()->subHours(3),
            'created_at' => Carbon::now()->subHours(3)->addMinutes(15),
            'first_response_at' => Carbon::now()->subHours(1), // 2 jam response (melebihi SLA)
            'resolved_at' => Carbon::now()->subMinutes(10),
            'status' => 'resolved',
        ]);
        
        $this->addThreads($ticket3, $admin);
        $this->command->line("  ❌ #{$ticket3->ticket_number} - Response: 2 hours ❌ | Resolution: 2.8 hours ✅");

        // ========================================
        // SCENARIO 4: Severe Delay (Kasus Real User) 🔴
        // ========================================
        $this->command->info("\n📊 Creating Scenario 4: Severe Delay (Real Case)");
        
        $ticket4 = $this->createTicket([
            'number' => $ticketNumber++,
            'user_id' => $user->id,
            'subject' => '[TEST KPI] Request akses database',
            'description' => 'Mohon dibuatkan akses ke database production.',
            'category' => 'General',
            'priority' => 'medium',
            'channel' => 'email',
            'email_received_at' => Carbon::parse('2025-10-21 13:00:00'), // Email diterima 21 Okt
            'created_at' => Carbon::parse('2025-10-25 10:00:00'), // Ticket dibuat 25 Okt (delay 94 jam!)
            'first_response_at' => Carbon::parse('2025-10-25 10:15:00'), // Response 15 menit dari ticket dibuat
            'resolved_at' => Carbon::parse('2025-10-27 10:00:00'), // Resolved 27 Okt
            'status' => 'resolved',
        ]);
        
        $this->addThreads($ticket4, $admin);
        $this->command->line("  🔴 #{$ticket4->ticket_number} - Creation Delay: 94 hours | Response: 144 hours | Resolution: 192 hours");

        // ========================================
        // SCENARIO 5: WhatsApp (No Email Tracking) 💬
        // ========================================
        $this->command->info("\n📊 Creating Scenario 5: WhatsApp Channel (No Email)");
        
        $ticket5 = $this->createTicket([
            'number' => $ticketNumber++,
            'user_id' => $user->id,
            'subject' => '[TEST KPI] Mouse wireless tidak connect',
            'description' => 'Mouse wireless saya tidak bisa connect ke laptop.',
            'category' => 'Technical',
            'priority' => 'low',
            'channel' => 'whatsapp',
            'email_received_at' => null, // Tidak ada tracking email
            'created_at' => Carbon::now()->subMinutes(90),
            'first_response_at' => Carbon::now()->subMinutes(85), // 5 menit
            'resolved_at' => Carbon::now()->subMinutes(30), // 60 menit total
            'status' => 'resolved',
        ]);
        
        $this->addThreads($ticket5, $admin);
        $this->command->line("  💬 #{$ticket5->ticket_number} - Response: 5 min ✅ | Resolution: 60 min ✅");

        // ========================================
        // SCENARIO 6: Still Open (No Response Yet) 🕐
        // ========================================
        $this->command->info("\n📊 Creating Scenario 6: Open Ticket (No Response)");
        
        $ticket6 = $this->createTicket([
            'number' => $ticketNumber++,
            'user_id' => $user->id,
            'subject' => '[TEST KPI] Layar monitor berkedip',
            'description' => 'Monitor saya berkedip-kedip terus.',
            'category' => 'Technical',
            'priority' => 'medium',
            'channel' => 'email',
            'email_received_at' => Carbon::now()->subMinutes(45),
            'created_at' => Carbon::now()->subMinutes(40),
            'first_response_at' => null, // Belum ada response
            'resolved_at' => null,
            'status' => 'pending_review',
        ]);
        
        $this->command->line("  🕐 #{$ticket6->ticket_number} - Waiting for response (45 min elapsed) ⚠️");

        // ========================================
        // SCENARIO 7: Responded but Not Resolved Yet 🔄
        // ========================================
        $this->command->info("\n📊 Creating Scenario 7: In Progress");
        
        $ticket7 = $this->createTicket([
            'number' => $ticketNumber++,
            'user_id' => $user->id,
            'subject' => '[TEST KPI] Instalasi software AutoCAD',
            'description' => 'Mohon bantuan instalasi AutoCAD di laptop saya.',
            'category' => 'Technical',
            'priority' => 'high',
            'channel' => 'email',
            'email_received_at' => Carbon::now()->subHours(24),
            'created_at' => Carbon::now()->subHours(24)->addMinutes(10),
            'first_response_at' => Carbon::now()->subHours(24)->addMinutes(20), // Response dalam SLA
            'resolved_at' => null, // Belum resolved
            'status' => 'in_progress',
        ]);
        
        $this->addThreads($ticket7, $admin);
        $this->command->line("  🔄 #{$ticket7->ticket_number} - Response: 10 min ✅ | In progress for 24 hours");

        // ========================================
        // SCENARIO 8: Multiple Categories
        // ========================================
        $this->command->info("\n📊 Creating Scenario 8: Various Categories");
        
        $categories = [
            ['Billing', 'Tagihan internet terlalu mahal', 'low'],
            ['General', 'Request cuti tahunan', 'low'],
            ['Technical', 'VPN tidak bisa connect', 'critical'],
        ];

        foreach ($categories as $idx => $cat) {
            $ticket = $this->createTicket([
                'number' => $ticketNumber++,
                'user_id' => $user->id,
                'subject' => "[TEST KPI] {$cat[1]}",
                'description' => "Deskripsi untuk {$cat[1]}",
                'category' => $cat[0],
                'priority' => $cat[2],
                'channel' => 'portal',
                'email_received_at' => null,
                'created_at' => Carbon::now()->subHours(rand(1, 10)),
                'first_response_at' => Carbon::now()->subHours(rand(1, 5)),
                'resolved_at' => rand(0, 1) ? Carbon::now()->subMinutes(rand(10, 60)) : null,
                'status' => rand(0, 1) ? 'resolved' : 'in_progress',
            ]);
            
            $this->addThreads($ticket, $admin);
            $this->command->line("  📁 #{$ticket->ticket_number} - Category: {$cat[0]}");
        }

        // ========================================
        // Summary
        // ========================================
        $this->command->info("\n" . str_repeat('=', 60));
        $this->command->info('✅ KPI Test Data Generation Complete!');
        $this->command->info(str_repeat('=', 60));
        
        $total = Ticket::where('subject', 'LIKE', '[TEST KPI]%')->count();
        $resolved = Ticket::where('subject', 'LIKE', '[TEST KPI]%')->where('status', 'resolved')->count();
        $inProgress = Ticket::where('subject', 'LIKE', '[TEST KPI]%')->whereIn('status', ['in_progress', 'pending_review'])->count();
        
        $this->command->table(
            ['Metric', 'Count'],
            [
                ['Total Tickets', $total],
                ['Resolved', $resolved],
                ['In Progress', $inProgress],
                ['With Email Tracking', Ticket::where('subject', 'LIKE', '[TEST KPI]%')->whereNotNull('email_received_at')->count()],
            ]
        );
        
        $this->command->info("\n📊 View KPI Dashboard: http://localhost:8000/admin/kpi");
        $this->command->info("🔍 Filter by '[TEST KPI]' to see only test data\n");
    }

    /**
     * Create ticket with KPI data
     */
    private function createTicket(array $data): Ticket
    {
        $ticket = Ticket::create([
            'ticket_number' => sprintf('TKT-%s-%04d', now()->format('YmdHis'), $data['number']),
            'user_id' => $data['user_id'],
            'user_name' => User::find($data['user_id'])->name,
            'user_email' => User::find($data['user_id'])->email,
            'user_phone' => '081234567890',
            
            'reporter_nip' => '123456789',
            'reporter_name' => 'Reporter Test',
            'reporter_department' => 'IT Department',
            
            'channel' => $data['channel'],
            'input_method' => $data['channel'] === 'email' ? 'email' : 'manual',
            'subject' => $data['subject'],
            'description' => $data['description'],
            'category' => $data['category'],
            'priority' => $data['priority'],
            'status' => $data['status'],
            
            // KPI Fields
            'email_received_at' => $data['email_received_at'],
            'first_response_at' => $data['first_response_at'],
            'resolved_at' => $data['resolved_at'],
            
            'created_at' => $data['created_at'],
            'updated_at' => $data['resolved_at'] ?? $data['created_at'],
        ]);

        // Calculate KPI metrics
        if ($ticket->email_received_at) {
            $ticket->calculateTicketCreationDelay();
        }
        
        if ($ticket->first_response_at) {
            $ticket->calculateResponseTime();
        }
        
        if ($ticket->resolved_at) {
            $ticket->calculateResolutionTime();
        }
        
        $ticket->saveQuietly(); // Save without triggering events

        return $ticket;
    }

    /**
     * Add thread messages to ticket
     */
    private function addThreads(Ticket $ticket, User $admin): void
    {
        // Initial complaint
        TicketThread::create([
            'ticket_id' => $ticket->id,
            'sender_type' => 'user',
            'sender_name' => $ticket->user_name,
            'message_type' => 'complaint',
            'message' => $ticket->description,
            'created_at' => $ticket->created_at,
        ]);

        // Admin response (if exists)
        if ($ticket->first_response_at) {
            TicketThread::create([
                'ticket_id' => $ticket->id,
                'sender_type' => 'admin',
                'sender_name' => $admin->name,
                'message_type' => 'reply',
                'message' => 'Terima kasih atas laporannya. Kami sedang menangani keluhan Anda.',
                'created_at' => $ticket->first_response_at,
            ]);
        }

        // Resolution message (if resolved)
        if ($ticket->resolved_at) {
            TicketThread::create([
                'ticket_id' => $ticket->id,
                'sender_type' => 'admin',
                'sender_name' => $admin->name,
                'message_type' => 'resolution',
                'message' => 'Masalah telah diselesaikan. Silakan cek kembali.',
                'created_at' => $ticket->resolved_at,
            ]);
        }
    }
}
