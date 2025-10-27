<?php

/**
 * TESTING SCRIPT - TICKET DATA
 * Script untuk testing manual input data ticket
 * 
 * CARA PAKAI:
 * 1. Buka browser: http://localhost:8000/test-ticket-data.php
 * 2. Atau run via CLI: php test-ticket-data.php
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use App\Models\Ticket;
use App\Services\TicketService;
use App\Services\ValidationService;

echo "==============================================\n";
echo "     TESTING TICKET DATA - VALID vs INVALID   \n";
echo "==============================================\n\n";

$ticketService = app(TicketService::class);
$validationService = app(ValidationService::class);

// ========================================
// 1. DATA VALID - Lengkap dan Benar
// ========================================
echo "📝 TEST 1: DATA VALID (Lengkap & Benar)\n";
echo "==========================================\n";

$validData = [
    // Data Pelapor (Reporter)
    'reporter_nip' => '198501012020',
    'reporter_name' => 'Budi Santoso',
    'reporter_email' => 'budi.santoso@company.com',
    'reporter_phone' => '081234567890',
    'reporter_department' => 'IT Support',
    'reporter_position' => 'Staff IT',
    
    // Data User (Pembuat Ticket - jika via admin)
    'user_id' => null, // External user
    'user_name' => 'Admin ITSO',
    'user_email' => 'admin@itso.com',
    'user_phone' => '081234567899',
    
    // Data Ticket
    'subject' => 'Laptop tidak bisa connect ke WiFi',
    'description' => 'Laptop saya tiba-tiba tidak bisa connect ke WiFi kantor. Sudah coba restart laptop dan router tetapi tetap tidak bisa. Password sudah benar. Error message: "Can\'t connect to this network"',
    'category' => 'Technical',
    'priority' => 'high',
    
    // Channel & Input Method
    'channel' => 'portal',
    'input_method' => 'manual',
    'original_message' => null,
    
    // Admin created
    'created_by_admin' => true,
];

echo "DATA INPUT:\n";
echo json_encode($validData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
echo "\n\n";

try {
    echo "🔄 Creating ticket...\n";
    $ticket1 = $ticketService->createTicketByAdmin($validData);
    
    echo "✅ SUCCESS! Ticket Created:\n";
    echo "   - Ticket Number: {$ticket1->ticket_number}\n";
    echo "   - Status: {$ticket1->status}\n";
    echo "   - Reporter: {$ticket1->reporter_name} (NIP: {$ticket1->reporter_nip})\n";
    echo "   - Subject: {$ticket1->subject}\n";
    echo "   - Priority: {$ticket1->priority}\n";
    echo "   - Channel: {$ticket1->channel}\n";
    
    // Test Validation
    echo "\n🔍 Running validation...\n";
    $validation = $validationService->validate($ticket1);
    if ($validation['valid']) {
        echo "✅ VALIDATION PASSED\n";
    } else {
        echo "❌ VALIDATION FAILED: {$validation['reason']}\n";
    }
    
} catch (\Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}

echo "\n\n";

// ========================================
// 2. DATA INVALID - Email Format Salah
// ========================================
echo "📝 TEST 2: DATA INVALID - Email Format Salah\n";
echo "==============================================\n";

$invalidData1 = [
    'reporter_nip' => '198501012021',
    'reporter_name' => 'Siti Nurhaliza',
    'reporter_email' => 'siti.nurhaliza@invalid', // ❌ Email format salah
    'reporter_phone' => '081234567891',
    'reporter_department' => 'Finance',
    
    'user_id' => null,
    'user_name' => 'Admin ITSO',
    'user_email' => 'admin@itso.com',
    'user_phone' => '081234567899',
    
    'subject' => 'Request akses sistem',
    'description' => 'Mohon bantuan untuk akses ke sistem SAP karena saya tidak bisa login',
    'category' => 'Access Request',
    'priority' => 'medium',
    
    'channel' => 'email',
    'input_method' => 'email',
    'created_by_admin' => true,
];

echo "DATA INPUT:\n";
echo json_encode($invalidData1, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
echo "\n\n";

try {
    echo "🔄 Creating ticket...\n";
    $ticket2 = $ticketService->createTicketByAdmin($invalidData1);
    
    echo "⚠️  Ticket created (validation will catch this):\n";
    echo "   - Ticket Number: {$ticket2->ticket_number}\n";
    
    // Test Validation
    echo "\n🔍 Running validation...\n";
    $validation = $validationService->validate($ticket2);
    if ($validation['valid']) {
        echo "✅ VALIDATION PASSED\n";
    } else {
        echo "❌ VALIDATION FAILED: {$validation['reason']}\n";
    }
    
} catch (\Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}

echo "\n\n";

// ========================================
// 3. DATA INVALID - Data Tidak Lengkap
// ========================================
echo "📝 TEST 3: DATA INVALID - Data Tidak Lengkap\n";
echo "==============================================\n";

$invalidData2 = [
    'reporter_nip' => '198501012022',
    'reporter_name' => '', // ❌ Nama kosong
    'reporter_email' => 'john.doe@company.com',
    'reporter_phone' => '081234567892',
    'reporter_department' => 'HR',
    
    'user_id' => null,
    'user_name' => 'Admin ITSO',
    'user_email' => 'admin@itso.com',
    'user_phone' => '081234567899',
    
    'subject' => 'Test', // ❌ Subject terlalu pendek
    'description' => 'Short', // ❌ Description terlalu pendek
    'category' => 'General',
    'priority' => 'low',
    
    'channel' => 'portal',
    'input_method' => 'manual',
    'created_by_admin' => true,
];

echo "DATA INPUT:\n";
echo json_encode($invalidData2, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
echo "\n\n";

try {
    echo "🔄 Creating ticket...\n";
    $ticket3 = $ticketService->createTicketByAdmin($invalidData2);
    
    echo "⚠️  Ticket created (validation will catch this):\n";
    echo "   - Ticket Number: {$ticket3->ticket_number}\n";
    
    // Test Validation
    echo "\n🔍 Running validation...\n";
    $validation = $validationService->validate($ticket3);
    if ($validation['valid']) {
        echo "✅ VALIDATION PASSED\n";
    } else {
        echo "❌ VALIDATION FAILED: {$validation['reason']}\n";
    }
    
} catch (\Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}

echo "\n\n";

// ========================================
// 4. DATA INVALID - Phone Format Salah
// ========================================
echo "📝 TEST 4: DATA INVALID - Nomor Telepon Terlalu Pendek\n";
echo "=========================================================\n";

$invalidData3 = [
    'reporter_nip' => '198501012023',
    'reporter_name' => 'Ahmad Yani',
    'reporter_email' => 'ahmad.yani@company.com',
    'reporter_phone' => '0812345', // ❌ Nomor terlalu pendek
    'reporter_department' => 'Operations',
    
    'user_id' => null,
    'user_name' => 'Admin ITSO',
    'user_email' => 'admin@itso.com',
    'user_phone' => '081234567899',
    
    'subject' => 'Printer tidak berfungsi dengan baik',
    'description' => 'Printer di lantai 3 tidak bisa print berwarna, hanya bisa print hitam putih saja.',
    'category' => 'Hardware',
    'priority' => 'medium',
    
    'channel' => 'call',
    'input_method' => 'manual',
    'created_by_admin' => true,
];

echo "DATA INPUT:\n";
echo json_encode($invalidData3, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
echo "\n\n";

try {
    echo "🔄 Creating ticket...\n";
    // This should fail at validation level
    $ticket4 = $ticketService->createTicketByAdmin($invalidData3);
    
    echo "⚠️  Ticket created (but phone will be normalized):\n";
    echo "   - Ticket Number: {$ticket4->ticket_number}\n";
    echo "   - Reporter Phone: {$ticket4->reporter_phone}\n";
    
} catch (\Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}

echo "\n\n";

// ========================================
// 5. DATA INVALID - Spam Detection
// ========================================
echo "📝 TEST 5: DATA INVALID - Spam Pattern\n";
echo "========================================\n";

$invalidData4 = [
    'reporter_nip' => '198501012024',
    'reporter_name' => 'Test User',
    'reporter_email' => 'test@test.com',
    'reporter_phone' => '081234567894',
    'reporter_department' => 'Testing',
    
    'user_id' => null,
    'user_name' => 'Admin ITSO',
    'user_email' => 'admin@itso.com',
    'user_phone' => '081234567899',
    
    'subject' => 'test test test', // ❌ Spam keyword
    'description' => 'testing aaaa xxxx testing testing', // ❌ Spam pattern
    'category' => 'General',
    'priority' => 'low',
    
    'channel' => 'portal',
    'input_method' => 'manual',
    'created_by_admin' => true,
];

echo "DATA INPUT:\n";
echo json_encode($invalidData4, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
echo "\n\n";

try {
    echo "🔄 Creating ticket...\n";
    $ticket5 = $ticketService->createTicketByAdmin($invalidData4);
    
    echo "⚠️  Ticket created:\n";
    echo "   - Ticket Number: {$ticket5->ticket_number}\n";
    
    // Test Validation
    echo "\n🔍 Running validation...\n";
    $validation = $validationService->validate($ticket5);
    if ($validation['valid']) {
        echo "✅ VALIDATION PASSED\n";
    } else {
        echo "❌ VALIDATION FAILED: {$validation['reason']}\n";
    }
    
} catch (\Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}

echo "\n\n";

// ========================================
// SUMMARY
// ========================================
echo "==============================================\n";
echo "                  SUMMARY                      \n";
echo "==============================================\n";
echo "Total Tickets Created: " . Ticket::count() . "\n";
echo "Valid Tickets: " . Ticket::where('status', '!=', 'rejected')->count() . "\n";
echo "Rejected Tickets: " . Ticket::where('status', 'rejected')->count() . "\n";
echo "\n";

echo "✅ Testing selesai!\n";
echo "Silakan cek database atau dashboard admin untuk melihat hasilnya.\n\n";
