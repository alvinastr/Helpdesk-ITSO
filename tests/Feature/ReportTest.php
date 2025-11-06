<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Ticket;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Tests\TestCase;
use Carbon\Carbon;

class ReportTest extends TestCase
{
    use RefreshDatabase, WithoutMiddleware;

    /** @test */
    public function admin_can_access_reports_page()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        
        $response = $this->actingAs($admin)->get('/admin/reports');
        $response->assertStatus(200);
        $response->assertViewIs('reports.index');
    }

    /** @test */
    public function regular_user_cannot_access_reports()
    {
        $user = User::factory()->create(['role' => 'user']);
        
        $response = $this->actingAs($user)->get('/admin/reports');
        $response->assertStatus(403);
    }

    /** @test */
    public function reports_show_default_30_days_data()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        
        // Create tickets in last 30 days
        Ticket::factory()->count(5)->create([
            'created_at' => Carbon::now()->subDays(10)
        ]);
        
        $response = $this->actingAs($admin)->get('/admin/reports');
        
        $response->assertStatus(200);
        $response->assertViewHas('data');
    }

    /** @test */
    public function reports_can_filter_by_date_range()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        
        $startDate = Carbon::now()->subDays(7)->format('Y-m-d');
        $endDate = Carbon::now()->format('Y-m-d');
        
        $response = $this->actingAs($admin)->get('/admin/reports', [
            'start_date' => $startDate,
            'end_date' => $endDate
        ]);
        
        $response->assertStatus(200);
        $response->assertViewHas(['startDate', 'endDate']);
    }

    /** @test */
    public function reports_show_total_tickets()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        
        Ticket::factory()->count(10)->create([
            'created_at' => Carbon::now()->subDays(5)
        ]);
        
        $response = $this->actingAs($admin)->get('/admin/reports');
        
        $response->assertStatus(200);
        $data = $response->viewData('data');
        $this->assertArrayHasKey('total_tickets', $data);
    }

    /** @test */
    public function reports_show_tickets_by_status()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        
        Ticket::factory()->create(['status' => 'open']);
        Ticket::factory()->create(['status' => 'closed']);
        Ticket::factory()->create(['status' => 'resolved']);
        
        $response = $this->actingAs($admin)->get('/admin/reports');
        
        $response->assertStatus(200);
        $data = $response->viewData('data');
        $this->assertArrayHasKey('by_status', $data);
    }

    /** @test */
    public function reports_show_tickets_by_category()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        
        Ticket::factory()->create(['category' => 'technical']);
        Ticket::factory()->create(['category' => 'general']);
        Ticket::factory()->create(['category' => 'billing']);
        
        $response = $this->actingAs($admin)->get('/admin/reports');
        
        $response->assertStatus(200);
        $data = $response->viewData('data');
        $this->assertArrayHasKey('by_category', $data);
    }

    /** @test */
    public function reports_show_tickets_by_channel()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        
        Ticket::factory()->create(['channel' => 'email']);
        Ticket::factory()->create(['channel' => 'portal']);
        Ticket::factory()->create(['channel' => 'whatsapp']);
        
        $response = $this->actingAs($admin)->get('/admin/reports');
        
        $response->assertStatus(200);
        $data = $response->viewData('data');
        $this->assertArrayHasKey('by_channel', $data);
    }

    /** @test */
    public function reports_show_tickets_by_priority()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        
        Ticket::factory()->create(['priority' => 'high']);
        Ticket::factory()->create(['priority' => 'medium']);
        Ticket::factory()->create(['priority' => 'low']);
        
        $response = $this->actingAs($admin)->get('/admin/reports');
        
        $response->assertStatus(200);
        $data = $response->viewData('data');
        $this->assertArrayHasKey('by_priority', $data);
    }

    /** @test */
    public function admin_can_export_report_to_excel()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        
        Ticket::factory()->count(5)->create();
        
        $response = $this->actingAs($admin)->get('/admin/reports/export-excel', [
            'start_date' => Carbon::now()->subDays(30)->format('Y-m-d'),
            'end_date' => Carbon::now()->format('Y-m-d')
        ]);
        
        // Should return JSON message or download
        $response->assertStatus(200);
    }

    /** @test */
    public function admin_can_export_report_to_pdf()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        
        Ticket::factory()->count(5)->create();
        
        $response = $this->actingAs($admin)->get('/admin/reports/export-pdf', [
            'start_date' => Carbon::now()->subDays(30)->format('Y-m-d'),
            'end_date' => Carbon::now()->format('Y-m-d')
        ]);
        
        // Should return JSON message or download
        $response->assertStatus(200);
    }

    /** @test */
    public function reports_handle_empty_data_gracefully()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        
        // No tickets created
        $response = $this->actingAs($admin)->get('/admin/reports');
        
        $response->assertStatus(200);
        $data = $response->viewData('data');
        $this->assertEquals(0, $data['total_tickets']);
    }

    /** @test */
    public function reports_show_correct_date_range()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        
        $startDate = '2024-01-01';
        $endDate = '2024-01-31';
        
        $response = $this->actingAs($admin)->get('/admin/reports', [
            'start_date' => $startDate,
            'end_date' => $endDate
        ]);
        
        $response->assertStatus(200);
        $this->assertEquals($startDate, $response->viewData('startDate'));
        $this->assertEquals($endDate, $response->viewData('endDate'));
    }

    /** @test */
    public function reports_only_count_tickets_within_date_range()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        
        // Create ticket outside range
        Ticket::factory()->create([
            'created_at' => Carbon::now()->subDays(60)
        ]);
        
        // Create tickets inside range
        Ticket::factory()->count(3)->create([
            'created_at' => Carbon::now()->subDays(10)
        ]);
        
        $response = $this->actingAs($admin)->get('/admin/reports', [
            'start_date' => Carbon::now()->subDays(30)->format('Y-m-d'),
            'end_date' => Carbon::now()->format('Y-m-d')
        ]);
        
        $response->assertStatus(200);
        $data = $response->viewData('data');
        $this->assertEquals(3, $data['total_tickets']);
    }
}
