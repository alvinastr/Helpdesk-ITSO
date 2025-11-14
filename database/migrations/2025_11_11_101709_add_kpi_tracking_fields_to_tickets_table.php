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
            // Check and add only columns that don't exist
            if (!Schema::hasColumn('tickets', 'first_response_at')) {
                $table->timestamp('first_response_at')->nullable()->after('created_at');
            }
            if (!Schema::hasColumn('tickets', 'response_time_minutes')) {
                $table->integer('response_time_minutes')->nullable()->after('first_response_at');
            }
            if (!Schema::hasColumn('tickets', 'resolution_time_minutes')) {
                $table->integer('resolution_time_minutes')->nullable()->after('response_time_minutes');
            }
            
            // SLA Management (New fields)
            if (!Schema::hasColumn('tickets', 'sla_breached')) {
                $table->boolean('sla_breached')->default(false)->after('resolution_time_minutes');
            }
            if (!Schema::hasColumn('tickets', 'sla_deadline')) {
                $table->timestamp('sla_deadline')->nullable()->after('sla_breached');
            }
            
            // Customer Satisfaction (New fields)
            if (!Schema::hasColumn('tickets', 'satisfaction_rating')) {
                $table->integer('satisfaction_rating')->nullable()->after('sla_deadline')->comment('1-5 scale');
            }
            if (!Schema::hasColumn('tickets', 'satisfaction_comment')) {
                $table->text('satisfaction_comment')->nullable()->after('satisfaction_rating');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropColumn([
                'first_response_at',
                'response_time_minutes',
                'resolution_time_minutes',
                'sla_breached',
                'sla_deadline',
                'satisfaction_rating',
                'satisfaction_comment'
            ]);
        });
    }
};
