<?php

declare(strict_types=1);

namespace App\Data\Registrar;

use InvalidArgumentException;

final class WhoisContactSetData
{
    public function __construct(
        public readonly WhoisContactData $registrant,
        public readonly WhoisContactData $admin,
        public readonly WhoisContactData $tech,
        public readonly WhoisContactData $aux_billing,
    ) {}

    /**
     * Accepts either:
     * - { "contact": { ... } } (applies to all roles), or
     * - { "registrant": { ... }, "admin": { ... }, "tech": { ... }, "aux_billing": { ... } }
     *
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $contact = $data['contact'] ?? null;

        if (is_array($contact)) {
            $c = WhoisContactData::fromArray($contact);

            return new self(
                registrant: $c,
                admin: $c,
                tech: $c,
                aux_billing: $c,
            );
        }

        $registrant = $data['registrant'] ?? null;
        $admin = $data['admin'] ?? null;
        $tech = $data['tech'] ?? null;
        $auxBilling = $data['aux_billing'] ?? $data['auxBilling'] ?? null;

        if (! is_array($registrant) || ! is_array($admin) || ! is_array($tech) || ! is_array($auxBilling)) {
            throw new InvalidArgumentException('Invalid contact payload. Provide "contact" or provide registrant/admin/tech/aux_billing.');
        }

        return new self(
            registrant: WhoisContactData::fromArray($registrant),
            admin: WhoisContactData::fromArray($admin),
            tech: WhoisContactData::fromArray($tech),
            aux_billing: WhoisContactData::fromArray($auxBilling),
        );
    }

    /**
     * @return array<string, string>
     */
    public function toNamecheapParams(): array
    {
        return array_merge(
            $this->registrant->toNamecheapParams('Registrant'),
            $this->admin->toNamecheapParams('Admin'),
            $this->tech->toNamecheapParams('Tech'),
            $this->aux_billing->toNamecheapParams('AuxBilling'),
        );
    }

    public function withEmailAddress(string $email_address): self
    {
        return new self(
            registrant: $this->registrant->withEmailAddress($email_address),
            admin: $this->admin->withEmailAddress($email_address),
            tech: $this->tech->withEmailAddress($email_address),
            aux_billing: $this->aux_billing->withEmailAddress($email_address),
        );
    }
}
