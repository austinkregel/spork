<?php

declare(strict_types=1);

Artisan::command('test', function () {
    \App\Models\Credential::firstWhere('type', 'email')->messages()->create([
        'from_person' => 1,
        'to_person' => 1,
        'type' => 'email',
        'event_id' => 'fake4',
        'originated_at' => now(),
        'is_decrypted' => true,
        'seen' => true,
        'spam' => false,
        'answered' => false,
    ]);
});
