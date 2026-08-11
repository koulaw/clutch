<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class CreateDemoUploadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'filename' => ['required', 'string', 'max:255', 'regex:/\.dem\z/i'],
            'size_bytes' => ['required', 'integer', 'min:1', 'max:'.config('demo_upload.max_size_bytes')],
            'checksum_sha256' => ['required', 'string', 'regex:/\A[a-f0-9]{64}\z/i'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('checksum_sha256')) {
            $this->merge([
                'checksum_sha256' => $this->string('checksum_sha256')->lower()->toString(),
            ]);
        }
    }
}
