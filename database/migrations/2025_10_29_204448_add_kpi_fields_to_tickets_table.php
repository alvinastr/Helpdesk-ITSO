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
        Schema::table('tickets', function (Blueprint $table) {
            // KPI Tracking Fields
            $table->timestamp('email_received_at')->nullable()->after('closed_at')
                ->comment('Waktu email/keluhan pertama kali diterima');
            
            $table->timestamp('first_response_at')->nullable()->after('email_received_at')
                ->comment('Waktu respon pertama dari admin/staff');
            
            $table->timestamp('resolved_at')->nullable()->after('first_response_at')
                ->comment('Waktu keluhan selesai diresolve (berbeda dengan closed_at)');
            
            // Calculated KPI Metrics (in minutes)
            $table->integer('response_time_minutes')->nullable()->after('resolved_at')
                ->comment('Durasi dari email_received_at ke first_response_at (menit)');
            
            $table->integer('resolution_time_minutes')->nullable()->after('response_time_minutes')
                ->comment('Durasi dari email_received_at ke resolved_at (menit)');
            
            $table->integer('ticket_creation_delay_minutes')->nullable()->after('resolution_time_minutes')
                ->comment('Durasi dari email_received_at ke created_at ticket (menit)');
            
            // Indexes for KPI reporting
            $table->index('email_received_at');
            $table->index('first_response_at');
            $table->index('resolved_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropColumn([
                'email_received_at',
                'first_response_at',
                'resolved_at',
                'response_time_minutes',
                'resolution_time_minutes',
                'ticket_creation_delay_minutes'
            ]);
        });
    }
};
