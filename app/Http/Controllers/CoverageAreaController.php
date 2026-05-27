<?php

namespace App\Http\Controllers;

use App\Models\CoverageArea;
use App\Models\Router;
use Illuminate\Http\Request;

class CoverageAreaController extends Controller
{
    /**
     * Display a listing of coverage areas
     */
    public function index()
    {
        $coverageAreas = CoverageArea::with('routers')
            ->ordered()
            ->paginate(20);

        return view('coverage-areas.index', compact('coverageAreas'));
    }

    /**
     * Show the form for creating a new coverage area
     */
    public function create()
    {
        $routers = Router::active()->orderByPriority()->get();
        return view('coverage-areas.create', compact('routers'));
    }

    /**
     * Store a newly created coverage area
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:200',
            'region' => 'required|string|max:100',
            'description' => 'nullable|string',
            'polygon_coordinates' => 'nullable|json',
            'center_latitude' => 'nullable|numeric|between:-90,90',
            'center_longitude' => 'nullable|numeric|between:-180,180',
            'radius_meters' => 'nullable|integer|min:0',
            'is_active' => 'required|boolean',
            'service_start_date' => 'nullable|date',
            'estimated_capacity' => 'nullable|integer|min:0',
            'signal_quality' => 'required|in:excellent,good,fair,poor',
            'coverage_notes' => 'nullable|string',
            'color_hex' => 'required|string|size:7',
            'display_order' => 'required|integer|min:0',
            'show_on_map' => 'required|boolean',
            'routers' => 'nullable|array',
            'routers.*' => 'exists:routers,id',
        ]);

        // Decode polygon coordinates if provided
        if ($request->has('polygon_coordinates')) {
            $validated['polygon_coordinates'] = json_decode($validated['polygon_coordinates'], true);
        }

        $coverageArea = CoverageArea::create($validated);

        // Attach routers
        if ($request->has('routers')) {
            $coverageArea->routers()->attach($request->routers);
        }

        return redirect()->route('coverage-areas.index')
            ->with('success', 'Coverage area created successfully');
    }

    /**
     * Display the specified coverage area
     */
    public function show(CoverageArea $coverageArea)
    {
        $coverageArea->load(['routers', 'customers']);

        return view('coverage-areas.show', compact('coverageArea'));
    }

    /**
     * Show the form for editing the specified coverage area
     */
    public function edit(CoverageArea $coverageArea)
    {
        $routers = Router::active()->orderByPriority()->get();
        $coverageArea->load('routers');

        return view('coverage-areas.edit', compact('coverageArea', 'routers'));
    }

    /**
     * Update the specified coverage area
     */
    public function update(Request $request, CoverageArea $coverageArea)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:200',
            'region' => 'required|string|max:100',
            'description' => 'nullable|string',
            'polygon_coordinates' => 'nullable|json',
            'center_latitude' => 'nullable|numeric|between:-90,90',
            'center_longitude' => 'nullable|numeric|between:-180,180',
            'radius_meters' => 'nullable|integer|min:0',
            'is_active' => 'required|boolean',
            'service_start_date' => 'nullable|date',
            'estimated_capacity' => 'nullable|integer|min:0',
            'signal_quality' => 'required|in:excellent,good,fair,poor',
            'coverage_notes' => 'nullable|string',
            'color_hex' => 'required|string|size:7',
            'display_order' => 'required|integer|min:0',
            'show_on_map' => 'required|boolean',
            'routers' => 'nullable|array',
            'routers.*' => 'exists:routers,id',
        ]);

        // Decode polygon coordinates if provided
        if ($request->has('polygon_coordinates')) {
            $validated['polygon_coordinates'] = json_decode($validated['polygon_coordinates'], true);
        }

        $coverageArea->update($validated);

        // Sync routers
        if ($request->has('routers')) {
            $coverageArea->routers()->sync($request->routers);
        }

        return redirect()->route('coverage-areas.index')
            ->with('success', 'Coverage area updated successfully');
    }

    /**
     * Remove the specified coverage area
     */
    public function destroy(CoverageArea $coverageArea)
    {
        if ($coverageArea->customers()->count() > 0) {
            return redirect()->route('coverage-areas.index')
                ->with('error', 'Cannot delete coverage area with active customers');
        }

        $coverageArea->delete();

        return redirect()->route('coverage-areas.index')
            ->with('success', 'Coverage area deleted successfully');
    }

    /**
     * Get GeoJSON data for map display
     */
    public function geojson()
    {
        $coverageAreas = CoverageArea::visibleOnMap()->get();

        $features = $coverageAreas->map(function ($area) {
            return $area->toGeoJson();
        });

        return response()->json([
            'type' => 'FeatureCollection',
            'features' => $features,
        ]);
    }

    /**
     * Get coverage areas by region
     */
    public function byRegion(Request $request)
    {
        $region = $request->input('region');

        $coverageAreas = CoverageArea::active()
            ->byRegion($region)
            ->ordered()
            ->get();

        return response()->json($coverageAreas);
    }
}
