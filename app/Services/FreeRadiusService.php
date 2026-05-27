<?php

namespace App\Services;

use App\Models\Customer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FreeRadiusService
{
    /**
     * Create RADIUS user for customer
     *
     * @param Customer $customer
     * @param string $password
     * @return bool
     */
    public function createUser(Customer $customer, string $password): bool
    {
        try {
            $username = $this->generateUsername($customer);

            DB::beginTransaction();

            // Check if user already exists
            $exists = DB::table('radcheck')
                ->where('username', $username)
                ->exists();

            if ($exists) {
                Log::warning('RADIUS user already exists', ['username' => $username]);
                DB::rollBack();
                return false;
            }

            // Insert authentication (radcheck)
            DB::table('radcheck')->insert([
                [
                    'username' => $username,
                    'attribute' => 'Cleartext-Password',
                    'op' => ':=',
                    'value' => $password,
                ],
                [
                    'username' => $username,
                    'attribute' => 'Auth-Type',
                    'op' => ':=',
                    'value' => 'Accept',
                ],
            ]);

            // Insert authorization (radreply) - bandwidth limits
            if ($customer->package) {
                $this->setBandwidthLimit($username, $customer->package->name);
            }

            // Add to user group if needed
            $this->addToGroup($username, 'customers');

            DB::commit();

            Log::info('RADIUS user created', [
                'username' => $username,
                'customer_id' => $customer->id,
            ]);

            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create RADIUS user', [
                'customer_id' => $customer->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Suspend user (instant)
     *
     * @param Customer $customer
     * @return bool
     */
    public function suspendUser(Customer $customer): bool
    {
        try {
            $username = $this->generateUsername($customer);

            // Update Auth-Type to Reject
            $updated = DB::table('radcheck')
                ->where('username', $username)
                ->where('attribute', 'Auth-Type')
                ->update(['value' => 'Reject']);

            if ($updated === 0) {
                // If Auth-Type doesn't exist, insert it
                DB::table('radcheck')->insert([
                    'username' => $username,
                    'attribute' => 'Auth-Type',
                    'op' => ':=',
                    'value' => 'Reject',
                ]);
            }

            // Send CoA (Change of Authorization) to disconnect active sessions
            $this->sendCoA($username, 'disconnect');

            Log::info('RADIUS user suspended', [
                'username' => $username,
                'customer_id' => $customer->id,
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to suspend RADIUS user', [
                'customer_id' => $customer->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Unsuspend user (instant)
     *
     * @param Customer $customer
     * @return bool
     */
    public function unsuspendUser(Customer $customer): bool
    {
        try {
            $username = $this->generateUsername($customer);

            // Update Auth-Type to Accept
            $updated = DB::table('radcheck')
                ->where('username', $username)
                ->where('attribute', 'Auth-Type')
                ->update(['value' => 'Accept']);

            if ($updated === 0) {
                // If Auth-Type doesn't exist, insert it
                DB::table('radcheck')->insert([
                    'username' => $username,
                    'attribute' => 'Auth-Type',
                    'op' => ':=',
                    'value' => 'Accept',
                ]);
            }

            Log::info('RADIUS user unsuspended', [
                'username' => $username,
                'customer_id' => $customer->id,
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to unsuspend RADIUS user', [
                'customer_id' => $customer->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Update user password
     *
     * @param Customer $customer
     * @param string $newPassword
     * @return bool
     */
    public function updatePassword(Customer $customer, string $newPassword): bool
    {
        try {
            $username = $this->generateUsername($customer);

            DB::table('radcheck')
                ->where('username', $username)
                ->where('attribute', 'Cleartext-Password')
                ->update(['value' => $newPassword]);

            Log::info('RADIUS password updated', [
                'username' => $username,
                'customer_id' => $customer->id,
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to update RADIUS password', [
                'customer_id' => $customer->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Set bandwidth limit for user
     *
     * @param string $username
     * @param string $packageName
     * @return bool
     */
    public function setBandwidthLimit(string $username, string $packageName): bool
    {
        try {
            // Parse bandwidth from package name (e.g., "10Mbps", "20Mbps")
            preg_match('/(\d+)\s*Mbps/i', $packageName, $matches);
            $bandwidth = $matches[1] ?? 10; // Default 10Mbps

            $downloadLimit = $bandwidth . 'M';
            $uploadLimit = $bandwidth . 'M';

            // Delete existing bandwidth limits
            DB::table('radreply')
                ->where('username', $username)
                ->whereIn('attribute', ['Mikrotik-Rate-Limit'])
                ->delete();

            // Insert new bandwidth limit (Mikrotik format)
            DB::table('radreply')->insert([
                'username' => $username,
                'attribute' => 'Mikrotik-Rate-Limit',
                'op' => '=',
                'value' => $downloadLimit . '/' . $uploadLimit,
            ]);

            Log::info('Bandwidth limit set', [
                'username' => $username,
                'bandwidth' => $bandwidth . 'Mbps',
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to set bandwidth limit', [
                'username' => $username,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Get user status
     *
     * @param Customer $customer
     * @return array
     */
    public function getUserStatus(Customer $customer): array
    {
        try {
            $username = $this->generateUsername($customer);

            // Get auth status
            $authType = DB::table('radcheck')
                ->where('username', $username)
                ->where('attribute', 'Auth-Type')
                ->value('value');

            // Get active sessions
            $activeSessions = DB::table('radacct')
                ->where('username', $username)
                ->whereNull('acctstoptime')
                ->count();

            // Get bandwidth usage (last 30 days)
            $usage = DB::table('radacct')
                ->where('username', $username)
                ->where('acctstarttime', '>=', now()->subDays(30))
                ->selectRaw('
                    SUM(acctinputoctets) as total_download,
                    SUM(acctoutputoctets) as total_upload,
                    SUM(acctinputoctets + acctoutputoctets) as total_usage
                ')
                ->first();

            return [
                'username' => $username,
                'status' => $authType === 'Accept' ? 'active' : 'suspended',
                'active_sessions' => $activeSessions,
                'total_download' => $usage->total_download ?? 0,
                'total_upload' => $usage->total_upload ?? 0,
                'total_usage' => $usage->total_usage ?? 0,
            ];

        } catch (\Exception $e) {
            Log::error('Failed to get user status', [
                'customer_id' => $customer->id,
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Delete user
     *
     * @param Customer $customer
     * @return bool
     */
    public function deleteUser(Customer $customer): bool
    {
        try {
            $username = $this->generateUsername($customer);

            DB::beginTransaction();

            // Delete from all tables
            DB::table('radcheck')->where('username', $username)->delete();
            DB::table('radreply')->where('username', $username)->delete();
            DB::table('radusergroup')->where('username', $username)->delete();

            // Note: Keep radacct for historical data

            DB::commit();

            Log::info('RADIUS user deleted', [
                'username' => $username,
                'customer_id' => $customer->id,
            ]);

            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to delete RADIUS user', [
                'customer_id' => $customer->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Batch suspend users (for auto-suspend command)
     *
     * @param array $customerIds
     * @return array
     */
    public function batchSuspend(array $customerIds): array
    {
        $suspended = 0;
        $failed = 0;

        foreach ($customerIds as $customerId) {
            $customer = Customer::find($customerId);
            if ($customer && $this->suspendUser($customer)) {
                $suspended++;
            } else {
                $failed++;
            }
        }

        return [
            'suspended' => $suspended,
            'failed' => $failed,
        ];
    }

    /**
     * Add user to group
     *
     * @param string $username
     * @param string $groupname
     * @param int $priority
     * @return bool
     */
    private function addToGroup(string $username, string $groupname, int $priority = 1): bool
    {
        try {
            DB::table('radusergroup')->insert([
                'username' => $username,
                'groupname' => $groupname,
                'priority' => $priority,
            ]);
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to add user to group', [
                'username' => $username,
                'groupname' => $groupname,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Send CoA (Change of Authorization) to disconnect user
     *
     * @param string $username
     * @param string $action
     * @return bool
     */
    private function sendCoA(string $username, string $action = 'disconnect'): bool
    {
        // TODO: Implement CoA using radclient or PHP RADIUS library
        // This requires radclient binary or PHP RADIUS extension
        // For now, just log the action

        Log::info('CoA requested', [
            'username' => $username,
            'action' => $action,
        ]);

        return true;
    }

    /**
     * Generate username from customer
     *
     * @param Customer $customer
     * @return string
     */
    private function generateUsername(Customer $customer): string
    {
        // Format: customer_{id} or use customer's phone/email
        return 'customer_' . $customer->id;
    }
}
