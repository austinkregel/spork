<?php

declare(strict_types=1);

namespace App\Operations;

use Carbon\Carbon;
use App\Exceptions\OperationCanceledException;
use App\Exceptions\OperationStoppedException;
use App\Jobs\OperationJob;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

abstract class Operation extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [
        'id',
        'started_run_at',
        'finished_run_at',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $dates = [
        'should_run_at',
        'started_run_at',
        'finished_run_at',
    ];

    /**
     * @throws OperationStoppedException
     */
    public function stop()
    {
        throw new OperationStoppedException();
    }

    /**
     * @throws OperationCanceledException
     */
    public function cancel()
    {
        throw new OperationCanceledException();
    }

    /**
     * @param  Carbon|string  $shouldRunAt
     * @param  array  $attributes
     * @return static
     */
    public static function schedule($shouldRunAt, $attributes = [])
    {
        return static::create(array_merge(['should_run_at' => $shouldRunAt], $attributes));
    }

    /**
     * @param  array  $attributes
     * @return static
     */
    public static function dispatch($attributes = [])
    {
        return static::schedule(Carbon::now(), $attributes);
    }

    /**
     * @param  array  $attributes
     * @return static
     */
    public static function dispatchNow($attributes = [])
    {
        $operation = static::dispatch($attributes);

        $operation->started_run_at = Carbon::now();

        if (method_exists($operation, 'queue')) {
            $operation->queue();
        }

        (new OperationJob($operation))->handle();

        return $operation;
    }

    /**
     * @return array
     */
    public function tags()
    {
        return [
            'operation',
            static::class.':'.$this->id,
        ];
    }
}
