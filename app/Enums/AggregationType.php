<?php

namespace App\Enums;

/**
 * Dashboard KPI 的彙總方式。
 *
 * 儲存在 schema_metadata.aggregation 欄位，
 * DashboardService 據此產生預定義 SQL 的 aggregate function。
 */
enum AggregationType: string
{
    case Sum = 'sum';
    case Count = 'count';
    case Avg = 'avg';
    case Max = 'max';
    case Min = 'min';
}
