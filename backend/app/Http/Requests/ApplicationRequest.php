<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['client_id' => ['required', 'integer', 'exists:clients,id'], 'service_id' => ['required', 'integer', 'exists:services,id'], 'reference' => ['nullable', 'string', 'max:100', Rule::unique('applications', 'reference')->ignore($this->route('application'))], 'status' => ['sometimes', 'in:active,in_progress,pending,completed,cancelled,rejected'], 'started_at' => ['nullable', 'date'], 'completed_at' => ['nullable', 'date', 'after_or_equal:started_at'], 'notes' => ['nullable', 'string', 'max:10000'], 'assigned_user_id' => ['sometimes', 'nullable', 'integer', 'exists:users,id']];
    }
}
