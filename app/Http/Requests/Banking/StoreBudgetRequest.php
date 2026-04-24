<?php

declare(strict_types=1);

namespace App\Http\Requests\Banking;

use App\Models\Finance\Budget;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBudgetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('frequency')) {
            $this->merge([
                'frequency' => strtoupper((string) $this->input('frequency')),
            ]);
        }
    }

    public function rules(): array
    {
        /** @var User|null $user */
        $user = $this->user();

        return [
            'name' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0'],
            'frequency' => [
                'required',
                'string',
                Rule::in([
                    Budget::FREQUENCY_DAILY,
                    Budget::FREQUENCY_WEEKLY,
                    Budget::FREQUENCY_BIWEEKLY,
                    Budget::FREQUENCY_SEMIMONTHLY,
                    Budget::FREQUENCY_MONTHLY,
                    Budget::FREQUENCY_BIMONTHLY,
                    Budget::FREQUENCY_YEARLY,
                ]),
            ],
            'interval' => ['nullable', 'integer', 'min:1'],
            'started_at' => ['nullable', 'date'],
            'count' => ['nullable', 'integer', 'min:1'],
            'tag_ids' => ['nullable', 'array'],
            'tag_ids.*' => [
                'integer',
                'distinct',
                Rule::exists('taggables', 'tag_id')->where(function ($query) use ($user) {
                    if (! $user) {
                        return;
                    }

                    $query
                        ->where('taggable_type', User::class)
                        ->where('taggable_id', $user->id);
                }),
            ],
        ];
    }
}
