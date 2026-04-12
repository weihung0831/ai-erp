<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class AdminController extends Controller
{
    public function queryLogs(): View
    {
        return view('pages.admin.query-logs');
    }

    public function quickActions(): View
    {
        return view('pages.admin.quick-actions');
    }

    public function schemaFields(): View
    {
        return view('pages.admin.schema-fields');
    }
}
