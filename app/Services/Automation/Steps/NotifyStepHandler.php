<?php

declare(strict_types=1);

namespace App\Services\Automation\Steps;

use App\Models\Automation;
use App\Models\AutomationStep;
use App\Models\User;
use App\Notifications\AutomationGenericNotification;

class NotifyStepHandler
{
    /**
     * @return array{output?:string,error?:string}
     */
    public function execute(Automation $automation, AutomationStep $step): array
    {
        $config = $step->config ?? [];
        $title = (string) ($config['title'] ?? '');
        $message = (string) ($config['message'] ?? '');
        $level = (string) ($config['level'] ?? 'info');
        $userIds = $config['user_ids'] ?? ($config['user_id'] ?? null);

        if ($title === '' || $message === '') {
            return ['error' => 'Notify step missing title or message'];
        }

        $recipients = collect();
        if (is_array($userIds)) {
            $recipients = User::query()->whereIn('id', $userIds)->get();
        } elseif (! is_null($userIds)) {
            $user = User::query()->find($userIds);
            if ($user) {
                $recipients = collect([$user]);
            }
        }

        if ($recipients->isEmpty()) {
            $recipients = collect([$automation->user]);
        }

        $notification = new AutomationGenericNotification($title, $message, $level);
        $recipients->each(fn (User $u) => $u->notify($notification));

        return ['output' => 'Notifications sent: '.$recipients->count()];
    }
}
