<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Services\NetworkService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class NetworkController extends Controller
{
    protected $networkService;

    public function __construct(NetworkService $networkService)
    {
        $this->networkService = $networkService;
    }

    /**
     * Suspend customer manually
     */
    public function suspend($customerId)
    {
        try {
            $customer = Customer::findOrFail($customerId);

            $result = $this->networkService->suspendCustomer($customer);

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => 'Customer suspended successfully',
                    'method_used' => $result['method_used'],
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to suspend customer',
                'result' => $result,
            ], 500);

        } catch (\Exception $e) {
            Log::error('Manual suspend failed', [
                'customer_id' => $customerId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Unsuspend customer manually
     */
    public function unsuspend($customerId)
    {
        try {
            $customer = Customer::findOrFail($customerId);

            $result = $this->networkService->unsuspendCustomer($customer);

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => 'Customer unsuspended successfully',
                    'method_used' => $result['method_used'],
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to unsuspend customer',
                'result' => $result,
            ], 500);

        } catch (\Exception $e) {
            Log::error('Manual unsuspend failed', [
                'customer_id' => $customerId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get customer network status
     */
    public function status($customerId)
    {
        try {
            $customer = Customer::findOrFail($customerId);

            $status = $this->networkService->getCustomerStatus($customer);

            return response()->json([
                'success' => true,
                'status' => $status,
            ]);

        } catch (\Exception $e) {
            Log::error('Get status failed', [
                'customer_id' => $customerId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Batch suspend customers
     */
    public function batchSuspend(Request $request)
    {
        try {
            $customerIds = $request->input('customer_ids', []);

            if (empty($customerIds)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No customer IDs provided',
                ], 400);
            }

            $result = $this->networkService->batchSuspend($customerIds);

            return response()->json([
                'success' => true,
                'message' => 'Batch suspend completed',
                'result' => $result,
            ]);

        } catch (\Exception $e) {
            Log::error('Batch suspend failed', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ], 500);
        }
    }
}
