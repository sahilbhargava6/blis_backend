<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCampaignRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'sometimes|required|string|max:255',
            'master_url' => 'sometimes|required|url',
            'total_payout' => 'sometimes|required|numeric|min:0',
            'split_member_percent' => 'sometimes|required|numeric|min:0|max:100',
            'split_leader_percent' => 'sometimes|required|numeric|min:0|max:100',
        ];
    }
}
