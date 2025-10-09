<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTicketThreadsTable extends Migration
{
    public function up()
    {
        Schema::create('ticket_threads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained()->onDelete('cascade');
            $table->enum('sender_type', ['user', 'admin', 'system'])->default('user');
            $table->foreignId('sender_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('sender_name');
            $table->enum('message_type', ['complaint', 'reply', 'note', 'resolution'])->default('reply');
            $table->text('message');
            $table->json('attachments')->nullable();
            $table->timestamps();
            
            $table->index('ticket_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('ticket_threads');
    }
};
