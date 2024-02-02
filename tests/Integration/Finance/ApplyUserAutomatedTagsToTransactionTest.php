<?php

declare(strict_types=1);

namespace Integration\Finance;

use App\Events\Models\Transaction\TransactionCreated;
use App\Listeners\Finance\ApplyUserAutomatedTagsToTransaction;
use App\Models\Credential;
use App\Models\Finance\Account;
use App\Models\Finance\Transaction;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Psr\Log\LoggerInterface;
use Tests\TestCase;

class ApplyUserAutomatedTagsToTransactionTest extends TestCase
{
    use RefreshDatabase;

    public function testWeCanApplyATagFromAUserBasedOnCreatedTransactions()
    {
        User::factory()->createQuietly([
            'id' => 1,
            'name' => 'Fake User',
        ]);
        $user = User::factory()->createQuietly([
            'name' => 'Test user',
            'id' => 458924,
        ]);
        User::factory()->createQuietly([
            'id' => 2,
            'name' => 'Fake User 2',
        ]);

        /** @var Tag $tag */
        $tag = $user->tags()->create([
            'name' => 'Test Tag',
            'type' => 'automatic',
        ]);

        $tag->conditions()->create([
            'parameter' => 'transaction.name',
            'comparator' => 'LIKE',
            'value' => 'Bar',
        ]);
        Credential::factory()->create([
            'user_id' => $user->id,
        ]);

        $credential = Credential::factory()->create([
            'user_id' => $user->id,
            'type' => 'finance',
        ]);
        Credential::factory()->create([
            'user_id' => $user->id,
        ]);
        Account::factory()->create([
            'credential_id' => $credential->id,
        ]);
        $account = Account::factory()->create([
            'credential_id' => $credential->id,
            'account_id' => 'my_account_id',
        ]);
        Account::factory()->create([
            'credential_id' => $credential->id,
        ]);
        Transaction::factory()->createQuietly([
            'account_id' => 'my_account_id',
            'name' => 'Doo do a dollup',
        ]);
        $transaction = Transaction::factory()->createQuietly([
            'account_id' => 'my_account_id',
            'name' => 'Duffys Bar and Grille',
        ]);
        Transaction::factory()->createQuietly([
            'account_id' => 'my_account_id',
            'name' => 'Daisys Flowers',
        ]);

        $logger = mock(LoggerInterface::class);
        $listener = new ApplyUserAutomatedTagsToTransaction($logger);

        $listener->handle(new TransactionCreated($transaction));
        $transaction->load('tags');
        $transaction->refresh();

        $this->assertNotEmpty($user->tags->toArray());
        $this->assertNotEmpty($transaction->tags->toArray());
    }
}
