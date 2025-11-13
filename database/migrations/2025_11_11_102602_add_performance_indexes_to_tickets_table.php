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
            // Try to add indexes, silently skip if already exists
            try {
                if (!Schema::hasColumn('tickets', 'validation_status_index')) {
                    $table->index('validation_status');
                }
            } catch (\Exception $e) {
                // Index already exists, skip
            }
            
            try {
                $table->index('sla_breached');
            } catch (\Exception $e) {
                // Index already exists, skip
            }
            
            try {
                $table->index(['channel', 'created_at']);
            } catch (\Exception $e) {
                // Index already exists, skip
            }
            
            try {
                $table->index(['status', 'priority']);
            } catch (\Exception $e) {
                // Index already exists, skip
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropIndex(['validation_status']);
            $table->dropIndex(['input_method']);
            $table->dropIndex(['first_response_at']);
            $table->dropIndex(['sla_breached']);
            $table->dropIndex(['email_message_id']);
            $table->dropIndex(['channel', 'created_at']);
            $table->dropIndex(['status', 'priority']);
        });
    }
};
