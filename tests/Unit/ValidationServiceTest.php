<?php

use App\Models\Ticket;
use App\Services\ValidationService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->validationService = new ValidationService();
});

test('validates ticket with complete valid data', function () {
    $ticket = Ticket::factory()->create([
        'user_name' => 'John Doe',
        'user_email' => 'john@example.com',
        'subject' => 'Need Help',
        'description' => 'This is a detailed description of my issue that is longer than 10 characters',
        'input_method' => 'manual',
    ]);

    $result = $this->validationService->validate($ticket);

    expect($result)->toBeArray()
        ->and($result['valid'])->toBeTrue();
});

test('bypasses validation for email auto fetch tickets', function () {
    $ticket = Ticket::factory()->create([
        'input_method' => 'email_auto_fetch',
        'user_name' => '',
        'user_email' => '',
        'subject' => '',
        'description' => '',
    ]);

    $result = $this->validationService->validate($ticket);

    expect($result)->toBeArray()
        ->and($result['valid'])->toBeTrue();
});

test('fails validation when user name is missing', function () {
    $ticket = Ticket::factory()->create([
        'user_name' => '',
        'user_email' => 'john@example.com',
        'subject' => 'Need Help',
        'description' => 'This is a detailed description',
        'input_method' => 'manual',
    ]);

    $result = $this->validationService->validate($ticket);

    expect($result['valid'])->toBeFalse()
        ->and($result['reason'])->toContain('Data tidak lengkap');
});

test('fails validation when user email is missing', function () {
    $ticket = Ticket::factory()->create([
        'user_name' => 'John Doe',
        'user_email' => '',
        'subject' => 'Need Help',
        'description' => 'This is a detailed description',
        'input_method' => 'manual',
    ]);

    $result = $this->validationService->validate($ticket);

    expect($result['valid'])->toBeFalse()
        ->and($result['reason'])->toContain('Data tidak lengkap');
});

test('fails validation when subject is missing', function () {
    $ticket = Ticket::factory()->create([
        'user_name' => 'John Doe',
        'user_email' => 'john@example.com',
        'subject' => '',
        'description' => 'This is a detailed description',
        'input_method' => 'manual',
    ]);

    $result = $this->validationService->validate($ticket);

    expect($result['valid'])->toBeFalse()
        ->and($result['reason'])->toContain('Data tidak lengkap');
});

test('fails validation when description is too short', function () {
    $ticket = Ticket::factory()->create([
        'user_name' => 'John Doe',
        'user_email' => 'john@example.com',
        'subject' => 'Need Help',
        'description' => 'Short',
        'input_method' => 'manual',
    ]);

    $result = $this->validationService->validate($ticket);

    expect($result['valid'])->toBeFalse()
        ->and($result['reason'])->toContain('Data tidak lengkap');
});

test('fails validation for invalid email format', function () {
    $ticket = Ticket::factory()->create([
        'user_name' => 'John Doe',
        'user_email' => 'invalid-email',
        'subject' => 'Need Help',
        'description' => 'This is a detailed description',
        'input_method' => 'manual',
    ]);

    $result = $this->validationService->validate($ticket);

    expect($result['valid'])->toBeFalse()
        ->and($result['reason'])->toContain('Format email tidak valid');
});

test('detects duplicate tickets within 48 hours', function () {
    // Create first ticket
    $firstTicket = Ticket::factory()->create([
        'user_name' => 'John Doe',
        'user_email' => 'john@example.com',
        'subject' => 'Internet Connection Problem',
        'description' => 'My internet is not working properly',
        'input_method' => 'manual',
        'created_at' => now()->subHours(24),
    ]);

    // Create duplicate ticket
    $duplicateTicket = Ticket::factory()->create([
        'user_name' => 'John Doe',
        'user_email' => 'john@example.com',
        'subject' => 'Internet Connection Problem',
        'description' => 'My internet is not working at all',
        'input_method' => 'manual',
    ]);

    $result = $this->validationService->validate($duplicateTicket);

    expect($result['valid'])->toBeFalse()
        ->and($result['reason'])->toContain('ticket serupa')
        ->and($result['reason'])->toContain($firstTicket->ticket_number);
});

test('allows similar tickets after 48 hours', function () {
    // Create old ticket
    Ticket::factory()->create([
        'user_name' => 'John Doe',
        'user_email' => 'john@example.com',
        'subject' => 'Internet Connection Problem',
        'description' => 'My internet is not working properly',
        'input_method' => 'manual',
        'created_at' => now()->subHours(49),
    ]);

    // Create new ticket
    $newTicket = Ticket::factory()->create([
        'user_name' => 'John Doe',
        'user_email' => 'john@example.com',
        'subject' => 'Internet Connection Problem',
        'description' => 'My internet is not working at all',
        'input_method' => 'manual',
    ]);

    $result = $this->validationService->validate($newTicket);

    expect($result['valid'])->toBeTrue();
});

test('detects spam with test keyword and short description', function () {
    $ticket = Ticket::factory()->create([
        'user_name' => 'John Doe',
        'user_email' => 'john@example.com',
        'subject' => 'Test ticket',
        'description' => 'Just testing',
        'input_method' => 'manual',
    ]);

    $result = $this->validationService->validate($ticket);

    expect($result['valid'])->toBeFalse()
        ->and($result['reason'])->toContain('spam');
});

test('allows test keyword with long description', function () {
    $ticket = Ticket::factory()->create([
        'user_name' => 'John Doe',
        'user_email' => 'john@example.com',
        'subject' => 'Test result issue',
        'description' => 'I am having issues with the test results showing incorrect data on the dashboard',
        'input_method' => 'manual',
    ]);

    $result = $this->validationService->validate($ticket);

    expect($result['valid'])->toBeTrue();
});

test('detects spam with multiple spam keywords', function () {
    $spamKeywords = ['aaaa', 'xxxx', 'testing'];
    
    foreach ($spamKeywords as $keyword) {
        $ticket = Ticket::factory()->create([
            'user_name' => 'Spam User',
            'user_email' => 'spam@example.com',
            'subject' => $keyword,
            'description' => 'Short spam ' . $keyword,
            'input_method' => 'manual',
        ]);

        $result = $this->validationService->validate($ticket);

        expect($result['valid'])->toBeFalse()
            ->and($result['reason'])->toContain('spam');
    }
});

test('allows different email addresses with same subject', function () {
    // Create first ticket
    Ticket::factory()->create([
        'user_name' => 'John Doe',
        'user_email' => 'john@example.com',
        'subject' => 'Password Reset',
        'description' => 'I need to reset my password',
        'input_method' => 'manual',
        'created_at' => now()->subHours(1),
    ]);

    // Create ticket with different email
    $newTicket = Ticket::factory()->create([
        'user_name' => 'Jane Smith',
        'user_email' => 'jane@example.com',
        'subject' => 'Password Reset',
        'description' => 'I need to reset my password',
        'input_method' => 'manual',
    ]);

    $result = $this->validationService->validate($newTicket);

    expect($result['valid'])->toBeTrue();
});

test('description minimum length is exactly 10 characters', function () {
    // 9 characters - should fail
    $shortTicket = Ticket::factory()->create([
        'user_name' => 'John Doe',
        'user_email' => 'john@example.com',
        'subject' => 'Help',
        'description' => '123456789',
        'input_method' => 'manual',
    ]);

    $shortResult = $this->validationService->validate($shortTicket);
    expect($shortResult['valid'])->toBeFalse();

    // 10 characters - should pass
    $validTicket = Ticket::factory()->create([
        'user_name' => 'John Doe',
        'user_email' => 'john2@example.com',
        'subject' => 'Help',
        'description' => '1234567890',
        'input_method' => 'manual',
    ]);

    $validResult = $this->validationService->validate($validTicket);
    expect($validResult['valid'])->toBeTrue();
});
