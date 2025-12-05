<?php

namespace App\Http\Controllers\Api;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

abstract class ApiController extends Controller
{
    protected function requireAdmin(Request $request): void
    {
        $user = $request->user();

        abort_unless($user && $user->role === UserRole::Admin, 403, 'Admin access required.');
    }

    protected function pgTextArray(?array $values): ?string
    {
        if (!$values || count($values) === 0) {
            return null;
        }

        $clean = [];

        foreach ($values as $value) {
            $slug = strtolower(trim((string) $value));

            if ($slug !== '') {
                $clean[$slug] = true;
            }
        }

        return '{' . implode(',', array_keys($clean)) . '}';
    }
}
