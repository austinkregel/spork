<?php

declare(strict_types=1);

namespace App\Services\Article;

use App\Models\Article;
use App\Models\Article\SocialFeed;
use App\Models\Condition;
use Illuminate\Database\Eloquent\Builder;

class SocialFeedService
{
    /**
     * Build the base query for a SocialFeed.
     *
     * - Articles match when they share at least one tag with the SocialFeed
     * - Conditions further filter the article dataset
     * - Default ordering is by published_at, which is stored as Article::created_at
     */
    public function getArticlesForSocialFeed(SocialFeed $feed, array $options = []): Builder
    {
        $feed->loadMissing(['tags', 'conditions']);

        $query = Article::query()
            ->with('author.tags');

        if ($feed->tags->isEmpty()) {
            // A SocialFeed with no tags matches nothing by default.
            return $query->whereRaw('1 = 0');
        }

        $query->withAnyTags($feed->tags);

        $this->applyConditionsToQuery($query, $feed);

        $sort = (string) ($options['sort'] ?? 'published_at_desc');

        return match ($sort) {
            'published_at_asc' => $query->orderBy('created_at'),
            'published_at_desc' => $query->orderByDesc('created_at'),
            default => $query->orderByDesc('created_at'),
        };
    }

    protected function applyConditionsToQuery(Builder $query, SocialFeed $feed): void
    {
        $conditions = $feed->conditions;

        if ($conditions->isEmpty()) {
            return;
        }

        $clauses = array_values(array_filter(
            $conditions->all(),
            fn (Condition $condition): bool => $this->resolveArticleColumn((string) $condition->parameter) !== null
        ));

        if (empty($clauses)) {
            return;
        }

        $applyClause = function (Builder $query, Condition $condition, bool $asOr): void {
            $column = $this->resolveArticleColumn((string) $condition->parameter);

            if ($column === null) {
                return;
            }

            $this->applyComparator(
                query: $query,
                asOr: $asOr,
                column: $column,
                comparator: (string) $condition->comparator,
                value: $condition->value,
            );
        };

        $mustAll = (bool) $feed->must_all_conditions_pass;

        $query->where(function (Builder $query) use ($clauses, $applyClause, $mustAll): void {
            foreach ($clauses as $index => $condition) {
                $asOr = ! $mustAll && $index > 0;

                $applyClause($query, $condition, $asOr);
            }
        });
    }

    protected function resolveArticleColumn(string $parameter): ?string
    {
        $parameter = trim($parameter);

        return match ($parameter) {
            'article.url' => 'url',
            'article.headline' => 'headline',
            'article.content' => 'content',
            'article.author_id' => 'author_id',
            'article.author_type' => 'author_type',
            'article.created_at', 'article.published_at' => 'created_at',
            'article.last_modified' => 'last_modified',
            default => null,
        };
    }

    protected function applyComparator(
        Builder $query,
        bool $asOr,
        string $column,
        string $comparator,
        mixed $value,
    ): void {
        $comparator = strtoupper(trim($comparator));

        // Normalize basic values
        $stringValue = is_string($value) ? $value : (string) $value;

        $where = $asOr ? 'orWhere' : 'where';

        match ($comparator) {
            Condition::COMPARATOR_EQUALS => $query->{$where}($column, '=', $stringValue),
            Condition::COMPARATOR_NOT_EQUAL => $query->{$where}($column, '!=', $stringValue),

            Condition::COMPARATOR_LIKE,
            Condition::COMPARATOR_LIKE_STRICT => $query->{$where}($column, 'like', '%'.$stringValue.'%'),
            Condition::COMPARATOR_NOT_LIKE => $query->{$where}($column, 'not like', '%'.$stringValue.'%'),

            Condition::COMPARATOR_STARTS_WITH => $query->{$where}($column, 'like', $stringValue.'%'),
            Condition::COMPARATOR_ENDS_WITH => $query->{$where}($column, 'like', '%'.$stringValue),

            Condition::COMPARATOR_IN => $this->applyIn($query, $asOr, $column, $stringValue, true),
            Condition::COMPARATOR_NOT_IN => $this->applyIn($query, $asOr, $column, $stringValue, false),

            Condition::COMPARATOR_GREATER_THAN => $query->{$where}($column, '>', $stringValue),
            Condition::COMPARATOR_GREATER_THAN_EQUAL => $query->{$where}($column, '>=', $stringValue),
            Condition::COMPARATOR_LESS_THAN => $query->{$where}($column, '<', $stringValue),
            Condition::COMPARATOR_LESS_THAN_EQUAL => $query->{$where}($column, '<=', $stringValue),

            default => null,
        };
    }

    protected function applyIn(Builder $query, bool $asOr, string $column, string $value, bool $in): void
    {
        $values = array_values(array_filter(array_map(
            static fn (string $part): string => trim($part),
            explode(',', $value),
        ), static fn (string $part): bool => $part !== ''));

        if (empty($values)) {
            return;
        }

        $whereIn = $in ? ($asOr ? 'orWhereIn' : 'whereIn') : ($asOr ? 'orWhereNotIn' : 'whereNotIn');

        $query->{$whereIn}($column, $values);
    }
}
