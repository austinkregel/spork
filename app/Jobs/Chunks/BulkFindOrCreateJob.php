<?php

namespace App\Jobs\Chunks;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class BulkFindOrCreateJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public string $model,
        public array $chunks = []
    ){
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        /** @var Collection $existingThings */
        $existingThings = $this->model::query()
            ->select('name', 'primary_address')
            ->get()
            ->map(fn ($row) => md5($row->name.$row->primary_address));


        foreach ($this->chunks as $chunk) {
            if ($existingThings->contains($hash = md5($chunk['name'].$chunk['primary_address']))) {
                continue;
            }

            $existingThings->push($hash);

            $this->model::create($chunk);
        }
    }
}
