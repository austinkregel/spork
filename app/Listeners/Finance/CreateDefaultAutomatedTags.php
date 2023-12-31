<?php

namespace App\Listeners\Finance;

use App\Events\Models\User\UserCreated;
use App\Models\Condition;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class CreateDefaultAutomatedTags
{
    protected const TAGS = [
        [
            'name' => 'subscriptions',
            'type' => 'automatic',
            'conditions' => [
                [
                    'parameter' => 'name',
                    'comparator' => Condition::COMPARATOR_LIKE,
                    'value' => 'hulu',
                ],
                [
                    'parameter' => 'name',
                    'comparator' => Condition::COMPARATOR_LIKE,
                    'value' => 'disney',
                ],
                [
                    'parameter' => 'name',
                    'comparator' => Condition::COMPARATOR_LIKE,
                    'value' => 'HBO',
                ],
                [
                    'parameter' => 'name',
                    'comparator' => Condition::COMPARATOR_LIKE,
                    'value' => 'twitch',
                ],
                [
                    'parameter' => 'name',
                    'comparator' => Condition::COMPARATOR_LIKE,
                    'value' => 'github',
                ],
                [
                    'parameter' => 'name',
                    'comparator' => Condition::COMPARATOR_LIKE,
                    'value' => 'plex',
                ],
                [
                    'parameter' => 'name',
                    'comparator' => Condition::COMPARATOR_LIKE,
                    'value' => 'protonmail',
                ],
                [
                    'parameter' => 'name',
                    'comparator' => Condition::COMPARATOR_LIKE,
                    'value' => 'youtube',
                ],
                [
                    'parameter' => 'category.name',
                    'comparator' => Condition::COMPARATOR_LIKE,
                    'value' => 'Subscription',
                ],
                [
                    'parameter' => 'category.name',
                    'comparator' => Condition::COMPARATOR_LIKE,
                    'value' => 'Discord',
                ],
                [
                    'parameter' => 'category.name',
                    'comparator' => Condition::COMPARATOR_LIKE,
                    'value' => 'netflix',
                ],
                [
                    'parameter' => 'name',
                    'comparator' => Condition::COMPARATOR_LIKE,
                    'value' => 'WASABI TECHNOLOGIES',
                ],
            ],
        ],

        [
            'name' => 'games',
            'type' => 'automatic',
            'conditions' => [
                [
                    'parameter' => 'transaction.name',
                    'comparator' => Condition::COMPARATOR_LIKE,
                    'value' => 'ORIGIN',
                ],
                [
                    'parameter' => 'transaction.name',
                    'comparator' => Condition::COMPARATOR_LIKE,
                    'value' => 'Steampowered',
                ],
                [
                    'parameter' => 'transaction.name',
                    'comparator' => Condition::COMPARATOR_LIKE,
                    'value' => 'Steam',
                ],
                [
                    'parameter' => 'transaction.name',
                    'comparator' => Condition::COMPARATOR_LIKE,
                    'value' => 'UBISOFT',
                ],
                [
                    'parameter' => 'transaction.name',
                    'comparator' => Condition::COMPARATOR_LIKE,
                    'value' => 'gamestop',
                ],
                [
                    'parameter' => 'category.name',
                    'comparator' => Condition::COMPARATOR_LIKE,
                    'value' => 'Video Games',
                ],
                [
                    'parameter' => 'transaction.name',
                    'comparator' => Condition::COMPARATOR_LIKE,
                    'value' => 'game store',
                ],
            ],
        ],
        [
            'name' => 'bills',
            'type' => 'automatic',
            'conditions' => [
                [
                    'parameter' => 'tag.name',
                    'comparator' => Condition::COMPARATOR_LIKE,
                    // anything that's a utility should automatically be a bill
                    'value' => 'utilities',
                ],
                [
                    'parameter' => 'category.name',
                    'comparator' => Condition::COMPARATOR_LIKE,
                    'value' => 'Loans and Mortgages',
                ],
                [
                    'parameter' => 'category.name',
                    'comparator' => Condition::COMPARATOR_EQUALS,
                    'value' => 'Billpay',
                ],
                [
                    'parameter' => 'category.name',
                    'comparator' => Condition::COMPARATOR_EQUALS,
                    'value' => 'USAA P&C INT AUTOPAY',
                ],
                [
                    'parameter' => 'category.name',
                    'comparator' => Condition::COMPARATOR_EQUALS,
                    'value' => 'Car Dealers and Leasing',
                ],
            ],
        ],
        [
            'name' => 'utilities',
            'type' => 'automatic',
            'conditions' => [
                [
                    'parameter' => 'category.name',
                    'comparator' => Condition::COMPARATOR_EQUALS,
                    'value' => 'Cable',
                ],
                [
                    'parameter' => 'category.name',
                    'comparator' => Condition::COMPARATOR_EQUALS,
                    'value' => 'Telecommunication Services',
                ],
                [
                    'parameter' => 'category.name',
                    'comparator' => Condition::COMPARATOR_EQUALS,
                    'value' => 'Utilities',
                ],
                [
                    'parameter' => 'category.name',
                    'comparator' => Condition::COMPARATOR_EQUALS,
                    'value' => 'Sanitary and Waste Management',
                ],
                [
                    'parameter' => 'category.name',
                    'comparator' => Condition::COMPARATOR_EQUALS,
                    // This is for people who get their power/water from the city (like those in petoskey)
                    'value' => 'Government Departments and Agencies',
                ],
            ],
        ],
        [
            'name' => 'fast food/restaurants',
            'type' => 'automatic',
            'conditions' => [
                [
                    'parameter' => 'category.name',
                    'comparator' => Condition::COMPARATOR_LIKE,
                    'value' => 'Restaurants',
                ],
                [
                    'parameter' => 'category.name',
                    'comparator' => Condition::COMPARATOR_EQUALS,
                    'value' => 'Fast Food',
                ],
            ],
        ],
        [
            'name' => 'fees',
            'type' => 'automatic',
            'conditions' => [
                [
                    'parameter' => 'name',
                    'comparator' => Condition::COMPARATOR_LIKE,
                    'value' => 'fee',
                ],
            ],
        ],
        [
            'name' => 'via Privacy.com',
            'type' => 'automatic',
            'conditions' => [
                [
                    'parameter' => 'name',
                    'comparator' => Condition::COMPARATOR_STARTS_WITH,
                    'value' => 'PWP*',
                ],
            ],
        ],
        [
            'name' => 'transfer',
            'type' => 'automatic',
            'conditions' => [
                [
                    'parameter' => 'name',
                    'comparator' => Condition::COMPARATOR_LIKE,
                    'value' => 'transfer',
                ],
            ],
        ],
        [
            'name' => 'credit/income',
            'type' => 'automatic',
            'conditions' => [
                [
                    'parameter' => 'name',
                    'comparator' => Condition::COMPARATOR_NOT_LIKE,
                    'value' => 'transfer',
                ],
                [
                    'parameter' => 'name',
                    'comparator' => Condition::COMPARATOR_NOT_LIKE,
                    'value' => 'fee',
                ],
                [
                    'parameter' => 'amount',
                    'comparator' => Condition::COMPARATOR_LESS_THAN,
                    'value' => 0,
                ],
            ],
        ],
        [
            'name' => 'debit/expense',
            'type' => 'automatic',
            'conditions' => [
                [
                    'parameter' => 'name',
                    'comparator' => Condition::COMPARATOR_NOT_LIKE,
                    'value' => 'transfer',
                ],
                [
                    'parameter' => 'name',
                    'comparator' => Condition::COMPARATOR_NOT_LIKE,
                    'value' => 'fee',
                ],
                [
                    'parameter' => 'amount',
                    'comparator' => Condition::COMPARATOR_GREATER_THAN,
                    'value' => 0,
                ],
            ],
        ],
    ];

    public function handle(UserCreated $event): void
    {
        /** @var User $user */
        $user = $event->model;

        foreach (static::TAGS as $tagInfo) {
            $conditions = $tagInfo['conditions'];
            unset($tagInfo['conditions']);

            /** @var Tag $tag */
            $tag = $user->tags()->create($tagInfo);
            foreach ($conditions as $condition) {
                $tag->conditions()->create($condition);
            }
        }
    }
}
