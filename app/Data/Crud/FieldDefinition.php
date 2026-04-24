<?php

declare(strict_types=1);

namespace App\Data\Crud;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Str;
use JsonSerializable;

class FieldDefinition implements Arrayable, JsonSerializable
{
    public function __construct(
        public readonly string $name,
        public readonly string $databaseType,
        public readonly string $inputType,
        public readonly bool $required = false,
        public readonly bool $sortable = false,
        public readonly ?string $default = null,
        public readonly ?int $maxLength = null,
        public readonly array $extra = [],
    ) {}

    /**
     * @param  array<object>|object[]  $rows
     * @return FieldDefinition[]
     */
    public static function collectionFromDescribeRows(array $rows): array
    {
        return array_map(fn (object $row) => self::fromDescribeRow($row), $rows);
    }

    public static function fromDescribeRow(object $row): self
    {
        $databaseType = $row->Type ?? 'text';
        $simpleType = Str::before($databaseType, '(') ?: $databaseType;
        $maxLength = null;

        if (Str::contains($databaseType, '(')) {
            $possibleLimit = Str::betweenFirst($databaseType, '(', ')');
            if (is_numeric($possibleLimit)) {
                $maxLength = (int) $possibleLimit;
            }
        }

        return new self(
            name: $row->Field ?? 'unknown',
            databaseType: $databaseType,
            inputType: self::mapInputType($simpleType),
            required: ($row->Null ?? '') === 'NO' && ($row->Extra ?? '') !== 'auto_increment',
            sortable: self::isSortable($row),
            default: $row->Default ?? null,
            maxLength: $maxLength,
            extra: [
                'key' => $row->Key ?? null,
                'extra' => $row->Extra ?? null,
                'raw_type' => $databaseType,
            ],
        );
    }

    private static function mapInputType(string $databaseType): string
    {
        return match (Str::lower($databaseType)) {
            'bigint', 'int', 'integer', 'double', 'float', 'decimal' => 'number',
            'varchar', 'char' => 'text',
            'text', 'longtext', 'mediumtext' => 'textarea',
            'datetime', 'timestamp', 'date' => 'datetime',
            'boolean', 'tinyint' => 'boolean',
            default => $databaseType,
        };
    }

    private static function isSortable(object $row): bool
    {
        $type = Str::lower($row->Type ?? '');
        $field = $row->Field ?? '';
        $isNotNullable = ($row->Null ?? '') === 'NO';

        if (Str::contains($type, 'int') && $isNotNullable) {
            return true;
        }

        if (Str::contains($type, ['timestamp', 'date']) && $isNotNullable) {
            return true;
        }

        if (Str::contains($field, [
            'name',
            'created_at',
            'deleted_at',
            'updated_at',
        ])) {
            return true;
        }

        return false;
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'database_type' => $this->databaseType,
            'input_type' => $this->inputType,
            'required' => $this->required,
            'sortable' => $this->sortable,
            'default' => $this->default,
            'max_length' => $this->maxLength,
            'extra' => array_filter($this->extra, fn ($value) => $value !== null),
        ];
    }

    public function toTypePayload(): array
    {
        return array_merge(
            ['type' => $this->inputType],
            $this->default !== null ? ['value' => $this->default] : [],
            $this->maxLength !== null ? ['max-length' => $this->maxLength] : [],
        );
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
