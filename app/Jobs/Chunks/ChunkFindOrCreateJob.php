<?php

declare(strict_types=1);

namespace App\Jobs\Chunks;

use Illuminate\Queue\SerializesModels;

class ChunkFindOrCreateJob
{
    use SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public string $model,
        public array $attributes = [],
        public array $values = [])
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $instance = $this->model;

        $instance::firstOrCreate($this->attributes, $this->values);
    }
}
