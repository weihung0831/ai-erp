<?php

namespace App\DataTransferObjects\Schema;

/**
 * 單一 table 的 schema metadata。包含欄位清單與中文顯示名稱，
 * 是 QueryEngine 組 system prompt 時的主要資料來源。
 */
final readonly class TableMetadata
{
    /**
     * @param  list<ColumnMetadata>  $columns
     */
    public function __construct(
        public string $name,
        public string $displayName,
        public array $columns,
    ) {}

    /**
     * @return list<string>
     */
    public function columnNames(): array
    {
        return array_map(fn (ColumnMetadata $column): string => $column->name, $this->columns);
    }

    public function findColumn(string $name): ?ColumnMetadata
    {
        foreach ($this->columns as $column) {
            if ($column->name === $name) {
                return $column;
            }
        }

        return null;
    }
}
