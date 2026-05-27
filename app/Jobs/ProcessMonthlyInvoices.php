<?php

namespace App\Jobs;

use App\Models\Customer;
use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ProcessMonthlyInvoices implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 3600; // 1 hour for processing thousands of invoices

    protected $billingDate;
    protected $month;
    protected $year;

    /**
     * Create a new job instance.
     */
    public function __construct($billingDate = null, $month = null, $year = null)
    {
        $this->billingDate = $billingDate;
        $this->month = $month ?? now()->month;
        $this->year = $year ?? now()->year;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('Starting monthly invoice generation', [
            'billing_date' => $this->billingDate,
            'month' => $this->month,
            'year' => $this->year
        ]);

        $processedCount = 0;
        $errorCount = 0;

        try {
            // Query customers yang perlu di-generate invoice
            $query = Customer::with(['package', 'creator'])
                ->where('is_active', true);

            if ($this->billingDate) {
                $query->where('billing_date', $this->billingDate);
            }

            // Process in chunks untuk menghindari memory issues
            $query->chunk(100, function ($customers) use (&$processedCount, &$errorCount) {
                foreach ($customers as $customer) {
                    try {
                        DB::beginTransaction();

                        // Check if invoice already exists for this period
                        $invoiceDate = Carbon::create($this->year, $this->month, $customer->billing_date);

                        $existingInvoice = Invoice::where('customer_id', $customer->id)
                            ->whereYear('invoice_date', $this->year)
                            ->whereMonth('invoice_date', $this->month)
                            ->first();

                        if ($existingInvoice) {
                            Log::info("Invoice already exists for customer {$customer->id}");
                            continue;
                        }

                        // Generate invoice number
                        $invoiceNumber = $this->generateInvoiceNumber($customer, $invoiceDate);

                        // Calculate amounts
                        $packagePrice = $customer->package->price;
                        $taxPercentage = Invoice::PPN_RATE * 100;
                        $basePrice = round($packagePrice / (1 + Invoice::PPN_RATE), 2);
                        $taxAmount = round($packagePrice - $basePrice, 2);

                        // Get technician fee from creator
                        $technicianFeePercentage = $customer->creator->technician_fee ?? 0;
                        $technicianFeeAmount = round($basePrice * ($technicianFeePercentage / 100), 2);

                        // Create invoice
                        Invoice::create([
                            'invoice_number' => $invoiceNumber,
                            'customer_id' => $customer->id,
                            'package_id' => $customer->package_id,
                            'invoice_date' => $invoiceDate,
                            'due_date' => $invoiceDate->copy()->addDays(7),
                            'amount' => $packagePrice,
                            'tax_percentage' => $taxPercentage,
                            'tax_amount' => $taxAmount,
                            'technician_fee_percentage' => $technicianFeePercentage,
                            'technician_fee_amount' => $technicianFeeAmount,
                            'total_amount' => $packagePrice,
                            'status' => 'unpaid',
                            'created_by' => $customer->created_by,
                        ]);

                        DB::commit();
                        $processedCount++;

                        // Dispatch notification job
                        SendInvoiceNotification::dispatch($customer->id, $invoiceNumber);

                    } catch (\Exception $e) {
                        DB::rollBack();
                        $errorCount++;
                        Log::error('Error generating invoice for customer', [
                            'customer_id' => $customer->id,
                            'error' => $e->getMessage()
                        ]);
                    }
                }
            });

            Log::info('Monthly invoice generation completed', [
                'processed' => $processedCount,
                'errors' => $errorCount
            ]);

        } catch (\Exception $e) {
            Log::error('Fatal error in monthly invoice generation', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Generate unique invoice number
     */
    private function generateInvoiceNumber($customer, $invoiceDate): string
    {
        $prefix = 'INV';
        $date = $invoiceDate->format('Ymd');
        $customerId = str_pad($customer->id, 5, '0', STR_PAD_LEFT);

        return "{$prefix}/{$date}/{$customerId}";
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('ProcessMonthlyInvoices job failed', [
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);
    }
}
