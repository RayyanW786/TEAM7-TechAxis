<?php

namespace App\Models\Traits;

trait FormatsPgArrays
{
    protected static function pgBigintArray(array $ids): string
    {
        $clean = [];

        foreach ($ids as $id) {
            if ($id === null) {
                continue;
            }

            $int = (int) $id;

            if ($int > 0) {
                $clean[$int] = true;
            }
        }

        return '{' . implode(',', array_keys($clean)) . '}';
    }
}
