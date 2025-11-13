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
        Schema::create('email_fetch_logs', function (Blueprint $table) {
            $table->id();
            $table->timestamp('fetch_started_at');
            $table->timestamp('fetch_completed_at')->nullable();
            $table->integer('total_fetched')->default(0);
            $table->integer('successful')->default(0);
            $table->integer('failed')->default(0);
            $table->integer('duplicates')->default(0);
            $table->text('error_message')->nullable();
            $table->enum('status', ['running', 'completed', 'failed'])->default('running');
            $table->timestamps();
            
            // Indexes
            $table->index('fetch_started_at');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('email_fetch_logs');
    }
};
