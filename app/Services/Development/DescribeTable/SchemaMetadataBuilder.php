<?php

declare(strict_types=1);

namespace App\Services\Development\DescribeTable;

use App\Data\Crud\FieldDefinition;
use Illuminate\Database\ConnectionInterface;

class SchemaMetadataBuilder
{
    public function __construct(
        private readonly ConnectionInterface $connection,
    ) {
    }

    /**
     * @return array{field_definitions: FieldDefinition[], filters: array, raw_description: array, raw_indexes: array}
     */
    public function build(string $table): array
    {
        $description = $this->connection->select('describe '.$table);
        $indexes = $this->connection->select('show indexes from '.$table);

        return [
            'field_definitions' => FieldDefinition::collectionFromDescribeRows($description),
            'filters' => array_values(array_unique(array_map(fn ($query) => $query->Column_name, $indexes))),
            'raw_description' => $description,
            'raw_indexes' => $indexes,
        ];
    }
}

