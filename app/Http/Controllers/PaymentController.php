<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Payment;
use App\Services\PaymentGateway\DuitkuService;
use App\Services\PaymentGateway\QrisService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class PaymentController extends Controller
{
    protected $duitkuService;
    protected $qrisService;

    public function __construct(DuitkuService $duitkuService, QrisService $qrisService)
    {
        $this->duitkuService = $duitkuService;
        $this->qrisService = $qrisService;
    }

    /**
     * Show payment page for invoice
     */
    public function show($invoiceId)
    {
        $invoice = Invoice::with(['customer', 'package'])->findOrFail($invoiceId);

        // Check if invoice is already paid
        if ($invoice->status === 'paid') {
            return redirect()->back()->with('info', 'Invoice sudah dibayar');
        }

        // Get available payment methods from Duitku
        $amount = (int) $invoice->total_amount;
        $paymentMethods = $this->duitkuService->getPaymentMethods($amount);

        // Get existing pending payment if any
        $existingPayment = Payment::where('invoice_id', $invoice->id)
            ->where('status', Payment::STATUS_PENDING)
            ->latest()
            ->first();

        return view('payment.show', compact('invoice', 'paymentMethods', 'existingPayment'));
    }

    /**
     * Create payment transaction
     */
    public function create(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'invoice_id' => 'required|exists:invoices,id',
            'payment_gateway' => 'required|in:duitku,qris',
            'payment_method' => 'required_if:payment_gateway,duitku',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            DB::beginTransaction();

            $invoice = Invoice::with(['customer', 'package'])->findOrFail($request->invoice_id);

            // Check if invoice is already paid
            if ($invoice->status === 'paid') {
                return response()->json([
                    'success' => false,
                    'message' => 'Invoice sudah dibayar',
                ], 400);
            }

            // Cancel existing pending payments
            Payment::where('invoice_id', $invoice->id)
                ->where('status', Payment::STATUS_PENDING)
                ->update(['status' => Payment::STATUS_CANCELLED]);

            // Create payment based on gateway
            if ($request->payment_gateway === 'duitku') {
                $result = $this->duitkuService->createTransaction($invoice, $request->payment_method);
            } else {
                $result = $this->qrisService->createQris($invoice);
            }

            if (!$result['success']) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => $result['message'] ?? 'Gagal membuat pembayaran',
                ], 500);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Pembayaran berhasil dibuat',
                'data' => $result,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Payment creation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Handle Duitku callback
     */
    public function duitkuCallback(Request $request)
    {
        Log::info('Duitku callback received', $request->all());

        try {
            $result = $this->duitkuService->handleCallback($request->all());

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => 'Callback processed successfully',
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => $result['message'] ?? 'Callback processing failed',
            ], 400);

        } catch (\Exception $e) {
            Log::error('Duitku callback error', [
                'error' => $e->getMessage(),
                'data' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Internal server error',
            ], 500);
        }
    }

    /**
     * Handle QRIS callback
     */
    public function qrisCallback(Request $request)
    {
        Log::info('QRIS callback received', $request->all());

        try {
            $result = $this->qrisService->handleCallback($request->all());

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => 'Callback processed successfully',
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => $result['message'] ?? 'Callback processing failed',
            ], 400);

        } catch (\Exception $e) {
            Log::error('QRIS callback error', [
                'error' => $e->getMessage(),
                'data' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Internal server error',
            ], 500);
        }
    }

    /**
     * Check payment status
     */
    public function checkStatus($paymentId)
    {
        try {
            $payment = Payment::findOrFail($paymentId);

            // Check status from gateway
            if ($payment->payment_gateway === Payment::GATEWAY_DUITKU) {
                $result = $this->duitkuService->checkStatus($payment->merchant_order_id);
            } else {
                $result = $this->qrisService->checkStatus($payment->reference_id);
            }

            return response()->json([
                'success' => true,
                'payment' => $payment,
                'gateway_status' => $result['data'] ?? null,
            ]);

        } catch (\Exception $e) {
            Log::error('Payment status check failed', [
                'payment_id' => $paymentId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengecek status pembayaran',
            ], 500);
        }
    }

    /**
     * Payment success page
     */
    public function success(Request $request)
    {
        $merchantOrderId = $request->merchantOrderId ?? $request->reference;

        if ($merchantOrderId) {
            $payment = Payment::where('merchant_order_id', $merchantOrderId)
                ->orWhere('reference_id', $merchantOrderId)
                ->with(['invoice', 'customer'])
                ->first();

            if ($payment) {
                return view('payment.success', compact('payment'));
            }
        }

        return view('payment.success');
    }

    /**
     * Payment failed page
     */
    public function failed(Request $request)
    {
        return view('payment.failed');
    }

    /**
     * Get payment history for invoice
     */
    public function history($invoiceId)
    {
        try {
            $payments = Payment::where('invoice_id', $invoiceId)
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'payments' => $payments,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil riwayat pembayaran',
            ], 500);
        }
    }
}
