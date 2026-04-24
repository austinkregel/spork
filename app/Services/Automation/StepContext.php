<?php

declare(strict_types=1);

namespace App\Services\Automation;

use App\Models\Automation;
use Illuminate\Support\Arr;

class StepContext
{
    protected array $stepData = [];

    protected array $globals;

    public function __construct(protected Automation $automation)
    {
        $user = $automation->user;

        $this->globals = [
            'time.iso' => now()->toIso8601String(),
            'time.timestamp' => now()->timestamp,
            'date' => now()->toDateString(),
            'automation.id' => $automation->getKey(),
            'automation.name' => $automation->name,
            'user.id' => $user?->getKey(),
            'user.email' => $user?->email,
        ];
    }

    public function globals(): array
    {
        return $this->globals;
    }

    public function record(int $index, ?array $items = null): void
    {
        $data = $items ?? [];

        if (! Arr::isAssoc($data) && $this->isListOfAssoc($data)) {
            $this->stepData[$index] = array_values($data);

            return;
        }

        if (Arr::isAssoc($data)) {
            $this->stepData[$index] = [$data];

            return;
        }

        $this->stepData[$index] = [];
    }

    public function dataFor(int $index): array
    {
        return $this->stepData[$index] ?? [];
    }

    public function latestData(): array
    {
        if (empty($this->stepData)) {
            return [];
        }

        return end($this->stepData) ?: [];
    }

    protected function isListOfAssoc(array $data): bool
    {
        return array_is_list($data) && collect($data)->every(fn ($item) => is_array($item));
    }
}
