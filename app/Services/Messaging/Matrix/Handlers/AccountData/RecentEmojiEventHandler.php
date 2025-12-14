<?php

declare(strict_types=1);

namespace App\Services\Messaging\Matrix\Handlers\AccountData;

use App\Contracts\Matrix\MatrixEventHandlerContract;
use App\Data\Matrix\MatrixEventContext;

class RecentEmojiEventHandler implements MatrixEventHandlerContract
{
    public function supports(string $eventType): bool
    {
        return $eventType === 'io.element.recent_emoji';
    }

    public function handle(MatrixEventContext $context): void
    {
        $emoji = array_map(
            fn (array $emojiEntry) => $emojiEntry[0] ?? null,
            $context->event['content']['recent_emoji'] ?? []
        );

        $emoji = array_values(array_filter($emoji));

        $context->state->storeRecentEmoji($emoji);
    }
}










