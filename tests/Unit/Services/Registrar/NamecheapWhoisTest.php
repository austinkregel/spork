<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Registrar;

use App\Data\Registrar\WhoisContactSetData;
use App\Models\Credential;
use App\Services\Registrar\NamecheapService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class NamecheapWhoisTest extends TestCase
{
    protected function makeCredential(): Credential
    {
        return new Credential([
            'access_token' => 'fake-token',
            'settings' => [
                'api_user' => 'api-user',
                'username' => 'api-user',
                'client_ip' => '127.0.0.1',
            ],
        ]);
    }

    public function test_set_domain_contacts_hits_set_contacts_command(): void
    {
        $xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<ApiResponse Status="OK">
  <Errors />
  <CommandResponse Type="namecheap.domains.setContacts">
    <DomainSetContactsResult Domain="example.com" IsSuccess="true" />
  </CommandResponse>
</ApiResponse>
XML;

        Http::fake([
            NamecheapService::NAMECHEAP_URL.'*' => Http::response($xml, 200),
        ]);

        $service = new NamecheapService($this->makeCredential());

        $contacts = WhoisContactSetData::fromArray([
            'contact' => [
                'first_name' => 'Jane',
                'last_name' => 'Doe',
                'address_1' => '123 Main St',
                'city' => 'Austin',
                'state_province' => 'TX',
                'postal_code' => '78701',
                'country' => 'US',
                'phone' => '+1.5125551212',
                'email_address' => 'jane@example.com',
            ],
        ]);

        $service->setDomainContacts('example.com', $contacts);

        Http::assertSent(function ($request) {
            $url = $request->url();

            return str_contains($url, 'Command=namecheap.domains.setContacts')
                && str_contains($url, 'DomainName=example.com')
                && str_contains($url, 'RegistrantFirstName=Jane')
                && str_contains($url, 'AdminEmailAddress=jane%40example.com');
        });
    }

    public function test_enable_whois_guard_returns_false_when_not_available(): void
    {
        $xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<ApiResponse Status="OK">
  <Errors />
  <CommandResponse Type="namecheap.whoisguard.getList">
    <WhoisguardGetListResult>
      <Whoisguard ID="999" DomainName="different.com" Status="NOTPRESENT" />
    </WhoisguardGetListResult>
  </CommandResponse>
</ApiResponse>
XML;

        Http::fake([
            NamecheapService::NAMECHEAP_URL.'*' => Http::response($xml, 200),
        ]);

        $service = new NamecheapService($this->makeCredential());

        $this->assertFalse($service->enableWhoisGuardForDomain('example.com'));
    }
}


