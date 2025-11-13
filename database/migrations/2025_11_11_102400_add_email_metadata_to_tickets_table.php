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
            // Email Tracking Fields
            if (!Schema::hasColumn('tickets', 'email_message_id')) {
                $table->string('email_message_id')->nullable()->unique()->after('user_phone');
            }
            if (!Schema::hasColumn('tickets', 'sender_email')) {
                $table->string('sender_email')->nullable()->after('email_message_id');
            }
            if (!Schema::hasColumn('tickets', 'email_headers')) {
                $table->text('email_headers')->nullable()->after('sender_email')->comment('JSON format');
            }
            if (!Schema::hasColumn('tickets', 'email_received_at')) {
                $table->timestamp('email_received_at')->nullable()->after('email_headers');
            }
            if (!Schema::hasColumn('tickets', 'processing_time_ms')) {
                $table->integer('processing_time_ms')->nullable()->after('email_received_at');
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
                'email_message_id',
                'sender_email',
                'email_headers',
                'email_received_at',
                'processing_time_ms'
            ]);
        });
    }
};
