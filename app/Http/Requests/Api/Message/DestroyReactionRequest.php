<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\Message;

use Illuminate\Foundation\Http\FormRequest;

class DestroyReactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'emoji' => ['required', 'string', 'max:32'],
        ];
    }
}

