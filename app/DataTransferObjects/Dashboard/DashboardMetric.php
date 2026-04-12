<?php

namespace App\DataTransferObjects\Dashboard;

use App\Enums\AggregationType;
use App\Enums\ValueFormat;

/**
 * 單一 Dashboard 指標的結果。
 *
 * DashboardService 查完 DB 後組裝此 DTO，
 * Controller 呼叫 toArray() 回傳 JSON。
 */
final readonly class DashboardMetric
{
    public function __construct(
        public string $label,
        public string $tableName,
        public string $columnName,
        public AggregationType $aggregation,
        public ValueFormat $valueFormat,
        public int|float $value,
        public string $formattedValue,
    ) {}

    /**
     * @return array{label: string, table_name: string, column_name: string, aggregation: string, value_format: string, value: int|float, formatted_value: string}
     */
    public function toArray(): array
    {
        return [
            'label' => $this->label,
            'table_name' => $this->tableName,
            'column_name' => $this->columnName,
            'aggregation' => $this->aggregation->value,
            'value_format' => $this->valueFormat->value,
            'value' => $this->value,
            'formatted_value' => $this->formattedValue,
        ];
    }
}
