<?php

declare(strict_types=1);

namespace Tests\Feature\Console;

use App\Models\Person;
use App\Models\Thread;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RenameDirectMessageThreadsCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_renames_two_participant_threads_when_ignored_participant_is_present(): void
    {
        $thread = Thread::factory()->create([
            'name' => '!placeholder:matrix',
        ]);

        $austin = Person::factory()->create(['name' => 'Austin Kregel']);
        $other = Person::factory()->create(['name' => 'Jane Doe']);

        $thread->participants()->attach([
            $austin->id => ['joined_at' => now()],
            $other->id => ['joined_at' => now()],
        ]);

        $this->artisan('spork:threads:rename-dms', [
            '--execute' => true,
            '--ignore' => 'Austin Kregel',
        ])->assertExitCode(0);

        $this->assertSame('Jane Doe', $thread->fresh()->name);
    }

    public function test_it_does_not_rename_two_participant_threads_when_ignored_participant_is_not_present(): void
    {
        $thread = Thread::factory()->create([
            'name' => '!placeholder:matrix',
        ]);

        $first = Person::factory()->create(['name' => 'Bob Smith']);
        $second = Person::factory()->create(['name' => 'Jane Doe']);

        $thread->participants()->attach([
            $first->id => ['joined_at' => now()],
            $second->id => ['joined_at' => now()],
        ]);

        $this->artisan('spork:threads:rename-dms', [
            '--execute' => true,
            '--ignore' => 'Austin Kregel',
        ])->assertExitCode(0);

        $this->assertSame('!placeholder:matrix', $thread->fresh()->name);
    }

    public function test_only_placeholder_option_skips_threads_with_real_names(): void
    {
        $thread = Thread::factory()->create([
            'name' => 'Already Named',
        ]);

        $austin = Person::factory()->create(['name' => 'Austin Kregel']);
        $other = Person::factory()->create(['name' => 'Jane Doe']);

        $thread->participants()->attach([
            $austin->id => ['joined_at' => now()],
            $other->id => ['joined_at' => now()],
        ]);

        $this->artisan('spork:threads:rename-dms', [
            '--execute' => true,
            '--ignore' => 'Austin Kregel',
            '--only-placeholder' => true,
        ])->assertExitCode(0);

        $this->assertSame('Already Named', $thread->fresh()->name);
    }
}


