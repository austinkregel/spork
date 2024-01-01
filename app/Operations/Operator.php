<?php

declare(strict_types=1);

namespace App\Operations;

use App\Jobs\OperationJob;
use Carbon\Carbon;

final class Operator
{
    public static function queue()
    {
        foreach (config('operations.operations') as $operation) {
            $operation::whereNull('started_run_at')
                ->where('should_run_at', '<=', Carbon::now())
                ->get()
                ->each(function (Operation $operation) {
                    $operation->started_run_at = Carbon::now();
                    $operation->save();

                    if (method_exists($operation, 'queue')) {
                        $operation->queue();
                    }

                    $operationJob = OperationJob::dispatch($operation);

                    if (property_exists($operation, 'queue')) {
                        $operationJob->onQueue($operation->queue);
                    }

                    if (property_exists($operation, 'queueConnection')) {
                        $operationJob->onConnection($operation->queueConnection);
                    }
                });
        }
    }
}
