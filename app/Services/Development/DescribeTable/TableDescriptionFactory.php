<?php

declare(strict_types=1);

namespace App\Services\Development\DescribeTable;

use App\Data\Crud\TableDescription;
use App\Services\ActionFilter;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class TableDescriptionFactory
{
    public function __construct(
        private readonly SchemaMetadataBuilder $schemaBuilder,
        private readonly RelationInspector $relationInspector,
        private readonly ActionMetadataCollector $actionCollector,
        private readonly PermissionAdvisor $permissionAdvisor,
        private readonly TagMetadataCollector $tagCollector,
    ) {}

    public function forModel(Model $model, bool $withPermissions = true): TableDescription
    {
        $schema = $this->schemaBuilder->build($model->getTable());

        return new TableDescription(
            name: $model->getTable(),
            modelClass: $model::class,
            prettyName: class_basename($model),
            fieldDefinitions: $schema['field_definitions'],
            fillable: $this->resolveFillable($model),
            filters: $schema['filters'],
            includes: $this->relationInspector->inspect($model),
            queryActions: ActionFilter::WHITELISTED_ACTIONS,
            actions: $this->actionCollector->collect($model),
            tags: $this->tagCollector->collect($model),
            permissions: $withPermissions ? $this->permissionAdvisor->forModel($model) : [],
        );
    }

    public function forTable(string $table): TableDescription
    {
        $schema = $this->schemaBuilder->build($table);

        return new TableDescription(
            name: $table,
            modelClass: null,
            prettyName: Str::headline($table),
            fieldDefinitions: $schema['field_definitions'],
            fillable: ['name'],
            filters: $schema['filters'],
            includes: [],
            queryActions: ActionFilter::WHITELISTED_ACTIONS,
            actions: [],
            tags: [],
            permissions: [],
        );
    }

    private function resolveFillable(Model $model): array
    {
        $fillable = $model->getFillable();

        return empty($fillable) ? ['name'] : $fillable;
    }

    public function withPermissions(TableDescription $description, Model $model): TableDescription
    {
        return $description->withOverrides([
            'permissions' => $this->permissionAdvisor->forModel($model),
        ]);
    }
}
