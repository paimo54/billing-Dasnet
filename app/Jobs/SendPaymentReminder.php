<?php

namespace App\Jobs;

use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;

class SendPaymentReminder implements ShouldQueue
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

    protected $invoiceId;
    protected $reminderType;

    /**
     * Create a new job instance.
     *
     * @param int $invoiceId
     * @param string $reminderType (before_due, due_date, overdue)
     */
    public function __construct($invoiceId, $reminderType = 'before_due')
    {
        $this->invoiceId = $invoiceId;
        $this->reminderType = $reminderType;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $invoice = Invoice::with(['customer', 'package'])->find($this->invoiceId);

            if (!$invoice) {
                Log::warning("Invoice not found for reminder", ['invoice_id' => $this->invoiceId]);
                return;
            }

            // Skip if already paid
            if ($invoice->status === 'paid') {
                Log::info("Invoice already paid, skipping reminder", ['invoice_id' => $this->invoiceId]);
                return;
            }

            $customer = $invoice->customer;

            if (!$customer->email) {
                Log::warning("Customer has no email, skipping reminder", [
                    'invoice_id' => $this->invoiceId,
                    'customer_id' => $customer->id
                ]);
                return;
            }

            // Prepare reminder data
            $reminderData = $this->prepareReminderData($invoice);

            // Send email notification
            // TODO: Implement actual email sending with Mail facade
            // Mail::to($customer->email)->send(new PaymentReminderMail($reminderData));

            Log::info('Payment reminder sent', [
                'invoice_id' => $this->invoiceId,
                'customer_id' => $customer->id,
                'reminder_type' => $this->reminderType,
                'email' => $customer->email
            ]);

            // TODO: Send SMS notification if phone number exists
            if ($customer->phone) {
                $this->sendSmsReminder($customer->phone, $reminderData);
            }

            // TODO: Send WhatsApp notification if configured
            // $this->sendWhatsAppReminder($customer->phone, $reminderData);

        } catch (\Exception $e) {
            Log::error('Error sending payment reminder', [
                'invoice_id' => $this->invoiceId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Prepare reminder data based on type
     */
    private function prepareReminderData($invoice): array
    {
        $daysUntilDue = Carbon::now()->diffInDays($invoice->due_date, false);
        $daysOverdue = $daysUntilDue < 0 ? abs($daysUntilDue) : 0;

        $subject = '';
        $message = '';

        switch ($this->reminderType) {
            case 'before_due':
                $subject = "Reminder: Invoice {$invoice->invoice_number} akan jatuh tempo";
                $message = "Invoice Anda akan jatuh tempo dalam {$daysUntilDue} hari.";
                break;

            case 'due_date':
                $subject = "Reminder: Invoice {$invoice->invoice_number} jatuh tempo hari ini";
                $message = "Invoice Anda jatuh tempo hari ini. Mohon segera lakukan pembayaran.";
                break;

            case 'overdue':
                $subject = "URGENT: Invoice {$invoice->invoice_number} sudah lewat jatuh tempo";
                $message = "Invoice Anda sudah lewat jatuh tempo {$daysOverdue} hari. Layanan Anda akan di-suspend jika tidak segera dibayar.";
                break;
        }

        return [
            'invoice_number' => $invoice->invoice_number,
            'customer_name' => $invoice->customer->name,
            'package_name' => $invoice->package->name,
            'amount' => $invoice->total_amount,
            'due_date' => $invoice->due_date->format('d-m-Y'),
            'days_until_due' => $daysUntilDue,
            'days_overdue' => $daysOverdue,
            'subject' => $subject,
            'message' => $message,
            'reminder_type' => $this->reminderType,
        ];
    }

    /**
     * Send SMS reminder
     */
    private function sendSmsReminder($phone, $reminderData): void
    {
        // TODO: Implement SMS gateway integration
        // Example: Twilio, Nexmo, or local SMS gateway

        Log::info('SMS reminder queued', [
            'phone' => $phone,
            'invoice_number' => $reminderData['invoice_number']
        ]);
    }

    /**
     * Send WhatsApp reminder
     */
    private function sendWhatsAppReminder($phone, $reminderData): void
    {
        // TODO: Implement WhatsApp Business API integration
        // Example: Twilio WhatsApp, WhatsApp Business API

        Log::info('WhatsApp reminder queued', [
            'phone' => $phone,
            'invoice_number' => $reminderData['invoice_number']
        ]);
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('SendPaymentReminder job failed', [
            'invoice_id' => $this->invoiceId,
            'reminder_type' => $this->reminderType,
            'error' => $exception->getMessage()
        ]);
    }
}
