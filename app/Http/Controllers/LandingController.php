<?php

namespace App\Http\Controllers;

use App\Models\Package;
use App\Models\CoverageArea;
use Illuminate\Http\Request;

class LandingController extends Controller
{
    /**
     * Display landing page
     */
    public function index()
    {
        // Get active packages
        $packages = Package::where('is_active', true)
            ->orderBy('price', 'asc')
            ->get();

        // Get coverage statistics
        $coverageStats = [
            'total_areas' => CoverageArea::active()->count(),
            'total_regions' => CoverageArea::active()->distinct('region')->count('region'),
            'total_subscribers' => CoverageArea::sum('current_subscribers'),
        ];

        return view('landing.index', compact('packages', 'coverageStats'));
    }

    /**
     * Display coverage map page
     */
    public function coverage()
    {
        $regions = CoverageArea::active()
            ->select('region')
            ->distinct()
            ->orderBy('region')
            ->pluck('region');

        return view('landing.coverage', compact('regions'));
    }

    /**
     * Display packages page
     */
    public function packages()
    {
        $packages = Package::where('is_active', true)
            ->orderBy('price', 'asc')
            ->get();

        return view('landing.packages', compact('packages'));
    }

    /**
     * Display about/service quality page
     */
    public function about()
    {
        return view('landing.about');
    }

    /**
     * Display registration form
     */
    public function register()
    {
        $packages = Package::where('is_active', true)
            ->orderBy('price', 'asc')
            ->get();

        $coverageAreas = CoverageArea::active()
            ->ordered()
            ->get();

        return view('landing.register', compact('packages', 'coverageAreas'));
    }

    /**
     * Handle registration submission
     */
    public function submitRegistration(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'required|string|max:20',
            'address' => 'required|string',
            'coverage_area_id' => 'required|exists:coverage_areas,id',
            'package_id' => 'required|exists:packages,id',
            'installation_notes' => 'nullable|string',
        ]);

        // Store registration request (you can create a separate table for this)
        // For now, we'll just send notification to admin

        // TODO: Create RegistrationRequest model and store data
        // TODO: Send email notification to admin
        // TODO: Send confirmation email to customer

        return redirect()->route('landing.index')
            ->with('success', 'Registration submitted successfully! Our team will contact you soon.');
    }

    /**
     * Check coverage availability
     */
    public function checkCoverage(Request $request)
    {
        $latitude = $request->input('latitude');
        $longitude = $request->input('longitude');

        // Simple distance-based check
        // In production, use proper geospatial queries
        $nearestArea = CoverageArea::active()
            ->selectRaw('*, (
                6371 * acos(
                    cos(radians(?)) * cos(radians(center_latitude)) *
                    cos(radians(center_longitude) - radians(?)) +
                    sin(radians(?)) * sin(radians(center_latitude))
                )
            ) AS distance', [$latitude, $longitude, $latitude])
            ->having('distance', '<', 10) // Within 10km
            ->orderBy('distance')
            ->first();

        if ($nearestArea) {
            return response()->json([
                'available' => true,
                'coverage_area' => $nearestArea,
                'distance' => round($nearestArea->distance, 2),
            ]);
        }

        return response()->json([
            'available' => false,
            'message' => 'Sorry, our service is not available in your area yet.',
        ]);
    }
}
