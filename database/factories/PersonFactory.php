<?php

declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Arr;

class PersonFactory extends Factory
{
    public function definition(): array
    {
        return [
            'birthdate' => $this->faker->date(),
            'phone_numbers' => $phoneNumbers = [$this->faker->phoneNumber()],
            'addresses' => $addresses = [
                [
                    'street' => $this->faker->streetAddress(),
                    'city' => $this->faker->city(),
                    'state' => $this->faker->state(),
                    'postal_code' => $this->faker->postcode(),
                    'country' => $this->faker->country(),
                ],
            ],
            'pronouns' => $this->faker->randomElement(['he/him', 'she/her', 'they/them', 'he/they', 'she/they', 'other']),
            'emails' => $emails = [$this->faker->safeEmail()],
            'names' => $names = [$this->faker->name(), $this->faker->name()],
            'estimated_home_value' => mt_rand(0, 1000000),
            'estimated_income' => mt_rand(0, 200000),
            'identifiers' => [
                'ssn' => $this->faker->ssn(),
                'passport' => $this->faker->uuid(),
                'facebook' => $this->faker->userName(),
                'twitter' => $this->faker->userName(),
                'linkedin' => $this->faker->userName(),
            ],
            'locality' => [
                'latitude' => $this->faker->latitude(),
                'longitude' => $this->faker->longitude(),
            ],
            'jobs' => [
                [
                    'title' => $this->faker->jobTitle(),
                    'company' => $this->faker->company(),
                    'start_date' => $this->faker->date(),
                    'end_date' => $this->faker->optional()->date(),
                ],
            ],
            'education' => [
                [
                    'institution' => $this->faker->company(),
                    'degree' => $this->faker->word(),
                    'field_of_study' => $this->faker->word(),
                    'start_date' => $this->faker->date(),
                    'end_date' => $this->faker->optional()->date(),
                ],
            ],
            'name' => Arr::first($names),
            'primary_address' => Arr::first($addresses)['street'],
            'primary_email' => Arr::first($emails),
            'primary_number' => Arr::first($phoneNumbers),
        ];
    }
}
