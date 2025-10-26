<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TicketRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            // Data Reporter (Pelapor) - untuk user
            'reporter_nip' => 'required|string|max:50',
            'reporter_name' => 'required|string|max:255',
            'reporter_department' => 'required|string|max:255',
            
            // Contact method - salah satu wajib diisi
            'reporter_email' => 'required_without:reporter_phone|nullable|email|max:255',
            'reporter_phone' => 'required_without:reporter_email|nullable|string|min:10|max:20',
            
            // Data Ticket
            'subject' => 'required|string|max:255|min:5',
            'description' => 'required|string|min:10',
            'category' => 'nullable|string|max:100',
            'priority' => 'nullable|in:low,medium,high,critical',
            
            // Data Input Method
            'input_method' => 'required|in:manual,whatsapp,email',
            'original_message' => 'nullable|string',
            'channel' => 'required|in:email,whatsapp,call,portal',
            
            // Attachments
            'attachments.*' => 'nullable|file|max:5120' // 5MB
        ];
    }

    public function messages()
    {
        return [
            // Reporter validation messages
            'reporter_nip.required' => 'NIP pelapor wajib diisi',
            'reporter_name.required' => 'Nama pelapor wajib diisi',
            'reporter_department.required' => 'Departemen pelapor wajib diisi',
            
            // Contact method validation
            'reporter_email.required_without' => 'Email atau nomor telepon pelapor wajib diisi',
            'reporter_phone.required_without' => 'Nomor telepon atau email pelapor wajib diisi',
            'reporter_email.email' => 'Format email pelapor tidak valid',
            'reporter_phone.min' => 'Nomor telepon minimal 10 karakter',
            
            // Ticket validation messages
            'subject.required' => 'Subjek keluhan wajib diisi',
            'subject.min' => 'Subjek minimal 5 karakter',
            'description.required' => 'Deskripsi keluhan wajib diisi',
            'description.min' => 'Deskripsi minimal 10 karakter',
            'priority.in' => 'Prioritas tidak valid',
            
            // Input method validation
            'input_method.required' => 'Metode input wajib dipilih',
            'input_method.in' => 'Metode input tidak valid',
            'channel.required' => 'Channel wajib dipilih',
            'channel.in' => 'Channel tidak valid',
            
            // File validation
            'attachments.*.max' => 'File maksimal 5MB'
        ];
    }

    public function attributes()
    {
        return [
            'reporter_nip' => 'NIP Pelapor',
            'reporter_name' => 'Nama Pelapor',
            'reporter_email' => 'Email Pelapor',
            'reporter_phone' => 'Telepon Pelapor',
            'reporter_department' => 'Departemen Pelapor',
            'subject' => 'Subjek',
            'description' => 'Deskripsi',
            'input_method' => 'Metode Input',
            'original_message' => 'Pesan Asli'
        ];
    }
}
