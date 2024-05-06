<?php

declare(strict_types=1);

namespace App\Http\Controllers\Spork;

use App\Models\Message;
use App\Services\Messaging\ImapCredentialService;
use Inertia\Inertia;

class InboxController
{
    public function index()
    {
        return Inertia::render('Postal/Inbox', [
            'messages' => Message::query()
                ->with('from', 'to')
                ->where('type', 'email')
                ->orderByDesc('originated_at')
                ->paginate(),
        ]);
    }

    public function show(Message $message)
    {
        abort_if($message->type !== 'email', 404);

        $message->load('credential');

        abort_unless($message->credential->user_id === auth()->id(), 404);

        $message = (new ImapCredentialService($message->credential))->findMessage($message->event_id, true);
        $messageBody = base64_decode($message['body']);

        $bodyWithTheImagesDisabledForPrivacy = str_replace(' src=', ' data-src=', $messageBody);

        return view('emails.'.$message['view'], [
            'body' => $bodyWithTheImagesDisabledForPrivacy,
        ]);
    }
}
