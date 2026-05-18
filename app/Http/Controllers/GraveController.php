<?php

namespace App\Http\Controllers;

use App\Models\Grave;
use App\Models\CemeteryMapCalibration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class GraveController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            $graves = Grave::select('id', 'grave_number', 'status', 'deceased_name', 'date_of_birth', 'date_of_death', 'image_path')
                ->get()
                ->map(function ($grave) {
                    // Convert relative image path to full URL
                    if ($grave->image_path) {
                        $grave->image_url = asset($grave->image_path);
                    } else {
                        $grave->image_url = null;
                    }
                    unset($grave->image_path);
                    return $grave;
                });

            return response()->json($graves)
                ->header('Cache-Control', 'public, max-age=300');
        } catch (\Exception $e) {
            Log::error('Error loading graves: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to load graves'], 500);
        }
    }

    /**
     * Return grave status data for the interactive map.
     */
    public function statuses()
    {
        try {
            $graves = Grave::select('id', 'grave_number', 'status', 'deceased_name', 'date_of_birth', 'date_of_death', 'image_path')
                ->orderBy('id')
                ->get()
                ->map(function ($grave) {
                    return [
                        'id' => $grave->id,
                        'grave_number' => $grave->grave_number,
                        'status' => $grave->status,
                        'deceased_name' => $grave->deceased_name,
                        'date_of_birth' => optional($grave->date_of_birth)->toDateString(),
                        'date_of_death' => optional($grave->date_of_death)->toDateString(),
                        'image_url' => $grave->image_path ? asset($grave->image_path) : null,
                    ];
                });

            return response()->json($graves)
                ->header('Cache-Control', 'no-cache, no-store, must-revalidate');
        } catch (\Exception $e) {
            Log::error('Error loading grave statuses: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to load grave statuses'], 500);
        }
    }

    /**
     * Return GPS-to-SVG calibration points for the Leaflet cemetery map.
     */
    public function calibration()
    {
        $savedCalibration = Schema::hasTable('cemetery_map_calibrations')
            ? CemeteryMapCalibration::latest()->first()
            : null;

        if ($savedCalibration) {
            return response()->json([
                'configured' => true,
                'top_left' => [
                    'lat' => $savedCalibration->top_left_lat,
                    'lng' => $savedCalibration->top_left_lng,
                    'x' => $savedCalibration->top_left_svg_x,
                    'y' => $savedCalibration->top_left_svg_y,
                ],
                'bottom_right' => [
                    'lat' => $savedCalibration->bottom_right_lat,
                    'lng' => $savedCalibration->bottom_right_lng,
                    'x' => $savedCalibration->bottom_right_svg_x,
                    'y' => $savedCalibration->bottom_right_svg_y,
                ],
                'accuracy_warning_meters' => 20,
            ])->header('Cache-Control', 'no-cache, no-store, must-revalidate');
        }

        $topLeft = config('cemetery-map.calibration.top_left');
        $bottomRight = config('cemetery-map.calibration.bottom_right');

        $hasCalibration = $this->hasCalibrationPoint($topLeft)
            && $this->hasCalibrationPoint($bottomRight);

        return response()->json([
            'configured' => $hasCalibration,
            'top_left' => $this->formatCalibrationPoint($topLeft),
            'bottom_right' => $this->formatCalibrationPoint($bottomRight),
            'accuracy_warning_meters' => 20,
        ])->header('Cache-Control', 'no-cache, no-store, must-revalidate');
    }

    private function hasCalibrationPoint(array $point): bool
    {
        return $point['lat'] !== null
            && $point['lng'] !== null
            && $point['x'] !== null
            && $point['y'] !== null;
    }

    private function formatCalibrationPoint(array $point): array
    {
        return [
            'lat' => $point['lat'] !== null ? (float) $point['lat'] : null,
            'lng' => $point['lng'] !== null ? (float) $point['lng'] : null,
            'x' => $point['x'] !== null ? (float) $point['x'] : null,
            'y' => $point['y'] !== null ? (float) $point['y'] : null,
        ];
    }

    /**
     * Display the cemetery map view.
     */
    public function map()
    {
        return view('admin.map');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Clear cache when creating
        Cache::forget('graves_list');

        $validated = $request->validate([
            'grave_number' => 'required|string|unique:graves',
            'x_position' => 'required|numeric',
            'y_position' => 'required|numeric',
            'width' => 'required|numeric',
            'height' => 'required|numeric',
            'rotation' => 'nullable|numeric',
            'status' => 'required|in:vacant,occupied,reserved',
            'deceased_name' => 'nullable|string',
            'date_of_birth' => 'nullable|date',
            'date_of_death' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);

        $grave = Grave::create($validated);
        return response()->json($grave, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Grave $grave)
    {
        return response()->json($grave);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Grave $grave)
    {
        // Clear cache when updating
        Cache::forget('graves_list');

        Log::info('Grave update request', [
            'grave_id' => $grave->id,
            'request_data' => $request->except('image')
        ]);

        $validated = $request->validate([
            'grave_number' => 'sometimes|string|unique:graves,grave_number,' . $grave->id,
            'x_position' => 'sometimes|numeric',
            'y_position' => 'sometimes|numeric',
            'width' => 'sometimes|numeric',
            'height' => 'sometimes|numeric',
            'rotation' => 'nullable|numeric',
            'status' => 'sometimes|in:vacant,occupied,reserved',
            'deceased_name' => 'nullable|string',
            'date_of_birth' => 'nullable|date',
            'date_of_death' => 'nullable|date',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'clear_image' => 'nullable|string',
        ]);

        // Handle image upload
        if ($request->hasFile('image')) {
            // Delete old image if exists
            if ($grave->image_path) {
                // Try storage path first
                Storage::disk('public')->delete(str_replace('/storage/', '', $grave->image_path));
                // Try public path as fallback
                if (file_exists(public_path($grave->image_path))) {
                    @unlink(public_path($grave->image_path));
                }
            }

            $image = $request->file('image');
            $imageName = time() . '_' . str_replace(['/', '\\'], '_', $grave->grave_number) . '.' . $image->getClientOriginalExtension();

            // Store in storage/app/public/graves
            $path = $image->storeAs('graves', $imageName, 'public');
            $validated['image_path'] = '/storage/' . $path;
        }

        // Clear image if requested
        if ($request->input('clear_image') === '1' && $grave->image_path) {
            // Try storage path first
            Storage::disk('public')->delete(str_replace('/storage/', '', $grave->image_path));
            // Try public path as fallback
            if (file_exists(public_path($grave->image_path))) {
                @unlink(public_path($grave->image_path));
            }
            $validated['image_path'] = null;
        }

        // Remove validation-only fields before update
        unset($validated['image']);
        unset($validated['clear_image']);

        // Convert empty strings to null for nullable fields
        if (isset($validated['deceased_name']) && $validated['deceased_name'] === '') {
            $validated['deceased_name'] = null;
        }
        if (isset($validated['date_of_birth']) && $validated['date_of_birth'] === '') {
            $validated['date_of_birth'] = null;
        }
        if (isset($validated['date_of_death']) && $validated['date_of_death'] === '') {
            $validated['date_of_death'] = null;
        }

        Log::info('Updating grave with validated data', [
            'grave_id' => $grave->id,
            'validated' => $validated
        ]);

        $grave->update($validated);

        Log::info('Grave updated successfully', ['grave_id' => $grave->id]);

        return response()->json($grave);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Grave $grave)
    {
        // Clear cache when deleting
        Cache::forget('graves_list');

        $grave->delete();
        return response()->json(null, 204);
    }

    /**
     * Update grave status
     */
    public function updateStatus(Request $request, Grave $grave)
    {
        // Clear cache when updating status
        Cache::forget('graves_list');

        $validated = $request->validate([
            'status' => 'required|in:vacant,occupied,reserved',
        ]);

        $grave->update($validated);
        return response()->json($grave);
    }
}
