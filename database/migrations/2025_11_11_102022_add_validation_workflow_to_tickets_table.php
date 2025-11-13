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
            // Validation Status
            if (!Schema::hasColumn('tickets', 'validation_status')) {
                $table->enum('validation_status', [
                    'pending',
                    'approved', 
                    'rejected',
                    'needs_revision',
                    'auto_approved'
                ])->default('pending')->after('status');
            }
            
            // Input Method Tracking
            if (!Schema::hasColumn('tickets', 'input_method')) {
                $table->enum('input_method', [
                    'manual',
                    'email_auto_fetch',
                    'whatsapp',
                    'api'
                ])->default('manual')->after('validation_status');
            }
            
            // Revision Management
            if (!Schema::hasColumn('tickets', 'revision_notes')) {
                $table->text('revision_notes')->nullable()->after('rejection_reason');
            }
            if (!Schema::hasColumn('tickets', 'revision_count')) {
                $table->integer('revision_count')->default(0)->after('revision_notes');
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
                'validation_status',
                'input_method',
                'revision_notes',
                'revision_count'
            ]);
        });
    }
};
