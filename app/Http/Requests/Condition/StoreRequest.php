<?php

declare(strict_types=1);

namespace App\Http\Requests\Condition;

use App\Contracts\Conditionable;
use App\Services\Condition\ContainsValueOperator;
use App\Services\Condition\ContainsValueStrictOperator;
use App\Services\Condition\DoesntContainValueOperator;
use App\Services\Condition\DoesntEqualValueOperator;
use App\Services\Condition\EndsWithOperator;
use App\Services\Condition\EqualsValueOperator;
use App\Services\Condition\FilterIn;
use App\Services\Condition\GreaterThanOperator;
use App\Services\Condition\GreaterThanOrEqualToOperator;
use App\Services\Condition\LessThanOperator;
use App\Services\Condition\LessThanOrEqualToOperator;
use App\Services\Condition\StartsWithOperator;
use App\Services\Programming\LaravelProgrammingStyle;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'comparator' => [
                'required',
                Rule::in([
                    ContainsValueOperator::class,
                    ContainsValueStrictOperator::class,
                    DoesntContainValueOperator::class,
                    DoesntEqualValueOperator::class,
                    EndsWithOperator::class,
                    EqualsValueOperator::class,
                    FilterIn::class,
                    GreaterThanOperator::class,
                    GreaterThanOrEqualToOperator::class,
                    LessThanOperator::class,
                    LessThanOrEqualToOperator::class,
                    StartsWithOperator::class,
                ]),
            ],
            'parameter' => 'required',
            'value' => 'required',
            'conditionable_id' => '',
            'conditionable_type' => [
                'required',
                Rule::in(LaravelProgrammingStyle::instancesOf(Conditionable::class)->getClasses()),
            ],
        ];
    }
}
