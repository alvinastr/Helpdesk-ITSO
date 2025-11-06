<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class AdminTicketRequest extends FormRequest
{
    public function authorize()
    {
        return Auth::check() && Auth::user()->isAdmin();
    }

    public function rules()
    {
        $rules = [
            // Data Reporter (Pelapor) - sama dengan user
            'reporter_nip' => 'required|string|max:50',
            'reporter_name' => 'required|string|max:255',
            'reporter_department' => 'required|string|max:255',
            
            // Contact method - salah satu wajib diisi (sama dengan user)
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
            
            // KPI Fields
            'email_received_at' => 'nullable|date|before_or_equal:now',
            'first_response_at' => 'nullable|date|after:email_received_at|before_or_equal:now',
            'resolved_at' => 'nullable|date|after:first_response_at|before_or_equal:now',
            
            // Email Content Fields
            'email_subject' => 'nullable|string|max:500',
            'email_body_original' => 'nullable|string',
            'email_response_admin' => 'nullable|string',
            'email_resolution_message' => 'nullable|string',
            'email_from' => 'nullable|email|max:255',
            'email_to' => 'nullable|string|max:500',
            'email_cc' => 'nullable|string|max:1000',
            
            // Attachments
            'attachments.*' => 'nullable|file|max:5120' // 5MB
        ];

        // Make email_received_at required if channel is email
        if (request()->input('channel') === 'email') {
            $rules['email_received_at'] = 'required|date|before_or_equal:now';
        }

        return $rules;
    }

    public function messages()
    {
        return [
            // Reporter validation messages
            'reporter_nip.required' => 'NIP pelapor wajib diisi',
            'reporter_name.required' => 'Nama pelapor wajib diisi',
            'reporter_department.required' => 'Departemen pelapor wajib diisi',
            
            // Contact method validation (sama dengan user)
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
            
            // KPI validation messages
            'email_received_at.required' => 'Waktu email diterima wajib diisi untuk channel email',
            'email_received_at.date' => 'Format waktu email tidak valid',
            'email_received_at.before_or_equal' => 'Waktu email tidak boleh di masa depan',
            
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
