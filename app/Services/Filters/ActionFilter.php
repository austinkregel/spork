<?php

declare(strict_types=1);

namespace App\Services\Filters;

use Illuminate\Support\Arr;
use Spatie\QueryBuilder\QueryBuilder;

class ActionFilter
{
    protected const WHITELISTED_ACTIONS = [
        'paginate',
        'count',
        'sum',
        'avg',
        'get',
        'first',
    ];

    protected const DEFAULT_ACTION = 'paginate';

    /**
     * @var string
     */
    protected $actionString;

    /**
     * @var string
     */
    protected $method;

    /**
     * @var array
     */
    protected $arguments;

    /**
     * @var bool
     */
    protected $hasArguments = false;

    public function __construct(string $actionString)
    {
        $this->actionString = $actionString;
        $this->parseAction();
    }

    public function getMethod()
    {
        return $this->method;
    }

    public function getArguments(): array
    {
        return $this->arguments;
    }

    public function hasArguments(): bool
    {
        return $this->hasArguments;
    }

    public function execute(QueryBuilder $query)
    {
        if ($this->hasArguments()) {
            return $query->{$this->getMethod()}(...$this->getArguments());
        }

        return $query->{$this->getMethod()}();
    }

    protected function parseAction()
    {
        $parts = explode(':', $this->actionString);

        $this->method = $this->validateAction(Arr::first($parts));

        if (count($parts) === 1) {
            $this->hasArguments = false;

            return;
        }

        $this->arguments = array_filter(explode(',', end($parts)), function ($bit) {
            return ! empty($bit);
        });

        $this->hasArguments = count($this->arguments) > 0;
    }

    protected function validateAction(string $action): string
    {
        if (! in_array($action, static::WHITELISTED_ACTIONS)) {
            return static::DEFAULT_ACTION;
        }

        return $action;
    }
}
