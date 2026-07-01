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
            'member_id' => [
                'required',
                'exists:users,id',
                function ($attribute, $value, $fail) {
                    // Logic to check if leader's group has < 20 members
                    // e.g.
                    // $leader = $this->user();
                    // $group = Group::where('leader_id', $leader->id)->first();
                    // if ($group && $group->members()->count() >= 20) {
                    //     $fail('The group has reached its maximum limit of 20 members.');
                    // }
                },
            ],
        ];
    }
}
