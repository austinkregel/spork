<?php

declare(strict_types=1);

namespace App\Data\Crud;

use App\Contracts\ActionInterface;
use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;

class ActionMetadata implements Arrayable, JsonSerializable
{
    public function __construct(
        public readonly string $className,
        public readonly array $payload = [],
    ) {}

    public static function fromAction(ActionInterface $action): self
    {
        $payload = (array) $action;

        if (method_exists($action, 'fields')) {
            $payload['fields'] = $action->fields();
        }

        $payload['class'] = $payload['class'] ?? $action::class;

        return new self(
            className: $action::class,
            payload: $payload,
        );
    }

    public static function fromArray(array $payload): self
    {
        $className = $payload['class'] ?? ($payload['className'] ?? 'unknown');

        return new self($className, $payload);
    }

    public function toArray(): array
    {
        return $this->payload + [
            'class' => $this->className,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
