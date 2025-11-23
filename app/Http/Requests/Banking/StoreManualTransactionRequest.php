<?php

declare(strict_types=1);

namespace App\Http\Requests\Banking;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreManualTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'account_id' => [
                'required',
                'string',
                Rule::exists('accounts', 'account_id')
                    ->where(function ($query) {
                        $credentialIds = $this->user()?->credentials()->pluck('id');

                        if ($credentialIds && $credentialIds->isNotEmpty()) {
                            $query->whereIn('credential_id', $credentialIds);
                        }
                    }),
            ],
            'name' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric'],
            'date' => ['required', 'date'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['integer', 'exists:tags,id'],
            'notes' => ['nullable', 'string'],
        ];
    }
}


