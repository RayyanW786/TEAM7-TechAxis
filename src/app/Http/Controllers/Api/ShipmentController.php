<?php

namespace App\Http\Controllers\Api;

use App\Models\Order;
use App\Models\Shipment;
use Illuminate\Http\Request;

class ShipmentController extends ApiController
{
    public function store(Request $request, Order $order)
    {
        $this->requireAdmin($request);

        $data = $request->validate([
            'carrier' => ['nullable', 'string', 'max:100'],
            'tracking_number' => ['nullable', 'string', 'max:255'],
            'shipped_at' => ['nullable', 'date'],
            'delivered_at' => ['nullable', 'date'],
        ]);

        $shipment = Shipment::create([
            'order_id' => $order->id,
            'carrier' => $data['carrier'] ?? null,
            'tracking_number' => $data['tracking_number'] ?? null,
            'shipped_at' => $data['shipped_at'] ?? null,
            'delivered_at' => $data['delivered_at'] ?? null,
        ]);

        return response()->json($shipment, 201);
    }

    public function update(Request $request, Order $order, Shipment $shipment)
    {
        $this->requireAdmin($request);

        abort_unless((int) $shipment->order_id === (int) $order->id, 404);

        $data = $request->validate([
            'carrier' => ['nullable', 'string', 'max:100'],
            'tracking_number' => ['nullable', 'string', 'max:255'],
            'shipped_at' => ['nullable', 'date'],
            'delivered_at' => ['nullable', 'date'],
        ]);

        $shipment->fill($data)->save();

        return response()->json($shipment->fresh());
    }

    public function destroy(Request $request, Order $order, Shipment $shipment)
    {
        $this->requireAdmin($request);

        abort_unless((int) $shipment->order_id === (int) $order->id, 404);

        $shipment->delete();

        return response()->json(['ok' => true]);
    }
}
