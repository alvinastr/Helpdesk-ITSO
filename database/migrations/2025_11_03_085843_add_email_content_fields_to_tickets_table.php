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
            // Email Content Fields - untuk menyimpan konten email asli
            $table->text('email_subject')->nullable()->after('subject')
                ->comment('Subject email asli (bisa berbeda dengan subject ticket)');
            
            $table->text('email_body_original')->nullable()->after('description')
                ->comment('Isi email keluhan pertama dari user (plain text)');
            
            $table->text('email_response_admin')->nullable()->after('email_body_original')
                ->comment('Isi email response pertama dari admin');
            
            $table->text('email_resolution_message')->nullable()->after('email_response_admin')
                ->comment('Isi email saat ticket diresolve/tutup');
            
            $table->json('email_thread')->nullable()->after('email_resolution_message')
                ->comment('Full email thread dalam format JSON (untuk rekap lengkap)');
            
            // Email metadata
            $table->string('email_from')->nullable()->after('email_thread')
                ->comment('Email address pengirim');
            
            $table->string('email_to')->nullable()->after('email_from')
                ->comment('Email address penerima (support email)');
            
            $table->text('email_cc')->nullable()->after('email_to')
                ->comment('CC emails (comma separated)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropColumn([
                'email_subject',
                'email_body_original',
                'email_response_admin',
                'email_resolution_message',
                'email_thread',
                'email_from',
                'email_to',
                'email_cc',
            ]);
        });
    }
};
