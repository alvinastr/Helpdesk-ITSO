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
            // Field untuk data pelapor (user yang melaporkan)
            $table->string('reporter_nip')->nullable()->after('user_phone'); // NIP pelapor
            $table->string('reporter_name')->nullable()->after('reporter_nip'); // Nama pelapor
            $table->string('reporter_email')->nullable()->after('reporter_name'); // Email pelapor
            $table->string('reporter_phone')->nullable()->after('reporter_email'); // Telepon pelapor
            $table->string('reporter_department')->nullable()->after('reporter_phone'); // Departemen pelapor
            $table->string('reporter_position')->nullable()->after('reporter_department'); // Jabatan pelapor
            
            // Field untuk sumber data
            $table->enum('input_method', ['manual', 'whatsapp', 'email'])->default('manual')->after('channel'); // Cara input data
            $table->text('original_message')->nullable()->after('description'); // Pesan asli dari WA/Email
            
            // Field untuk admin yang input
            $table->foreignId('created_by_admin')->nullable()->constrained('users')->nullOnDelete()->after('approved_by'); // Admin yang input
            
            // Index untuk pencarian
            $table->index('reporter_nip');
            $table->index('reporter_email');
            $table->index('input_method');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropIndex(['reporter_nip']);
            $table->dropIndex(['reporter_email']);
            $table->dropIndex(['input_method']);
            
            $table->dropForeign(['created_by_admin']);
            $table->dropColumn([
                'reporter_nip',
                'reporter_name', 
                'reporter_email',
                'reporter_phone',
                'reporter_department',
                'reporter_position',
                'input_method',
                'original_message',
                'created_by_admin'
            ]);
        });
    }
};
