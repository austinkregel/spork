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
            'must_all_conditions_pass' => false,
            'conditions' => [
                [
                    'parameter' => 'transaction.name',
                    'comparator' => Condition::COMPARATOR_LIKE,
                    'value' => 'hulu',
                ],
                [
                    'parameter' => 'transaction.name',
                    'comparator' => Condition::COMPARATOR_LIKE,
                    'value' => 'disney',
                ],
                [
                    'parameter' => 'transaction.name',
                    'comparator' => Condition::COMPARATOR_LIKE,
                    'value' => 'HBO',
                ],
                [
                    'parameter' => 'transaction.name',
                    'comparator' => Condition::COMPARATOR_LIKE,
                    'value' => 'twitch',
                ],
                [
                    'parameter' => 'transaction.name',
                    'comparator' => Condition::COMPARATOR_LIKE,
                    'value' => 'github',
                ],
                [
                    'parameter' => 'transaction.name',
                    'comparator' => Condition::COMPARATOR_LIKE,
                    'value' => 'plex',
                ],
                [
                    'parameter' => 'transaction.name',
                    'comparator' => Condition::COMPARATOR_LIKE,
                    'value' => 'protonmail',
                ],
                [
                    'parameter' => 'transaction.name',
                    'comparator' => Condition::COMPARATOR_LIKE,
                    'value' => 'youtube',
                ],
                [
                    'parameter' => 'transaction.category.name',
                    'comparator' => Condition::COMPARATOR_LIKE,
                    'value' => 'Subscription',
                ],
                [
                    'parameter' => 'transaction.category.name',
                    'comparator' => Condition::COMPARATOR_LIKE,
                    'value' => 'Discord',
                ],
                [
                    'parameter' => 'transaction.category.name',
                    'comparator' => Condition::COMPARATOR_LIKE,
                    'value' => 'netflix',
                ],
                [
                    'parameter' => 'transaction.name',
                    'comparator' => Condition::COMPARATOR_LIKE,
                    'value' => 'WASABI TECHNOLOGIES',
                ],
            ],
        ],

        [
            'name' => 'games',
            'type' => 'automatic',
            'must_all_conditions_pass' => false,
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
                    'parameter' => 'transaction.category.name',
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
            'must_all_conditions_pass' => false,
            'conditions' => [
                [
                    'parameter' => 'tag.name',
                    'comparator' => Condition::COMPARATOR_LIKE,
                    // anything that's a utility should automatically be a bill
                    'value' => 'utilities',
                ],
                [
                    'parameter' => 'transaction.category.name',
                    'comparator' => Condition::COMPARATOR_LIKE,
                    'value' => 'Loans and Mortgages',
                ],
                [
                    'parameter' => 'transaction.category.name',
                    'comparator' => Condition::COMPARATOR_EQUALS,
                    'value' => 'Billpay',
                ],
                [
                    'parameter' => 'transaction.category.name',
                    'comparator' => Condition::COMPARATOR_EQUALS,
                    'value' => 'USAA P&C INT AUTOPAY',
                ],
                [
                    'parameter' => 'transaction.category.name',
                    'comparator' => Condition::COMPARATOR_EQUALS,
                    'value' => 'Car Dealers and Leasing',
                ],
            ],
        ],
        [
            'name' => 'utilities',
            'type' => 'automatic',
            'must_all_conditions_pass' => false,
            'conditions' => [
                [
                    'parameter' => 'transaction.category.name',
                    'comparator' => Condition::COMPARATOR_EQUALS,
                    'value' => 'Cable',
                ],
                [
                    'parameter' => 'transaction.category.name',
                    'comparator' => Condition::COMPARATOR_EQUALS,
                    'value' => 'Telecommunication Services',
                ],
                [
                    'parameter' => 'transaction.category.name',
                    'comparator' => Condition::COMPARATOR_EQUALS,
                    'value' => 'Utilities',
                ],
                [
                    'parameter' => 'transaction.category.name',
                    'comparator' => Condition::COMPARATOR_EQUALS,
                    'value' => 'Sanitary and Waste Management',
                ],
                [
                    'parameter' => 'transaction.category.name',
                    'comparator' => Condition::COMPARATOR_EQUALS,
                    // This is for people who get their power/water from the city (like those in petoskey)
                    'value' => 'Government Departments and Agencies',
                ],
            ],
        ],
        [
            'name' => 'fast food/restaurants',
            'type' => 'automatic',
            'must_all_conditions_pass' => false,
            'conditions' => [
                [
                    'parameter' => 'transaction.category.name',
                    'comparator' => Condition::COMPARATOR_LIKE,
                    'value' => 'Restaurants',
                ],
                [
                    'parameter' => 'transaction.category.name',
                    'comparator' => Condition::COMPARATOR_EQUALS,
                    'value' => 'Fast Food',
                ],
            ],
        ],
        [
            'name' => 'fees',
            'type' => 'automatic',
            'must_all_conditions_pass' => true,
            'conditions' => [
                [
                    'parameter' => 'transaction.name',
                    'comparator' => Condition::COMPARATOR_LIKE,
                    'value' => 'fee',
                ],
            ],
        ],
        [
            'name' => 'via Privacy.com',
            'type' => 'automatic',
            'must_all_conditions_pass' => true,
            'conditions' => [
                [
                    'parameter' => 'transaction.name',
                    'comparator' => Condition::COMPARATOR_STARTS_WITH,
                    'value' => 'PWP*',
                ],
            ],
        ],
        [
            'name' => 'transfer',
            'type' => 'automatic',
            'must_all_conditions_pass' => true,
            'conditions' => [
                [
                    'parameter' => 'transaction.name',
                    'comparator' => Condition::COMPARATOR_LIKE,
                    'value' => 'transfer',
                ],
            ],
        ],
        [
            'name' => 'credit/income',
            'type' => 'automatic',
            'must_all_conditions_pass' => true,
            'conditions' => [
                [
                    'parameter' => 'transaction.name',
                    'comparator' => Condition::COMPARATOR_NOT_LIKE,
                    'value' => 'transfer',
                ],
                [
                    'parameter' => 'transaction.name',
                    'comparator' => Condition::COMPARATOR_NOT_LIKE,
                    'value' => 'fee',
                ],
                [
                    'parameter' => 'transaction.amount',
                    'comparator' => Condition::COMPARATOR_LESS_THAN,
                    'value' => 0,
                ],
            ],
        ],
        [
            'name' => 'debit/expense',
            'type' => 'automatic',
            'must_all_conditions_pass' => true,
            'conditions' => [
                [
                    'parameter' => 'transaction.name',
                    'comparator' => Condition::COMPARATOR_NOT_LIKE,
                    'value' => 'transfer',
                ],
                [
                    'parameter' => 'transaction.name',
                    'comparator' => Condition::COMPARATOR_NOT_LIKE,
                    'value' => 'fee',
                ],
                [
                    'parameter' => 'transaction.amount',
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
