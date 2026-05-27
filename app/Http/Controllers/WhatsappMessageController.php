<?php

namespace App\Http\Controllers;

use App\Models\WhatsappMessage;
use App\Models\Customer;
use App\Services\WhatsappService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WhatsappMessageController extends Controller
{
    protected $whatsappService;

    public function __construct(WhatsappService $whatsappService)
    {
        $this->whatsappService = $whatsappService;
    }

    /**
     * Display a listing of messages
     */
    public function index(Request $request)
    {
        $query = WhatsappMessage::with(['customer', 'template'])
            ->orderBy('created_at', 'desc');

        // Filter by status
        if ($request->has('status') && $request->status != '') {
            $query->where('status', $request->status);
        }

        // Filter by customer
        if ($request->has('customer_id') && $request->customer_id != '') {
            $query->where('customer_id', $request->customer_id);
        }

        // Filter by template type
        if ($request->has('template_type') && $request->template_type != '') {
            $query->where('template_type', $request->template_type);
        }

        // Filter by date range
        if ($request->has('date_from') && $request->date_from != '') {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->has('date_to') && $request->date_to != '') {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $messages = $query->paginate(50);

        return view('admin.whatsapp.messages.index', compact('messages'));
    }

    /**
     * Display the specified message
     */
    public function show(WhatsappMessage $message)
    {
        $message->load(['customer', 'template']);

        return view('admin.whatsapp.messages.show', compact('message'));
    }

    /**
     * Send WhatsApp message
     */
    public function send(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'template_type' => 'required|string',
            'data' => 'nullable|array',
        ]);

        try {
            $customer = Customer::findOrFail($validated['customer_id']);
            $data = $validated['data'] ?? [];

            $result = $this->whatsappService->sendFromTemplate(
                $validated['template_type'],
                $customer,
                $data
            );

            return response()->json($result);
        } catch (\Exception $e) {
            Log::error('Send WhatsApp message failed', [
                'data' => $validated,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to send message: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Send direct WhatsApp message
     */
    public function sendDirect(Request $request)
    {
        $validated = $request->validate([
            'phone' => 'required|string',
            'message' => 'required|string',
            'customer_id' => 'nullable|exists:customers,id',
        ]);

        try {
            $result = $this->whatsappService->send(
                $validated['phone'],
                $validated['message'],
                $validated['customer_id'] ?? null
            );

            return response()->json($result);
        } catch (\Exception $e) {
            Log::error('Send direct WhatsApp message failed', [
                'data' => $validated,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to send message: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Resend failed message
     */
    public function resend(WhatsappMessage $message)
    {
        try {
            if (!$message->isFailed()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only failed messages can be resent',
                ], 400);
            }

            $result = $this->whatsappService->send(
                $message->phone,
                $message->message,
                $message->customer_id,
                $message->template_id,
                $message->template_type
            );

            return response()->json($result);
        } catch (\Exception $e) {
            Log::error('Resend WhatsApp message failed', [
                'message_id' => $message->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to resend message: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get customer message history
     */
    public function customerHistory(Customer $customer)
    {
        try {
            $result = $this->whatsappService->getCustomerHistory($customer->id);

            return response()->json($result);
        } catch (\Exception $e) {
            Log::error('Get customer WhatsApp history failed', [
                'customer_id' => $customer->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to get message history',
            ], 500);
        }
    }

    /**
     * Get statistics
     */
    public function statistics(Request $request)
    {
        try {
            $days = $request->input('days', 30);
            $stats = $this->whatsappService->getStatistics($days);

            return response()->json([
                'success' => true,
                'data' => $stats,
            ]);
        } catch (\Exception $e) {
            Log::error('Get WhatsApp statistics failed', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to get statistics',
            ], 500);
        }
    }

    /**
     * Bulk send messages
     */
    public function bulkSend(Request $request)
    {
        $validated = $request->validate([
            'customer_ids' => 'required|array',
            'customer_ids.*' => 'exists:customers,id',
            'template_type' => 'required|string',
            'data' => 'nullable|array',
        ]);

        try {
            $results = [
                'total' => count($validated['customer_ids']),
                'success' => 0,
                'failed' => 0,
                'details' => [],
            ];

            foreach ($validated['customer_ids'] as $customerId) {
                $customer = Customer::find($customerId);

                if (!$customer) {
                    $results['failed']++;
                    continue;
                }

                $result = $this->whatsappService->sendFromTemplate(
                    $validated['template_type'],
                    $customer,
                    $validated['data'] ?? []
                );

                if ($result['success']) {
                    $results['success']++;
                } else {
                    $results['failed']++;
                }

                $results['details'][] = [
                    'customer_id' => $customerId,
                    'customer_name' => $customer->name,
                    'success' => $result['success'],
                    'message' => $result['message'],
                ];
            }

            return response()->json([
                'success' => true,
                'data' => $results,
            ]);
        } catch (\Exception $e) {
            Log::error('Bulk send WhatsApp messages failed', [
                'data' => $validated,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to send bulk messages: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete message
     */
    public function destroy(WhatsappMessage $message)
    {
        try {
            $message->delete();

            return response()->json([
                'success' => true,
                'message' => 'Message deleted successfully',
            ]);
        } catch (\Exception $e) {
            Log::error('Delete WhatsApp message failed', [
                'message_id' => $message->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete message',
            ], 500);
        }
    }
}
