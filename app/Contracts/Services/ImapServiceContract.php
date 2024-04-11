<?php

declare(strict_types=1);

namespace App\Contracts\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;

interface ImapServiceContract
{
    public function findAllMailboxes(): Collection;

    public function findAllFromDate(string $mailbox, Carbon $date): Collection;

    /**
     * @param  bool  $peak  This will set the read status in the imap server, when set to true, mail will be marked as read.
     */
    public function findMessage(string $messageNumber, bool $peak = true): array;

    public function markAsRead(string $messageId): void;

    public function markAsUnread(string $messageId): void;
}
