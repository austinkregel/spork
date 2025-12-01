<?php

declare(strict_types=1);

namespace App\Operations;

use App\Models\Automation;
use App\Models\AutomationStep;
use App\Services\Automation\DuskExecutor;
use App\Services\Automation\StepRunner;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AutomationOperation extends Operation
{
    protected $table = 'automation_operations';

    protected $fillable = [
        'automation_id',
        'queue',
        'queueConnection',
        'should_run_at',
    ];

    public function automation(): BelongsTo
    {
        return $this->belongsTo(Automation::class);
    }

    public function run(): void
    {
        $automation = $this->automation()->with('steps')->first();
        if (! $automation) {
            return;
        }

        /** @var AutomationStep[] $steps */
        $steps = $automation->steps->all();

        /** @var StepRunner $runner */
        $runner = app(StepRunner::class);
        $result = $runner->run($automation, $steps);

        if (isset($result['error'])) {
            $this->error = (string) $result['error'];
        }
        if (isset($result['output'])) {
            $this->output = (string) $result['output'];
        }
    }
}


