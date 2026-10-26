<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class PublicIntakeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:160'],
            'email' => ['nullable', 'email', 'max:255', 'required_without:phone'],
            'phone' => ['nullable', 'string', 'max:40', 'required_without:email'],
            'country' => ['nullable', 'string', 'max:100'],
            'service_id' => ['required', 'integer', Rule::exists('services', 'id')->where('is_active', true)],
            'notes' => ['nullable', 'string', 'max:5000'],
            'passport' => ['required', 'file', 'max:10240', 'mimes:pdf,jpg,jpeg'],
            'supporting_documents' => ['nullable', 'array', 'max:8'],
            'supporting_documents.*.category' => ['required', Rule::in(['certificate', 'marksheet', 'other'])],
            'supporting_documents.*.file' => ['required', 'file', 'max:10240', 'mimes:pdf,jpg,jpeg'],
            'website' => ['nullable', 'prohibited'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $files = [
                $this->file('passport'),
                ...array_map(fn ($document) => is_array($document) ? ($document['file'] ?? null) : null, $this->file('supporting_documents', [])),
            ];
            $files = array_filter($files, fn ($file) => $file instanceof UploadedFile);
            $totalBytes = array_sum(array_map(fn (UploadedFile $file) => $file->getSize(), $files));

            if ($totalBytes > 30 * 1024 * 1024) {
                $validator->errors()->add('supporting_documents', 'The total size of all uploaded files must be 30 MB or less.');
            }
        });
    }
}
