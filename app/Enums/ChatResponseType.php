<?php

namespace App\Enums;

/**
 * 聊天回應的呈現類型。Query Engine 輸出這個值，
 * 前端據此決定要渲染大字數字、表格、釐清按鈕還是錯誤訊息。
 */
enum ChatResponseType: string
{
    /** 單一數字回答（營收、數量等），US-1 的唯一類型。 */
    case Numeric = 'numeric';

    /** 多筆資料列表（US-2）。 */
    case Table = 'table';

    /** 綜合分析（趨勢、比較），文字 + 高亮數字。 */
    case Summary = 'summary';

    /** 低信心度時的釐清引導（US-4）。 */
    case Clarification = 'clarification';

    /** 無法理解或執行，顯示友善錯誤訊息。 */
    case Error = 'error';
}
