<?php

namespace App\Console\Commands;

use App\Models\Customer;
use App\Models\Invoice;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class AutoSuspendOverdueCustomers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'billing:auto-suspend
                            {--grace-days=7 : Grace period in days after due date}
                            {--dry-run : Run without actually suspending customers}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automatically suspend customers with overdue invoices';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $graceDays = (int) $this->option('grace-days');
        $dryRun = $this->option('dry-run');

        $this->info('Starting auto-suspend process...');
        $this->info("Grace period: {$graceDays} days");

        if ($dryRun) {
            $this->warn('DRY RUN MODE - No customers will be suspended');
        }

        $suspendDate = Carbon::now()->subDays($graceDays)->toDateString();

        // Find customers with overdue invoices beyond grace period
        $overdueInvoices = Invoice::where('status', 'unpaid')
            ->whereDate('due_date', '<=', $suspendDate)
            ->with('customer')
            ->get();

        $customerIds = $overdueInvoices->pluck('customer_id')->unique();

        $this->info("Found {$customerIds->count()} customers with overdue invoices");

        $suspendedCount = 0;
        $alreadySuspendedCount = 0;
        $errorCount = 0;

        foreach ($customerIds as $customerId) {
            $customer = Customer::find($customerId);

            if (!$customer) {
                continue;
            }

            // Skip if already suspended
            if (!$customer->is_active) {
                $alreadySuspendedCount++;
                continue;
            }

            // Get overdue amount
            $overdueAmount = Invoice::where('customer_id', $customerId)
                ->where('status', 'unpaid')
                ->whereDate('due_date', '<=', $suspendDate)
                ->sum('total_amount');

            $this->line("Customer: {$customer->name} (ID: {$customer->id})");
            $this->line("  Overdue Amount: Rp " . number_format($overdueAmount, 0, ',', '.'));

            if (!$dryRun) {
                try {
                    // Suspend customer
                    $customer->is_active = false;
                    $customer->save();

                    // TODO: Integrate with Mikrotik to disable service
                    // $this->suspendMikrotikService($customer);

                    $suspendedCount++;

                    Log::info('Customer suspended due to overdue payment', [
                        'customer_id' => $customer->id,
                        'customer_name' => $customer->name,
                        'overdue_amount' => $overdueAmount,
                        'grace_days' => $graceDays
                    ]);

                    $this->info("  ✓ Suspended");

                } catch (\Exception $e) {
                    $errorCount++;
                    $this->error("  ✗ Error: {$e->getMessage()}");

                    Log::error('Error suspending customer', [
                        'customer_id' => $customer->id,
                        'error' => $e->getMessage()
                    ]);
                }
            } else {
                $this->warn("  [DRY RUN] Would suspend this customer");
            }
        }

        $this->newLine();
        $this->info('=== Summary ===');
        $this->info("Customers suspended: {$suspendedCount}");
        $this->info("Already suspended: {$alreadySuspendedCount}");

        if ($errorCount > 0) {
            $this->error("Errors: {$errorCount}");
        }

        if ($dryRun) {
            $this->warn('This was a DRY RUN - no changes were made');
        }

        return Command::SUCCESS;
    }

    /**
     * Suspend service in Mikrotik
     * TODO: Implement Mikrotik API integration
     */
    private function suspendMikrotikService($customer): void
    {
        // This will be implemented when Mikrotik integration is added
        // Example:
        // $mikrotik = new MikrotikAPI();
        // $mikrotik->connect();
        // $mikrotik->disableUser($customer->id);
        // $mikrotik->disconnect();

        Log::info('Mikrotik suspension pending implementation', [
            'customer_id' => $customer->id
        ]);
    }
}
