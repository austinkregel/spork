<?php

declare(strict_types=1);

namespace App\Listeners\Email;

use App\Events\Models\Email\EmailCreated;
use App\Models\Email;
use App\Models\Tag;
use App\Services\ConditionService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Psr\Log\LoggerInterface;

class ApplyUserAutomatedTagsToEmail implements ShouldQueue
{
    /**
     * Create the event listener.
     */
    public function __construct(
        protected LoggerInterface $logger,
    ) {}

    /**
     * Handle the event.
     */
    public function handle(EmailCreated $event): void
    {
        $event->model->load('credential.user');
        $event->model->refresh();

        /** @var Email $email */
        $email = $event->model;
        $credential = $email->credential;

        $user = $credential->user;

        if (empty($user)) {
            return;
        }

        $tags = $user->tags()->with('conditions')->where('type', 'automatic')->get();

        $conditionService = new ConditionService(
            $this->logger,
        );

        $tagsToApply = $tags->filter(fn (Tag $tag) => $conditionService->process($tag, [
            'email' => $email,
        ]));

        foreach ($tagsToApply as $tag) {
            if (! $email->tags()->where('id', $tag->id)->exists()) {
                $email->tags()->attach($tag);
            }
        }
    }
}
