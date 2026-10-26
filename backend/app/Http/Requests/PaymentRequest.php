<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['client_id' => ['required', 'integer', 'exists:clients,id'], 'application_id' => ['nullable', 'integer', 'exists:applications,id'], 'amount' => ['required', 'numeric', 'gt:0', 'max:9999999999'], 'currency' => ['sometimes', 'string', 'size:3'], 'status' => ['sometimes', 'in:pending,paid,refunded,void'], 'method' => ['nullable', 'string', 'max:60'], 'reference' => ['nullable', 'string', 'max:120'], 'paid_at' => ['nullable', 'date'], 'notes' => ['nullable', 'string', 'max:5000']];
    }
}
