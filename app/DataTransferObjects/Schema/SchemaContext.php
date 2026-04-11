<?php

namespace App\DataTransferObjects\Schema;

/**
 * 餵給 QueryEngine 的 schema 上下文，包含該租戶所有可查詢 table
 * 的 metadata。domainContext 用於引導 LLM 理解產業術語（「營收」、
 * 「毛利」在不同產業定義不同）。
 */
final readonly class SchemaContext
{
    /**
     * @param  list<TableMetadata>  $tables
     */
    public function __construct(
        public array $tables,
        public ?string $domainContext = null,
    ) {}

    /**
     * @return list<string>
     */
    public function tableNames(): array
    {
        return array_map(fn (TableMetadata $table): string => $table->name, $this->tables);
    }

    public function findTable(string $name): ?TableMetadata
    {
        foreach ($this->tables as $table) {
            if ($table->name === $name) {
                return $table;
            }
        }

        return null;
    }
}
