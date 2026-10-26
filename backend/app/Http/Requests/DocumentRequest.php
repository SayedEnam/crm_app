<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['application_id' => ['required', 'integer', 'exists:applications,id'], 'name' => ['nullable', 'string', 'max:180'], 'file' => ['required', 'file', 'max:20480', 'mimes:pdf,jpg,jpeg,png,doc,docx'], 'status' => ['sometimes', 'in:pending,received,approved,rejected'], 'notes' => ['nullable', 'string', 'max:5000']];
    }
}
