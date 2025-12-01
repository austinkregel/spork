<?php

declare(strict_types=1);

namespace Tests\Fixtures\Models;

use App\Models\Taggable;
use ArrayAccess;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Mockery;
use Spatie\Tags\Tag;

class ExampleModel extends Model
{
    protected $table = 'example_models';

    protected $fillable = ['name'];
}

class ExampleTaggableModel extends ExampleModel implements Taggable
{
    public function tags(): MorphToMany
    {
        return Mockery::mock(MorphToMany::class);
    }

    public function attachTags(array|ArrayAccess|Tag $tags, ?string $type = null): static
    {
        return $this;
    }
}

