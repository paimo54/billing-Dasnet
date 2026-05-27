<?php

namespace App\Console\Commands;

use App\Models\Payment;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class AutoExpirePayments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'payment:auto-expire
                            {--dry-run : Run without actually expiring payments}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automatically expire pending payments that have passed their expiry time';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');

        $this->info('Starting auto-expire payments process...');

        if ($dryRun) {
            $this->warn('DRY RUN MODE - No payments will be expired');
        }

        $now = Carbon::now();

        // Find pending payments that have expired
        $expiredPayments = Payment::where('status', Payment::STATUS_PENDING)
            ->where('expired_at', '<=', $now)
            ->with(['invoice', 'customer'])
            ->get();

        $this->info("Found {$expiredPayments->count()} expired payments");

        $expiredCount = 0;
        $errorCount = 0;

        foreach ($expiredPayments as $payment) {
            $this->line("Payment ID: {$payment->id}");
            $this->line("  Transaction: {$payment->transaction_id}");
            $this->line("  Invoice: {$payment->invoice->invoice_number}");
            $this->line("  Customer: {$payment->customer->name}");
            $this->line("  Amount: Rp " . number_format($payment->total_amount, 0, ',', '.'));
            $this->line("  Expired at: {$payment->expired_at->format('Y-m-d H:i:s')}");

            if (!$dryRun) {
                try {
                    $payment->markAsExpired();
                    $expiredCount++;

                    Log::info('Payment expired automatically', [
                        'payment_id' => $payment->id,
                        'transaction_id' => $payment->transaction_id,
                        'invoice_id' => $payment->invoice_id,
                    ]);

                    $this->info("  ✓ Expired");

                } catch (\Exception $e) {
                    $errorCount++;
                    $this->error("  ✗ Error: {$e->getMessage()}");

                    Log::error('Error expiring payment', [
                        'payment_id' => $payment->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            } else {
                $this->warn("  [DRY RUN] Would expire this payment");
            }

            $this->newLine();
        }

        $this->info('=== Summary ===');
        $this->info("Payments expired: {$expiredCount}");

        if ($errorCount > 0) {
            $this->error("Errors: {$errorCount}");
        }

        if ($dryRun) {
            $this->warn('This was a DRY RUN - no changes were made');
        }

        return Command::SUCCESS;
    }
}
