<?php

declare(strict_types=1);

namespace App\Listeners\Tags;

use App\Events\Models\Tag\TagCreated;
use Illuminate\Http\Request;

class EnsureUserIsAppliedToAutomaticTagsListener
{
    public function __construct(
        protected Request $request
    ) {}

    public function handle(TagCreated $event): void
    {
        $tag = $event->model;

        if ($tag->type !== 'automatic') {
            return;
        }

        $user = $this->request->user();

        if (empty($user)) {
            return;
        }

        if ($user->tags()->where('id', $tag->id)->exists()) {
            return;
        }

        $user->tags()->attach($tag);
    }
}
