<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EmailSettingsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->hasRole('super-admin') ?? false;
    }

    public function rules(): array
    {
        return ['host' => ['required', 'string', 'max:255'], 'port' => ['required', 'integer', 'between:1,65535'], 'username' => ['nullable', 'string', 'max:255'], 'password' => ['nullable', 'string', 'max:2000'], 'scheme' => ['nullable', 'in:smtp,smtps'], 'from_address' => ['required', 'email', 'max:255'], 'from_name' => ['required', 'string', 'max:255']];
    }
}
