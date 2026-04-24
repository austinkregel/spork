<?php

declare(strict_types=1);

namespace Tests\Feature\Dav;

use App\Jobs\Crm\SyncPersonToMonica;
use App\Models\Person;
use App\Models\User;
use App\Services\Dav\Sources\PersonCardDavSource;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;

class CardDavWriteTest extends DavTestCase
{
    use RefreshDatabase;

    public function test_put_creates_new_person_and_dispatches_monica_sync(): void
    {
        Bus::fake([SyncPersonToMonica::class]);

        $owner = User::factory()->create();
        $token = $this->tokenFor($owner);

        $vcard = $this->vcard('newcontact-uid', 'Alice Example', 'alice@example.test');

        $response = $this->davRequest(
            'PUT',
            '/dav/addressbooks/'.$owner->id.'/default/newcontact-uid.vcf',
            $token,
            $vcard,
            ['Content-Type' => 'text/vcard'],
        );

        $this->assertContains($response->getStatusCode(), [201, 204]);

        $person = Person::query()->where('user_id', $owner->id)->first();
        $this->assertNotNull($person);
        $this->assertSame('Alice Example', $person->name);
        $this->assertSame('alice@example.test', $person->primary_email);
        $this->assertSame('newcontact-uid', $person->identifiers[PersonCardDavSource::UID_KEY] ?? null);

        Bus::assertDispatched(SyncPersonToMonica::class);
    }

    public function test_put_updates_existing_person_by_uid(): void
    {
        Bus::fake([SyncPersonToMonica::class]);

        $owner = User::factory()->create();
        $person = Person::factory()->create([
            'user_id' => $owner->id,
            'name' => 'Stale Name',
            'identifiers' => [PersonCardDavSource::UID_KEY => 'stable-uid'],
        ]);

        $vcard = $this->vcard('stable-uid', 'Updated Name', 'updated@example.test');

        $response = $this->davRequest(
            'PUT',
            '/dav/addressbooks/'.$owner->id.'/default/stable-uid.vcf',
            $this->tokenFor($owner),
            $vcard,
            ['Content-Type' => 'text/vcard'],
        );

        $this->assertContains($response->getStatusCode(), [201, 204]);

        $this->assertSame(
            1,
            Person::query()
                ->where('user_id', $owner->id)
                ->where('identifiers->'.PersonCardDavSource::UID_KEY, 'stable-uid')
                ->count()
        );
        $person->refresh();
        $this->assertSame('Updated Name', $person->name);
        $this->assertSame('updated@example.test', $person->primary_email);
    }

    public function test_delete_removes_person(): void
    {
        $owner = User::factory()->create();
        $person = Person::factory()->create([
            'user_id' => $owner->id,
            'identifiers' => [PersonCardDavSource::UID_KEY => 'to-delete'],
        ]);

        $response = $this->davRequest(
            'DELETE',
            '/dav/addressbooks/'.$owner->id.'/default/to-delete.vcf',
            $this->tokenFor($owner),
        );

        $this->assertContains($response->getStatusCode(), [200, 204]);
        $this->assertNull(Person::query()->find($person->id));
    }

    public function test_put_without_dav_write_ability_is_rejected(): void
    {
        $owner = User::factory()->create();
        $token = $this->tokenFor($owner, ['dav:read']);

        $vcard = $this->vcard('forbidden-uid', 'Nope', 'nope@example.test');

        $response = $this->davRequest(
            'PUT',
            '/dav/addressbooks/'.$owner->id.'/default/forbidden-uid.vcf',
            $token,
            $vcard,
            ['Content-Type' => 'text/vcard'],
        );

        $this->assertContains($response->getStatusCode(), [401, 403]);
        $this->assertNull(
            Person::query()
                ->where('user_id', $owner->id)
                ->where('identifiers->'.PersonCardDavSource::UID_KEY, 'forbidden-uid')
                ->first()
        );
    }

    private function vcard(string $uid, string $fn, string $email): string
    {
        $parts = explode(' ', $fn, 2);
        $given = $parts[0];
        $family = $parts[1] ?? '';

        return implode("\r\n", [
            'BEGIN:VCARD',
            'VERSION:4.0',
            'UID:'.$uid,
            'FN:'.$fn,
            'N:'.$family.';'.$given.';;;',
            'EMAIL;TYPE=WORK;PREF=1:'.$email,
            'END:VCARD',
            '',
        ]);
    }
}
