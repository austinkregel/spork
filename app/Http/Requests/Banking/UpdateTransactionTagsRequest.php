<?php

declare(strict_types=1);

namespace App\Http\Requests\Banking;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTransactionTagsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        /** @var User|null $user */
        $user = $this->user();

        return [
            'tag_ids' => ['required', 'array'],
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
