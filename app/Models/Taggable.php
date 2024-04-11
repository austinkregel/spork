<?php

declare(strict_types=1);

namespace App\Models;

use ArrayAccess;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Spatie\Tags\Tag;

interface Taggable
{
    public function tags(): MorphToMany;

    public function attachTags(array|ArrayAccess|Tag $tags, ?string $type = null): static;
}
