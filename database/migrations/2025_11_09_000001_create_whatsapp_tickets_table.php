<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('whatsapp_tickets', function (Blueprint $table) {
            $table->id();
            $table->string('ticket_number')->unique()->index();

            // Sender Information
            $table->string('sender_wa_id', 100);
            $table->string('sender_phone', 20)->index();
            $table->string('sender_name', 100)->nullable();

            // Ticket Content
            $table->string('subject', 255);
            $table->text('description');
            $table->text('original_message');

            // Classification (Auto dari bot)
            $table->enum('category', [
                'network',
                'hardware',
                'software',
                'account',
                'email',
                'security',
                'other'
            ])->default('other')->index();

            $table->enum('priority', [
                'normal',
                'high',
                'urgent'
            ])->default('normal')->index();

            // Status & Assignment
            $table->enum('status', [
                'open',
                'in_progress',
                'pending',
                'resolved',
                'closed'
            ])->default('open')->index();

            if (Schema::hasTable('users')) {
                $table->foreignId('assigned_to')->nullable()
                    ->constrained('users')->nullOnDelete();
            } else {
                $table->unsignedBigInteger('assigned_to')->nullable()->index();
            }

            // Source & Metadata
            $table->string('source', 20)->default('whatsapp');
            $table->boolean('is_group')->default(false);
            $table->boolean('has_media')->default(false);
            $table->string('message_type', 20)->nullable();

            // Raw data from WhatsApp Bot
            $table->json('raw_data')->nullable();

            // Timestamps
            $table->timestamp('wa_timestamp')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('created_at');
            $table->index(['status', 'priority']);
            $table->index(['category', 'status']);
        });

        Schema::create('whatsapp_ticket_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')
                ->constrained('whatsapp_tickets')->cascadeOnDelete();
            if (Schema::hasTable('users')) {
                $table->foreignId('user_id')->nullable()
                    ->constrained('users')->nullOnDelete();
            } else {
                $table->unsignedBigInteger('user_id')->nullable()->index();
            }
            $table->text('message');
            $table->enum('type', ['internal_note', 'reply', 'status_change']);
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('whatsapp_ticket_responses');
        Schema::dropIfExists('whatsapp_tickets');
    }
};
