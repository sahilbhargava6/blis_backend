<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreCampaignRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'master_url' => 'required|url',
            'total_payout' => 'required|numeric|min:0',
            'split_member_percent' => 'required|numeric|min:0|max:100',
            'split_leader_percent' => 'required|numeric|min:0|max:100',
        ];
    }
}
