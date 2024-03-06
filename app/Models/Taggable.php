<?php
declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Spatie\Tags\Tag;
use ArrayAccess;

interface Taggable
{
    public function tags(): MorphToMany;
    public function attachTags(array | ArrayAccess | Tag $tags, string $type = null): static;
}
