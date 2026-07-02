<?php

namespace App\Http\Requests\Leader;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Group;

class InviteMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => [
                'required',
                'email',
                'unique:users,email',
            ],
        ];
    }
}
