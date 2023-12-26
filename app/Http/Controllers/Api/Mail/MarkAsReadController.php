<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Mail;

use App\Http\Controllers\Controller;
use App\Services\ImapService;

class MarkAsReadController extends Controller
{
    public function __invoke(ImapService $imap)
    {
        request()->validate([
            'id' => 'integer',
        ]);

        $imap->markAsRead(request('id'));
    }
}
