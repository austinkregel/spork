<?php

declare(strict_types=1);

namespace App\Data\Crud;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use JsonSerializable;

class TableDescription implements Arrayable, JsonSerializable
{
    /**
     * @param  FieldDefinition[]  $fieldDefinitions
     * @param  ActionMetadata[]  $actions
     */
    public function __construct(
        public readonly string $name,
        public readonly ?string $modelClass,
        public readonly string $prettyName,
        public readonly array $fieldDefinitions = [],
        public readonly array $fillable = [],
        public readonly array $filters = [],
        public readonly array $includes = [],
        public readonly array $queryActions = [],
        public readonly array $actions = [],
        public readonly array|Collection $tags = [],
        public readonly array $permissions = [],
    ) {}

    /**
     * Convenience constructor when a model instance is already in hand.
     */
    public static function fromModel(Model $model, array $fieldDefinitions, array $options = []): self
    {
        return new self(
            name: $model->getTable(),
            modelClass: $model::class,
            prettyName: class_basename($model),
            fieldDefinitions: $fieldDefinitions,
            fillable: $options['fillable'] ?? $model->getFillable(),
            filters: $options['filters'] ?? [],
            includes: $options['includes'] ?? [],
            queryActions: $options['query_actions'] ?? [],
            actions: $options['actions'] ?? [],
            tags: $options['tags'] ?? [],
            permissions: $options['permissions'] ?? [],
        );
    }

    /**
     * Hydrate from a previously persisted payload (see toPersistableArray()).
     */
    public static function fromPersisted(array $payload): self
    {
        $fields = array_map(
            fn (array $field) => new FieldDefinition(
                name: $field['name'],
                databaseType: $field['database_type'] ?? $field['input_type'],
                inputType: $field['input_type'],
                required: $field['required'] ?? false,
                sortable: $field['sortable'] ?? false,
                default: $field['default'] ?? null,
                maxLength: $field['max_length'] ?? null,
                extra: $field['extra'] ?? [],
            ),
            $payload['field_definitions'] ?? []
        );

        $actions = array_map(
            fn (array $action) => ActionMetadata::fromArray($action),
            $payload['actions'] ?? []
        );

        return new self(
            name: $payload['name'],
            modelClass: $payload['model'] ?? null,
            prettyName: $payload['pretty_name'] ?? Str::headline($payload['name']),
            fieldDefinitions: $fields,
            fillable: $payload['fillable'] ?? [],
            filters: $payload['filters'] ?? [],
            includes: $payload['includes'] ?? [],
            queryActions: $payload['query_actions'] ?? [],
            actions: $actions,
            tags: isset($payload['tags']) ? collect($payload['tags']) : [],
            permissions: $payload['permissions'] ?? [],
        );
    }

    public function withOverrides(array $overrides): self
    {
        return new self(
            name: $this->name,
            modelClass: $overrides['model'] ?? $this->modelClass,
            prettyName: $overrides['pretty_name'] ?? $this->prettyName,
            fieldDefinitions: $overrides['field_definitions'] ?? $this->fieldDefinitions,
            fillable: $overrides['fillable'] ?? $this->fillable,
            filters: $overrides['filters'] ?? $this->filters,
            includes: $overrides['includes'] ?? $this->includes,
            queryActions: $overrides['query_actions'] ?? $this->queryActions,
            actions: $overrides['actions'] ?? $this->actions,
            tags: $overrides['tags'] ?? $this->tags,
            permissions: $overrides['permissions'] ?? $this->permissions,
        );
    }

    public function getFieldNames(): array
    {
        return array_map(fn (FieldDefinition $field) => $field->name, $this->fieldDefinitions);
    }

    public function getSorts(): array
    {
        return array_values(array_map(
            fn (FieldDefinition $field) => $field->name,
            array_filter($this->fieldDefinitions, fn (FieldDefinition $field) => $field->sortable)
        ));
    }

    public function getRequired(): array
    {
        return array_values(array_map(
            fn (FieldDefinition $field) => $field->name,
            array_filter($this->fieldDefinitions, fn (FieldDefinition $field) => $field->required)
        ));
    }

    public function getTypes(): array
    {
        $types = [];
        foreach ($this->fieldDefinitions as $field) {
            $types[$field->name] = $field->toTypePayload();
        }

        return $types;
    }

    public function toArray(): array
    {
        $base = [
            'name' => $this->name,
            'model' => $this->modelClass,
            'pretty_name' => $this->prettyName,
            'actions' => array_map(fn (ActionMetadata $action) => $action->toArray(), $this->actions),
            'query_actions' => $this->queryActions,
            'fillable' => empty($this->fillable) ? ['name'] : $this->fillable,
            'fields' => $this->getFieldNames(),
            'filters' => $this->filters,
            'includes' => $this->includes,
            'sorts' => $this->getSorts(),
            'types' => $this->getTypes(),
            'required' => $this->getRequired(),
        ];

        if ($this->hasTags()) {
            $base['tags'] = $this->tags;
        }

        if (! empty($this->permissions)) {
            $base['permissions'] = $this->permissions;
        }

        return $base;
    }

    public function toPersistableArray(): array
    {
        return [
            'name' => $this->name,
            'model' => $this->modelClass,
            'pretty_name' => $this->prettyName,
            'fillable' => $this->fillable,
            'filters' => $this->filters,
            'includes' => $this->includes,
            'query_actions' => $this->queryActions,
            'tags' => $this->tagsToArray(),
            'permissions' => $this->permissions,
            'field_definitions' => array_map(fn (FieldDefinition $field) => $field->toArray(), $this->fieldDefinitions),
            'actions' => array_map(fn (ActionMetadata $action) => $action->toArray(), $this->actions),
        ];
    }

    private function hasTags(): bool
    {
        if ($this->tags instanceof Collection) {
            return $this->tags->isNotEmpty();
        }

        return ! empty($this->tags);
    }

    private function tagsToArray(): array
    {
        if ($this->tags instanceof Collection) {
            return $this->tags->toArray();
        }

        return $this->tags;
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
