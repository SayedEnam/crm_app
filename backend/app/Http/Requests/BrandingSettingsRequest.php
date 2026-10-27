<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BrandingSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('super-admin') ?? false;
    }

    public function rules(): array
    {
        return [
            'app_name' => ['required', 'string', 'max:100'],
            'company_name' => ['required', 'string', 'max:180'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'remove_logo' => ['sometimes', 'boolean'],
        ];
    }
}
