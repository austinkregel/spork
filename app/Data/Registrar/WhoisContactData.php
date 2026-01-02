<?php

declare(strict_types=1);

namespace App\Data\Registrar;

use InvalidArgumentException;

final class WhoisContactData
{
    public function __construct(
        public readonly string $first_name,
        public readonly string $last_name,
        public readonly string $address_1,
        public readonly ?string $address_2,
        public readonly string $city,
        public readonly string $state_province,
        public readonly string $postal_code,
        public readonly string $country,
        public readonly string $phone,
        public readonly string $email_address,
        public readonly ?string $organization_name = null,
        public readonly ?string $job_title = null,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $get = static function (array $data, string ...$keys): mixed {
            foreach ($keys as $key) {
                if (array_key_exists($key, $data)) {
                    return $data[$key];
                }
            }

            return null;
        };

        $required = [
            'first_name' => (string) $get($data, 'first_name', 'firstName', 'FirstName'),
            'last_name' => (string) $get($data, 'last_name', 'lastName', 'LastName'),
            'address_1' => (string) $get($data, 'address_1', 'address1', 'Address1'),
            'city' => (string) $get($data, 'city', 'City'),
            'state_province' => (string) $get($data, 'state_province', 'stateProvince', 'StateProvince'),
            'postal_code' => (string) $get($data, 'postal_code', 'postalCode', 'PostalCode'),
            'country' => (string) $get($data, 'country', 'Country'),
            'phone' => (string) $get($data, 'phone', 'Phone'),
            'email_address' => (string) $get($data, 'email_address', 'emailAddress', 'EmailAddress'),
        ];

        foreach ($required as $field => $value) {
            if ($value === '') {
                throw new InvalidArgumentException(sprintf('Missing required contact field: %s', $field));
            }
        }

        return new self(
            first_name: $required['first_name'],
            last_name: $required['last_name'],
            address_1: $required['address_1'],
            address_2: ($v = $get($data, 'address_2', 'address2', 'Address2')) !== null ? (string) $v : null,
            city: $required['city'],
            state_province: $required['state_province'],
            postal_code: $required['postal_code'],
            country: $required['country'],
            phone: $required['phone'],
            email_address: $required['email_address'],
            organization_name: ($v = $get($data, 'organization_name', 'organizationName', 'OrganizationName')) !== null ? (string) $v : null,
            job_title: ($v = $get($data, 'job_title', 'jobTitle', 'JobTitle')) !== null ? (string) $v : null,
        );
    }

    /**
     * @return array<string, string>
     */
    public function toNamecheapParams(string $prefix): array
    {
        $params = [
            "{$prefix}FirstName" => $this->first_name,
            "{$prefix}LastName" => $this->last_name,
            "{$prefix}Address1" => $this->address_1,
            "{$prefix}City" => $this->city,
            "{$prefix}StateProvince" => $this->state_province,
            "{$prefix}PostalCode" => $this->postal_code,
            "{$prefix}Country" => $this->country,
            "{$prefix}Phone" => $this->phone,
            "{$prefix}EmailAddress" => $this->email_address,
        ];

        if ($this->address_2 !== null && $this->address_2 !== '') {
            $params["{$prefix}Address2"] = $this->address_2;
        }

        if ($this->organization_name !== null && $this->organization_name !== '') {
            $params["{$prefix}OrganizationName"] = $this->organization_name;
        }

        if ($this->job_title !== null && $this->job_title !== '') {
            $params["{$prefix}JobTitle"] = $this->job_title;
        }

        return $params;
    }

    public function withEmailAddress(string $email_address): self
    {
        return new self(
            first_name: $this->first_name,
            last_name: $this->last_name,
            address_1: $this->address_1,
            address_2: $this->address_2,
            city: $this->city,
            state_province: $this->state_province,
            postal_code: $this->postal_code,
            country: $this->country,
            phone: $this->phone,
            email_address: $email_address,
            organization_name: $this->organization_name,
            job_title: $this->job_title,
        );
    }
}


