<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Mail;

use App\Http\Controllers\Controller;
use App\Http\Requests\Messages\MarkAsReadRequest;
use App\Models\Message;
use App\Services\ImapService;
use App\Services\Messaging\ImapFactoryService;

class MarkAsReadController extends Controller
{
    public function __invoke(MarkAsReadRequest $request, ImapFactoryService $factoryService)
    {
        request()->validate([
            'id' => 'integer',
        ]);

    }
}
