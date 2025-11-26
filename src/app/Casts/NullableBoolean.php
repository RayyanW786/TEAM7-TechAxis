<?php

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;

class NullableBoolean implements CastsAttributes
{
    public function get($model, string $key, $value, array $attributes): ?bool
    {
        if ($value === null) {
            return null;
        }

        return (bool) $value;
    }

    public function set($model, string $key, $value, array $attributes): ?bool
    {
        if ($value === null) {
            return null;
        }

        return (bool) $value;
    }
}
