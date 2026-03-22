<?php

namespace App\Http\Controllers\Api;

use App\Enums\UserRole;
use App\Models\CustomerProfile;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class CustomerAdminController extends ApiController
{
    public function index(Request $request)
    {
        $this->requireAdmin($request);

        $q = trim((string) $request->query('q', ''));
        $normalizedIdQuery = ltrim($q, '#');
        $idSearch = ctype_digit($normalizedIdQuery) ? (int) $normalizedIdQuery : null;

        $customers = User::query()
            ->where('role', UserRole::Customer)
            ->with(['customerProfile', 'addresses'])
            ->withCount('orders')
            ->when($q !== '', function ($qb) use ($q, $idSearch) {
                $like = '%' . $q . '%';
                $qb->where(function ($inner) use ($like, $idSearch) {
                    $inner
                        ->where('name', 'ilike', $like)
                        ->orWhere('email', 'ilike', $like)
                        ->when($idSearch !== null, fn ($idQuery) => $idQuery->orWhere('id', $idSearch));
                });
            })
            ->orderBy('name')
            ->paginate((int) $request->query('per_page', 20));

        return response()->json($customers);
    }

    public function show(Request $request, User $user)
    {
        $this->requireAdmin($request);
        abort_unless($user->role === UserRole::Customer, 404);

        $user->load([
            'customerProfile',
            'addresses',
            'orders' => fn ($qb) => $qb->withCount('items')->orderByDesc('created_at')->limit(10),
        ]);

        $totals = DB::selectOne(
            'select count(*) as order_count, coalesce(sum(total_amount), 0) as lifetime_value from orders where user_id = ?',
            [$user->id]
        );

        return response()->json([
            'customer' => $user,
            'stats' => [
                'order_count' => (int) ($totals->order_count ?? 0),
                'lifetime_value' => (float) ($totals->lifetime_value ?? 0),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $this->requireAdmin($request);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', Password::min(8)->mixedCase()->numbers()],
            'phone' => ['nullable', 'string', 'max:30'],
            'date_of_birth' => ['nullable', 'date'],
        ]);

        $user = DB::transaction(function () use ($data) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'role' => UserRole::Customer,
                'password_hash' => Hash::make($data['password']),
            ]);

            CustomerProfile::query()->updateOrCreate(
                ['user_id' => $user->id],
                [
                    'phone' => $data['phone'] ?? null,
                    'date_of_birth' => $data['date_of_birth'] ?? null,
                ]
            );

            return $user;
        });

        return response()->json($user->fresh(['customerProfile', 'addresses']), 201);
    }

    public function update(Request $request, User $user)
    {
        $this->requireAdmin($request);
        abort_unless($user->role === UserRole::Customer, 404);

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['nullable', Password::min(8)->mixedCase()->numbers()],
            'phone' => ['nullable', 'string', 'max:30'],
            'date_of_birth' => ['nullable', 'date'],
        ]);

        DB::transaction(function () use ($data, $user) {
            if (array_key_exists('name', $data)) {
                $user->name = $data['name'];
            }

            if (array_key_exists('email', $data)) {
                $user->email = $data['email'];
            }

            if (! empty($data['password'])) {
                $user->password_hash = Hash::make($data['password']);
            }

            $user->save();

            CustomerProfile::query()->updateOrCreate(
                ['user_id' => $user->id],
                [
                    'phone' => $data['phone'] ?? $user->customerProfile?->phone,
                    'date_of_birth' => $data['date_of_birth'] ?? $user->customerProfile?->date_of_birth,
                ]
            );
        });

        return response()->json($user->fresh(['customerProfile', 'addresses']));
    }

    public function destroy(Request $request, User $user)
    {
        $this->requireAdmin($request);
        abort_unless($user->role === UserRole::Customer, 404);

        $user->delete();

        return response()->json(['ok' => true]);
    }
}
