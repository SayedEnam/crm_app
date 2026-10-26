<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ClientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['name' => ['required', 'string', 'max:160'], 'email' => ['nullable', 'email', 'max:255'], 'phone' => ['nullable', 'string', 'max:40'], 'company' => ['nullable', 'string', 'max:160'], 'country' => ['nullable', 'string', 'max:100'], 'address' => ['nullable', 'string', 'max:500'], 'status' => ['sometimes', 'in:lead,active,inactive,archived'], 'notes' => ['nullable', 'string', 'max:10000'], 'assigned_user_id' => ['sometimes', 'nullable', 'integer', 'exists:users,id']];
    }
}
