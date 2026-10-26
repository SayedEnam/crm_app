<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ClientPortalRegistrationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return ['name' => ['required', 'string', 'min:2', 'max:160'], 'email' => ['required', 'email', 'max:255', Rule::unique('client_portal_accounts', 'email')], 'phone' => ['nullable', 'string', 'max:40'], 'country' => ['nullable', 'string', 'max:100'], 'password' => ['required', 'confirmed', 'string', 'min:12']];
    }
}
