<?php

declare(strict_types=1);

namespace Tests\Feature\Dav;

use App\Models\Person;
use App\Models\User;
use App\Services\Dav\Sources\PersonCardDavSource;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

class CardDavReadTest extends DavTestCase
{
    use RefreshDatabase;

    public function test_propfind_lists_only_authenticated_users_people(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();

        $mine = Person::factory()->create([
            'user_id' => $owner->id,
            'name' => 'Mine Self',
            'identifiers' => [PersonCardDavSource::UID_KEY => (string) Str::uuid()],
        ]);

        Person::factory()->create([
            'user_id' => $other->id,
            'name' => 'Not Mine',
            'identifiers' => [PersonCardDavSource::UID_KEY => (string) Str::uuid()],
        ]);

        $token = $this->tokenFor($owner);

        $response = $this->davRequest(
            'PROPFIND',
            '/dav/addressbooks/'.$owner->id.'/default/',
            $token,
            '',
            ['Depth' => '1'],
        );

        $response->assertStatus(207);
        $body = $response->getContent();

        $this->assertStringContainsString($mine->identifiers[PersonCardDavSource::UID_KEY].'.vcf', $body);
        $this->assertStringNotContainsString('Not Mine', $body);
    }

    public function test_get_card_returns_vcard_with_etag(): void
    {
        $owner = User::factory()->create();
        $person = Person::factory()->create([
            'user_id' => $owner->id,
            'name' => 'Sample Person',
            'primary_email' => 'sample@example.test',
            'emails' => [['email' => 'sample@example.test', 'type' => 'work', 'primary' => true]],
            'identifiers' => [PersonCardDavSource::UID_KEY => 'fixed-uid-123'],
        ]);

        $response = $this->davRequest(
            'GET',
            '/dav/addressbooks/'.$owner->id.'/default/fixed-uid-123.vcf',
            $this->tokenFor($owner),
        );

        $response->assertStatus(200);
        $body = $response->getContent();

        $this->assertStringContainsString('BEGIN:VCARD', $body);
        $this->assertStringContainsString('UID:fixed-uid-123', $body);
        $this->assertStringContainsString('FN:Sample Person', $body);
        $this->assertStringContainsString('sample@example.test', $body);
        $this->assertNotEmpty($response->headers->get('ETag'));

        unset($person);
    }

    public function test_unknown_card_returns_404(): void
    {
        $owner = User::factory()->create();

        $response = $this->davRequest(
            'GET',
            '/dav/addressbooks/'.$owner->id.'/default/missing.vcf',
            $this->tokenFor($owner),
        );

        $response->assertStatus(404);
    }
}
