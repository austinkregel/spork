<?php

declare(strict_types=1);

namespace App\Contracts\Matrix;

use App\Data\Matrix\MatrixEventContext;

interface MatrixEventHandlerContract
{
    public function supports(string $eventType): bool;

    public function handle(MatrixEventContext $context): void;
}








