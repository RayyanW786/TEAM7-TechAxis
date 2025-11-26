<?php

namespace App\Http\Controllers\Api;

use App\Models\Address;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AddressController extends ApiController
{
    public function index(Request $request)
    {
        $user = $request->user();
        abort_unless($user, 401);

        return response()->json(
            Address::query()->where('user_id', $user->id)->orderByDesc('created_at')->get()
        );
    }

    public function store(Request $request)
    {
        $user = $request->user();
        abort_unless($user, 401);

        $data = $request->validate([
            'recipient_name' => ['required', 'string', 'max:255'],
            'line1' => ['required', 'string', 'max:255'],
            'line2' => ['nullable', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:255'],
            'region' => ['nullable', 'string', 'max:255'],
            'postal_code' => ['required', 'string', 'max:50'],
            'country' => ['required', 'string', 'max:2'],
            'is_default_shipping' => ['nullable', 'boolean'],
        ]);

        $address = DB::transaction(function () use ($user, $data) {
            if (!empty($data['is_default_shipping'])) {
                Address::query()->where('user_id', $user->id)->update(['is_default_shipping' => false]);
            }

            return Address::create(['user_id' => $user->id] + $data);
        });

        return response()->json($address, 201);
    }

    public function update(Request $request, Address $address)
    {
        $user = $request->user();
        abort_unless($user, 401);
        abort_unless((int) $address->user_id === (int) $user->id, 404);

        $data = $request->validate([
            'recipient_name' => ['sometimes', 'string', 'max:255'],
            'line1' => ['sometimes', 'string', 'max:255'],
            'line2' => ['nullable', 'string', 'max:255'],
            'city' => ['sometimes', 'string', 'max:255'],
            'region' => ['nullable', 'string', 'max:255'],
            'postal_code' => ['sometimes', 'string', 'max:50'],
            'country' => ['sometimes', 'string', 'max:2'],
            'is_default_shipping' => ['nullable', 'boolean'],
        ]);

        DB::transaction(function () use ($user, $address, $data) {
            if (!empty($data['is_default_shipping'])) {
                Address::query()->where('user_id', $user->id)->update(['is_default_shipping' => false]);
            }

            $address->fill($data)->save();
        });

        return response()->json($address->fresh());
    }

    public function destroy(Request $request, Address $address)
    {
        $user = $request->user();
        abort_unless($user, 401);
        abort_unless((int) $address->user_id === (int) $user->id, 404);

        $address->delete();

        return response()->json(['ok' => true]);
    }
}
