<?php

namespace App\DataTransferObjects\Schema;

/**
 * 單一 column 的 schema metadata，由 SchemaIntrospector 載入後
 * 餵給 QueryEngine 的 system prompt 使用。
 *
 * restricted 為 true 時 QueryEngine 不得在 SQL 中 SELECT 此欄位（US-7）。
 */
final readonly class ColumnMetadata
{
    public function __construct(
        public string $name,
        public string $type,
        public string $displayName,
        public ?string $description = null,
        public bool $restricted = false,
    ) {}
}
