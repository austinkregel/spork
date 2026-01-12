<?php

declare(strict_types=1);

namespace App\Http\Controllers\Spork;

use App\Http\Requests\Automation\StoreTagConditionRequest;
use App\Http\Requests\Automation\UpdateTagConditionRequest;
use App\Models\Condition;
use App\Models\Tag;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class AutomationTagConditionsController
{
    public function store(StoreTagConditionRequest $request, Tag $tag): JsonResponse
    {
        $user = $request->user();
        abort_unless($user !== null, 404);

        abort_unless($user->tags()->whereKey($tag->getKey())->exists(), 404);

        /** @var Condition $condition */
        $condition = $tag->conditions()->create($request->validated());

        return response()->json($condition->refresh());
    }

    public function update(UpdateTagConditionRequest $request, Tag $tag, Condition $condition): JsonResponse
    {
        $user = $request->user();
        abort_unless($user !== null, 404);

        abort_unless($user->tags()->whereKey($tag->getKey())->exists(), 404);
        abort_unless(
            $condition->conditionable_type === Tag::class && (int) $condition->conditionable_id === (int) $tag->getKey(),
            404
        );

        $condition->update($request->validated());

        return response()->json($condition->refresh());
    }

    public function destroy(Request $request, Tag $tag, Condition $condition): Response
    {
        $user = $request->user();
        abort_unless($user !== null, 404);

        abort_unless($user->tags()->whereKey($tag->getKey())->exists(), 404);
        abort_unless(
            $condition->conditionable_type === Tag::class && (int) $condition->conditionable_id === (int) $tag->getKey(),
            404
        );

        $condition->delete();

        return response('', 204);
    }
}
