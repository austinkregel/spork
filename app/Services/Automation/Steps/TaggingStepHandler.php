<?php

declare(strict_types=1);

namespace App\Services\Automation\Steps;

use App\Models\Automation;
use App\Models\AutomationStep;
use App\Models\Tag;
use Illuminate\Database\Eloquent\Model;

class TaggingStepHandler
{
    /**
     * @return array{output?:string,error?:string}
     */
    public function execute(Automation $automation, AutomationStep $step): array
    {
        $config = $step->config ?? [];
        $target = $config['target'] ?? null;
        $action = $config['action'] ?? null;
        $tagIds = $config['tag_ids'] ?? [];

        if (! $target || ! $action || ! is_array($tagIds)) {
            return ['error' => 'Tagging step missing target, action, or tag_ids'];
        }

        $class = $target['type'] ?? null;
        $id = $target['id'] ?? null;

        if (! $class || ! $id || ! class_exists($class)) {
            return ['error' => 'Invalid target'];
        }

        /** @var Model $model */
        $model = $class::query()->find($id);
        if (! $model) {
            return ['error' => 'Target not found'];
        }

        // Ensure tags belong to the automation's user
        $ownedTagIds = $automation->user->tags()->pluck('id')->all();
        $attachable = array_values(array_intersect($ownedTagIds, $tagIds));
        if (empty($attachable)) {
            return ['error' => 'No permissible tags'];
        }

        if (! method_exists($model, 'attachTags')) {
            return ['error' => 'Target is not taggable'];
        }

        $tags = Tag::query()->whereIn('id', $attachable)->get();

        if ($action === 'attach') {
            $model->attachTags($tags);
        } elseif ($action === 'detach') {
            foreach ($tags as $tag) {
                $model->detachTag($tag);
            }
        } else {
            return ['error' => 'Unknown tagging action'];
        }

        return ['output' => sprintf('Tagging %s: %s %d tags', $class, $action, count($attachable))];
    }
}
