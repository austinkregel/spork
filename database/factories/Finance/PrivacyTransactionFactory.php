<?php

declare(strict_types=1);

namespace Database\Factories\Finance;

use App\Models\Credential;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Finance\PrivacyTransaction>
 */
class PrivacyTransactionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'credential_id' => Credential::factory(),
            'privacy_transaction_id' => $this->faker->uuid(),
            'result' => 'APPROVED',
            'status' => 'SETTLED',
            'amount_cents' => $this->faker->numberBetween(1, 50000),
            'currency_code' => 'USD',
            'date_authorized' => $this->faker->dateTime(),
            'date_settled' => $this->faker->dateTime(),
            'descriptor' => $this->faker->company(),
            'memo' => $this->faker->company(),
            'mcc' => (string) $this->faker->numberBetween(1000, 9999),
            'card_uuid' => $this->faker->uuid(),
            'card_id' => $this->faker->numberBetween(1, 9999),
            'data' => [],
        ];
    }
}
