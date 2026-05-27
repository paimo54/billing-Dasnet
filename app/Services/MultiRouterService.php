<?php

namespace App\Services;

use App\Models\Router;
use App\Models\Customer;
use App\Models\CoverageArea;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class MultiRouterService
{
    protected $mikrotikService;
    protected $freeRadiusService;

    public function __construct(
        MikrotikService $mikrotikService,
        FreeRadiusService $freeRadiusService
    ) {
        $this->mikrotikService = $mikrotikService;
        $this->freeRadiusService = $freeRadiusService;
    }

    /**
     * Auto-assign customer to best available router
     */
    public function autoAssignRouter(Customer $customer, ?CoverageArea $coverageArea = null): array
    {
        try {
            // Get available routers
            $routers = $this->getAvailableRouters($customer, $coverageArea);

            if ($routers->isEmpty()) {
                return [
                    'success' => false,
                    'message' => 'No available routers found',
                ];
            }

            // Select best router (highest priority, lowest load)
            $router = $routers->first();

            // Assign customer to router
            $result = $this->assignCustomerToRouter($customer, $router);

            return $result;
        } catch (\Exception $e) {
            Log::error('Auto-assign router failed', [
                'customer_id' => $customer->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Failed to auto-assign router: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Get available routers for customer
     */
    protected function getAvailableRouters(Customer $customer, ?CoverageArea $coverageArea = null)
    {
        $query = Router::available();

        // Filter by coverage area if provided
        if ($coverageArea) {
            $routerIds = $coverageArea->routers()
                ->where('status', Router::STATUS_ACTIVE)
                ->where('auto_assign', true)
                ->whereRaw('current_users < max_capacity')
                ->pluck('routers.id');

            $query->whereIn('id', $routerIds);
        }

        // Filter by region if customer has location
        if ($customer->region) {
            $query->where(function ($q) use ($customer) {
                $q->where('region', $customer->region)
                    ->orWhereNull('region');
            });
        }

        // Order by priority and load
        return $query->orderByPriority()
            ->orderByLoad()
            ->get();
    }

    /**
     * Assign customer to specific router
     */
    public function assignCustomerToRouter(Customer $customer, Router $router): array
    {
        DB::beginTransaction();

        try {
            // Generate PPPoE credentials
            $username = $this->generateUsername($customer, $router);
            $password = $this->generatePassword();

            // Create user on router
            if ($router->use_radius) {
                // Create RADIUS user
                $radiusResult = $this->freeRadiusService->createUser(
                    $username,
                    $password,
                    $customer->package->download_speed ?? '10M',
                    $customer->package->upload_speed ?? '10M'
                );

                if (!$radiusResult) {
                    throw new \Exception('Failed to create RADIUS user');
                }
            } else {
                // Create Mikrotik PPPoE user
                $this->mikrotikService->setRouter($router);
                $mikrotikResult = $this->mikrotikService->createUser(
                    $username,
                    $password,
                    $customer->package->name ?? 'default'
                );

                if (!$mikrotikResult) {
                    throw new \Exception('Failed to create Mikrotik user');
                }
            }

            // Update customer record
            $customer->update([
                'router_id' => $router->id,
                'router_assigned_at' => now(),
                'pppoe_username' => $username,
                'pppoe_password' => $password,
            ]);

            // Increment router user count
            $router->incrementUsers();

            // Increment coverage area subscribers if assigned
            if ($customer->coverage_area_id) {
                $customer->coverageArea?->incrementSubscribers();
            }

            DB::commit();

            Log::info('Customer assigned to router', [
                'customer_id' => $customer->id,
                'router_id' => $router->id,
                'username' => $username,
            ]);

            return [
                'success' => true,
                'message' => 'Customer assigned to router successfully',
                'router' => $router,
                'username' => $username,
                'password' => $password,
            ];
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Assign customer to router failed', [
                'customer_id' => $customer->id,
                'router_id' => $router->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Failed to assign customer: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Reassign customer to different router
     */
    public function reassignCustomer(Customer $customer, Router $newRouter): array
    {
        $oldRouter = $customer->router;

        DB::beginTransaction();

        try {
            // Remove from old router
            if ($oldRouter) {
                $this->removeCustomerFromRouter($customer, $oldRouter);
            }

            // Assign to new router
            $result = $this->assignCustomerToRouter($customer, $newRouter);

            if (!$result['success']) {
                throw new \Exception($result['message']);
            }

            DB::commit();

            return [
                'success' => true,
                'message' => 'Customer reassigned successfully',
                'old_router' => $oldRouter,
                'new_router' => $newRouter,
            ];
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Reassign customer failed', [
                'customer_id' => $customer->id,
                'old_router_id' => $oldRouter?->id,
                'new_router_id' => $newRouter->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Failed to reassign customer: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Remove customer from router
     */
    protected function removeCustomerFromRouter(Customer $customer, Router $router): bool
    {
        try {
            if ($router->use_radius && $customer->pppoe_username) {
                $this->freeRadiusService->deleteUser($customer->pppoe_username);
            } elseif ($customer->pppoe_username) {
                $this->mikrotikService->setRouter($router);
                $this->mikrotikService->deleteUser($customer->pppoe_username);
            }

            $router->decrementUsers();

            return true;
        } catch (\Exception $e) {
            Log::error('Remove customer from router failed', [
                'customer_id' => $customer->id,
                'router_id' => $router->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Check health of all routers
     */
    public function checkRoutersHealth(): array
    {
        $routers = Router::all();
        $results = [
            'total' => $routers->count(),
            'active' => 0,
            'error' => 0,
            'maintenance' => 0,
            'inactive' => 0,
            'details' => [],
        ];

        foreach ($routers as $router) {
            $health = $this->checkRouterHealth($router);
            $results['details'][] = $health;

            switch ($health['status']) {
                case Router::STATUS_ACTIVE:
                    $results['active']++;
                    break;
                case Router::STATUS_ERROR:
                    $results['error']++;
                    break;
                case Router::STATUS_MAINTENANCE:
                    $results['maintenance']++;
                    break;
                case Router::STATUS_INACTIVE:
                    $results['inactive']++;
                    break;
            }
        }

        return $results;
    }

    /**
     * Check health of single router
     */
    public function checkRouterHealth(Router $router): array
    {
        try {
            $this->mikrotikService->setRouter($router);
            $connected = $this->mikrotikService->connect();

            if ($connected) {
                // Get system resource info
                $identity = $this->mikrotikService->getIdentity();

                $router->markAsActive();

                return [
                    'router_id' => $router->id,
                    'name' => $router->name,
                    'status' => Router::STATUS_ACTIVE,
                    'identity' => $identity,
                    'current_users' => $router->current_users,
                    'capacity_percentage' => $router->getCapacityPercentage(),
                    'last_check' => now(),
                ];
            } else {
                $router->markAsError('Connection failed');

                return [
                    'router_id' => $router->id,
                    'name' => $router->name,
                    'status' => Router::STATUS_ERROR,
                    'error' => 'Connection failed',
                    'last_check' => now(),
                ];
            }
        } catch (\Exception $e) {
            $router->markAsError($e->getMessage());

            return [
                'router_id' => $router->id,
                'name' => $router->name,
                'status' => Router::STATUS_ERROR,
                'error' => $e->getMessage(),
                'last_check' => now(),
            ];
        }
    }

    /**
     * Balance load across routers in coverage area
     */
    public function balanceLoad(CoverageArea $coverageArea): array
    {
        $routers = $coverageArea->availableRouters()->get();

        if ($routers->count() < 2) {
            return [
                'success' => false,
                'message' => 'Need at least 2 routers for load balancing',
            ];
        }

        // Calculate average load
        $totalUsers = $routers->sum('current_users');
        $totalCapacity = $routers->sum('max_capacity');
        $targetPercentage = ($totalUsers / $totalCapacity) * 100;

        $moved = 0;
        $errors = [];

        // Find overloaded routers
        foreach ($routers as $router) {
            if ($router->getCapacityPercentage() > $targetPercentage + 10) {
                // Find customers to move
                $customersToMove = $router->customers()
                    ->where('is_active', true)
                    ->limit(5)
                    ->get();

                foreach ($customersToMove as $customer) {
                    // Find least loaded router
                    $targetRouter = $routers
                        ->where('id', '!=', $router->id)
                        ->sortBy(fn($r) => $r->getCapacityPercentage())
                        ->first();

                    if ($targetRouter && $targetRouter->isAvailable()) {
                        $result = $this->reassignCustomer($customer, $targetRouter);
                        if ($result['success']) {
                            $moved++;
                        } else {
                            $errors[] = $result['message'];
                        }
                    }
                }
            }
        }

        return [
            'success' => true,
            'message' => "Load balancing completed. Moved {$moved} customers.",
            'moved' => $moved,
            'errors' => $errors,
        ];
    }

    /**
     * Generate username for customer
     */
    protected function generateUsername(Customer $customer, Router $router): string
    {
        $format = config('network.username_format', 'customer_{id}');

        return str_replace(
            ['{id}', '{router_id}', '{name}'],
            [$customer->id, $router->id, strtolower(str_replace(' ', '', $customer->name))],
            $format
        );
    }

    /**
     * Generate random password
     */
    protected function generatePassword(int $length = 12): string
    {
        $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $password = '';

        for ($i = 0; $i < $length; $i++) {
            $password .= $characters[rand(0, strlen($characters) - 1)];
        }

        return $password;
    }

    /**
     * Get router statistics
     */
    public function getRouterStatistics(): array
    {
        return [
            'total_routers' => Router::count(),
            'active_routers' => Router::active()->count(),
            'available_routers' => Router::available()->count(),
            'total_capacity' => Router::sum('max_capacity'),
            'total_users' => Router::sum('current_users'),
            'average_load' => Router::avg(DB::raw('(current_users / max_capacity) * 100')),
            'routers_near_capacity' => Router::active()
                ->whereRaw('(current_users / max_capacity) >= 0.9')
                ->count(),
        ];
    }
}
