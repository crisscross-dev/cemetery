<?php

namespace App\Http\Controllers;

use App\Models\CemeteryMapCalibration;
use Illuminate\Http\Request;

class MapCalibrationController extends Controller
{
    private const ANCHOR_KEYS = ['a', 'b', 'c', 'd'];

    public function edit()
    {
        return view('admin.map-calibration', [
            'calibration' => CemeteryMapCalibration::latest()->first(),
            'anchors' => $this->anchorsForView(CemeteryMapCalibration::latest()->first()),
        ]);
    }

    public function show()
    {
        $calibration = CemeteryMapCalibration::latest()->first();

        if (!$calibration) {
            return response()->json($this->emptyCalibration());
        }

        return response()->json($this->formatCalibration($calibration))
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate');
    }

    public function update(Request $request)
    {
        $rules = [];

        foreach (self::ANCHOR_KEYS as $key) {
            $rules["anchors.$key.lat"] = ['required', 'numeric', 'between:-90,90'];
            $rules["anchors.$key.lng"] = ['required', 'numeric', 'between:-180,180'];
            $rules["anchors.$key.x"] = ['required', 'numeric'];
            $rules["anchors.$key.y"] = ['required', 'numeric'];
        }

        $validated = $request->validate($rules);
        $anchors = $this->normalizeAnchors($validated['anchors']);

        $payload = [
            'anchors' => $anchors,
            'top_left_lat' => $anchors['a']['lat'],
            'top_left_lng' => $anchors['a']['lng'],
            'top_left_svg_x' => $anchors['a']['x'],
            'top_left_svg_y' => $anchors['a']['y'],
            'bottom_right_lat' => $anchors['d']['lat'],
            'bottom_right_lng' => $anchors['d']['lng'],
            'bottom_right_svg_x' => $anchors['d']['x'],
            'bottom_right_svg_y' => $anchors['d']['y'],
        ];

        $calibration = CemeteryMapCalibration::latest()->first();

        if ($calibration) {
            $calibration->update($payload);
        } else {
            $calibration = CemeteryMapCalibration::create($payload);
        }

        return response()->json([
            'message' => '4-anchor map calibration saved successfully.',
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

    private function emptyCalibration(): array
    {
        return [
            'configured' => false,
            'anchor_count' => 0,
            'anchors' => $this->emptyAnchors(),
            'top_left' => null,
            'bottom_right' => null,
            'accuracy_warning_meters' => 20,
            'transform' => 'homography_4_anchor',
        ];
    }

    private function formatCalibration(CemeteryMapCalibration $calibration): array
    {
        $anchors = $this->anchorsForView($calibration);
        $anchorCount = $this->configuredAnchorCount($anchors);

        return [
            'configured' => $anchorCount === 4,
            'anchor_count' => $anchorCount,
            'anchors' => $anchors,
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
            'transform' => 'homography_4_anchor',
        ];
    }

    private function anchorsForView(?CemeteryMapCalibration $calibration): array
    {
        if (!$calibration) {
            return $this->emptyAnchors();
        }

        if (is_array($calibration->anchors)) {
            return array_replace_recursive($this->emptyAnchors(), $calibration->anchors);
        }

        if (
            $calibration->top_left_lat !== null
            && $calibration->bottom_right_lat !== null
        ) {
            $anchors = $this->emptyAnchors();
            $anchors['a'] = [
                'lat' => $calibration->top_left_lat,
                'lng' => $calibration->top_left_lng,
                'x' => $calibration->top_left_svg_x,
                'y' => $calibration->top_left_svg_y,
            ];
            $anchors['d'] = [
                'lat' => $calibration->bottom_right_lat,
                'lng' => $calibration->bottom_right_lng,
                'x' => $calibration->bottom_right_svg_x,
                'y' => $calibration->bottom_right_svg_y,
            ];

            return $anchors;
        }

        return $this->emptyAnchors();
    }

    private function normalizeAnchors(array $anchors): array
    {
        $normalized = [];

        foreach (self::ANCHOR_KEYS as $key) {
            $normalized[$key] = [
                'lat' => (float) $anchors[$key]['lat'],
                'lng' => (float) $anchors[$key]['lng'],
                'x' => (float) $anchors[$key]['x'],
                'y' => (float) $anchors[$key]['y'],
            ];
        }

        return $normalized;
    }

    private function emptyAnchors(): array
    {
        return [
            'a' => ['lat' => null, 'lng' => null, 'x' => null, 'y' => null],
            'b' => ['lat' => null, 'lng' => null, 'x' => null, 'y' => null],
            'c' => ['lat' => null, 'lng' => null, 'x' => null, 'y' => null],
            'd' => ['lat' => null, 'lng' => null, 'x' => null, 'y' => null],
        ];
    }

    private function configuredAnchorCount(array $anchors): int
    {
        return collect($anchors)->filter(function ($anchor) {
            return $anchor['lat'] !== null
                && $anchor['lng'] !== null
                && $anchor['x'] !== null
                && $anchor['y'] !== null;
        })->count();
    }
}
