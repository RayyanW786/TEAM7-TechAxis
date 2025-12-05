<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminReportsController extends ApiController
{
    public function salesSummary(Request $request)
    {
        $this->requireAdmin($request);

        $data = $request->validate([
            'from' => ['required', 'date'],
            'to' => ['required', 'date', 'after:from'],
        ]);

        $row = DB::selectOne(
            'select * from fn_admin_sales_summary(?::timestamptz, ?::timestamptz)',
            [$data['from'], $data['to']]
        );

        return response()->json($row);
    }
}
