<?php

declare(strict_types=1);

namespace App\Events\Models\Article;

use App\Events\AbstractLogicalEvent;
use App\Models\Article;

class ArticleUpdating extends AbstractLogicalEvent
{
    public function __construct(
        public Article $model,
    ) {
    }
}
