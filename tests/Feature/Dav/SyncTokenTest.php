<?php

declare(strict_types=1);

namespace Tests\Feature\Dav;

use App\Models\Person;
use App\Models\User;
use App\Services\Dav\Backends\SyncDiffBuilder;
use App\Services\Dav\Sources\PersonCardDavSource;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;

class SyncTokenTest extends DavTestCase
{
    use RefreshDatabase;

    public function test_initial_sync_returns_all_existing_objects_as_added(): void
    {
        $owner = User::factory()->create();
        Auth::login($owner);

        Person::factory()->create([
            'user_id' => $owner->id,
            'identifiers' => [PersonCardDavSource::UID_KEY => 'present-uid'],
        ]);

        $diff = SyncDiffBuilder::build(app(PersonCardDavSource::class), $owner, null, null);

        $this->assertNotNull($diff);
        $this->assertContains('present-uid.vcf', $diff['added']);
        $this->assertNotEmpty($diff['syncToken']);
    }

    public function test_changes_after_sync_token_only_include_modifications(): void
    {
        $owner = User::factory()->create();
        Auth::login($owner);

        $first = Person::factory()->create([
            'user_id' => $owner->id,
            'identifiers' => [PersonCardDavSource::UID_KEY => 'first-uid'],
        ]);

        $initial = SyncDiffBuilder::build(app(PersonCardDavSource::class), $owner, null, null);

        Person::factory()->create([
            'user_id' => $owner->id,
            'identifiers' => [PersonCardDavSource::UID_KEY => 'second-uid'],
        ]);

        $first->update(['name' => 'Renamed']);

        $diff = SyncDiffBuilder::build(app(PersonCardDavSource::class), $owner, $initial['syncToken'], null);

        $this->assertNotNull($diff);
        $this->assertContains('second-uid.vcf', $diff['added']);
        $this->assertContains('first-uid.vcf', $diff['modified']);
        $this->assertNotSame($initial['syncToken'], $diff['syncToken']);
    }

    public function test_invalid_sync_token_returns_null(): void
    {
        $owner = User::factory()->create();

        $diff = SyncDiffBuilder::build(app(PersonCardDavSource::class), $owner, 'not-a-number', null);

        $this->assertNull($diff);
    }

    public function test_deleted_objects_show_up_in_diff(): void
    {
        $owner = User::factory()->create();
        Auth::login($owner);

        $person = Person::factory()->create([
            'user_id' => $owner->id,
            'identifiers' => [PersonCardDavSource::UID_KEY => 'doomed-uid'],
        ]);

        $initial = SyncDiffBuilder::build(app(PersonCardDavSource::class), $owner, null, null);

        $person->delete();

        $diff = SyncDiffBuilder::build(app(PersonCardDavSource::class), $owner, $initial['syncToken'], null);

        $this->assertNotNull($diff);
        $this->assertContains('doomed-uid.vcf', $diff['deleted']);
    }
}
