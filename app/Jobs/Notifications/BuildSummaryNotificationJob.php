<?php

namespace App\Jobs\Notifications;

use App\Contracts\Services\News\NewsServiceContract;
use App\Contracts\Services\WeatherServiceContract;
use App\Models\Article;
use App\Models\Finance\Transaction;
use App\Models\Person;
use App\Models\User;
use App\Notifications\Daily\SummaryNotification;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class BuildSummaryNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public function handle(
        WeatherServiceContract $weatherService,
    ): void
    {
        $now = Carbon::now();
        $nowLocal = Carbon::now('America/Detroit');
        $page = 1;
        $headlines = Article::query()
            ->limit(10)
            ->distinct('headline')
            ->where('last_modified', '>=', $nowLocal->copy()->startOfDay())
            ->inRandomOrder()
            ->get();

        do {
            $userPaginator = User::query()
                ->paginate(
                    1,
                    ['*'],
                    'page',
                    $page++
                );

            /** @var User $user */
            foreach ($userPaginator->items() as $user) {
                /** @var Person $person */
                $person = $user->person();
                if (empty($person)) {
                    continue;
                }

                $weather = null;

                if (!empty($person->primary_address)) {
                    $weatherResponse = $weatherService->query($person->primary_address);
                    $weather = collect($weatherResponse)->first();
                }

                $user->notify(new SummaryNotification(
                    $weather,
                    $headlines->toArray(),
                    Transaction::query()
                        ->whereIn('account_id', $user->accounts()->pluck('account_id'))
                        ->where('date', '<=', $nowLocal->copy()->addDay()->endOfDay())
                        ->where('date', '>=', $nowLocal->copy()->subDays(7)->startOfDay())
                        ->where('name', 'not like', '%Transfer%')
                        ->orderBy('date', 'desc')
                        ->get()
                        ->groupBy('date'),
                    $user->accounts
                ));
            }
        } while ($userPaginator->hasMorePages());
    }
}
