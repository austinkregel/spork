<?php

declare(strict_types=1);

namespace Tests\Feature\Console;

use App\Models\Credential;
use App\Models\Person;
use App\Services\Registrar\NamecheapService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class UpdateNamecheapWhoisCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_updates_contacts_and_attempts_privacy_enable(): void
    {
        $credential = Credential::factory()->create([
            'service' => Credential::NAMECHEAP,
            'type' => Credential::TYPE_REGISTRAR,
            'access_token' => 'fake-token',
            'settings' => [
                'api_user' => 'api-user',
                'username' => 'api-user',
                'client_ip' => '127.0.0.1',
            ],
        ]);

        $person = Person::factory()->create([
            'user_id' => $credential->user_id,
            'name' => 'Jane Doe',
            'primary_email' => 'jane@example.com',
            'primary_number' => '+1.5125551212',
            'addresses' => [
                [
                    'street' => '123 Main St',
                    'city' => 'Austin',
                    'state' => 'TX',
                    'postal_code' => '78701',
                    'country' => 'US',
                ],
            ],
        ]);

        $domainsXml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<ApiResponse Status="OK">
  <Errors />
  <CommandResponse Type="namecheap.domains.getList">
    <DomainGetListResult>
      <Domain ID="1" Name="example.com" IsExpired="false" IsLocked="false" AutoRenew="false" WhoisGuard="NOTPRESENT" Created="2020-01-01" Expires="2030-01-01" />
    </DomainGetListResult>
    <Paging TotalItems="1" CurrentPage="1" PageSize="100" />
  </CommandResponse>
</ApiResponse>
XML;

        $setContactsXml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<ApiResponse Status="OK">
  <Errors />
  <CommandResponse Type="namecheap.domains.setContacts">
    <DomainSetContactsResult Domain="example.com" IsSuccess="true" />
  </CommandResponse>
</ApiResponse>
XML;

        $whoisguardListXml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<ApiResponse Status="OK">
  <Errors />
  <CommandResponse Type="namecheap.whoisguard.getList">
    <WhoisguardGetListResult>
      <Whoisguard ID="123" DomainName="example.com" Status="DISABLED" />
    </WhoisguardGetListResult>
  </CommandResponse>
</ApiResponse>
XML;

        $whoisguardEnableXml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<ApiResponse Status="OK">
  <Errors />
  <CommandResponse Type="namecheap.whoisguard.enable">
    <WhoisguardEnableResult WhoisguardID="123" IsSuccess="true" />
  </CommandResponse>
</ApiResponse>
XML;

        Http::fake(function ($request) use ($domainsXml, $setContactsXml, $whoisguardListXml, $whoisguardEnableXml) {
            $url = $request->url();

            if (str_contains($url, 'Command=namecheap.domains.getList')) {
                return Http::response($domainsXml, 200);
            }

            if (str_contains($url, 'Command=namecheap.domains.setContacts')) {
                return Http::response($setContactsXml, 200);
            }

            if (str_contains($url, 'Command=namecheap.whoisguard.getList')) {
                return Http::response($whoisguardListXml, 200);
            }

            if (str_contains($url, 'Command=namecheap.whoisguard.enable')) {
                return Http::response($whoisguardEnableXml, 200);
            }

            return Http::response('unexpected', 500);
        });

        $this->artisan('infrastructure:namecheap:update-whois', [
            '--credential-id' => (string) $credential->id,
            '--person-id' => (string) $person->id,
        ])->assertExitCode(0);

        Http::assertSentCount(4);
    }

    public function test_command_fails_when_credential_is_not_namecheap(): void
    {
        $credential = Credential::factory()->create([
            'service' => Credential::CLOUDFLARE,
            'type' => Credential::TYPE_REGISTRAR,
        ]);

        $person = Person::factory()->create([
            'user_id' => $credential->user_id,
            'name' => 'Jane Doe',
        ]);

        $this->artisan('infrastructure:namecheap:update-whois', [
            '--credential-id' => (string) $credential->id,
            '--person-id' => (string) $person->id,
        ])->assertExitCode(1);
    }

    public function test_command_can_use_primary_address_full_string_when_addresses_are_empty(): void
    {
        $credential = Credential::factory()->create([
            'service' => Credential::NAMECHEAP,
            'type' => Credential::TYPE_REGISTRAR,
            'access_token' => 'fake-token',
            'settings' => [
                'api_user' => 'api-user',
                'username' => 'api-user',
                'client_ip' => '127.0.0.1',
            ],
        ]);

        $person = Person::factory()->create([
            'user_id' => $credential->user_id,
            'name' => 'Jane Doe',
            'primary_email' => 'jane@example.com',
            'primary_number' => '+1.5125551212',
            'addresses' => [],
            // Important: stored as full address city/state/zip in this app
            'primary_address' => '123 Main St, Austin, TX 78701',
        ]);

        $domainsXml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<ApiResponse Status="OK">
  <Errors />
  <CommandResponse Type="namecheap.domains.getList">
    <DomainGetListResult>
      <Domain ID="1" Name="example.com" IsExpired="false" IsLocked="false" AutoRenew="false" WhoisGuard="NOTPRESENT" Created="2020-01-01" Expires="2030-01-01" />
    </DomainGetListResult>
    <Paging TotalItems="1" CurrentPage="1" PageSize="100" />
  </CommandResponse>
</ApiResponse>
XML;

        $setContactsXml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<ApiResponse Status="OK">
  <Errors />
  <CommandResponse Type="namecheap.domains.setContacts">
    <DomainSetContactsResult Domain="example.com" IsSuccess="true" />
  </CommandResponse>
</ApiResponse>
XML;

        $whoisguardListXml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<ApiResponse Status="OK">
  <Errors />
  <CommandResponse Type="namecheap.whoisguard.getList">
    <WhoisguardGetListResult />
  </CommandResponse>
</ApiResponse>
XML;

        Http::fake(function ($request) use ($domainsXml, $setContactsXml, $whoisguardListXml) {
            $url = $request->url();

            if (str_contains($url, 'Command=namecheap.domains.getList')) {
                return Http::response($domainsXml, 200);
            }

            if (str_contains($url, 'Command=namecheap.domains.setContacts')) {
                return Http::response($setContactsXml, 200);
            }

            if (str_contains($url, 'Command=namecheap.whoisguard.getList')) {
                return Http::response($whoisguardListXml, 200);
            }

            return Http::response('unexpected', 500);
        });

        $this->artisan('infrastructure:namecheap:update-whois', [
            '--credential-id' => (string) $credential->id,
            '--person-id' => (string) $person->id,
        ])->assertExitCode(0);

        Http::assertSent(function ($request) {
            $url = $request->url();

            if (! str_contains($url, 'Command=namecheap.domains.setContacts')) {
                return false;
            }

            return str_contains($url, 'RegistrantAddress1=123+Main+St')
                && str_contains($url, 'RegistrantCity=Austin')
                && str_contains($url, 'RegistrantStateProvince=TX')
                && str_contains($url, 'RegistrantPostalCode=78701')
                && str_contains($url, 'RegistrantCountry=US');
        });
    }

    public function test_command_can_parse_production_primary_address_format(): void
    {
        $credential = Credential::factory()->create([
            'service' => Credential::NAMECHEAP,
            'type' => Credential::TYPE_REGISTRAR,
            'access_token' => 'fake-token',
            'settings' => [
                'api_user' => 'api-user',
                'username' => 'api-user',
                'client_ip' => '127.0.0.1',
            ],
        ]);

        $person = Person::factory()->create([
            'user_id' => $credential->user_id,
            'name' => 'Jane Doe',
            'primary_email' => 'jane@example.com',
            'primary_number' => '+1.5125551212',
            'addresses' => [],
            // Example from production:
            'primary_address' => '810 Grace St Owosso, MI 48867',
        ]);

        $domainsXml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<ApiResponse Status="OK">
  <Errors />
  <CommandResponse Type="namecheap.domains.getList">
    <DomainGetListResult>
      <Domain ID="1" Name="example.com" IsExpired="false" IsLocked="false" AutoRenew="false" WhoisGuard="NOTPRESENT" Created="2020-01-01" Expires="2030-01-01" />
    </DomainGetListResult>
    <Paging TotalItems="1" CurrentPage="1" PageSize="100" />
  </CommandResponse>
</ApiResponse>
XML;

        $setContactsXml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<ApiResponse Status="OK">
  <Errors />
  <CommandResponse Type="namecheap.domains.setContacts">
    <DomainSetContactsResult Domain="example.com" IsSuccess="true" />
  </CommandResponse>
</ApiResponse>
XML;

        $whoisguardListXml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<ApiResponse Status="OK">
  <Errors />
  <CommandResponse Type="namecheap.whoisguard.getList">
    <WhoisguardGetListResult />
  </CommandResponse>
</ApiResponse>
XML;

        Http::fake(function ($request) use ($domainsXml, $setContactsXml, $whoisguardListXml) {
            $url = $request->url();

            if (str_contains($url, 'Command=namecheap.domains.getList')) {
                return Http::response($domainsXml, 200);
            }

            if (str_contains($url, 'Command=namecheap.domains.setContacts')) {
                return Http::response($setContactsXml, 200);
            }

            if (str_contains($url, 'Command=namecheap.whoisguard.getList')) {
                return Http::response($whoisguardListXml, 200);
            }

            return Http::response('unexpected', 500);
        });

        $this->artisan('infrastructure:namecheap:update-whois', [
            '--credential-id' => (string) $credential->id,
            '--person-id' => (string) $person->id,
        ])->assertExitCode(0);

        Http::assertSent(function ($request) {
            $url = $request->url();

            if (! str_contains($url, 'Command=namecheap.domains.setContacts')) {
                return false;
            }

            return str_contains($url, 'RegistrantAddress1=810+Grace+St')
                && str_contains($url, 'RegistrantCity=Owosso')
                && str_contains($url, 'RegistrantStateProvince=MI')
                && str_contains($url, 'RegistrantPostalCode=48867')
                && str_contains($url, 'RegistrantCountry=US');
        });
    }

    public function test_command_domain_filter_only_updates_matching_domain(): void
    {
        $credential = Credential::factory()->create([
            'service' => Credential::NAMECHEAP,
            'type' => Credential::TYPE_REGISTRAR,
            'access_token' => 'fake-token',
            'settings' => [
                'api_user' => 'api-user',
                'username' => 'api-user',
                'client_ip' => '127.0.0.1',
            ],
        ]);

        $person = Person::factory()->create([
            'user_id' => $credential->user_id,
            'name' => 'Jane Doe',
            'primary_email' => 'jane@example.com',
            'primary_number' => '+1.5125551212',
            'addresses' => [
                [
                    'street' => '123 Main St',
                    'city' => 'Austin',
                    'state' => 'TX',
                    'postal_code' => '78701',
                    'country' => 'US',
                ],
            ],
        ]);

        $domainsXml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<ApiResponse Status="OK">
  <Errors />
  <CommandResponse Type="namecheap.domains.getList">
    <DomainGetListResult>
      <Domain ID="1" Name="example.com" IsExpired="false" IsLocked="false" AutoRenew="false" WhoisGuard="NOTPRESENT" Created="2020-01-01" Expires="2030-01-01" />
      <Domain ID="2" Name="other.com" IsExpired="false" IsLocked="false" AutoRenew="false" WhoisGuard="NOTPRESENT" Created="2020-01-01" Expires="2030-01-01" />
    </DomainGetListResult>
    <Paging TotalItems="2" CurrentPage="1" PageSize="100" />
  </CommandResponse>
</ApiResponse>
XML;

        $setContactsXml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<ApiResponse Status="OK">
  <Errors />
  <CommandResponse Type="namecheap.domains.setContacts">
    <DomainSetContactsResult Domain="example.com" IsSuccess="true" />
  </CommandResponse>
</ApiResponse>
XML;

        $whoisguardListXml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<ApiResponse Status="OK">
  <Errors />
  <CommandResponse Type="namecheap.whoisguard.getList">
    <WhoisguardGetListResult />
  </CommandResponse>
</ApiResponse>
XML;

        Http::fake(function ($request) use ($domainsXml, $setContactsXml, $whoisguardListXml) {
            $url = $request->url();

            if (str_contains($url, 'Command=namecheap.domains.getList')) {
                return Http::response($domainsXml, 200);
            }

            if (str_contains($url, 'Command=namecheap.domains.setContacts')) {
                return Http::response($setContactsXml, 200);
            }

            if (str_contains($url, 'Command=namecheap.whoisguard.getList')) {
                return Http::response($whoisguardListXml, 200);
            }

            if (str_contains($url, 'Command=namecheap.whoisguard.enable')) {
                return Http::response($whoisguardListXml, 200);
            }

            return Http::response('unexpected', 500);
        });

        $this->artisan('infrastructure:namecheap:update-whois', [
            '--credential-id' => (string) $credential->id,
            '--person-id' => (string) $person->id,
            '--domain' => 'example.com',
        ])->assertExitCode(0);

        Http::assertSent(function ($request) {
            $url = $request->url();

            return str_contains($url, 'Command=namecheap.domains.setContacts')
                && str_contains($url, 'DomainName=example.com');
        });

        Http::assertNotSent(function ($request) {
            $url = $request->url();

            return str_contains($url, 'Command=namecheap.domains.setContacts')
                && str_contains($url, 'DomainName=other.com');
        });
    }

    public function test_command_normalizes_us_phone_to_namecheap_format(): void
    {
        $credential = Credential::factory()->create([
            'service' => Credential::NAMECHEAP,
            'type' => Credential::TYPE_REGISTRAR,
            'access_token' => 'fake-token',
            'settings' => [
                'api_user' => 'api-user',
                'username' => 'api-user',
                'client_ip' => '127.0.0.1',
            ],
        ]);

        $person = Person::factory()->create([
            'user_id' => $credential->user_id,
            'name' => 'Jane Doe',
            'primary_email' => 'jane@example.com',
            // Common format that Namecheap rejects unless normalized:
            'primary_number' => '(989) 123-4567',
            'addresses' => [
                [
                    'street' => '123 Main St',
                    'city' => 'Austin',
                    'state' => 'TX',
                    'postal_code' => '78701',
                    'country' => 'US',
                ],
            ],
        ]);

        $domainsXml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<ApiResponse Status="OK">
  <Errors />
  <CommandResponse Type="namecheap.domains.getList">
    <DomainGetListResult>
      <Domain ID="1" Name="example.com" IsExpired="false" IsLocked="false" AutoRenew="false" WhoisGuard="NOTPRESENT" Created="2020-01-01" Expires="2030-01-01" />
    </DomainGetListResult>
    <Paging TotalItems="1" CurrentPage="1" PageSize="100" />
  </CommandResponse>
</ApiResponse>
XML;

        $setContactsXml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<ApiResponse Status="OK">
  <Errors />
  <CommandResponse Type="namecheap.domains.setContacts">
    <DomainSetContactsResult Domain="example.com" IsSuccess="true" />
  </CommandResponse>
</ApiResponse>
XML;

        $whoisguardListXml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<ApiResponse Status="OK">
  <Errors />
  <CommandResponse Type="namecheap.whoisguard.getList">
    <WhoisguardGetListResult />
  </CommandResponse>
</ApiResponse>
XML;

        Http::fake(function ($request) use ($domainsXml, $setContactsXml, $whoisguardListXml) {
            $url = $request->url();

            if (str_contains($url, 'Command=namecheap.domains.getList')) {
                return Http::response($domainsXml, 200);
            }

            if (str_contains($url, 'Command=namecheap.domains.setContacts')) {
                return Http::response($setContactsXml, 200);
            }

            if (str_contains($url, 'Command=namecheap.whoisguard.getList')) {
                return Http::response($whoisguardListXml, 200);
            }

            return Http::response('unexpected', 500);
        });

        $this->artisan('infrastructure:namecheap:update-whois', [
            '--credential-id' => (string) $credential->id,
            '--person-id' => (string) $person->id,
            '--domain' => 'example.com',
        ])->assertExitCode(0);

        Http::assertSent(function ($request) {
            $url = $request->url();

            if (! str_contains($url, 'Command=namecheap.domains.setContacts')) {
                return false;
            }

            // + needs URL encoding in the query string.
            return str_contains($url, 'RegistrantPhone=%2B1.9891234567')
                && str_contains($url, 'AdminPhone=%2B1.9891234567')
                && str_contains($url, 'TechPhone=%2B1.9891234567')
                && str_contains($url, 'AuxBillingPhone=%2B1.9891234567');
        });
    }

    public function test_command_skips_contact_updates_when_registry_blocks_modifications(): void
    {
        $credential = Credential::factory()->create([
            'service' => Credential::NAMECHEAP,
            'type' => Credential::TYPE_REGISTRAR,
            'access_token' => 'fake-token',
            'settings' => [
                'api_user' => 'api-user',
                'username' => 'api-user',
                'client_ip' => '127.0.0.1',
            ],
        ]);

        $person = Person::factory()->create([
            'user_id' => $credential->user_id,
            'name' => 'Jane Doe',
            'primary_email' => 'jane@example.com',
            'primary_number' => '+1.5125551212',
            'addresses' => [
                [
                    'street' => '123 Main St',
                    'city' => 'Austin',
                    'state' => 'TX',
                    'postal_code' => '78701',
                    'country' => 'US',
                ],
            ],
        ]);

        $domainsXml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<ApiResponse Status="OK">
  <Errors />
  <CommandResponse Type="namecheap.domains.getList">
    <DomainGetListResult>
      <Domain ID="1" Name="kbc.li" IsExpired="false" IsLocked="false" AutoRenew="false" WhoisGuard="NOTPRESENT" Created="2020-01-01" Expires="2030-01-01" />
    </DomainGetListResult>
    <Paging TotalItems="1" CurrentPage="1" PageSize="100" />
  </CommandResponse>
</ApiResponse>
XML;

        $setContactsBlockedXml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<ApiResponse Status="ERROR">
  <Errors>
    <Error Number="2019166">Cannot set the contact information for the given tld(li). Because its disableModstatus is true</Error>
  </Errors>
</ApiResponse>
XML;

        $whoisguardListXml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<ApiResponse Status="OK">
  <Errors />
  <CommandResponse Type="namecheap.whoisguard.getList">
    <WhoisguardGetListResult />
  </CommandResponse>
</ApiResponse>
XML;

        Http::fake(function ($request) use ($domainsXml, $setContactsBlockedXml, $whoisguardListXml) {
            $url = $request->url();

            if (str_contains($url, 'Command=namecheap.domains.getList')) {
                return Http::response($domainsXml, 200);
            }

            if (str_contains($url, 'Command=namecheap.domains.setContacts')) {
                return Http::response($setContactsBlockedXml, 200);
            }

            if (str_contains($url, 'Command=namecheap.whoisguard.getList')) {
                return Http::response($whoisguardListXml, 200);
            }

            return Http::response('unexpected', 500);
        });

        // With a domain filter, we treat "contacts blocked" as a failure (exit 1),
        // but we still attempt privacy.
        $this->artisan('infrastructure:namecheap:update-whois', [
            '--credential-id' => (string) $credential->id,
            '--person-id' => (string) $person->id,
            '--domain' => 'kbc.li',
        ])->assertExitCode(1);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'Command=namecheap.whoisguard.getList')
                && str_contains($request->url(), 'SearchTerm=kbc.li');
        });
    }

    public function test_command_can_set_per_domain_contact_email_alias(): void
    {
        $credential = Credential::factory()->create([
            'service' => Credential::NAMECHEAP,
            'type' => Credential::TYPE_REGISTRAR,
            'access_token' => 'fake-token',
            'settings' => [
                'api_user' => 'api-user',
                'username' => 'api-user',
                'client_ip' => '127.0.0.1',
            ],
        ]);

        $person = Person::factory()->create([
            'user_id' => $credential->user_id,
            'name' => 'Jane Doe',
            'primary_email' => 'jane@example.com',
            'primary_number' => '+1.5125551212',
            'addresses' => [
                [
                    'street' => '123 Main St',
                    'city' => 'Austin',
                    'state' => 'TX',
                    'postal_code' => '78701',
                    'country' => 'US',
                ],
            ],
        ]);

        $domainsXml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<ApiResponse Status="OK">
  <Errors />
  <CommandResponse Type="namecheap.domains.getList">
    <DomainGetListResult>
      <Domain ID="1" Name="kbc.li" IsExpired="false" IsLocked="false" AutoRenew="false" WhoisGuard="NOTPRESENT" Created="2020-01-01" Expires="2030-01-01" />
    </DomainGetListResult>
    <Paging TotalItems="1" CurrentPage="1" PageSize="100" />
  </CommandResponse>
</ApiResponse>
XML;

        $setContactsXml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<ApiResponse Status="OK">
  <Errors />
  <CommandResponse Type="namecheap.domains.setContacts">
    <DomainSetContactsResult Domain="kbc.li" IsSuccess="true" />
  </CommandResponse>
</ApiResponse>
XML;

        $whoisguardListXml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<ApiResponse Status="OK">
  <Errors />
  <CommandResponse Type="namecheap.whoisguard.getList">
    <WhoisguardGetListResult />
  </CommandResponse>
</ApiResponse>
XML;

        Http::fake(function ($request) use ($domainsXml, $setContactsXml, $whoisguardListXml) {
            $url = $request->url();

            if (str_contains($url, 'Command=namecheap.domains.getList')) {
                return Http::response($domainsXml, 200);
            }

            if (str_contains($url, 'Command=namecheap.domains.setContacts')) {
                return Http::response($setContactsXml, 200);
            }

            if (str_contains($url, 'Command=namecheap.whoisguard.getList')) {
                return Http::response($whoisguardListXml, 200);
            }

            return Http::response('unexpected', 500);
        });

        $this->artisan('infrastructure:namecheap:update-whois', [
            '--credential-id' => (string) $credential->id,
            '--person-id' => (string) $person->id,
            '--domain' => 'kbc.li',
            '--contact-email-domain' => 'kregel.email',
        ])->assertExitCode(0);

        Http::assertSent(function ($request) {
            $url = $request->url();

            if (! str_contains($url, 'Command=namecheap.domains.setContacts')) {
                return false;
            }

            // local-part should be: kbc_li_domains
            return str_contains($url, 'RegistrantEmailAddress=kbc_li_domains%40kregel.email')
                && str_contains($url, 'AdminEmailAddress=kbc_li_domains%40kregel.email')
                && str_contains($url, 'TechEmailAddress=kbc_li_domains%40kregel.email')
                && str_contains($url, 'AuxBillingEmailAddress=kbc_li_domains%40kregel.email');
        });
    }

    public function test_command_retries_on_namecheap_rate_limit_error_for_contacts(): void
    {
        $credential = Credential::factory()->create([
            'service' => Credential::NAMECHEAP,
            'type' => Credential::TYPE_REGISTRAR,
            'access_token' => 'fake-token',
            'settings' => [
                'api_user' => 'api-user',
                'username' => 'api-user',
                'client_ip' => '127.0.0.1',
            ],
        ]);

        $person = Person::factory()->create([
            'user_id' => $credential->user_id,
            'name' => 'Jane Doe',
            'primary_email' => 'jane@example.com',
            'primary_number' => '+1.5125551212',
            'addresses' => [
                [
                    'street' => '123 Main St',
                    'city' => 'Austin',
                    'state' => 'TX',
                    'postal_code' => '78701',
                    'country' => 'US',
                ],
            ],
        ]);

        $domainsXml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<ApiResponse Status="OK">
  <Errors />
  <CommandResponse Type="namecheap.domains.getList">
    <DomainGetListResult>
      <Domain ID="1" Name="example.com" IsExpired="false" IsLocked="false" AutoRenew="false" WhoisGuard="NOTPRESENT" Created="2020-01-01" Expires="2030-01-01" />
    </DomainGetListResult>
    <Paging TotalItems="1" CurrentPage="1" PageSize="100" />
  </CommandResponse>
</ApiResponse>
XML;

        $rateLimitedXml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<ApiResponse Status="ERROR">
  <Errors>
    <Error Number="2019166">Too many requests</Error>
  </Errors>
</ApiResponse>
XML;

        $setContactsOkXml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<ApiResponse Status="OK">
  <Errors />
  <CommandResponse Type="namecheap.domains.setContacts">
    <DomainSetContactsResult Domain="example.com" IsSuccess="true" />
  </CommandResponse>
</ApiResponse>
XML;

        $whoisguardListXml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<ApiResponse Status="OK">
  <Errors />
  <CommandResponse Type="namecheap.whoisguard.getList">
    <WhoisguardGetListResult />
  </CommandResponse>
</ApiResponse>
XML;

        $setContactsCalls = 0;

        Http::fake(function ($request) use ($domainsXml, $rateLimitedXml, $setContactsOkXml, $whoisguardListXml, &$setContactsCalls) {
            $url = $request->url();

            if (str_contains($url, 'Command=namecheap.domains.getList')) {
                return Http::response($domainsXml, 200);
            }

            if (str_contains($url, 'Command=namecheap.domains.setContacts')) {
                $setContactsCalls++;

                return $setContactsCalls === 1
                    ? Http::response($rateLimitedXml, 200)
                    : Http::response($setContactsOkXml, 200);
            }

            if (str_contains($url, 'Command=namecheap.whoisguard.getList')) {
                return Http::response($whoisguardListXml, 200);
            }

            return Http::response('unexpected', 500);
        });

        $this->artisan('infrastructure:namecheap:update-whois', [
            '--credential-id' => (string) $credential->id,
            '--person-id' => (string) $person->id,
            '--domain' => 'example.com',
            '--rate-limit-max-retries' => 2,
            '--rate-limit-backoff-ms' => 1,
            '--rate-limit-jitter-ms' => 0,
        ])->assertExitCode(0);

        $this->assertSame(2, $setContactsCalls);
    }
}


