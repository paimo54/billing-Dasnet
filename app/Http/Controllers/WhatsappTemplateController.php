<?php

namespace App\Http\Controllers;

use App\Models\WhatsappTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WhatsappTemplateController extends Controller
{
    /**
     * Display a listing of templates
     */
    public function index()
    {
        $templates = WhatsappTemplate::orderBy('type')->get();

        return view('admin.whatsapp.templates.index', compact('templates'));
    }

    /**
     * Get all templates (API)
     */
    public function getTemplates()
    {
        try {
            $templates = WhatsappTemplate::orderBy('type')->get();

            return response()->json([
                'success' => true,
                'data' => $templates,
            ]);
        } catch (\Exception $e) {
            Log::error('Get WhatsApp templates failed', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to load templates',
            ], 500);
        }
    }

    /**
     * Show the form for creating a new template
     */
    public function create()
    {
        return view('admin.whatsapp.templates.create');
    }

    /**
     * Store a newly created template
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'type' => 'required|string|max:100|unique:whatsapp_templates,type',
            'message' => 'required|string',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        try {
            $template = WhatsappTemplate::create($validated);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Template created successfully',
                    'data' => $template,
                ]);
            }

            return redirect()->route('admin.whatsapp.templates.index')
                ->with('success', 'Template created successfully');
        } catch (\Exception $e) {
            Log::error('Create WhatsApp template failed', [
                'data' => $validated,
                'error' => $e->getMessage(),
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Failed to create template',
                ], 500);
            }

            return back()->withInput()
                ->with('error', 'Failed to create template');
        }
    }

    /**
     * Display the specified template
     */
    public function show(WhatsappTemplate $template)
    {
        return view('admin.whatsapp.templates.show', compact('template'));
    }

    /**
     * Show the form for editing the specified template
     */
    public function edit(WhatsappTemplate $template)
    {
        return view('admin.whatsapp.templates.edit', compact('template'));
    }

    /**
     * Update the specified template
     */
    public function update(Request $request, WhatsappTemplate $template)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:100',
            'message' => 'sometimes|string',
            'description' => 'nullable|string',
            'is_active' => 'sometimes|boolean',
        ]);

        try {
            $template->update($validated);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Template updated successfully',
                    'data' => $template->fresh(),
                ]);
            }

            return redirect()->route('admin.whatsapp.templates.index')
                ->with('success', 'Template updated successfully');
        } catch (\Exception $e) {
            Log::error('Update WhatsApp template failed', [
                'template_id' => $template->id,
                'data' => $validated,
                'error' => $e->getMessage(),
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Failed to update template',
                ], 500);
            }

            return back()->withInput()
                ->with('error', 'Failed to update template');
        }
    }

    /**
     * Remove the specified template
     */
    public function destroy(WhatsappTemplate $template)
    {
        try {
            $template->delete();

            if (request()->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Template deleted successfully',
                ]);
            }

            return redirect()->route('admin.whatsapp.templates.index')
                ->with('success', 'Template deleted successfully');
        } catch (\Exception $e) {
            Log::error('Delete WhatsApp template failed', [
                'template_id' => $template->id,
                'error' => $e->getMessage(),
            ]);

            if (request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Failed to delete template',
                ], 500);
            }

            return back()->with('error', 'Failed to delete template');
        }
    }

    /**
     * Toggle template active status
     */
    public function toggleActive(WhatsappTemplate $template)
    {
        try {
            $template->update(['is_active' => !$template->is_active]);

            return response()->json([
                'success' => true,
                'message' => 'Template status updated',
                'is_active' => $template->is_active,
            ]);
        } catch (\Exception $e) {
            Log::error('Toggle WhatsApp template status failed', [
                'template_id' => $template->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to update template status',
            ], 500);
        }
    }

    /**
     * Get template variables
     */
    public function getVariables(WhatsappTemplate $template)
    {
        try {
            $variables = $template->getAvailableVariables();

            return response()->json([
                'success' => true,
                'data' => $variables,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to get template variables',
            ], 500);
        }
    }
}
