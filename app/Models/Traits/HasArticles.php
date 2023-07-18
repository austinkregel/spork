<?php
declare(strict_types=1);

namespace App\Models\Traits;

use App\Models\Article;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * @extends \Eloquent
 */
trait HasArticles
{
    public function articles(): MorphMany
    {
        return $this->morphMany(Article::class, 'author')->orderByDesc('created_at');
    }
}
