<?php

declare(strict_types=1);

namespace App\Services\Article;

use App\Models\Article\SocialFeed;
use App\Models\Condition;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DefaultArticleSocialFeedsService
{
    /**
     * @return array<int, array{
     *   key:string,
     *   name:string,
     *   type?:string,
     *   must_all_conditions_pass?:bool,
     *   conditions?:array<int, array{parameter:string,comparator:string,value:mixed}>
     * }>
     */
    public static function defaultTagDefinitions(): array
    {
        // These tags are intended to be defaults specific to Articles/News.
        // Use a clear name prefix so we can safely re-generate without guessing ownership.
        return [
            [
                'key' => 'ai',
                'name' => 'News: AI',
                'type' => 'automatic',
                'must_all_conditions_pass' => false,
                'conditions' => [
                    ['parameter' => 'article.headline', 'comparator' => Condition::COMPARATOR_LIKE, 'value' => 'OpenAI'],
                    ['parameter' => 'article.headline', 'comparator' => Condition::COMPARATOR_LIKE, 'value' => 'GPT'],
                    ['parameter' => 'article.headline', 'comparator' => Condition::COMPARATOR_LIKE, 'value' => 'LLM'],
                    ['parameter' => 'article.headline', 'comparator' => Condition::COMPARATOR_LIKE, 'value' => 'AI'],
                ],
            ],
            [
                'key' => 'security',
                'name' => 'News: Security',
                'type' => 'automatic',
                'must_all_conditions_pass' => false,
                'conditions' => [
                    ['parameter' => 'article.headline', 'comparator' => Condition::COMPARATOR_LIKE, 'value' => 'CVE-'],
                    ['parameter' => 'article.headline', 'comparator' => Condition::COMPARATOR_LIKE, 'value' => 'vulnerability'],
                    ['parameter' => 'article.headline', 'comparator' => Condition::COMPARATOR_LIKE, 'value' => 'breach'],
                    ['parameter' => 'article.headline', 'comparator' => Condition::COMPARATOR_LIKE, 'value' => 'exploit'],
                ],
            ],
            [
                'key' => 'startups',
                'name' => 'News: Startups',
                'type' => 'automatic',
                'must_all_conditions_pass' => false,
                'conditions' => [
                    ['parameter' => 'article.headline', 'comparator' => Condition::COMPARATOR_LIKE, 'value' => 'startup'],
                    ['parameter' => 'article.headline', 'comparator' => Condition::COMPARATOR_LIKE, 'value' => 'funding'],
                    ['parameter' => 'article.headline', 'comparator' => Condition::COMPARATOR_LIKE, 'value' => 'seed round'],
                    ['parameter' => 'article.headline', 'comparator' => Condition::COMPARATOR_LIKE, 'value' => 'Series A'],
                ],
            ],
            [
                'key' => 'programming',
                'name' => 'News: Programming',
                'type' => 'automatic',
                'must_all_conditions_pass' => false,
                'conditions' => [
                    ['parameter' => 'article.headline', 'comparator' => Condition::COMPARATOR_LIKE, 'value' => 'Laravel'],
                    ['parameter' => 'article.headline', 'comparator' => Condition::COMPARATOR_LIKE, 'value' => 'PHP'],
                    ['parameter' => 'article.headline', 'comparator' => Condition::COMPARATOR_LIKE, 'value' => 'TypeScript'],
                    ['parameter' => 'article.headline', 'comparator' => Condition::COMPARATOR_LIKE, 'value' => 'JavaScript'],
                    ['parameter' => 'article.headline', 'comparator' => Condition::COMPARATOR_LIKE, 'value' => 'Python'],
                    ['parameter' => 'article.headline', 'comparator' => Condition::COMPARATOR_LIKE, 'value' => 'Rust'],
                    ['parameter' => 'article.headline', 'comparator' => Condition::COMPARATOR_LIKE, 'value' => 'Go '],
                ],
            ],
            [
                'key' => 'business',
                'name' => 'News: Business',
                'type' => 'automatic',
                'must_all_conditions_pass' => false,
                'conditions' => [
                    ['parameter' => 'article.headline', 'comparator' => Condition::COMPARATOR_LIKE, 'value' => 'earnings'],
                    ['parameter' => 'article.headline', 'comparator' => Condition::COMPARATOR_LIKE, 'value' => 'IPO'],
                    ['parameter' => 'article.headline', 'comparator' => Condition::COMPARATOR_LIKE, 'value' => 'acquisition'],
                    ['parameter' => 'article.headline', 'comparator' => Condition::COMPARATOR_LIKE, 'value' => 'market'],
                ],
            ],
            [
                'key' => 'politics',
                'name' => 'News: Politics',
                'type' => 'automatic',
                'must_all_conditions_pass' => false,
                'conditions' => [
                    ['parameter' => 'article.headline', 'comparator' => Condition::COMPARATOR_LIKE, 'value' => 'election'],
                    ['parameter' => 'article.headline', 'comparator' => Condition::COMPARATOR_LIKE, 'value' => 'Congress'],
                    ['parameter' => 'article.headline', 'comparator' => Condition::COMPARATOR_LIKE, 'value' => 'Senate'],
                    ['parameter' => 'article.headline', 'comparator' => Condition::COMPARATOR_LIKE, 'value' => 'government'],
                ],
            ],
            [
                'key' => 'science',
                'name' => 'News: Science',
                'type' => 'automatic',
                'must_all_conditions_pass' => false,
                'conditions' => [
                    ['parameter' => 'article.headline', 'comparator' => Condition::COMPARATOR_LIKE, 'value' => 'study'],
                    ['parameter' => 'article.headline', 'comparator' => Condition::COMPARATOR_LIKE, 'value' => 'research'],
                    ['parameter' => 'article.headline', 'comparator' => Condition::COMPARATOR_LIKE, 'value' => 'NASA'],
                    ['parameter' => 'article.headline', 'comparator' => Condition::COMPARATOR_LIKE, 'value' => 'scientists'],
                ],
            ],
        ];
    }

    /**
     * @return array<int, array{name:string,description?:string,tag_keys:array<int,string>}>
     */
    public static function defaultSocialFeedDefinitions(): array
    {
        return [
            [
                'name' => 'Tech Brief',
                'description' => 'AI, security, startups, and programming updates.',
                'tag_keys' => [
                    'ai',
                    'security',
                    'startups',
                    'programming',
                ],
            ],
            [
                'name' => 'Security Watch',
                'description' => 'Breaches, vulnerabilities, and incident reports.',
                'tag_keys' => [
                    'security',
                ],
            ],
            [
                'name' => 'World & Business',
                'description' => 'Politics, business, and major world news.',
                'tag_keys' => [
                    'business',
                    'politics',
                ],
            ],
            [
                'name' => 'Science Desk',
                'description' => 'Research, discoveries, and science news.',
                'tag_keys' => [
                    'science',
                ],
            ],
        ];
    }

    public function createOrUpdateForUser(User $user, bool $dryRun = false): array
    {
        $created_tags = 0;
        $updated_tags = 0;
        $created_feeds = 0;
        $updated_feeds = 0;

        $tagsByKey = [];

        foreach (self::defaultTagDefinitions() as $def) {
            $key = (string) $def['key'];
            $name = (string) $def['name'];
            $type = (string) ($def['type'] ?? 'automatic');
            $mustAll = (bool) ($def['must_all_conditions_pass'] ?? false);
            $conditions = $def['conditions'] ?? [];

            /** @var Tag|null $existing */
            $existing = Tag::query()
                ->where('type', $type)
                ->where('name->en', $name)
                ->first();

            if ($dryRun) {
                $tagsByKey[$key] = $existing;

                continue;
            }

            $tag = $existing ?? Tag::query()->create([
                'name' => ['en' => $name],
                'slug' => ['en' => Str::slug($name)],
                'type' => $type,
                'must_all_conditions_pass' => $mustAll,
                'order_column' => 1,
            ]);

            if ($existing) {
                $tag->update([
                    'name' => ['en' => $name],
                    'must_all_conditions_pass' => $mustAll,
                ]);
                $updated_tags++;
            } else {
                $created_tags++;
            }

            // Attach tag to user without removing any existing tags they have.
            $user->tags()->syncWithoutDetaching([$tag->getKey()]);

            // Replace default tag conditions for deterministic regeneration.
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

            $tagsByKey[$key] = $tag;
        }

        foreach (self::defaultSocialFeedDefinitions() as $feedDef) {
            $name = (string) $feedDef['name'];
            $description = (string) ($feedDef['description'] ?? '');
            $tagKeys = $feedDef['tag_keys'] ?? [];

            if ($dryRun) {
                continue;
            }

            /** @var SocialFeed $socialFeed */
            $socialFeed = SocialFeed::query()->updateOrCreate(
                [
                    'user_id' => $user->id,
                    'name' => $name,
                ],
                [
                    'description' => $description !== '' ? $description : null,
                    'is_public' => false,
                    'must_all_conditions_pass' => false,
                ],
            );

            if ($socialFeed->wasRecentlyCreated) {
                $created_feeds++;
            } else {
                $updated_feeds++;
            }

            $tags = array_values(array_filter(array_map(
                fn (string $key) => $tagsByKey[$key] ?? null,
                $tagKeys
            )));

            // Keep the SocialFeed tags exactly in sync with the default definition.
            $socialFeed->syncTags($tags);
        }

        return compact('created_tags', 'updated_tags', 'created_feeds', 'updated_feeds');
    }
}
