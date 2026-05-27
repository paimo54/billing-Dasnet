<?php

namespace App\Http\Controllers;

use App\Models\WhatsappProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WhatsappProviderController extends Controller
{
    /**
     * Display a listing of providers
     */
    public function index()
    {
        $providers = WhatsappProvider::orderBy('is_default', 'desc')
            ->orderBy('name')
            ->get();

        return view('admin.whatsapp.providers.index', compact('providers'));
    }

    /**
     * Show the form for creating a new provider
     */
    public function create()
    {
        return view('admin.whatsapp.providers.create');
    }

    /**
     * Store a newly created provider
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'provider' => 'required|string|max:50|unique:whatsapp_providers,provider',
            'api_key' => 'required|string',
            'api_url' => 'nullable|url',
            'sender_number' => 'nullable|string|max:20',
            'is_active' => 'boolean',
            'is_default' => 'boolean',
            'daily_limit' => 'required|integer|min:1',
            'config' => 'nullable|array',
        ]);

        try {
            // If setting as default, unset other defaults
            if ($validated['is_default'] ?? false) {
                WhatsappProvider::where('is_default', true)->update(['is_default' => false]);
            }

            $provider = WhatsappProvider::create($validated);

            return redirect()->route('admin.whatsapp.providers.index')
                ->with('success', 'Provider created successfully');
        } catch (\Exception $e) {
            Log::error('Create WhatsApp provider failed', [
                'data' => $validated,
                'error' => $e->getMessage(),
            ]);

            return back()->withInput()
                ->with('error', 'Failed to create provider');
        }
    }

    /**
     * Display the specified provider
     */
    public function show(WhatsappProvider $provider)
    {
        return view('admin.whatsapp.providers.show', compact('provider'));
    }

    /**
     * Show the form for editing the specified provider
     */
    public function edit(WhatsappProvider $provider)
    {
        return view('admin.whatsapp.providers.edit', compact('provider'));
    }

    /**
     * Update the specified provider
     */
    public function update(Request $request, WhatsappProvider $provider)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:100',
            'api_key' => 'sometimes|string',
            'api_url' => 'nullable|url',
            'sender_number' => 'nullable|string|max:20',
            'is_active' => 'sometimes|boolean',
            'is_default' => 'sometimes|boolean',
            'daily_limit' => 'sometimes|integer|min:1',
            'config' => 'nullable|array',
        ]);

        try {
            // If setting as default, unset other defaults
            if (isset($validated['is_default']) && $validated['is_default']) {
                WhatsappProvider::where('id', '!=', $provider->id)
                    ->where('is_default', true)
                    ->update(['is_default' => false]);
            }

            $provider->update($validated);

            return redirect()->route('admin.whatsapp.providers.index')
                ->with('success', 'Provider updated successfully');
        } catch (\Exception $e) {
            Log::error('Update WhatsApp provider failed', [
                'provider_id' => $provider->id,
                'data' => $validated,
                'error' => $e->getMessage(),
            ]);

            return back()->withInput()
                ->with('error', 'Failed to update provider');
        }
    }

    /**
     * Remove the specified provider
     */
    public function destroy(WhatsappProvider $provider)
    {
        try {
            if ($provider->is_default) {
                return back()->with('error', 'Cannot delete default provider. Set another provider as default first.');
            }

            $provider->delete();

            return redirect()->route('admin.whatsapp.providers.index')
                ->with('success', 'Provider deleted successfully');
        } catch (\Exception $e) {
            Log::error('Delete WhatsApp provider failed', [
                'provider_id' => $provider->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Failed to delete provider');
        }
    }

    /**
     * Toggle provider active status
     */
    public function toggleActive(WhatsappProvider $provider)
    {
        try {
            $provider->update(['is_active' => !$provider->is_active]);

            return response()->json([
                'success' => true,
                'message' => 'Provider status updated',
                'is_active' => $provider->is_active,
            ]);
        } catch (\Exception $e) {
            Log::error('Toggle WhatsApp provider status failed', [
                'provider_id' => $provider->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to update provider status',
            ], 500);
        }
    }

    /**
     * Set provider as default
     */
    public function setDefault(WhatsappProvider $provider)
    {
        try {
            // Unset other defaults
            WhatsappProvider::where('id', '!=', $provider->id)
                ->where('is_default', true)
                ->update(['is_default' => false]);

            // Set this as default and activate
            $provider->update([
                'is_default' => true,
                'is_active' => true,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Provider set as default',
            ]);
        } catch (\Exception $e) {
            Log::error('Set WhatsApp provider as default failed', [
                'provider_id' => $provider->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to set provider as default',
            ], 500);
        }
    }

    /**
     * Reset daily counter
     */
    public function resetCounter(WhatsappProvider $provider)
    {
        try {
            $provider->update([
                'daily_sent' => 0,
                'last_reset_date' => now()->toDateString(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Daily counter reset successfully',
            ]);
        } catch (\Exception $e) {
            Log::error('Reset WhatsApp provider counter failed', [
                'provider_id' => $provider->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to reset counter',
            ], 500);
        }
    }

    /**
     * Test provider connection
     */
    public function testConnection(WhatsappProvider $provider, Request $request)
    {
        $validated = $request->validate([
            'test_phone' => 'required|string',
        ]);

        try {
            $whatsappService = new \App\Services\WhatsappService();

            $result = $whatsappService->send(
                $validated['test_phone'],
                'Test message from ' . config('app.name') . '. Provider: ' . $provider->name,
                null,
                null,
                'test-connection'
            );

            return response()->json($result);
        } catch (\Exception $e) {
            Log::error('Test WhatsApp provider connection failed', [
                'provider_id' => $provider->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Connection test failed: ' . $e->getMessage(),
            ], 500);
        }
    }
}
