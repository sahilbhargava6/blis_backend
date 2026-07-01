<?php

namespace App\Http\Requests\Member;

use Illuminate\Foundation\Http\FormRequest;

class CustomizeLinkRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'link_id' => 'required|exists:links,id',
            'custom_label' => 'required|string|max:50',
        ];
    }
}
