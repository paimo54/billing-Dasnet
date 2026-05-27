<?php

namespace App\Services;

use App\Models\WhatsappProvider;
use App\Models\WhatsappMessage;
use App\Models\WhatsappTemplate;
use App\Models\Customer;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsappService
{
    protected $provider;

    public function __construct()
    {
        $this->provider = WhatsappProvider::active()->default()->first();
    }

    /**
     * Send WhatsApp message using template
     */
    public function sendFromTemplate(string $templateType, Customer $customer, array $data = []): array
    {
        try {
            // Get template
            $template = WhatsappTemplate::active()->byType($templateType)->first();

            if (!$template) {
                return [
                    'success' => false,
                    'message' => "Template '{$templateType}' not found or inactive",
                ];
            }

            // Prepare default data
            $defaultData = $this->prepareCustomerData($customer);
            $mergedData = array_merge($defaultData, $data);

            // Render message
            $message = $template->render($mergedData);

            // Send message
            return $this->send($customer->phone, $message, $customer->id, $template->id, $templateType);
        } catch (\Exception $e) {
            Log::error('WhatsApp send from template failed', [
                'template_type' => $templateType,
                'customer_id' => $customer->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Failed to send WhatsApp: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Send WhatsApp message directly
     */
    public function send(
        string $phone,
        string $message,
        ?int $customerId = null,
        ?int $templateId = null,
        ?string $templateType = null
    ): array {
        try {
            // Check provider
            if (!$this->provider) {
                throw new \Exception('No active WhatsApp provider configured');
            }

            // Check daily limit
            if ($this->provider->hasReachedDailyLimit()) {
                throw new \Exception('Daily message limit reached for provider: ' . $this->provider->name);
            }

            // Format phone number
            $phone = $this->formatPhoneNumber($phone);

            // Create message record
            $whatsappMessage = WhatsappMessage::create([
                'customer_id' => $customerId,
                'template_id' => $templateId,
                'phone' => $phone,
                'message' => $message,
                'template_type' => $templateType,
                'status' => WhatsappMessage::STATUS_PENDING,
                'provider' => $this->provider->provider,
            ]);

            // Send via provider
            $result = $this->sendViaProvider($phone, $message);

            if ($result['success']) {
                $whatsappMessage->markAsSent($result['message_id'] ?? null, $result['response'] ?? null);
                $this->provider->incrementDailySent();

                return [
                    'success' => true,
                    'message' => 'WhatsApp message sent successfully',
                    'message_id' => $whatsappMessage->id,
                    'provider_message_id' => $result['message_id'] ?? null,
                ];
            } else {
                $whatsappMessage->markAsFailed($result['error'] ?? 'Unknown error');

                return [
                    'success' => false,
                    'message' => 'Failed to send WhatsApp: ' . ($result['error'] ?? 'Unknown error'),
                ];
            }
        } catch (\Exception $e) {
            Log::error('WhatsApp send failed', [
                'phone' => $phone,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Failed to send WhatsApp: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Send via provider API
     */
    protected function sendViaProvider(string $phone, string $message): array
    {
        switch ($this->provider->provider) {
            case WhatsappProvider::PROVIDER_FONNTE:
                return $this->sendViaFonnte($phone, $message);

            case WhatsappProvider::PROVIDER_WABLAS:
                return $this->sendViaWablas($phone, $message);

            case WhatsappProvider::PROVIDER_WOOWA:
                return $this->sendViaWoowa($phone, $message);

            default:
                return [
                    'success' => false,
                    'error' => 'Unsupported provider: ' . $this->provider->provider,
                ];
        }
    }

    /**
     * Send via Fonnte API
     */
    protected function sendViaFonnte(string $phone, string $message): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => $this->provider->api_key,
            ])->post($this->provider->api_url ?: 'https://api.fonnte.com/send', [
                'target' => $phone,
                'message' => $message,
                'countryCode' => '62',
            ]);

            $data = $response->json();

            if ($response->successful() && isset($data['status']) && $data['status'] === true) {
                return [
                    'success' => true,
                    'message_id' => $data['id'] ?? null,
                    'response' => json_encode($data),
                ];
            }

            return [
                'success' => false,
                'error' => $data['reason'] ?? 'Unknown error from Fonnte',
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Send via Wablas API
     */
    protected function sendViaWablas(string $phone, string $message): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => $this->provider->api_key,
            ])->post($this->provider->api_url ?: 'https://console.wablas.com/api/send-message', [
                'phone' => $phone,
                'message' => $message,
            ]);

            $data = $response->json();

            if ($response->successful() && isset($data['status']) && $data['status'] === true) {
                return [
                    'success' => true,
                    'message_id' => $data['data']['id'] ?? null,
                    'response' => json_encode($data),
                ];
            }

            return [
                'success' => false,
                'error' => $data['message'] ?? 'Unknown error from Wablas',
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Send via Woowa API
     */
    protected function sendViaWoowa(string $phone, string $message): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->provider->api_key,
            ])->post($this->provider->api_url ?: 'https://api.woowa.id/v1/send', [
                'number' => $phone,
                'message' => $message,
            ]);

            $data = $response->json();

            if ($response->successful() && isset($data['success']) && $data['success'] === true) {
                return [
                    'success' => true,
                    'message_id' => $data['data']['message_id'] ?? null,
                    'response' => json_encode($data),
                ];
            }

            return [
                'success' => false,
                'error' => $data['message'] ?? 'Unknown error from Woowa',
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Format phone number to international format
     */
    protected function formatPhoneNumber(string $phone): string
    {
        // Remove non-numeric characters
        $phone = preg_replace('/[^0-9]/', '', $phone);

        // Remove leading zeros
        $phone = ltrim($phone, '0');

        // Add country code if not present
        if (!str_starts_with($phone, '62')) {
            $phone = '62' . $phone;
        }

        return $phone;
    }

    /**
     * Prepare customer data for template
     */
    protected function prepareCustomerData(Customer $customer): array
    {
        return [
            'customerId' => $customer->id,
            'customerName' => $customer->name,
            'phone' => $customer->phone,
            'email' => $customer->email ?? '-',
            'address' => $customer->address ?? '-',
            'packageName' => $customer->package->name ?? '-',
            'profileName' => $customer->package->name ?? '-',
            'region' => $customer->region ?? '-',
            'area' => $customer->region ?? '-',
            'username' => $customer->pppoe_username ?? '-',
            'password' => $customer->pppoe_password ?? '-',
            'companyName' => config('app.name', 'ISP Company'),
            'companyPhone' => config('company.phone', ''),
            'companyEmail' => config('company.email', ''),
            'companyAddress' => config('company.address', ''),
            'baseUrl' => config('app.url'),
        ];
    }

    /**
     * Get message history for customer
     */
    public function getCustomerHistory(int $customerId, int $limit = 50): array
    {
        $messages = WhatsappMessage::byCustomer($customerId)
            ->with('template')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();

        return [
            'success' => true,
            'data' => $messages,
        ];
    }

    /**
     * Get statistics
     */
    public function getStatistics(int $days = 30): array
    {
        $startDate = now()->subDays($days);

        return [
            'total_sent' => WhatsappMessage::where('created_at', '>=', $startDate)->count(),
            'total_delivered' => WhatsappMessage::where('status', WhatsappMessage::STATUS_DELIVERED)
                ->where('created_at', '>=', $startDate)
                ->count(),
            'total_failed' => WhatsappMessage::where('status', WhatsappMessage::STATUS_FAILED)
                ->where('created_at', '>=', $startDate)
                ->count(),
            'total_pending' => WhatsappMessage::where('status', WhatsappMessage::STATUS_PENDING)->count(),
            'provider_quota' => $this->provider ? $this->provider->getRemainingQuota() : 0,
            'provider_name' => $this->provider ? $this->provider->name : 'No provider',
        ];
    }
}
