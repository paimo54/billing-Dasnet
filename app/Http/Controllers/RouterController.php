<?php

namespace App\Http\Controllers;

use App\Models\Router;
use App\Models\CoverageArea;
use App\Services\MultiRouterService;
use Illuminate\Http\Request;

class RouterController extends Controller
{
    protected $multiRouterService;

    public function __construct(MultiRouterService $multiRouterService)
    {
        $this->multiRouterService = $multiRouterService;
    }

    /**
     * Display a listing of routers
     */
    public function index()
    {
        $routers = Router::with('coverageAreas')
            ->orderByPriority()
            ->paginate(20);

        $statistics = $this->multiRouterService->getRouterStatistics();

        return view('routers.index', compact('routers', 'statistics'));
    }

    /**
     * Show the form for creating a new router
     */
    public function create()
    {
        $coverageAreas = CoverageArea::active()->ordered()->get();
        return view('routers.create', compact('coverageAreas'));
    }

    /**
     * Store a newly created router
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'identity' => 'required|string|max:100|unique:routers',
            'host' => 'required|string|max:50',
            'port' => 'required|integer|min:1|max:65535',
            'username' => 'required|string|max:50',
            'password' => 'required|string',
            'region' => 'nullable|string|max:100',
            'location' => 'nullable|string|max:200',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'max_capacity' => 'required|integer|min:1',
            'radius_secret' => 'required|string|max:100',
            'use_radius' => 'required|boolean',
            'priority' => 'required|integer|min:1',
            'auto_assign' => 'required|boolean',
            'description' => 'nullable|string',
            'coverage_areas' => 'nullable|array',
            'coverage_areas.*' => 'exists:coverage_areas,id',
        ]);

        $router = Router::create($validated);

        // Attach coverage areas
        if ($request->has('coverage_areas')) {
            $router->coverageAreas()->attach($request->coverage_areas);
        }

        return redirect()->route('routers.index')
            ->with('success', 'Router created successfully');
    }

    /**
     * Display the specified router
     */
    public function show(Router $router)
    {
        $router->load(['coverageAreas', 'customers']);

        $health = $this->multiRouterService->checkRouterHealth($router);

        return view('routers.show', compact('router', 'health'));
    }

    /**
     * Show the form for editing the specified router
     */
    public function edit(Router $router)
    {
        $coverageAreas = CoverageArea::active()->ordered()->get();
        $router->load('coverageAreas');

        return view('routers.edit', compact('router', 'coverageAreas'));
    }

    /**
     * Update the specified router
     */
    public function update(Request $request, Router $router)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'identity' => 'required|string|max:100|unique:routers,identity,' . $router->id,
            'host' => 'required|string|max:50',
            'port' => 'required|integer|min:1|max:65535',
            'username' => 'required|string|max:50',
            'password' => 'required|string',
            'region' => 'nullable|string|max:100',
            'location' => 'nullable|string|max:200',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'max_capacity' => 'required|integer|min:1',
            'status' => 'required|in:active,inactive,maintenance,error',
            'radius_secret' => 'required|string|max:100',
            'use_radius' => 'required|boolean',
            'priority' => 'required|integer|min:1',
            'auto_assign' => 'required|boolean',
            'description' => 'nullable|string',
            'coverage_areas' => 'nullable|array',
            'coverage_areas.*' => 'exists:coverage_areas,id',
        ]);

        $router->update($validated);

        // Sync coverage areas
        if ($request->has('coverage_areas')) {
            $router->coverageAreas()->sync($request->coverage_areas);
        }

        return redirect()->route('routers.index')
            ->with('success', 'Router updated successfully');
    }

    /**
     * Remove the specified router
     */
    public function destroy(Router $router)
    {
        if ($router->customers()->count() > 0) {
            return redirect()->route('routers.index')
                ->with('error', 'Cannot delete router with active customers');
        }

        $router->delete();

        return redirect()->route('routers.index')
            ->with('success', 'Router deleted successfully');
    }

    /**
     * Check health of all routers
     */
    public function checkHealth()
    {
        $results = $this->multiRouterService->checkRoutersHealth();

        return response()->json($results);
    }

    /**
     * Check health of specific router
     */
    public function checkRouterHealth(Router $router)
    {
        $health = $this->multiRouterService->checkRouterHealth($router);

        return response()->json($health);
    }

    /**
     * Get router statistics
     */
    public function statistics()
    {
        $statistics = $this->multiRouterService->getRouterStatistics();

        return response()->json($statistics);
    }

    /**
     * Set router to maintenance mode
     */
    public function setMaintenance(Router $router)
    {
        $router->markAsMaintenance();

        return redirect()->back()
            ->with('success', 'Router set to maintenance mode');
    }

    /**
     * Activate router
     */
    public function activate(Router $router)
    {
        $router->markAsActive();

        return redirect()->back()
            ->with('success', 'Router activated successfully');
    }
}
