<?php

declare(strict_types=1);

namespace App\Services\Development;

use App\Contracts\Services\Development\DescribeTableServiceContract;
use App\Repositories\TableDescriptionRepository;
use App\Services\Development\DescribeTable\TableDescriptionFactory;
use Illuminate\Database\Eloquent\Model;

class DescribeTableService implements DescribeTableServiceContract
{
    private TableDescriptionRepository $repository;

    private TableDescriptionFactory $factory;

    public function __construct(
        ?TableDescriptionRepository $repository = null,
        ?TableDescriptionFactory $factory = null,
    ) {
        $this->repository = $repository ?? app(TableDescriptionRepository::class);
        $this->factory = $factory ?? app(TableDescriptionFactory::class);
    }

    public function describe(Model $model): array
    {
        $table = $model->getTable();
        $description = $this->repository->get($table);
        $stale = $description === null;

        if (! $stale) {
            $stale = $this->repository->needsRefresh($table);
        }

        if ($stale) {
            $description = $this->factory->forModel($model, false);
            $this->repository->put($description);
        }

        $descriptionWithPermissions = $this->factory->withPermissions($description, $model);

        return $descriptionWithPermissions->toArray();
    }

    public function describeTable(string $table): array
    {
        $description = $this->repository->get($table);
        $stale = $description === null;

        if (! $stale) {
            $stale = $this->repository->needsRefresh($table);
        }

        if ($stale) {
            $description = $this->factory->forTable($table);
            $this->repository->put($description);
        }

        return $description->toArray();
    }
}
