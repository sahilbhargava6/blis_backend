<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreGroupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'group_name' => 'required|string|max:255|unique:groups',
            'leader_id' => 'required|exists:users,id',
        ];
    }
}
