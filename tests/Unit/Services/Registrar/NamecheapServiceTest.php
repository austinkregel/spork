<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Registrar;

use App\Models\Credential;
use App\Services\Registrar\NamecheapService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class NamecheapServiceTest extends TestCase
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

    public function test_get_tlds_returns_registerable_tlds(): void
    {
        $xml = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<ApiResponse Status="OK">
  <Errors />
  <CommandResponse Type="namecheap.domains.getTldList">
    <Tlds>
      <Tld Name="com" IsApiRegisterable="true" />
      <Tld Name="net" IsApiRegisterable="false" />
    </Tlds>
  </CommandResponse>
</ApiResponse>
XML;

        Http::fake([
            NamecheapService::NAMECHEAP_URL.'*' => Http::response($xml, 200),
        ]);

        $service = new NamecheapService($this->makeCredential());

        $tlds = $service->getTlds();

        $this->assertCount(1, $tlds);
        $this->assertSame('com', $tlds[0]['name']);
        $this->assertTrue($tlds[0]['registerable']);
    }

    public function test_get_domains_handles_single_domain_object_shape(): void
    {
        $xml = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<ApiResponse Status="OK">
  <Errors />
  <CommandResponse Type="namecheap.domains.getList">
    <DomainGetListResult>
      <Domain ID="1" Name="example.com" IsExpired="false" IsLocked="false" AutoRenew="false" WhoisGuard="ENABLED" Created="2020-01-01" Expires="2030-01-01" />
    </DomainGetListResult>
    <Paging TotalItems="1" CurrentPage="1" PageSize="10" />
  </CommandResponse>
</ApiResponse>
XML;

        Http::fake([
            NamecheapService::NAMECHEAP_URL.'*' => Http::response($xml, 200),
        ]);

        $service = new NamecheapService($this->makeCredential());

        $domains = $service->getDomains(10, 1);

        $this->assertCount(1, $domains->items());
        $this->assertSame('example.com', $domains->items()[0]['domain']);
        $this->assertTrue($domains->items()[0]['has_whois_guard']);
    }

    public function test_get_tlds_throws_on_error(): void
    {
        $xml = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<ApiResponse Status="ERROR">
  <Errors>
    <Error Number="101010">Something went wrong</Error>
  </Errors>
</ApiResponse>
XML;

        Http::fake([
            NamecheapService::NAMECHEAP_URL.'*' => Http::response($xml, 200),
        ]);

        $service = new NamecheapService($this->makeCredential());

        $this->expectException(\Exception::class);

        // getTlds uses xml_parse_into_struct, so we assert that an exception
        // is thrown when the response contains errors.
        $service->getTlds();
    }

    public function test_search_domain_maps_available_result(): void
    {
        $xml = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<ApiResponse Status="OK">
  <Errors />
  <CommandResponse Type="namecheap.domains.check">
    <DomainCheckResult Domain="example.com" Available="true" IsPremiumName="false" />
  </CommandResponse>
</ApiResponse>
XML;

        Http::fake([
            NamecheapService::NAMECHEAP_URL.'*' => Http::response($xml, 200),
        ]);

        $service = new NamecheapService($this->makeCredential());

        $result = $service->searchDomain('example.com');

        $this->assertSame('example.com', $result['domain']);
        $this->assertTrue($result['available']);
        $this->assertFalse($result['is_premium']);
    }

    public function test_search_domain_throws_on_error(): void
    {
        $xml = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<ApiResponse Status="ERROR">
  <Errors>
    <Error Number="2019166">Parameter DomainList is missing</Error>
  </Errors>
</ApiResponse>
XML;

        Http::fake([
            NamecheapService::NAMECHEAP_URL.'*' => Http::response($xml, 200),
        ]);

        $service = new NamecheapService($this->makeCredential());

        $this->expectException(\Exception::class);

        $service->searchDomain('example.com');
    }

    public function test_register_domain_maps_success_result(): void
    {
        $xml = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<ApiResponse Status="OK">
  <Errors />
  <CommandResponse Type="namecheap.domains.create">
    <DomainCreateResult Domain="example.com" Registered="true" OrderID="123" TransactionID="456" IsSuccess="true" />
  </CommandResponse>
</ApiResponse>
XML;

        Http::fake([
            NamecheapService::NAMECHEAP_URL.'*' => Http::response($xml, 200),
        ]);

        $service = new NamecheapService($this->makeCredential());

        $result = $service->registerDomain('example.com', 1);

        $this->assertSame('example.com', $result['domain']);
        $this->assertTrue($result['success']);
        $this->assertSame('123', $result['order_id']);
        $this->assertSame('456', $result['transaction_id']);
    }

    public function test_register_domain_throws_on_error(): void
    {
        $xml = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<ApiResponse Status="ERROR">
  <Errors>
    <Error Number="2019166">Some error</Error>
  </Errors>
</ApiResponse>
XML;

        Http::fake([
            NamecheapService::NAMECHEAP_URL.'*' => Http::response($xml, 200),
        ]);

        $service = new NamecheapService($this->makeCredential());

        $this->expectException(\Exception::class);

        $service->registerDomain('example.com', 1);
    }

    public function test_renew_domain_maps_success_result(): void
    {
        $xml = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<ApiResponse Status="OK">
  <Errors />
  <CommandResponse Type="namecheap.domains.renew">
    <DomainRenewResult Domain="example.com" Renew="true" OrderID="789" TransactionID="999" IsSuccess="true" />
  </CommandResponse>
</ApiResponse>
XML;

        Http::fake([
            NamecheapService::NAMECHEAP_URL.'*' => Http::response($xml, 200),
        ]);

        $service = new NamecheapService($this->makeCredential());

        $result = $service->renewDomain('example.com', 1);

        $this->assertSame('example.com', $result['domain']);
        $this->assertTrue($result['success']);
        $this->assertSame('789', $result['order_id']);
        $this->assertSame('999', $result['transaction_id']);
    }

    public function test_renew_domain_throws_on_error(): void
    {
        $xml = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<ApiResponse Status="ERROR">
  <Errors>
    <Error Number="2019166">Some error</Error>
  </Errors>
</ApiResponse>
XML;

        Http::fake([
            NamecheapService::NAMECHEAP_URL.'*' => Http::response($xml, 200),
        ]);

        $service = new NamecheapService($this->makeCredential());

        $this->expectException(\Exception::class);

        $service->renewDomain('example.com', 1);
    }
}
