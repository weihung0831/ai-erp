<?php

namespace App\DataTransferObjects\Dashboard;

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
        public string $section,
        public ValueFormat $valueFormat,
        public int|float $value,
        public string $formattedValue,
        public ?float $trend = null,
        public string $severity = 'normal',
    ) {}

    public function toArray(): array
    {
        return [
            'label' => $this->label,
            'section' => $this->section,
            'value_format' => $this->valueFormat->value,
            'value' => $this->value,
            'formatted_value' => $this->formattedValue,
            'trend' => $this->trend,
            'severity' => $this->severity,
        ];
    }
}
