<?php

namespace App\Services;

use App\Models\Customer;
use Illuminate\Support\Facades\Log;

class NetworkService
{
    protected $radiusService;
    protected $mikrotikService;
    protected $primaryMethod;

    public function __construct(FreeRadiusService $radiusService, MikrotikService $mikrotikService)
    {
        $this->radiusService = $radiusService;
        $this->mikrotikService = $mikrotikService;
        $this->primaryMethod = config('network.primary_method', 'radius'); // radius or mikrotik
    }

    /**
     * Suspend customer service
     * Uses primary method with fallback to secondary
     *
     * @param Customer $customer
     * @return array
     */
    public function suspendCustomer(Customer $customer): array
    {
        $results = [
            'success' => false,
            'primary' => null,
            'fallback' => null,
            'method_used' => null,
        ];

        try {
            // Try primary method
            if ($this->primaryMethod === 'radius') {
                $results['primary'] = $this->radiusService->suspendUser($customer);

                if ($results['primary']) {
                    $results['success'] = true;
                    $results['method_used'] = 'radius';

                    Log::info('Customer suspended via RADIUS', [
                        'customer_id' => $customer->id,
                    ]);
                } else {
                    // Fallback to Mikrotik
                    Log::warning('RADIUS suspend failed, trying Mikrotik fallback', [
                        'customer_id' => $customer->id,
                    ]);

                    $results['fallback'] = $this->mikrotikService->suspendUser($customer);

                    if ($results['fallback']) {
                        $results['success'] = true;
                        $results['method_used'] = 'mikrotik_fallback';

                        Log::info('Customer suspended via Mikrotik (fallback)', [
                            'customer_id' => $customer->id,
                        ]);
                    }
                }
            } else {
                // Primary: Mikrotik
                $results['primary'] = $this->mikrotikService->suspendUser($customer);

                if ($results['primary']) {
                    $results['success'] = true;
                    $results['method_used'] = 'mikrotik';

                    Log::info('Customer suspended via Mikrotik', [
                        'customer_id' => $customer->id,
                    ]);
                } else {
                    // Fallback to RADIUS
                    Log::warning('Mikrotik suspend failed, trying RADIUS fallback', [
                        'customer_id' => $customer->id,
                    ]);

                    $results['fallback'] = $this->radiusService->suspendUser($customer);

                    if ($results['fallback']) {
                        $results['success'] = true;
                        $results['method_used'] = 'radius_fallback';

                        Log::info('Customer suspended via RADIUS (fallback)', [
                            'customer_id' => $customer->id,
                        ]);
                    }
                }
            }

            // Update customer status in database
            if ($results['success']) {
                $customer->update(['is_active' => false]);
            }

            return $results;

        } catch (\Exception $e) {
            Log::error('Failed to suspend customer', [
                'customer_id' => $customer->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Unsuspend customer service
     * Uses primary method with fallback to secondary
     *
     * @param Customer $customer
     * @return array
     */
    public function unsuspendCustomer(Customer $customer): array
    {
        $results = [
            'success' => false,
            'primary' => null,
            'fallback' => null,
            'method_used' => null,
        ];

        try {
            // Try primary method
            if ($this->primaryMethod === 'radius') {
                $results['primary'] = $this->radiusService->unsuspendUser($customer);

                if ($results['primary']) {
                    $results['success'] = true;
                    $results['method_used'] = 'radius';

                    Log::info('Customer unsuspended via RADIUS', [
                        'customer_id' => $customer->id,
                    ]);
                } else {
                    // Fallback to Mikrotik
                    Log::warning('RADIUS unsuspend failed, trying Mikrotik fallback', [
                        'customer_id' => $customer->id,
                    ]);

                    $results['fallback'] = $this->mikrotikService->unsuspendUser($customer);

                    if ($results['fallback']) {
                        $results['success'] = true;
                        $results['method_used'] = 'mikrotik_fallback';

                        Log::info('Customer unsuspended via Mikrotik (fallback)', [
                            'customer_id' => $customer->id,
                        ]);
                    }
                }
            } else {
                // Primary: Mikrotik
                $results['primary'] = $this->mikrotikService->unsuspendUser($customer);

                if ($results['primary']) {
                    $results['success'] = true;
                    $results['method_used'] = 'mikrotik';

                    Log::info('Customer unsuspended via Mikrotik', [
                        'customer_id' => $customer->id,
                    ]);
                } else {
                    // Fallback to RADIUS
                    Log::warning('Mikrotik unsuspend failed, trying RADIUS fallback', [
                        'customer_id' => $customer->id,
                    ]);

                    $results['fallback'] = $this->radiusService->unsuspendUser($customer);

                    if ($results['fallback']) {
                        $results['success'] = true;
                        $results['method_used'] = 'radius_fallback';

                        Log::info('Customer unsuspended via RADIUS (fallback)', [
                            'customer_id' => $customer->id,
                        ]);
                    }
                }
            }

            // Update customer status in database
            if ($results['success']) {
                $customer->update(['is_active' => true]);
            }

            return $results;

        } catch (\Exception $e) {
            Log::error('Failed to unsuspend customer', [
                'customer_id' => $customer->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Batch suspend customers (optimized for thousands)
     *
     * @param array $customerIds
     * @return array
     */
    public function batchSuspend(array $customerIds): array
    {
        $results = [
            'total' => count($customerIds),
            'suspended' => 0,
            'failed' => 0,
            'errors' => [],
        ];

        // Use RADIUS for batch operations (much faster)
        if ($this->primaryMethod === 'radius') {
            Log::info('Starting batch suspend via RADIUS', [
                'count' => count($customerIds),
            ]);

            foreach ($customerIds as $customerId) {
                $customer = Customer::find($customerId);

                if (!$customer) {
                    $results['failed']++;
                    continue;
                }

                $result = $this->suspendCustomer($customer);

                if ($result['success']) {
                    $results['suspended']++;
                } else {
                    $results['failed']++;
                    $results['errors'][] = [
                        'customer_id' => $customerId,
                        'error' => $result['error'] ?? 'Unknown error',
                    ];
                }
            }
        } else {
            // Mikrotik batch (slower, but still works)
            Log::info('Starting batch suspend via Mikrotik', [
                'count' => count($customerIds),
            ]);

            foreach ($customerIds as $customerId) {
                $customer = Customer::find($customerId);

                if (!$customer) {
                    $results['failed']++;
                    continue;
                }

                $result = $this->suspendCustomer($customer);

                if ($result['success']) {
                    $results['suspended']++;
                } else {
                    $results['failed']++;
                    $results['errors'][] = [
                        'customer_id' => $customerId,
                        'error' => $result['error'] ?? 'Unknown error',
                    ];
                }
            }
        }

        Log::info('Batch suspend completed', $results);

        return $results;
    }

    /**
     * Get customer network status
     *
     * @param Customer $customer
     * @return array
     */
    public function getCustomerStatus(Customer $customer): array
    {
        try {
            // Get status from both sources
            $radiusStatus = $this->radiusService->getUserStatus($customer);
            $mikrotikSessions = $this->mikrotikService->getActiveSessions($customer);

            return [
                'customer_id' => $customer->id,
                'is_active' => $customer->is_active,
                'radius' => $radiusStatus,
                'mikrotik_sessions' => count($mikrotikSessions),
                'sessions' => $mikrotikSessions,
            ];

        } catch (\Exception $e) {
            Log::error('Failed to get customer status', [
                'customer_id' => $customer->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'customer_id' => $customer->id,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Create network user for customer
     *
     * @param Customer $customer
     * @param string $password
     * @return array
     */
    public function createNetworkUser(Customer $customer, string $password): array
    {
        $results = [
            'success' => false,
            'radius' => false,
            'mikrotik' => false,
        ];

        try {
            // Create in RADIUS
            $results['radius'] = $this->radiusService->createUser($customer, $password);

            // Create in Mikrotik (optional, for direct access)
            if (config('network.create_mikrotik_user', false)) {
                $results['mikrotik'] = $this->mikrotikService->createUser($customer, $password);
            }

            $results['success'] = $results['radius'] || $results['mikrotik'];

            return $results;

        } catch (\Exception $e) {
            Log::error('Failed to create network user', [
                'customer_id' => $customer->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Auto-unsuspend after payment
     *
     * @param Customer $customer
     * @return bool
     */
    public function autoUnsuspendAfterPayment(Customer $customer): bool
    {
        Log::info('Auto-unsuspend triggered after payment', [
            'customer_id' => $customer->id,
        ]);

        $result = $this->unsuspendCustomer($customer);

        return $result['success'];
    }
}
