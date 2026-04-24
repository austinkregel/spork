<?php

declare(strict_types=1);

namespace App\Http\Controllers\Spork;

use App\Http\Requests\Automation\StoreAutomationTagRequest;
use App\Http\Requests\Automation\UpdateAutomationTagRequest;
use App\Models\Tag;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;

class AutomationTagsController
{
    public function __invoke(StoreAutomationTagRequest $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user !== null, 404);

        $payload = $request->validated();

        $type = $payload['type'] ?? null;
        if ($type === '') {
            $type = null;
        }

        $mustAll = (bool) ($payload['must_all_conditions_pass'] ?? false);
        if ($type !== 'automatic') {
            $mustAll = false;
        }

        $slug = Str::slug($payload['name']);

        /** @var Tag|null $existing */
        $existing = $user
            ->tags()
            ->when($type === null, fn ($q) => $q->whereNull('type'), fn ($q) => $q->where('type', $type))
            ->where('slug->en', $slug)
            ->first();

        if ($existing) {
            return redirect()
                ->route('automations.tags.show', $existing)
                ->with('flash.banner', 'Tag already exists.');
        }

        /** @var Tag $tag */
        $tag = Tag::query()->create([
            'name' => ['en' => $payload['name']],
            'slug' => ['en' => $slug],
            'type' => $type,
            'must_all_conditions_pass' => $mustAll,
        ]);

        $user->tags()->syncWithoutDetaching([$tag->getKey()]);

        return redirect()->route('automations.tags.show', $tag);
    }

    public function update(UpdateAutomationTagRequest $request, Tag $tag): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user !== null, 404);

        abort_unless($user->tags()->whereKey($tag->getKey())->exists(), 404);

        $payload = $request->validated();

        if (array_key_exists('name', $payload)) {
            $existing = is_array($tag->name) ? $tag->name : [];
            $tag->name = array_merge($existing, ['en' => $payload['name']]);
            $existingSlug = is_array($tag->slug) ? $tag->slug : [];
            $tag->slug = array_merge($existingSlug, ['en' => Str::slug($payload['name'])]);
        }

        if (array_key_exists('type', $payload)) {
            $type = $payload['type'];
            if ($type === '') {
                $type = null;
            }
            $tag->type = $type;

            if ($type !== 'automatic') {
                $tag->must_all_conditions_pass = false;
            }
        }

        if (array_key_exists('must_all_conditions_pass', $payload)) {
            if (($tag->type ?? null) === 'automatic') {
                $tag->must_all_conditions_pass = (bool) $payload['must_all_conditions_pass'];
            } else {
                $tag->must_all_conditions_pass = false;
            }
        }

        $tag->save();

        return back()->with('flash.banner', 'Tag updated.');
    }
}
