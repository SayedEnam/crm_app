<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SendTestEmailRequest extends FormRequest
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
        return ['email' => ['required', 'email', 'max:255']];
    }
}
