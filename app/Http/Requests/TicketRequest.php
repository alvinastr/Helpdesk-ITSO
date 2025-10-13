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
            'subject' => 'required|string|max:255|min:5',
            'description' => 'required|string|min:10',
            'user_phone' => 'nullable|string|min:10|max:20',
            'attachments.*' => 'nullable|file|max:5120' // 5MB
        ];
    }

    public function messages()
    {
        return [
            'subject.required' => 'Subjek wajib diisi',
            'subject.min' => 'Subjek minimal 5 karakter',
            'description.required' => 'Deskripsi keluhan wajib diisi',
            'description.min' => 'Deskripsi minimal 10 karakter',
        ];
    }
}
