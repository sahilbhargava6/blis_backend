<?php

namespace App\Http\Requests\Member;

use Illuminate\Foundation\Http\FormRequest;

class WithdrawRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'amount' => [
                'required',
                'numeric',
                'min:1',
                function ($attribute, $value, $fail) {
                    // $user = $this->user();
                    // if ($user->cleared_balance < $value) {
                    //     $fail('Insufficient cleared balance for withdrawal.');
                    // }
                },
            ],
            'payout_method_details' => 'required|string|max:1000',
        ];
    }
}
