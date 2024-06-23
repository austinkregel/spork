<?php

declare(strict_types=1);

namespace App\Http\Controllers\Spork;

use App\Models\Email;
use App\Models\Message;
use App\Services\Messaging\ImapCredentialService;
use Inertia\Inertia;

class InboxController
{
    public function index()
    {
        return Inertia::render('Postal/Inbox', [
            'messages' => Email::query()
                ->with('from', 'to')
                ->orderByDesc('sent_at')
                ->paginate(),
        ]);
    }

    public function show(Email $email)
    {
        $email->load('credential');

        abort_unless($email->credential->user_id === auth()->id(), 4035);

        $email = (new ImapCredentialService($email->credential))->findMessage($email->email_id, true);
        $messageBody = base64_decode($email['body']);

        $bodyWithTheImagesDisabledForPrivacy = str_replace(' src=', ' data-src=', $messageBody);

        return view('emails.'.$email['view'], [
            'body' => $bodyWithTheImagesDisabledForPrivacy,
        ]);
    }
}
