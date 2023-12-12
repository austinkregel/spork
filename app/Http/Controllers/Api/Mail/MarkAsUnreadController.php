<?php

namespace App\Http\Controllers\Api\Mail;

use App\Http\Controllers\Controller;
use App\Services\ImapService;
use Illuminate\Http\Request;

class MarkAsUnreadController extends Controller
{
    public function __invoke(ImapService $imap)
    {
        request()->validate([
            'id' => 'integer'
        ]);

        $imap->markAsUnread(request('id'));
    }
}
