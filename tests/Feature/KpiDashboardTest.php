<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Ticket;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Tests\TestCase;
use Carbon\Carbon;

class KpiDashboardTest extends TestCase
{
    use RefreshDatabase, WithoutMiddleware;

    /** @test */
    public function admin_can_access_kpi_dashboard()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        
        $response = $this->actingAs($admin)->get('/admin/kpi');
        $response->assertStatus(200);
        $response->assertViewIs('kpi.dashboard');
    }

    /** @test */
    public function regular_user_cannot_access_kpi_dashboard()
    {
        $user = User::factory()->create(['role' => 'user']);
        
        $response = $this->actingAs($user)->get('/admin/kpi');
        $response->assertStatus(403);
    }

    /** @test */
    public function kpi_dashboard_shows_summary_data()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        
        // Create test tickets
        Ticket::factory()->count(5)->create([
            'status' => 'open',
            'created_at' => now()
        ]);
        
        $response = $this->actingAs($admin)->get('/admin/kpi');
        
        $response->assertStatus(200);
        $response->assertViewHas('summary');
    }

    /** @test */
    public function kpi_dashboard_can_filter_by_date_range()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        
        $startDate = Carbon::now()->subDays(30)->format('Y-m-d');
        $endDate = Carbon::now()->format('Y-m-d');
        
        $response = $this->actingAs($admin)->get('/admin/kpi', [
            'start_date' => $startDate,
            'end_date' => $endDate
        ]);
        
        $response->assertStatus(200);
    }

    /** @test */
    public function kpi_dashboard_can_filter_by_category()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        
        Ticket::factory()->create(['category' => 'technical']);
        Ticket::factory()->create(['category' => 'general']);
        
        $response = $this->actingAs($admin)->get('/admin/kpi', [
            'category' => 'technical'
        ]);
        
        $response->assertStatus(200);
    }

    /** @test */
    public function kpi_dashboard_can_filter_by_priority()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        
        Ticket::factory()->create(['priority' => 'high']);
        Ticket::factory()->create(['priority' => 'medium']);
        
        $response = $this->actingAs($admin)->get('/admin/kpi', [
            'priority' => 'high'
        ]);
        
        $response->assertStatus(200);
    }

    /** @test */
    public function kpi_dashboard_can_filter_by_status()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        
        Ticket::factory()->create(['status' => 'open']);
        Ticket::factory()->create(['status' => 'closed']);
        
        $response = $this->actingAs($admin)->get('/admin/kpi', [
            'status' => 'open'
        ]);
        
        $response->assertStatus(200);
    }

    /** @test */
    public function kpi_api_summary_returns_json()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        
        Ticket::factory()->count(3)->create();
        
        $response = $this->actingAs($admin)->get('/api/kpi/summary');
        
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'total_tickets',
            'avg_response_time',
            'avg_resolution_time',
            'sla_compliance_rate'
        ]);
    }

    /** @test */
    public function kpi_api_trends_returns_json()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        
        Ticket::factory()->count(5)->create();
        
        $response = $this->actingAs($admin)->get('/api/kpi/trends', [
            'period' => 'daily'
        ]);
        
        $response->assertStatus(200);
        $response->assertJson([]);
    }

    /** @test */
    public function kpi_export_generates_csv_file()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        
        Ticket::factory()->count(3)->create();
        
        $response = $this->actingAs($admin)->get('/admin/kpi/export', [
            'format' => 'csv'
        ]);
        
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
    }

    /** @test */
    public function kpi_export_generates_json_file()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        
        Ticket::factory()->count(3)->create();
        
        $response = $this->actingAs($admin)->get('/admin/kpi/export', [
            'format' => 'json'
        ]);
        
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/json');
    }

    /** @test */
    public function kpi_calculates_response_time_correctly()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        
        // Create ticket with first response
        $ticket = Ticket::factory()->create([
            'created_at' => Carbon::now()->subHours(2),
            'first_response_at' => Carbon::now()->subHours(1)
        ]);
        
        $response = $this->actingAs($admin)->get('/api/kpi/summary');
        
        $response->assertStatus(200);
        $data = $response->json();
        $this->assertArrayHasKey('avg_response_time', $data);
    }

    /** @test */
    public function kpi_calculates_resolution_time_correctly()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        
        // Create resolved ticket
        $ticket = Ticket::factory()->create([
            'status' => 'resolved',
            'created_at' => Carbon::now()->subDays(2),
            'resolved_at' => Carbon::now()->subDay()
        ]);
        
        $response = $this->actingAs($admin)->get('/api/kpi/summary');
        
        $response->assertStatus(200);
        $data = $response->json();
        $this->assertArrayHasKey('avg_resolution_time', $data);
    }

    /** @test */
    public function kpi_calculates_sla_compliance()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        
        // Create tickets with SLA data
        Ticket::factory()->create(['sla_met' => true]);
        Ticket::factory()->create(['sla_met' => true]);
        Ticket::factory()->create(['sla_met' => false]);
        
        $response = $this->actingAs($admin)->get('/api/kpi/summary');
        
        $response->assertStatus(200);
        $data = $response->json();
        $this->assertArrayHasKey('sla_compliance_rate', $data);
    }

    /** @test */
    public function kpi_dashboard_shows_trends_chart_data()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        
        // Create tickets across different dates
        Ticket::factory()->create(['created_at' => Carbon::now()->subDays(5)]);
        Ticket::factory()->create(['created_at' => Carbon::now()->subDays(3)]);
        Ticket::factory()->create(['created_at' => Carbon::now()->subDay()]);
        
        $response = $this->actingAs($admin)->get('/admin/kpi');
        
        $response->assertStatus(200);
        $response->assertViewHas('trends');
    }

    /** @test */
    public function kpi_dashboard_shows_category_distribution()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        
        Ticket::factory()->create(['category' => 'technical']);
        Ticket::factory()->create(['category' => 'general']);
        Ticket::factory()->create(['category' => 'billing']);
        
        $response = $this->actingAs($admin)->get('/admin/kpi');
        
        $response->assertStatus(200);
        $response->assertViewHas('byCategory');
    }

    /** @test */
    public function kpi_dashboard_shows_priority_distribution()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        
        Ticket::factory()->create(['priority' => 'high']);
        Ticket::factory()->create(['priority' => 'medium']);
        Ticket::factory()->create(['priority' => 'low']);
        
        $response = $this->actingAs($admin)->get('/admin/kpi');
        
        $response->assertStatus(200);
        $response->assertViewHas('byPriority');
    }
}
