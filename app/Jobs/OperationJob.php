<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Operations\ServerAction;
use Carbon\Carbon;
use App\Exceptions\OperationCanceledException;
use App\Exceptions\OperationStoppedException;
use App\Operations\Operation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\App;

class OperationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        /** @var Operation|ServerAction $operation */
        protected Operation $operation
    ) {
    }

    public function handle(): void
    {
        try {
            if (! method_exists($this->operation, 'run')) {
                throw new OperationCanceledException();
            }

            App::call([$this->operation, 'run']);
        } catch (OperationStoppedException $e) {
            $this->operation->started_run_at = null;
            $this->operation->save();

            return;
        } catch (OperationCanceledException $e) {
            $this->operation->delete();

            return;
        } finally {
            $this->operation->finished_run_at = Carbon::now();
            $this->operation->save();
        }
    }

    public function getOperation(): Operation
    {
        return $this->operation;
    }

    public function displayName()
    {
        return get_class($this->operation);
    }

    public function tags()
    {
        return $this->operation->tags();
    }
}
