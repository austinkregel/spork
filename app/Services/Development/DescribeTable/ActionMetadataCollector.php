<?php

declare(strict_types=1);

namespace App\Services\Development\DescribeTable;

use App\Contracts\ActionInterface;
use App\Data\Crud\ActionMetadata;
use App\Services\Code;
use Illuminate\Database\Eloquent\Model;

class ActionMetadataCollector
{
    public function __construct(
        private readonly ?array $actionClasses = null,
    ) {
    }

    /**
     * @return ActionMetadata[]
     */
    public function collect(Model $model): array
    {
        $actions = [];
        $notedInstances = $this->actionClasses ?? Code::instancesOf(ActionInterface::class)->getClasses();

        foreach ($notedInstances as $class) {
            $instance = app($class);

            if (! property_exists($instance, 'models')) {
                continue;
            }

            $models = is_array($instance->models) ? $instance->models : [];

            if (! in_array($model::class, $models, true)) {
                continue;
            }

            $actions[] = ActionMetadata::fromAction($instance);
        }

        return $actions;
    }
}

