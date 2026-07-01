<?php

namespace App\Http\Requests\Tracking;

use Illuminate\Foundation\Http\FormRequest;

class PostbackRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Webhooks might use server IP auth or custom token signature
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'sub_id' => 'required|string',
            'amount' => 'required|numeric',
            'signature' => 'nullable|string', // For network signature validation
            'status' => 'required|string',
        ];
    }
}
