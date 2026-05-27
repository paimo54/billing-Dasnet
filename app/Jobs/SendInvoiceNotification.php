<?php

namespace App\Jobs;

use App\Models\Customer;
use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendInvoiceNotification implements ShouldQueue
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
    public $timeout = 120;

    protected $customerId;
    protected $invoiceNumber;

    /**
     * Create a new job instance.
     */
    public function __construct($customerId, $invoiceNumber)
    {
        $this->customerId = $customerId;
        $this->invoiceNumber = $invoiceNumber;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $customer = Customer::find($this->customerId);

            if (!$customer) {
                Log::warning("Customer not found for invoice notification", [
                    'customer_id' => $this->customerId
                ]);
                return;
            }

            $invoice = Invoice::with(['package'])
                ->where('invoice_number', $this->invoiceNumber)
                ->where('customer_id', $this->customerId)
                ->first();

            if (!$invoice) {
                Log::warning("Invoice not found for notification", [
                    'invoice_number' => $this->invoiceNumber,
                    'customer_id' => $this->customerId
                ]);
                return;
            }

            // Prepare notification data
            $notificationData = [
                'customer_name' => $customer->name,
                'invoice_number' => $invoice->invoice_number,
                'package_name' => $invoice->package->name,
                'amount' => $invoice->total_amount,
                'invoice_date' => $invoice->invoice_date->format('d-m-Y'),
                'due_date' => $invoice->due_date->format('d-m-Y'),
                'payment_methods' => $this->getPaymentMethods(),
            ];

            // Send email notification
            if ($customer->email) {
                $this->sendEmailNotification($customer->email, $notificationData);
            }

            // Send SMS notification
            if ($customer->phone) {
                $this->sendSmsNotification($customer->phone, $notificationData);
            }

            Log::info('Invoice notification sent successfully', [
                'customer_id' => $this->customerId,
                'invoice_number' => $this->invoiceNumber
            ]);

        } catch (\Exception $e) {
            Log::error('Error sending invoice notification', [
                'customer_id' => $this->customerId,
                'invoice_number' => $this->invoiceNumber,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Send email notification
     */
    private function sendEmailNotification($email, $data): void
    {
        try {
            // TODO: Implement actual email sending with Mail facade
            // Mail::to($email)->send(new InvoiceCreatedMail($data));

            Log::info('Invoice email notification sent', [
                'email' => $email,
                'invoice_number' => $data['invoice_number']
            ]);

        } catch (\Exception $e) {
            Log::error('Error sending invoice email', [
                'email' => $email,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Send SMS notification
     */
    private function sendSmsNotification($phone, $data): void
    {
        try {
            // TODO: Implement SMS gateway integration
            $message = "Invoice baru telah dibuat.\n";
            $message .= "No: {$data['invoice_number']}\n";
            $message .= "Paket: {$data['package_name']}\n";
            $message .= "Total: Rp " . number_format($data['amount'], 0, ',', '.') . "\n";
            $message .= "Jatuh Tempo: {$data['due_date']}\n";
            $message .= "Mohon segera lakukan pembayaran.";

            Log::info('Invoice SMS notification queued', [
                'phone' => $phone,
                'invoice_number' => $data['invoice_number']
            ]);

        } catch (\Exception $e) {
            Log::error('Error sending invoice SMS', [
                'phone' => $phone,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get available payment methods
     */
    private function getPaymentMethods(): array
    {
        // TODO: Get from settings or configuration
        return [
            [
                'name' => 'Transfer Bank',
                'details' => 'BCA 1234567890 a.n. PT ISP Indonesia'
            ],
            [
                'name' => 'Virtual Account',
                'details' => 'Akan tersedia setelah integrasi payment gateway'
            ],
        ];
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('SendInvoiceNotification job failed', [
            'customer_id' => $this->customerId,
            'invoice_number' => $this->invoiceNumber,
            'error' => $exception->getMessage()
        ]);
    }
}
