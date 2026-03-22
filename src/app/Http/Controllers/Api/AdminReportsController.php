<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminReportsController extends ApiController
{
    public function overview(Request $request)
    {
        $this->requireAdmin($request);

        $overview = DB::selectOne(
            <<<SQL
            select
              (select count(*) from orders) as total_orders,
              (select count(*) from orders where status in ('pending', 'placed', 'processing', 'shipped')) as open_orders,
              (select count(*) from shipments where shipped_at is not null and delivered_at is null) as shipments_in_transit,
              (select count(*) from support_tickets where status <> 'closed') as open_tickets,
              (select count(*) from inventory_alerts where created_at >= now() - interval '7 days') as recent_inventory_alerts,
              (select coalesce(sum(total_amount), 0) from orders where created_at >= now() - interval '30 days') as revenue_last_30_days
            SQL
        );

        $orderStatusBreakdown = DB::select(
            'select status, count(*) as total from orders group by status order by status'
        );

        $ticketKindBreakdown = DB::select(
            'select ticket_kind, count(*) as total from support_tickets group by ticket_kind order by ticket_kind'
        );

        return response()->json([
            'overview' => $overview,
            'order_status_breakdown' => $orderStatusBreakdown,
            'ticket_kind_breakdown' => $ticketKindBreakdown,
        ]);
    }

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
