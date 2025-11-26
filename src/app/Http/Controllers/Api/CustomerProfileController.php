<?php

namespace App\Http\Controllers\Api;

use App\Models\CustomerProfile;
use Illuminate\Http\Request;

class CustomerProfileController extends ApiController
{
    public function show(Request $request)
    {
        $user = $request->user();
        abort_unless($user, 401);

        return response()->json(
            CustomerProfile::query()->firstOrCreate(['user_id' => $user->id])
        );
    }

    public function update(Request $request)
    {
        $user = $request->user();
        abort_unless($user, 401);

        $data = $request->validate([
            'date_of_birth' => ['nullable', 'date'],
        ]);

        $profile = CustomerProfile::query()->firstOrCreate(['user_id' => $user->id]);
        $profile->fill($data)->save();

        return response()->json($profile->fresh());
    }
}
