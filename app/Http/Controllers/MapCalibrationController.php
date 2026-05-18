<?php

namespace App\Http\Controllers;

use App\Models\CemeteryMapCalibration;
use Illuminate\Http\Request;

class MapCalibrationController extends Controller
{
    public function edit()
    {
        return view('admin.map-calibration', [
            'calibration' => CemeteryMapCalibration::latest()->first(),
        ]);
    }

    public function show()
    {
        $calibration = CemeteryMapCalibration::latest()->first();

        if (!$calibration) {
            return response()->json([
                'configured' => false,
                'top_left' => null,
                'bottom_right' => null,
                'accuracy_warning_meters' => 20,
            ]);
        }

        return response()->json($this->formatCalibration($calibration))
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate');
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'top_left_lat' => ['required', 'numeric', 'between:-90,90'],
            'top_left_lng' => ['required', 'numeric', 'between:-180,180'],
            'top_left_svg_x' => ['required', 'numeric'],
            'top_left_svg_y' => ['required', 'numeric'],
            'bottom_right_lat' => ['required', 'numeric', 'between:-90,90'],
            'bottom_right_lng' => ['required', 'numeric', 'between:-180,180'],
            'bottom_right_svg_x' => ['required', 'numeric'],
            'bottom_right_svg_y' => ['required', 'numeric'],
        ]);

        $calibration = CemeteryMapCalibration::latest()->first();

        if ($calibration) {
            $calibration->update($validated);
        } else {
            $calibration = CemeteryMapCalibration::create($validated);
        }

        return response()->json([
            'message' => 'Map calibration saved successfully.',
            'calibration' => $this->formatCalibration($calibration),
        ]);
    }

    public function destroy()
    {
        CemeteryMapCalibration::query()->delete();

        return response()->json([
            'message' => 'Map calibration has been reset.',
        ]);
    }

    private function formatCalibration(CemeteryMapCalibration $calibration): array
    {
        return [
            'configured' => true,
            'top_left' => [
                'lat' => $calibration->top_left_lat,
                'lng' => $calibration->top_left_lng,
                'x' => $calibration->top_left_svg_x,
                'y' => $calibration->top_left_svg_y,
            ],
            'bottom_right' => [
                'lat' => $calibration->bottom_right_lat,
                'lng' => $calibration->bottom_right_lng,
                'x' => $calibration->bottom_right_svg_x,
                'y' => $calibration->bottom_right_svg_y,
            ],
            'accuracy_warning_meters' => 20,
        ];
    }
}
