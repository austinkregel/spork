<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Registrar;

use App\Models\Credential;
use App\Services\Registrar\TucowsEnomService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TucowsEnomServiceTest extends TestCase
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
            TucowsEnomService::ENOM_URL.'*' => Http::response($xml, 200),
        ]);

        $service = new TucowsEnomService($this->makeCredential());

        $tlds = $service->getTlds();

        $this->assertCount(1, $tlds);
        $this->assertSame('com', $tlds[0]['name']);
        $this->assertTrue($tlds[0]['registerable']);
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
            TucowsEnomService::ENOM_URL.'*' => Http::response($xml, 200),
        ]);

        $service = new TucowsEnomService($this->makeCredential());

        $result = $service->searchDomain('example.com');

        $this->assertSame('example.com', $result['domain']);
        $this->assertTrue($result['available']);
        $this->assertFalse($result['is_premium']);
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
            TucowsEnomService::ENOM_URL.'*' => Http::response($xml, 200),
        ]);

        $service = new TucowsEnomService($this->makeCredential());

        $result = $service->registerDomain('example.com', 1);

        $this->assertSame('example.com', $result['domain']);
        $this->assertTrue($result['success']);
        $this->assertSame('123', $result['order_id']);
        $this->assertSame('456', $result['transaction_id']);
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
            TucowsEnomService::ENOM_URL.'*' => Http::response($xml, 200),
        ]);

        $service = new TucowsEnomService($this->makeCredential());

        $result = $service->renewDomain('example.com', 1);

        $this->assertSame('example.com', $result['domain']);
        $this->assertTrue($result['success']);
        $this->assertSame('789', $result['order_id']);
        $this->assertSame('999', $result['transaction_id']);
    }
}
