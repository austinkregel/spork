<?php

namespace App\Http\Requests\Messages;

use App\Models\Message;
use Illuminate\Foundation\Http\FormRequest;

class MarkAsReadRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $messageId = $this->get('id');
        $message = Message::findOrFail($messageId);

dd($message);

        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            //
        ];
    }
}
