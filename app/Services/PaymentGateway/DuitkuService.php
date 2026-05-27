<?php

namespace App\Services\PaymentGateway;

use App\Models\Payment;
use App\Models\Invoice;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class DuitkuService
{
    protected $merchantCode;
    protected $apiKey;
    protected $baseUrl;
    protected $callbackUrl;
    protected $returnUrl;

    public function __construct()
    {
        $this->merchantCode = config('payment.duitku.merchant_code');
        $this->apiKey = config('payment.duitku.api_key');
        $this->baseUrl = config('payment.duitku.base_url');
        $this->callbackUrl = config('payment.duitku.callback_url');
        $this->returnUrl = config('payment.duitku.return_url');
    }

    /**
     * Create payment transaction
     *
     * @param Invoice $invoice
     * @param string $paymentMethod (VA, QRIS, etc)
     * @return array
     */
    public function createTransaction(Invoice $invoice, string $paymentMethod): array
    {
        try {
            // Generate unique transaction ID
            $transactionId = $this->generateTransactionId($invoice);
            $merchantOrderId = 'INV-' . $invoice->id . '-' . time();

            // Prepare payment data
            $amount = (int) $invoice->total_amount;
            $expiryPeriod = 1440; // 24 hours in minutes

            // Create signature
            $signature = $this->createSignature($merchantOrderId, $amount);

            // Prepare request data
            $requestData = [
                'merchantCode' => $this->merchantCode,
                'paymentAmount' => $amount,
                'paymentMethod' => $paymentMethod,
                'merchantOrderId' => $merchantOrderId,
                'productDetails' => $this->getProductDetails($invoice),
                'customerVaName' => $invoice->customer->name,
                'email' => $invoice->customer->email ?? 'noreply@example.com',
                'phoneNumber' => $this->formatPhoneNumber($invoice->customer->phone),
                'callbackUrl' => $this->callbackUrl,
                'returnUrl' => $this->returnUrl,
                'signature' => $signature,
                'expiryPeriod' => $expiryPeriod,
            ];

            // Send request to Duitku
            $response = Http::post($this->baseUrl . '/api/merchant/createinvoice', $requestData);

            if (!$response->successful()) {
                throw new \Exception('Duitku API Error: ' . $response->body());
            }

            $result = $response->json();

            // Check response status
            if (!isset($result['statusCode']) || $result['statusCode'] !== '00') {
                throw new \Exception('Duitku Error: ' . ($result['statusMessage'] ?? 'Unknown error'));
            }

            // Save payment record
            $payment = $this->savePayment($invoice, $transactionId, $merchantOrderId, $paymentMethod, $result);

            Log::info('Duitku payment created', [
                'transaction_id' => $transactionId,
                'invoice_id' => $invoice->id,
                'payment_method' => $paymentMethod,
            ]);

            return [
                'success' => true,
                'payment' => $payment,
                'payment_url' => $result['paymentUrl'] ?? null,
                'va_number' => $result['vaNumber'] ?? null,
                'qris_string' => $result['qrString'] ?? null,
                'reference' => $result['reference'] ?? null,
            ];

        } catch (\Exception $e) {
            Log::error('Duitku payment creation failed', [
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
     * Handle payment callback from Duitku
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

            $merchantOrderId = $callbackData['merchantOrderId'] ?? null;
            $resultCode = $callbackData['resultCode'] ?? null;

            if (!$merchantOrderId) {
                throw new \Exception('Missing merchantOrderId in callback');
            }

            // Find payment
            $payment = Payment::where('merchant_order_id', $merchantOrderId)->first();

            if (!$payment) {
                throw new \Exception('Payment not found: ' . $merchantOrderId);
            }

            // Update payment status based on result code
            if ($resultCode === '00') {
                // Payment success
                $payment->update([
                    'status' => Payment::STATUS_SUCCESS,
                    'paid_at' => now(),
                    'payment_date' => now(),
                    'callback_data' => $callbackData,
                ]);

                // Update invoice status
                $invoice = $payment->invoice;
                $invoice->update(['status' => 'paid']);

                // TODO: Unsuspend customer service if suspended
                // $this->unsuspendCustomerService($invoice->customer);

                Log::info('Payment success via Duitku callback', [
                    'payment_id' => $payment->id,
                    'invoice_id' => $invoice->id,
                ]);

            } else {
                // Payment failed
                $payment->update([
                    'status' => Payment::STATUS_FAILED,
                    'callback_data' => $callbackData,
                    'notes' => 'Payment failed with code: ' . $resultCode,
                ]);

                Log::warning('Payment failed via Duitku callback', [
                    'payment_id' => $payment->id,
                    'result_code' => $resultCode,
                ]);
            }

            return [
                'success' => true,
                'payment' => $payment,
            ];

        } catch (\Exception $e) {
            Log::error('Duitku callback handling failed', [
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
     * @param string $merchantOrderId
     * @return array
     */
    public function checkStatus(string $merchantOrderId): array
    {
        try {
            $signature = md5($this->merchantCode . $merchantOrderId . $this->apiKey);

            $response = Http::post($this->baseUrl . '/api/merchant/transactionStatus', [
                'merchantCode' => $this->merchantCode,
                'merchantOrderId' => $merchantOrderId,
                'signature' => $signature,
            ]);

            if (!$response->successful()) {
                throw new \Exception('Duitku API Error: ' . $response->body());
            }

            $result = $response->json();

            return [
                'success' => true,
                'data' => $result,
            ];

        } catch (\Exception $e) {
            Log::error('Duitku status check failed', [
                'merchant_order_id' => $merchantOrderId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get available payment methods
     *
     * @param int $amount
     * @return array
     */
    public function getPaymentMethods(int $amount): array
    {
        try {
            $datetime = date('Y-m-d H:i:s');
            $signature = md5($this->merchantCode . $amount . $datetime . $this->apiKey);

            $response = Http::get($this->baseUrl . '/api/merchant/paymentmethod/getpaymentmethod', [
                'merchantcode' => $this->merchantCode,
                'amount' => $amount,
                'datetime' => $datetime,
                'signature' => $signature,
            ]);

            if (!$response->successful()) {
                throw new \Exception('Duitku API Error: ' . $response->body());
            }

            $result = $response->json();

            return [
                'success' => true,
                'methods' => $result['paymentFee'] ?? [],
            ];

        } catch (\Exception $e) {
            Log::error('Failed to get Duitku payment methods', [
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
                'methods' => [],
            ];
        }
    }

    /**
     * Generate unique transaction ID
     */
    private function generateTransactionId(Invoice $invoice): string
    {
        return 'TRX-' . $invoice->id . '-' . time() . '-' . Str::random(6);
    }

    /**
     * Create signature for request
     */
    private function createSignature(string $merchantOrderId, int $amount): string
    {
        return md5($this->merchantCode . $merchantOrderId . $amount . $this->apiKey);
    }

    /**
     * Validate callback signature
     */
    private function validateCallbackSignature(array $callbackData): bool
    {
        $merchantOrderId = $callbackData['merchantOrderId'] ?? '';
        $amount = $callbackData['amount'] ?? 0;
        $receivedSignature = $callbackData['signature'] ?? '';

        $calculatedSignature = md5($this->merchantCode . $amount . $merchantOrderId . $this->apiKey);

        return $receivedSignature === $calculatedSignature;
    }

    /**
     * Get product details for invoice
     */
    private function getProductDetails(Invoice $invoice): string
    {
        return 'Pembayaran Invoice ' . $invoice->invoice_number . ' - ' . $invoice->package->name;
    }

    /**
     * Format phone number for Duitku (must start with 08 or 62)
     */
    private function formatPhoneNumber(?string $phone): string
    {
        if (!$phone) {
            return '081234567890'; // Default phone
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
    private function savePayment(Invoice $invoice, string $transactionId, string $merchantOrderId, string $paymentMethod, array $result): Payment
    {
        $expiredAt = now()->addMinutes(1440); // 24 hours

        return Payment::create([
            'invoice_id' => $invoice->id,
            'customer_id' => $invoice->customer_id,
            'payment_gateway' => Payment::GATEWAY_DUITKU,
            'payment_method' => $paymentMethod,
            'payment_channel' => $result['paymentName'] ?? $paymentMethod,
            'transaction_id' => $transactionId,
            'reference_id' => $result['reference'] ?? $transactionId,
            'merchant_order_id' => $merchantOrderId,
            'amount' => $invoice->total_amount,
            'admin_fee' => $result['fee'] ?? 0,
            'total_amount' => $invoice->total_amount + ($result['fee'] ?? 0),
            'status' => Payment::STATUS_PENDING,
            'va_number' => $result['vaNumber'] ?? null,
            'qris_string' => $result['qrString'] ?? null,
            'qris_url' => $result['qrString'] ?? null,
            'expired_at' => $expiredAt,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}
