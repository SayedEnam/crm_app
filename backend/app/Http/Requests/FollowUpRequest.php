<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FollowUpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['client_id' => ['required', 'integer', 'exists:clients,id'], 'application_id' => ['nullable', 'integer', 'exists:applications,id'], 'due_at' => ['required', 'date'], 'status' => ['sometimes', 'in:pending,completed,cancelled'], 'notes' => ['nullable', 'string', 'max:5000'], 'assigned_user_id' => ['sometimes', 'nullable', 'integer', 'exists:users,id']];
    }
}
