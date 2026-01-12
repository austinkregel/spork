<?php

declare(strict_types=1);

namespace App\Listeners\Finance;

use App\Events\Models\User\UserCreated;
use App\Models\Condition;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

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
                    'parameter' => 'transaction.name',
                    'comparator' => Condition::COMPARATOR_LIKE,
                    'value' => 'Subscription',
                ],
                [
                    'parameter' => 'transaction.name',
                    'comparator' => Condition::COMPARATOR_LIKE,
                    'value' => 'Discord',
                ],
                [
                    'parameter' => 'transaction.name',
                    'comparator' => Condition::COMPARATOR_LIKE,
                    'value' => 'netflix',
                ],
                [
                    'parameter' => 'transaction.name',
                    'comparator' => Condition::COMPARATOR_LIKE,
                    'value' => 'WASABI TECHNOLOGIES',
                ],
                [
                    'parameter' => 'transaction.name',
                    'comparator' => Condition::COMPARATOR_LIKE,
                    'value' => 'Membership',
                ],
                [
                    'parameter' => 'transaction.name',
                    'comparator' => Condition::COMPARATOR_LIKE,
                    'value' => 'CURSOR',
                ],
                [
                    'parameter' => 'transaction.name',
                    'comparator' => Condition::COMPARATOR_LIKE,
                    'value' => 'TRAKT.TV',
                ],
                [
                    'parameter' => 'transaction.name',
                    'comparator' => Condition::COMPARATOR_LIKE,
                    'value' => 'GROUND NEWS',
                ],
                [
                    'parameter' => 'transaction.name',
                    'comparator' => Condition::COMPARATOR_LIKE,
                    'value' => 'Displate com',
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
                [
                    'parameter' => 'transaction.name',
                    'comparator' => Condition::COMPARATOR_LIKE,
                    'value' => 'CITY OF',
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
                [
                    'parameter' => 'transaction.name',
                    'comparator' => Condition::COMPARATOR_LIKE,
                    'value' => 'WM.COM',
                ],
                [
                    'parameter' => 'transaction.name',
                    'comparator' => Condition::COMPARATOR_EQUALS,
                    'value' => 'consumer energy',
                ],
                [
                    'parameter' => 'transaction.name',
                    'comparator' => Condition::COMPARATOR_LIKE,
                    'value' => 'daystarr internet',
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
            'name' => 'doordash',
            'type' => 'automatic',
            'must_all_conditions_pass' => false,
            'conditions' => [
                [
                    'parameter' => 'transaction.name',
                    'comparator' => Condition::COMPARATOR_LIKE,
                    'value' => 'DOORDASH',
                ],
                [
                    'parameter' => 'transaction.name',
                    'comparator' => Condition::COMPARATOR_LIKE,
                    'value' => 'DD *DOORDASH',
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
                    'comparator' => Condition::COMPARATOR_GREATER_THAN,
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
                    'comparator' => Condition::COMPARATOR_LESS_THAN,
                    'value' => 0,
                ],
            ],
        ],
        [
            'name' => 'debt/loans',
            'type' => 'automatic',
            'must_all_conditions_pass' => false,
            'conditions' => [
                [
                    'parameter' => 'transaction.name',
                    'comparator' => Condition::COMPARATOR_EQUALS,
                    'value' => 'ICPAYMENT',
                ],
                [
                    'parameter' => 'transaction.name',
                    'comparator' => Condition::COMPARATOR_EQUALS,
                    'value' => 'USAA CREDIT CARD PAYMENT',
                ],
                [
                    'parameter' => 'transaction.amount',
                    'comparator' => Condition::COMPARATOR_EQUALS,
                    'value' => 'AUTOMATIC PAYMENT - THANK',
                ],
                [
                    'parameter' => 'transaction.amount',
                    'comparator' => Condition::COMPARATOR_EQUALS,
                    'value' => 'CHASE CREDIT CRD AUTOPAY',
                ],
                [
                    'parameter' => 'transaction.amount',
                    'comparator' => Condition::COMPARATOR_EQUALS,
                    'value' => 'Interest on Purchases',
                ],
                [
                    'parameter' => 'transaction.amount',
                    'comparator' => Condition::COMPARATOR_EQUALS,
                    'value' => 'CHASE CREDIT CRD EPAY',
                ],
                [
                    'parameter' => 'transaction.amount',
                    'comparator' => Condition::COMPARATOR_EQUALS,
                    'value' => 'PURCHASE INTEREST CHARGE',
                ],
            ],
        ],
        [
            'name' => 'tech',
            'type' => 'automatic',
            'must_all_conditions_pass' => false,
            'conditions' => [
                [
                    'parameter' => 'transaction.name',
                    'comparator' => Condition::COMPARATOR_LIKE,
                    'value' => 'digitalocean',
                ],
                [
                    'parameter' => 'transaction.name',
                    'comparator' => Condition::COMPARATOR_STARTS_WITH,
                    'value' => 'NAME-CHEAP',
                ],
                [
                    'parameter' => 'transaction.name',
                    'comparator' => Condition::COMPARATOR_LIKE,
                    'value' => 'linode',
                ],
                [
                    'parameter' => 'transaction.name',
                    'comparator' => Condition::COMPARATOR_LIKE,
                    'value' => 'Laravel',
                ],
                [
                    'parameter' => 'transaction.name',
                    'comparator' => Condition::COMPARATOR_LIKE,
                    'value' => 'cursor',
                ],
                [
                    'parameter' => 'transaction.name',
                    'comparator' => Condition::COMPARATOR_LIKE,
                    'value' => 'cloudflare',
                ],
                [
                    'parameter' => 'transaction.name',
                    'comparator' => Condition::COMPARATOR_LIKE,
                    'value' => 'github',
                ],
                [
                    'parameter' => 'transaction.name',
                    'comparator' => Condition::COMPARATOR_LIKE,
                    'value' => 'aws',
                ],
                [
                    'parameter' => 'transaction.name',
                    'comparator' => Condition::COMPARATOR_LIKE,
                    'value' => 'vultr',
                ],
                [
                    'parameter' => 'transaction.name',
                    'comparator' => Condition::COMPARATOR_EQUALS,
                    'value' => 'OVH US LLC',
                ],
                [
                    'parameter' => 'transaction.name',
                    'comparator' => Condition::COMPARATOR_LIKE,
                    'value' => 'zerotier',
                ],
                [
                    'parameter' => 'transaction.name',
                    'comparator' => Condition::COMPARATOR_LIKE,
                    'value' => 'TRAKT.TV VIP',
                ],
            ],
        ],
        [
            'name' => 'transportation',
            'type' => 'automatic',
            'must_all_conditions_pass' => false,
            'conditions' => [
                [
                    'parameter' => 'transaction.personal_finance_category',
                    'comparator' => Condition::COMPARATOR_IN,
                    'value' => 'GAS_STATIONS,AUTOMOTIVE,PUBLIC_TRANSPORTATION,TAXICABS_AND_RIDE_SHARES,PARKING',
                ],
                [
                    'parameter' => 'transaction.personal_finance_category_detailed',
                    'comparator' => Condition::COMPARATOR_EQUALS,
                    'value' => 'TRANSPORTATION_GAS',
                ],
                [
                    'parameter' => 'transaction.name',
                    'comparator' => Condition::COMPARATOR_EQUALS,
                    'value' => 'shell',
                ],
                [
                    'parameter' => 'transaction.name',
                    'comparator' => Condition::COMPARATOR_LIKE,
                    'value' => 'exxon',
                ],
                [
                    'parameter' => 'transaction.name',
                    'comparator' => Condition::COMPARATOR_LIKE,
                    'value' => 'chevron',
                ],
                [
                    'parameter' => 'transaction.name',
                    'comparator' => Condition::COMPARATOR_STARTS_WITH,
                    'value' => 'BP#',
                ],
            ],
        ],
        [
            'name' => 'personal/household',
            'type' => 'automatic',
            'must_all_conditions_pass' => false,
            'conditions' => [
                [
                    'parameter' => 'transaction.personal_finance_category',
                    'comparator' => Condition::COMPARATOR_IN,
                    'value' => 'CLOTHING_AND_ACCESSORIES,HOME_AND_GARDEN,PERSONAL_CARE',
                ],
                [
                    'parameter' => 'transaction.personal_finance_category_detailed',
                    'comparator' => Condition::COMPARATOR_LIKE,
                    'value' => 'CLOTHING',
                ],
                [
                    'parameter' => 'transaction.personal_finance_category_detailed',
                    'comparator' => Condition::COMPARATOR_LIKE,
                    'value' => 'HOME',
                ],
                [
                    'parameter' => 'transaction.personal_finance_category_detailed',
                    'comparator' => Condition::COMPARATOR_LIKE,
                    'value' => 'PERSONAL_CARE',
                ],
                [
                    'parameter' => 'transaction.name',
                    'comparator' => Condition::COMPARATOR_LIKE,
                    'value' => 'target',
                ],
                [
                    'parameter' => 'transaction.name',
                    'comparator' => Condition::COMPARATOR_LIKE,
                    'value' => 'walmart',
                ],
                [
                    'parameter' => 'transaction.name',
                    'comparator' => Condition::COMPARATOR_LIKE,
                    'value' => 'costco',
                ],
                [
                    'parameter' => 'transaction.name',
                    'comparator' => Condition::COMPARATOR_LIKE,
                    'value' => 'home depot',
                ],
                [
                    'parameter' => 'transaction.name',
                    'comparator' => Condition::COMPARATOR_LIKE,
                    'value' => 'lowes',
                ],
                [
                    'parameter' => 'transaction.name',
                    'comparator' => Condition::COMPARATOR_LIKE,
                    'value' => 'ikea',
                ],
                [
                    'parameter' => 'transaction.name',
                    'comparator' => Condition::COMPARATOR_LIKE,
                    'value' => 'walgreens',
                ],
                [
                    'parameter' => 'transaction.name',
                    'comparator' => Condition::COMPARATOR_LIKE,
                    'value' => 'cvs',
                ],
            ],
        ],
        [
            'name' => 'housing',
            'type' => 'automatic',
            'must_all_conditions_pass' => false,
            'conditions' => [
                [
                    'parameter' => 'transaction.name',
                    'comparator' => Condition::COMPARATOR_LIKE,
                    'value' => 'ROCKET MORTGAGE',
                ],
            ],
        ],
        [
            'name' => 'insurance',
            'type' => 'automatic',
            'must_all_conditions_pass' => false,
            'conditions' => [
                [
                    'parameter' => 'transaction.name',
                    'comparator' => Condition::COMPARATOR_LIKE,
                    'value' => 'Insurance',
                ],
                [
                    'parameter' => 'transaction.name',
                    'comparator' => Condition::COMPARATOR_EQUALS,
                    'value' => 'USAA.COM PAY INT LIFE',
                ],
            ],
        ],
        [
            'name' => 'donations',
            'type' => 'automatic',
            'must_all_conditions_pass' => false,
            'conditions' => [
                [
                    'parameter' => 'transaction.name',
                    'comparator' => Condition::COMPARATOR_LIKE,
                    'value' => 'Donation',
                ],
                [
                    'parameter' => 'transaction.name',
                    'comparator' => Condition::COMPARATOR_LIKE,
                    'value' => 'aclu',
                ],
                [
                    'parameter' => 'transaction.name',
                    'comparator' => Condition::COMPARATOR_LIKE,
                    'value' => 'naacp',
                ],
                [
                    'parameter' => 'transaction.name',
                    'comparator' => Condition::COMPARATOR_LIKE,
                    'value' => 'planparentadvocates',
                ],
                [
                    'parameter' => 'transaction.name',
                    'comparator' => Condition::COMPARATOR_LIKE,
                    'value' => 'scishow',
                ],
                [
                    'parameter' => 'transaction.name',
                    'comparator' => Condition::COMPARATOR_LIKE,
                    'value' => 'sciencemuseum',
                ],
                [
                    'parameter' => 'transaction.name',
                    'comparator' => Condition::COMPARATOR_LIKE,
                    'value' => 'food bank',
                ],
            ],
        ],
        [
            'name' => 'interest',
            'type' => 'automatic',
            'must_all_conditions_pass' => false,
            'conditions' => [
                [
                    'parameter' => 'transaction.name',
                    'comparator' => Condition::COMPARATOR_EQUALS,
                    'value' => 'Interest on Purchases',
                ],
                [
                    'parameter' => 'transaction.amount',
                    'comparator' => Condition::COMPARATOR_EQUALS,
                    'value' => 'PURCHASE INTEREST CHARGE',
                ],
                [
                    'parameter' => 'transaction.amount',
                    'comparator' => Condition::COMPARATOR_EQUALS,
                    'value' => 'INTEREST',
                ],
            ],
        ],
        [
            'name' => 'cannabis',
            'type' => 'automatic',
            'must_all_conditions_pass' => false,
            'conditions' => [
                [
                    'parameter' => 'transaction.name',
                    'comparator' => Condition::COMPARATOR_LIKE,
                    'value' => 'CANNABIS',
                ],
                [
                    'parameter' => 'transaction.name',
                    'comparator' => Condition::COMPARATOR_EQUALS,
                    'value' => 'ATM Pinconning',
                ],
                [
                    'parameter' => 'transaction.name',
                    'comparator' => Condition::COMPARATOR_EQUALS,
                    'value' => 'PAY BY BANK PURCHASE',
                ],
            ],
        ],
    ];

    public static function standardTagDefinitions(): array
    {
        return self::TAGS;
    }

    public function handle(UserCreated $event): void
    {
        /** @var User $user */
        $user = $event->model;

        foreach (static::TAGS as $tagInfo) {
            $conditions = $tagInfo['conditions'];
            $tagName = (string) $tagInfo['name'];
            $tagType = (string) ($tagInfo['type'] ?? 'automatic');
            $mustAll = (bool) ($tagInfo['must_all_conditions_pass'] ?? false);

            // Check if tag already exists globally (tags are shared across users)
            $existing = Tag::query()
                ->where('type', $tagType)
                ->where('name->en', $tagName)
                ->first();

            // Create tag if it doesn't exist, or use existing one
            if ($existing) {
                $tag = $existing;
                // Update must_all_conditions_pass in case it changed
                $tag->update([
                    'must_all_conditions_pass' => $mustAll,
                ]);
            } else {
                $tag = Tag::query()->create([
                    'name' => ['en' => $tagName],
                    'slug' => ['en' => Str::slug($tagName)],
                    'type' => $tagType,
                    'must_all_conditions_pass' => $mustAll,
                    'order_column' => 1,
                ]);
            }

            // Attach tag to user without removing any existing tags
            $user->tags()->syncWithoutDetaching([$tag->getKey()]);

            // Replace default tag conditions for deterministic regeneration
            DB::transaction(function () use ($tag, $conditions): void {
                $tag->conditions()->delete();

                foreach ($conditions as $condition) {
                    $tag->conditions()->create([
                        'parameter' => (string) ($condition['parameter'] ?? ''),
                        'comparator' => (string) ($condition['comparator'] ?? Condition::COMPARATOR_LIKE),
                        'value' => $condition['value'] ?? null,
                    ]);
                }
            });
        }
    }
}
