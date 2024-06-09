<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Credential;
use App\Models\Server;
use App\Services\Development\DescribeTableService;
use Illuminate\Foundation\Http\FormRequest;

class StoreServerRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        if (!$this->hasHeader('Authentication')) {
            return false;
        }

        if (!$this->hasHeader('User-agent')) {
            return false;
        }
        // Does the user agent match the pattern (\w|\d)+@(\w|\d)+:installer:
        preg_match('/^([\w|\d]+)@([\w|\d]+):(installer)$/i', $this->header('User-agent'),$matches);

        if (empty($matches)) {
            return false;
        }
        if (count($matches) !== 4) {
            return false;
        }

        [$full, $user, $host, $type] = $matches;

        if ($type !== 'installer') {
            return false;
        }

        [$bearer, $token] = explode(' ', $this->header('Authentication'), 2);
        $credential = Credential::firstWhere('api_key', $token);

        if (!$credential) {
            return false;
        }

        $this->merge([
            'credential' => $credential,
        ]);

        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        $description = new DescribeTableService();
        $fields = $description->describe(new Server());
        return array_reduce($fields['required'], function ($carry, $field) {
            return array_merge($carry, [
                $field => 'required',
            ]);
        }, []);
    }
}
