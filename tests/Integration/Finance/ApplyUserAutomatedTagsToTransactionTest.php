<?php
declare(strict_types=1);

namespace Integration\Finance;

use App\Events\Models\Transaction\TransactionCreated;
use App\Events\Models\User\UserCreated;
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
        $user = User::factory()->createQuietly();
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

        $credential = Credential::factory()->create([
            'user_id' => $user->id,
        ]);

        $account = Account::factory()->create([
            'credential_id' => $credential->id,
        ]);

        $transaction = Transaction::factory()->createQuietly([
            'account_id' => $account->account_id,
            'name' => 'Duffys Bar and Grille'
        ]);

        $transaction->load('tags');
        $user->load('tags');

        $this->assertEmpty($transaction->tags);
        $this->assertNotEmpty($user->tags);

        $logger = mock(LoggerInterface::class);
        $listener = new ApplyUserAutomatedTagsToTransaction($logger);

        $listener->handle(new TransactionCreated($transaction));
        $transaction->refresh();
        $transaction->load('tags');
        $user->load('tags');
        $transaction->refresh();
        $user->refresh();

        $this->assertNotEmpty($transaction->tags->toArray());
        $this->assertNotEmpty($user->tags->toArray());
    }
}
