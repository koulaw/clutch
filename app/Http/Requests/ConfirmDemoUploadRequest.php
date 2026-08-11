<?php

namespace App\Http\Requests;

use App\Models\Demo;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ConfirmDemoUploadRequest extends FormRequest
{
    public function authorize(): bool
    {
        $demo = $this->route('demo');

        return $demo instanceof Demo && $this->user()?->can('confirm', $demo) === true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [];
    }
}
