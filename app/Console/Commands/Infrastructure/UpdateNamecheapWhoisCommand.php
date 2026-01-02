<?php

declare(strict_types=1);

namespace App\Console\Commands\Infrastructure;

use App\Data\Registrar\WhoisContactSetData;
use App\Models\Credential;
use App\Models\Person;
use App\Services\Registrar\NamecheapService;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use RuntimeException;

class UpdateNamecheapWhoisCommand extends Command
{
    protected $signature = 'infrastructure:namecheap:update-whois
        {--credential-id= : Credential id for the Namecheap credential}
        {--person-id= : Person id to use for contact details (defaults to selecting a Person owned by the credential user)}
        {--domain= : Only update this domain name (exact match, case-insensitive)}
        {--contact-email-domain= : If set, contact email becomes domain_snake_case + suffix + @contact-email-domain}
        {--contact-email-suffix=_domains : Suffix appended after domain_snake_case when building per-domain contact emails}
        {--min-interval-ms=0 : Minimum delay between Namecheap API requests (0 disables pacing)}
        {--rate-limit-max-retries=8 : Max retries when Namecheap returns Too many requests}
        {--rate-limit-backoff-ms=750 : Initial backoff in ms when rate limited}
        {--rate-limit-max-backoff-ms=15000 : Maximum backoff in ms}
        {--rate-limit-backoff-multiplier=2 : Backoff multiplier per retry}
        {--rate-limit-jitter-ms=250 : Random jitter (0..N ms) added to backoff}
        {--dry-run : Do not perform writes, just print what would happen}
        {--page-size=100 : Page size for Namecheap domain listing}
        {--fail-fast : Abort on the first domain error (default continues)}
        {--first-name= : Override first name}
        {--last-name= : Override last name}
        {--address-1= : Override address line 1}
        {--address-2= : Override address line 2}
        {--city= : Override city}
        {--state-province= : Override state/province}
        {--postal-code= : Override postal/zip}
        {--country= : Override country (2-letter code, e.g. US)}
        {--phone= : Override phone}
        {--email-address= : Override email}
        {--organization-name= : Override organization}
        {--job-title= : Override job title}';

    protected $description = 'Fetch all Namecheap domains for a credential, set WHOIS contacts, and enable WhoisGuard where available.';

    private float $last_namecheap_request_at = 0.0;

    public function handle(): int
    {
        $credentialId = $this->option('credential-id');
        if (empty($credentialId)) {
            $this->error('Missing --credential-id=');

            return self::FAILURE;
        }

        $credential = Credential::query()->find((int) $credentialId);
        if (! $credential) {
            $this->error('Credential not found.');

            return self::FAILURE;
        }

        if ($credential->service !== Credential::NAMECHEAP) {
            $this->error(sprintf('Credential service must be "%s". Got "%s".', Credential::NAMECHEAP, (string) $credential->service));

            return self::FAILURE;
        }

        $person = $this->resolvePerson($credential);
        if (! $person) {
            $this->error('Unable to resolve Person. Provide --person-id= or create a Person for this user.');

            return self::FAILURE;
        }

        $contacts = $this->contactsFromPersonWithOverrides($person);
        $service = new NamecheapService($credential);

        $dryRun = (bool) $this->option('dry-run');
        $pageSize = max(1, (int) $this->option('page-size'));
        $failFast = (bool) $this->option('fail-fast');
        $targetDomain = trim((string) ($this->option('domain') ?? ''));
        $stopAfterTarget = $targetDomain !== '';
        $foundTarget = false;
        $processedTarget = false;
        $contactEmailDomain = trim((string) ($this->option('contact-email-domain') ?? ''));
        $contactEmailSuffix = (string) ($this->option('contact-email-suffix') ?? '_domains');
        $minIntervalMs = max(0, (int) $this->option('min-interval-ms'));
        $rateLimitMaxRetries = max(0, (int) $this->option('rate-limit-max-retries'));
        $rateLimitBackoffMs = max(0, (int) $this->option('rate-limit-backoff-ms'));
        $rateLimitMaxBackoffMs = max(0, (int) $this->option('rate-limit-max-backoff-ms'));
        $rateLimitBackoffMultiplier = max(1, (int) $this->option('rate-limit-backoff-multiplier'));
        $rateLimitJitterMs = max(0, (int) $this->option('rate-limit-jitter-ms'));

        $page = 1;
        $updated = 0;
        $contactsSkipped = 0;
        $privacyEnabled = 0;
        $privacySkipped = 0;
        $errors = 0;
        $targetContactsBlocked = false;

        do {
            $domains = $this->callNamecheap(
                fn () => $service->getDomains($pageSize, $page++),
                min_interval_ms: $minIntervalMs,
                max_retries: $rateLimitMaxRetries,
                backoff_ms: $rateLimitBackoffMs,
                max_backoff_ms: $rateLimitMaxBackoffMs,
                backoff_multiplier: $rateLimitBackoffMultiplier,
                jitter_ms: $rateLimitJitterMs,
            );

            foreach ($domains as $domain) {
                $domainName = (string) Arr::get($domain, 'domain', '');
                if ($domainName === '') {
                    continue;
                }

                if ($targetDomain !== '' && strcasecmp($domainName, $targetDomain) !== 0) {
                    continue;
                }

                if ($stopAfterTarget) {
                    $foundTarget = true;
                }

                $this->line(sprintf('Domain: %s', $domainName));

                // Contacts
                if ($dryRun) {
                    $this->line('  - would set contacts');
                } else {
                    $domainContacts = $contacts;

                    if ($contactEmailDomain !== '') {
                        $domainContacts = $contacts->withEmailAddress(
                            $this->makeDomainContactEmail($domainName, $contactEmailDomain, $contactEmailSuffix),
                        );
                    }

                    try {
                        $this->callNamecheap(
                            fn () => $service->setDomainContacts($domainName, $domainContacts),
                            min_interval_ms: $minIntervalMs,
                            max_retries: $rateLimitMaxRetries,
                            backoff_ms: $rateLimitBackoffMs,
                            max_backoff_ms: $rateLimitMaxBackoffMs,
                            backoff_multiplier: $rateLimitBackoffMultiplier,
                            jitter_ms: $rateLimitJitterMs,
                        );
                        $this->info('  - contacts updated');
                        $updated++;
                    } catch (\Throwable $e) {
                        $message = $e->getMessage();

                        if ($this->isNamecheapRateLimited($message)) {
                            $errors++;
                            $this->error(sprintf('  - error: %s', $message));

                            if ($failFast) {
                                return self::FAILURE;
                            }

                            continue;
                        }

                        if ($this->isNamecheapContactUpdateBlocked($message)) {
                            $contactsSkipped++;
                            $this->warn('  - contacts skipped (registry/TLD blocks modifications)');

                            if ($stopAfterTarget) {
                                $targetContactsBlocked = true;
                            }
                        } else {
                            $errors++;
                            $this->error(sprintf('  - error: %s', $message));

                            if ($failFast) {
                                return self::FAILURE;
                            }

                            if (str_contains($message, 'RegistrantPhone') && str_contains($message, 'Invalid')) {
                                $this->line('  - hint: Namecheap expects phone like +1.5555555555 (country code + dot + digits). Use --phone= to override.');
                            }

                            // If contacts failed unexpectedly, skip privacy attempt for this domain
                            // to avoid masking the original failure.
                            if ($stopAfterTarget) {
                                $processedTarget = true;
                                break;
                            }

                            continue;
                        }
                    }
                }

                $hasWhoisGuard = (bool) Arr::get($domain, 'has_whois_guard', false);
                if ($hasWhoisGuard) {
                    $this->line('  - privacy already enabled');
                    $privacySkipped++;

                    continue;
                }

                if ($dryRun) {
                    $this->line('  - would attempt to enable privacy');
                    continue;
                }

                try {
                    $enabled = $this->callNamecheap(
                        fn () => $service->enableWhoisGuardForDomain($domainName),
                        min_interval_ms: $minIntervalMs,
                        max_retries: $rateLimitMaxRetries,
                        backoff_ms: $rateLimitBackoffMs,
                        max_backoff_ms: $rateLimitMaxBackoffMs,
                        backoff_multiplier: $rateLimitBackoffMultiplier,
                        jitter_ms: $rateLimitJitterMs,
                    );

                    if ($enabled) {
                        $this->info('  - privacy enabled');
                        $privacyEnabled++;
                    } else {
                        $this->warn('  - privacy not enabled (not available or vendor rejected)');
                        $privacySkipped++;
                    }
                } catch (\Throwable $e) {
                    $errors++;
                    $this->error(sprintf('  - error: %s', $e->getMessage()));

                    if ($failFast) {
                        return self::FAILURE;
                    }
                }

                if ($stopAfterTarget) {
                    $processedTarget = true;

                    break;
                }
            }
        } while ($domains->hasMorePages() && ! $processedTarget);

        if ($stopAfterTarget && ! $foundTarget) {
            $this->error(sprintf('Domain not found on Namecheap for this credential: %s', $targetDomain));

            return self::FAILURE;
        }

        $this->newLine();
        $this->info('Done.');
        $this->line(sprintf('Contacts updated: %d', $updated));
        $this->line(sprintf('Contacts skipped: %d', $contactsSkipped));
        $this->line(sprintf('Privacy enabled: %d', $privacyEnabled));
        $this->line(sprintf('Privacy skipped: %d', $privacySkipped));
        $this->line(sprintf('Errors: %d', $errors));

        if ($stopAfterTarget && $targetContactsBlocked) {
            // Caller explicitly requested this domain; treat "cannot update contacts" as failure.
            return self::FAILURE;
        }

        return $errors > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function isNamecheapContactUpdateBlocked(string $message): bool
    {
        $message = strtolower($message);

        return str_contains($message, 'disablemodstatus')
            || str_contains($message, 'cannot set the contact information for the given tld');
    }

    private function isNamecheapRateLimited(string $message): bool
    {
        return str_contains(strtolower($message), 'too many requests');
    }

    /**
     * @template T
     *
     * @param  callable():T  $fn
     * @return T
     */
    private function callNamecheap(
        callable $fn,
        int $min_interval_ms,
        int $max_retries,
        int $backoff_ms,
        int $max_backoff_ms,
        int $backoff_multiplier,
        int $jitter_ms,
    ): mixed {
        $attempt = 0;
        $sleepMs = $backoff_ms;

        while (true) {
            $this->sleepForPacing($min_interval_ms);

            try {
                $result = $fn();
                $this->last_namecheap_request_at = microtime(true);

                return $result;
            } catch (\Throwable $e) {
                $message = $e->getMessage();

                if (! $this->isNamecheapRateLimited($message) || $attempt >= $max_retries) {
                    throw $e;
                }

                $attempt++;
                $jitter = $jitter_ms > 0 ? random_int(0, $jitter_ms) : 0;
                $delay = min($max_backoff_ms, $sleepMs + $jitter);

                $this->warn(sprintf('  - rate limited; backing off %dms (attempt %d/%d)', $delay, $attempt, $max_retries));
                usleep($delay * 1000);

                $sleepMs = min($max_backoff_ms, $sleepMs * $backoff_multiplier);
            }
        }
    }

    private function sleepForPacing(int $min_interval_ms): void
    {
        if ($min_interval_ms <= 0) {
            return;
        }

        $now = microtime(true);
        if ($this->last_namecheap_request_at <= 0) {
            return;
        }

        $elapsedMs = (int) (($now - $this->last_namecheap_request_at) * 1000);
        $remaining = $min_interval_ms - $elapsedMs;

        if ($remaining > 0) {
            usleep($remaining * 1000);
        }
    }

    private function makeDomainContactEmail(string $domain, string $emailDomain, string $suffix): string
    {
        $domainSnakeCase = $this->domainToSnakeCase($domain);
        $suffix = trim($suffix);

        return $domainSnakeCase.$suffix.'@'.ltrim($emailDomain, '@');
    }

    private function domainToSnakeCase(string $domain): string
    {
        $value = strtolower(trim($domain));
        $value = preg_replace('/[^a-z0-9]+/', '_', $value) ?? $value;
        $value = preg_replace('/_+/', '_', $value) ?? $value;
        $value = trim($value, '_');

        return $value;
    }

    private function resolvePerson(Credential $credential): ?Person
    {
        $personId = $this->option('person-id');

        if (! empty($personId)) {
            $person = Person::query()->whereKey((int) $personId)->first();

            if ($person && (int) $person->user_id !== (int) $credential->user_id) {
                $this->error('Person does not belong to the credential user.');

                return null;
            }

            return $person;
        }

        $people = Person::query()
            ->where('user_id', $credential->user_id)
            ->orderByDesc('id')
            ->limit(25)
            ->get();

        if ($people->count() === 1) {
            return $people->first();
        }

        if (! $this->input->isInteractive()) {
            // Avoid blocking in CI / tests.
            return null;
        }

        if ($people->isEmpty()) {
            return null;
        }

        $options = $people->map(function (Person $person): string {
            $label = trim((string) ($person->name ?? ''));
            $email = trim((string) ($person->primary_email ?? Arr::first($person->emails ?? []) ?? ''));

            return sprintf('%d: %s%s', $person->id, $label !== '' ? $label : 'Unnamed', $email !== '' ? ' <'.$email.'>' : '');
        })->values()->all();

        $choice = (string) $this->choice('Which Person should be used for WHOIS contact info?', $options);
        $id = (int) Str::before($choice, ':');

        return $people->firstWhere('id', $id);
    }

    private function contactsFromPersonWithOverrides(Person $person): WhoisContactSetData
    {
        [$firstName, $lastName] = $this->splitName((string) ($person->name ?? Arr::first($person->names ?? []) ?? ''));

        $address = Arr::first($person->addresses ?? []);
        $addressStreet = is_array($address) ? (string) ($address['street'] ?? '') : '';
        $addressCity = is_array($address) ? (string) ($address['city'] ?? '') : '';
        $addressState = is_array($address) ? (string) ($address['state'] ?? '') : '';
        $addressPostal = is_array($address) ? (string) ($address['postal_code'] ?? '') : '';
        $addressCountry = is_array($address) ? (string) ($address['country'] ?? '') : '';

        $primaryAddressParsed = $this->parsePrimaryAddress((string) ($person->primary_address ?? ''));

        $data = [
            'contact' => [
                'first_name' => $firstName,
                'last_name' => $lastName,
                'address_1' => $addressStreet !== '' ? $addressStreet : (string) ($primaryAddressParsed['address_1'] ?? (string) ($person->primary_address ?? '')),
                'address_2' => null,
                'city' => $addressCity !== '' ? $addressCity : (string) ($primaryAddressParsed['city'] ?? ''),
                'state_province' => $addressState !== '' ? $addressState : (string) ($primaryAddressParsed['state_province'] ?? ''),
                'postal_code' => $addressPostal !== '' ? $addressPostal : (string) ($primaryAddressParsed['postal_code'] ?? ''),
                'country' => $addressCountry !== '' ? $addressCountry : (string) ($primaryAddressParsed['country'] ?? ''),
                'phone' => (string) ($person->primary_number ?? Arr::first($person->phone_numbers ?? []) ?? ''),
                'email_address' => (string) ($person->primary_email ?? Arr::first($person->emails ?? []) ?? ''),
                'organization_name' => null,
                'job_title' => null,
            ],
        ];

        $overrides = [
            'first_name' => $this->option('first-name'),
            'last_name' => $this->option('last-name'),
            'address_1' => $this->option('address-1'),
            'address_2' => $this->option('address-2'),
            'city' => $this->option('city'),
            'state_province' => $this->option('state-province'),
            'postal_code' => $this->option('postal-code'),
            'country' => $this->option('country'),
            'phone' => $this->option('phone'),
            'email_address' => $this->option('email-address'),
            'organization_name' => $this->option('organization-name'),
            'job_title' => $this->option('job-title'),
        ];

        foreach ($overrides as $key => $value) {
            if (! empty($value)) {
                $data['contact'][$key] = (string) $value;
            }
        }

        // Prompt once for any missing required fields (never per-domain).
        $required = ['first_name', 'last_name', 'address_1', 'city', 'state_province', 'postal_code', 'country', 'phone', 'email_address'];

        foreach ($required as $field) {
            $value = trim((string) ($data['contact'][$field] ?? ''));
            if ($value !== '') {
                continue;
            }

            if (! $this->input->isInteractive()) {
                throw new RuntimeException(sprintf('Missing required contact field "%s". Provide --%s= override or update the Person record.', $field, str_replace('_', '-', $field)));
            }

            $data['contact'][$field] = (string) $this->ask(sprintf('Enter %s', str_replace('_', ' ', $field)));
        }

        // Namecheap expects ISO-3166 alpha-2 codes. If Person has a full country name, prompt once.
        $country = trim((string) $data['contact']['country']);
        if (strlen($country) !== 2) {
            if (! $this->input->isInteractive()) {
                throw new RuntimeException('Country must be a 2-letter code (e.g. US). Provide --country= override.');
            }

            $data['contact']['country'] = strtoupper((string) $this->ask('Country code (2 letters, e.g. US)', strtoupper(substr($country, 0, 2))));
        } else {
            $data['contact']['country'] = strtoupper($country);
        }

        $data['contact']['phone'] = $this->normalizePhoneForNamecheap(
            phone: (string) $data['contact']['phone'],
            country: (string) $data['contact']['country'],
        );

        return WhoisContactSetData::fromArray($data);
    }

    /**
     * Namecheap expects phone numbers in "+CC.NNNNNNNNN" format (e.g. +1.5555555555).
     */
    private function normalizePhoneForNamecheap(string $phone, string $country): string
    {
        $raw = trim($phone);
        if ($raw === '') {
            return '';
        }

        // If it already looks like Namecheap format, keep it.
        if (preg_match('/^\+\d{1,3}\.\d{4,20}$/', $raw)) {
            return $raw;
        }

        $digits = preg_replace('/\D+/', '', $raw) ?? '';
        $digits = trim($digits);
        $country = strtoupper(trim($country));

        if ($digits === '') {
            return $raw;
        }

        // If user provided +{cc}... but no dot, attempt to insert dot after cc.
        if (str_starts_with($raw, '+')) {
            // Guess CC as first 1-3 digits.
            foreach ([1, 2, 3] as $ccLen) {
                if (strlen($digits) <= $ccLen) {
                    continue;
                }

                $cc = substr($digits, 0, $ccLen);
                $rest = substr($digits, $ccLen);

                // Prefer a "reasonable" remainder length (avoid absurdly short).
                if (strlen($rest) >= 4) {
                    return '+'.$cc.'.'.$rest;
                }
            }
        }

        // US/CA common case: 10 digits -> assume +1
        if (in_array($country, ['US', 'CA'], true)) {
            if (strlen($digits) === 10) {
                return '+1.'.$digits;
            }

            if (strlen($digits) === 11 && str_starts_with($digits, '1')) {
                return '+1.'.substr($digits, 1);
            }
        }

        // Fallback: if it's long enough, treat first 1-3 digits as CC and the rest as national number.
        foreach ([1, 2, 3] as $ccLen) {
            if (strlen($digits) <= $ccLen) {
                continue;
            }

            return '+'.substr($digits, 0, $ccLen).'.'.substr($digits, $ccLen);
        }

        return $raw;
    }

    /**
     * primary_address in this app commonly stores a full "street, city, ST 12345" string.
     *
     * @return array{address_1?:string, city?:string, state_province?:string, postal_code?:string, country?:string}
     */
    private function parsePrimaryAddress(string $primaryAddress): array
    {
        $primaryAddress = trim($primaryAddress);
        if ($primaryAddress === '') {
            return [];
        }

        // Common case: "123 Main St, Austin, TX 78701"
        if (preg_match('/^(?<address>.+?),\s*(?<city>.+?),\s*(?<state>[A-Za-z]{2})\s*(?<zip>\d{5}(?:-\d{4})?)\s*$/', $primaryAddress, $m)) {
            return [
                'address_1' => trim((string) ($m['address'] ?? '')),
                'city' => trim((string) ($m['city'] ?? '')),
                'state_province' => strtoupper((string) ($m['state'] ?? '')),
                'postal_code' => trim((string) ($m['zip'] ?? '')),
                // Default to US if state+zip pattern matched and no country is stored elsewhere.
                'country' => 'US',
            ];
        }

        // Slightly different: "123 Main St, Austin, TX, 78701"
        if (preg_match('/^(?<address>.+?),\s*(?<city>.+?),\s*(?<state>[A-Za-z]{2})\s*,\s*(?<zip>\d{5}(?:-\d{4})?)\s*$/', $primaryAddress, $m)) {
            return [
                'address_1' => trim((string) ($m['address'] ?? '')),
                'city' => trim((string) ($m['city'] ?? '')),
                'state_province' => strtoupper((string) ($m['state'] ?? '')),
                'postal_code' => trim((string) ($m['zip'] ?? '')),
                'country' => 'US',
            ];
        }

        // Production common case in this app: "810 Grace St Owosso, MI 48867"
        // (street + city before the comma, then state + zip)
        if (preg_match('/^(?<pre>.+?),\s*(?<state>[A-Za-z]{2})\s*(?<zip>\d{5}(?:-\d{4})?)\s*$/', $primaryAddress, $m)) {
            $pre = trim((string) ($m['pre'] ?? ''));
            $lastSpace = strrpos($pre, ' ');

            // Heuristic: split the part before the comma into "address_1" and "city" using the last space.
            // If it's ambiguous (no spaces), fall back to treating it as address_1.
            $address1 = $lastSpace !== false ? trim(substr($pre, 0, $lastSpace)) : $pre;
            $city = $lastSpace !== false ? trim(substr($pre, $lastSpace + 1)) : '';

            return [
                'address_1' => $address1,
                'city' => $city,
                'state_province' => strtoupper((string) ($m['state'] ?? '')),
                'postal_code' => trim((string) ($m['zip'] ?? '')),
                'country' => 'US',
            ];
        }

        // Fallback: treat as address_1 (will prompt once for missing city/state/zip/country)
        return [
            'address_1' => $primaryAddress,
        ];
    }

    private function splitName(string $name): array
    {
        $name = trim($name);
        if ($name === '') {
            return ['', ''];
        }

        $parts = preg_split('/\s+/', $name) ?: [];
        $first = Arr::first($parts) ?? '';
        $last = count($parts) > 1 ? implode(' ', array_slice($parts, 1)) : '';

        return [$first, $last];
    }
}


