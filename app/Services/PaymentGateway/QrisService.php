<?php

namespace App\Services\PaymentGateway;

use App\Models\Payment;
use App\Models\Invoice;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class QrisService
{
    protected $merchantId;
    protected $apiKey;
    protected $baseUrl;
    protected $callbackUrl;

    public function __construct()
    {
        $this->merchantId = config('payment.qris.merchant_id');
        $this->apiKey = config('payment.qris.api_key');
        $this->baseUrl = config('payment.qris.base_url');
        $this->callbackUrl = config('payment.qris.callback_url');
    }

    /**
     * Create QRIS Dynamic payment
     *
     * @param Invoice $invoice
     * @return array
     */
    public function createQris(Invoice $invoice): array
    {
        try {
            // Generate unique transaction ID
            $transactionId = $this->generateTransactionId($invoice);
            $referenceId = 'QRIS-' . $invoice->id . '-' . time();

            // Prepare payment data
            $amount = (int) $invoice->total_amount;

            // Create request data
            $requestData = [
                'merchant_id' => $this->merchantId,
                'reference_id' => $referenceId,
                'amount' => $amount,
                'customer_name' => $invoice->customer->name,
                'customer_phone' => $this->formatPhoneNumber($invoice->customer->phone),
                'customer_email' => $invoice->customer->email ?? 'noreply@example.com',
                'description' => $this->getDescription($invoice),
                'callback_url' => $this->callbackUrl,
                'expired_at' => now()->addHours(24)->toIso8601String(),
            ];

            // Add signature
            $requestData['signature'] = $this->createSignature($requestData);

            // Send request to QRIS provider
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl . '/v1/qris/create', $requestData);

            if (!$response->successful()) {
                throw new \Exception('QRIS API Error: ' . $response->body());
            }

            $result = $response->json();

            // Check response status
            if (!isset($result['status']) || $result['status'] !== 'success') {
                throw new \Exception('QRIS Error: ' . ($result['message'] ?? 'Unknown error'));
            }

            // Save payment record
            $payment = $this->savePayment($invoice, $transactionId, $referenceId, $result);

            Log::info('QRIS payment created', [
                'transaction_id' => $transactionId,
                'invoice_id' => $invoice->id,
            ]);

            return [
                'success' => true,
                'payment' => $payment,
                'qris_string' => $result['data']['qris_string'] ?? null,
                'qris_url' => $result['data']['qris_url'] ?? null,
                'expired_at' => $result['data']['expired_at'] ?? null,
            ];

        } catch (\Exception $e) {
            Log::error('QRIS payment creation failed', [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Handle payment callback from QRIS provider
     *
     * @param array $callbackData
     * @return array
     */
    public function handleCallback(array $callbackData): array
    {
        try {
            // Validate signature
            if (!$this->validateCallbackSignature($callbackData)) {
                throw new \Exception('Invalid callback signature');
            }

            $referenceId = $callbackData['reference_id'] ?? null;
            $status = $callbackData['status'] ?? null;

            if (!$referenceId) {
                throw new \Exception('Missing reference_id in callback');
            }

            // Find payment
            $payment = Payment::where('reference_id', $referenceId)->first();

            if (!$payment) {
                throw new \Exception('Payment not found: ' . $referenceId);
            }

            // Update payment status
            if ($status === 'paid' || $status === 'success') {
                // Payment success
                $payment->update([
                    'status' => Payment::STATUS_SUCCESS,
                    'paid_at' => now(),
                    'payment_date' => $callbackData['paid_at'] ?? now(),
                    'callback_data' => $callbackData,
                ]);

                // Update invoice status
                $invoice = $payment->invoice;
                $invoice->update(['status' => 'paid']);

                // Auto-unsuspend customer service if suspended
                if (!$invoice->customer->is_active) {
                    $networkService = app(\App\Services\NetworkService::class);
                    $networkService->autoUnsuspendAfterPayment($invoice->customer);
                }

                Log::info('Payment success via QRIS callback', [
                    'payment_id' => $payment->id,
                    'invoice_id' => $invoice->id,
                ]);

            } else if ($status === 'expired') {
                // Payment expired
                $payment->update([
                    'status' => Payment::STATUS_EXPIRED,
                    'callback_data' => $callbackData,
                ]);

                Log::info('Payment expired via QRIS callback', [
                    'payment_id' => $payment->id,
                ]);

            } else {
                // Payment failed
                $payment->update([
                    'status' => Payment::STATUS_FAILED,
                    'callback_data' => $callbackData,
                    'notes' => 'Payment failed with status: ' . $status,
                ]);

                Log::warning('Payment failed via QRIS callback', [
                    'payment_id' => $payment->id,
                    'status' => $status,
                ]);
            }

            return [
                'success' => true,
                'payment' => $payment,
            ];

        } catch (\Exception $e) {
            Log::error('QRIS callback handling failed', [
                'callback_data' => $callbackData,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check payment status
     *
     * @param string $referenceId
     * @return array
     */
    public function checkStatus(string $referenceId): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->get($this->baseUrl . '/v1/qris/status/' . $referenceId);

            if (!$response->successful()) {
                throw new \Exception('QRIS API Error: ' . $response->body());
            }

            $result = $response->json();

            return [
                'success' => true,
                'data' => $result,
            ];

        } catch (\Exception $e) {
            Log::error('QRIS status check failed', [
                'reference_id' => $referenceId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Generate unique transaction ID
     */
    private function generateTransactionId(Invoice $invoice): string
    {
        return 'QRIS-TRX-' . $invoice->id . '-' . time() . '-' . Str::random(6);
    }

    /**
     * Create signature for request
     */
    private function createSignature(array $data): string
    {
        // Sort data by key
        ksort($data);

        // Create string to sign
        $stringToSign = '';
        foreach ($data as $key => $value) {
            if ($key !== 'signature') {
                $stringToSign .= $key . '=' . $value . '&';
            }
        }
        $stringToSign = rtrim($stringToSign, '&');

        // Create signature
        return hash_hmac('sha256', $stringToSign, $this->apiKey);
    }

    /**
     * Validate callback signature
     */
    private function validateCallbackSignature(array $callbackData): bool
    {
        $receivedSignature = $callbackData['signature'] ?? '';
        unset($callbackData['signature']);

        $calculatedSignature = $this->createSignature($callbackData);

        return hash_equals($calculatedSignature, $receivedSignature);
    }

    /**
     * Get description for payment
     */
    private function getDescription(Invoice $invoice): string
    {
        return 'Pembayaran Invoice ' . $invoice->invoice_number . ' - ' . $invoice->package->name;
    }

    /**
     * Format phone number
     */
    private function formatPhoneNumber(?string $phone): string
    {
        if (!$phone) {
            return '081234567890';
        }

        // Remove non-numeric characters
        $phone = preg_replace('/[^0-9]/', '', $phone);

        // Convert to 08 format
        if (substr($phone, 0, 2) === '62') {
            $phone = '0' . substr($phone, 2);
        } elseif (substr($phone, 0, 1) !== '0') {
            $phone = '0' . $phone;
        }

        return $phone;
    }

    /**
     * Save payment record to database
     */
    private function savePayment(Invoice $invoice, string $transactionId, string $referenceId, array $result): Payment
    {
        $expiredAt = isset($result['data']['expired_at'])
            ? \Carbon\Carbon::parse($result['data']['expired_at'])
            : now()->addHours(24);

        return Payment::create([
            'invoice_id' => $invoice->id,
            'customer_id' => $invoice->customer_id,
            'payment_gateway' => Payment::GATEWAY_QRIS,
            'payment_method' => 'qris',
            'payment_channel' => 'QRIS Dynamic',
            'transaction_id' => $transactionId,
            'reference_id' => $referenceId,
            'merchant_order_id' => $referenceId,
            'amount' => $invoice->total_amount,
            'admin_fee' => 0,
            'total_amount' => $invoice->total_amount,
            'status' => Payment::STATUS_PENDING,
            'qris_string' => $result['data']['qris_string'] ?? null,
            'qris_url' => $result['data']['qris_url'] ?? null,
            'expired_at' => $expiredAt,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}
