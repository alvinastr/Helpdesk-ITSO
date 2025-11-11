<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('whatsapp_tickets', function (Blueprint $table) {
            // KPI Tracking Fields
            
            // Actual report time (manual input by admin if different from created_at)
            $table->timestamp('actual_report_time')->nullable()->after('created_at');
            
            // First admin response time
            $table->timestamp('first_response_at')->nullable()->after('actual_report_time');
            
            // Time when ticket was first assigned
            $table->timestamp('first_assigned_at')->nullable()->after('first_response_at');
            
            // Time when work actually started (status changed to in_progress)
            $table->timestamp('work_started_at')->nullable()->after('first_assigned_at');
            
            // Time spent in each status (in minutes) - JSON field
            $table->json('time_tracking')->nullable()->after('work_started_at')
                ->comment('Track time spent in each status: {open: 30, in_progress: 120, pending: 60}');
            
            // Total handle time (excluding pending) in minutes
            $table->integer('total_handle_time')->nullable()->after('time_tracking')
                ->comment('Total active handling time in minutes');
            
            // Total customer wait time in minutes
            $table->integer('total_wait_time')->nullable()->after('total_handle_time')
                ->comment('Total time customer waited for admin response');
            
            // SLA related
            $table->timestamp('sla_breach_at')->nullable()->after('total_wait_time')
                ->comment('When SLA was breached based on priority');
            
            $table->boolean('sla_breached')->default(false)->after('sla_breach_at')
                ->comment('Whether SLA was breached');
            
            // Indexes for reporting
            $table->index('first_response_at');
            $table->index('actual_report_time');
            $table->index('sla_breached');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('whatsapp_tickets', function (Blueprint $table) {
            $table->dropIndex(['first_response_at']);
            $table->dropIndex(['actual_report_time']);
            $table->dropIndex(['sla_breached']);
            
            $table->dropColumn([
                'actual_report_time',
                'first_response_at',
                'first_assigned_at',
                'work_started_at',
                'time_tracking',
                'total_handle_time',
                'total_wait_time',
                'sla_breach_at',
                'sla_breached',
            ]);
        });
    }
};
