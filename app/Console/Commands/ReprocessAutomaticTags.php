<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Article;
use App\Models\Email;
use App\Models\Finance\Transaction;
use App\Models\Message;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use App\Services\ConditionService;
use App\Models\Tag;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

class ReprocessAutomaticTags extends Command
{
    protected $signature = 'reprocess:automatic-tags {model} {id}';
    protected $description = 'Reprocess user automatic tags for a model with HasTags.';

    public function handle(LoggerInterface $logger)
    {
        $modelClass = $this->argument('model');
        $id = $this->argument('id');

        if (!class_exists($modelClass)) {
            $this->error("Model class does not exist: $modelClass");
            return 1;
        }

        /** @var Model $model */
        $model = $modelClass::find($id);
        if (!$model) {
            $this->error("Model not found.");
            return 1;
        }

        // Try to find the user (owner) for the model
        $user = $this->resolveUser($model);
        if (!$user) {
            $this->error('Could not resolve user for this model.');
            return 1;
        }

        $tags = $user->tags()->with('conditions')->where('type', 'automatic')->get();
        $conditionService = new ConditionService($logger);

        $context = $this->buildContext($model);
        $tagsToApply = $tags->filter(fn (Tag $tag) => $conditionService->process($tag, $context));

        $applied = 0;
        foreach ($tagsToApply as $tag) {
            if (!$model->tags()->where('id', $tag->id)->exists()) {
                $model->tags()->attach($tag);
                $applied++;
            }
        }

        $this->info("Applied $applied automatic tags.");
        return 0;
    }

    protected function resolveUser(Model $model)
    {
        // Try common patterns from listeners
        // Article: $model->externalRssFeed->owner
        if (method_exists($model, 'externalRssFeed') && $model->externalRssFeed) {
            return $model->externalRssFeed->owner ?? null;
        }
        // Email: $model->user or $model->credential->user
        if (isset($model->user) && $model->user) {
            return $model->user;
        }
        if (isset($model->credential) && $model->credential && isset($model->credential->user)) {
            return $model->credential->user;
        }
        // Message: $model->credential->user
        if (isset($model->credential) && $model->credential && isset($model->credential->user)) {
            return $model->credential->user;
        }
        // Transaction: $model->account->credential->user
        if (isset($model->account) && $model->account && isset($model->account->credential) && $model->account->credential && isset($model->account->credential->user)) {
            return $model->account->credential->user;
        }
        return null;
    }

    protected function buildContext(Model $model): array
    {
        // Build context based on model type
        $class = get_class($model);
        switch ($class) {
            case Article::class:
                return ['article' => $model->toArray()];
            case Email::class:
                return ['email' => $model];
            case Message::class:
                return ['message' => $model];
            case Transaction::class:
                return [
                    'transaction' => $model,
                    'account' => $model->account ?? null,
                ];
            default:
                return [Str::snake(class_basename($class),'_') => $model];
        }
    }
}

