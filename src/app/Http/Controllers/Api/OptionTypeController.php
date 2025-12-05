<?php

namespace App\Http\Controllers\Api;

use App\Models\OptionType;
use App\Models\OptionValue;
use Illuminate\Http\Request;

class OptionTypeController extends ApiController
{
    public function index()
    {
        return response()->json(
            OptionType::query()->with('values')->orderBy('name')->get()
        );
    }

    public function store(Request $request)
    {
        $this->requireAdmin($request);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        return response()->json(OptionType::create($data), 201);
    }

    public function addValue(Request $request, OptionType $optionType)
    {
        $this->requireAdmin($request);

        $data = $request->validate([
            'value' => ['required', 'string', 'max:255'],
        ]);

        $value = OptionValue::create([
            'option_type_id' => $optionType->id,
            'value' => $data['value'],
        ]);

        return response()->json($value, 201);
    }

    public function destroy(Request $request, OptionType $optionType)
    {
        $this->requireAdmin($request);

        $optionType->delete();

        return response()->json(['ok' => true]);
    }

    public function destroyValue(Request $request, OptionType $optionType, OptionValue $value)
    {
        $this->requireAdmin($request);

        abort_unless((int) $value->option_type_id === (int) $optionType->id, 404);

        $value->delete();

        return response()->json(['ok' => true]);
    }
}
