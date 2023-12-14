<?php

namespace App\Http\Controllers;

use App\Models\PresetShift;
use Illuminate\Http\Request;

class PresetShiftController extends Controller
{
    public function index(): void
    {
    }

    public function create(): void
    {
    }

    public function store(Request $request): void
    {
    }

    public function show(PresetShift $presetShift): void
    {
    }

    public function edit(PresetShift $presetShift): void
    {
    }

    public function update(Request $request, PresetShift $presetShift): void
    {
        $presetShift->update(
            $request->only(
                [
                    'start',
                    'end',
                    'break_minutes',
                    'craft_id',
                    'number_employees',
                    'number_masters',
                    'description'
                ]
            )
        );
    }

    public function destroy(PresetShift $presetShift): void
    {
        $presetShift->delete();
    }
}
