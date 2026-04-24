<?php

declare(strict_types=1);

namespace Tests\Feature\Dav;

use App\Contracts\Dav\DavCollectionSource;
use App\Models\Person;
use App\Models\User;
use App\Services\Dav\Sources\PersonCardDavSource;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CardDavTagCollectionsTest extends DavTestCase
{
    use RefreshDatabase;

    public function test_dav_collection_tag_creates_separate_addressbook(): void
    {
        $owner = User::factory()->create();

        $tagged = Person::factory()->create([
            'user_id' => $owner->id,
            'name' => 'Work Person',
            'identifiers' => [PersonCardDavSource::UID_KEY => 'work-uid'],
        ]);
        $tagged->syncTagsWithType(['work'], DavCollectionSource::COLLECTION_TAG_NAMESPACE);

        Person::factory()->create([
            'user_id' => $owner->id,
            'name' => 'Default Person',
            'identifiers' => [PersonCardDavSource::UID_KEY => 'default-uid'],
        ]);

        $token = $this->tokenFor($owner);

        $workResponse = $this->davRequest(
            'PROPFIND',
            '/dav/addressbooks/'.$owner->id.'/work/',
            $token,
            '',
            ['Depth' => '1'],
        );
        $workResponse->assertStatus(207);
        $workBody = $workResponse->getContent();
        $this->assertStringContainsString('work-uid.vcf', $workBody);
        $this->assertStringNotContainsString('default-uid.vcf', $workBody);

        $defaultResponse = $this->davRequest(
            'PROPFIND',
            '/dav/addressbooks/'.$owner->id.'/default/',
            $token,
            '',
            ['Depth' => '1'],
        );
        $defaultResponse->assertStatus(207);
        $defaultBody = $defaultResponse->getContent();
        $this->assertStringContainsString('default-uid.vcf', $defaultBody);
        $this->assertStringNotContainsString('work-uid.vcf', $defaultBody);
    }
}
