<?php

namespace App\Console\Commands;

use App\Jobs\SendPaymentReminder;
use App\Models\Invoice;
use Illuminate\Console\Command;
use Carbon\Carbon;

class SendPaymentReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'billing:send-payment-reminders
                            {--type=all : Reminder type: all, before_due, due_date, overdue}
                            {--days=7 : Days before due date for before_due reminders}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send payment reminders to customers with unpaid invoices';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting payment reminder process...');

        $type = $this->option('type');
        $days = (int) $this->option('days');

        $totalSent = 0;

        // Send reminders based on type
        if ($type === 'all' || $type === 'before_due') {
            $count = $this->sendBeforeDueReminders($days);
            $totalSent += $count;
            $this->info("Sent {$count} before-due reminders (H-{$days})");
        }

        if ($type === 'all' || $type === 'due_date') {
            $count = $this->sendDueDateReminders();
            $totalSent += $count;
            $this->info("Sent {$count} due-date reminders");
        }

        if ($type === 'all' || $type === 'overdue') {
            $count = $this->sendOverdueReminders();
            $totalSent += $count;
            $this->info("Sent {$count} overdue reminders");
        }

        $this->info("Total reminders sent: {$totalSent}");

        return Command::SUCCESS;
    }

    /**
     * Send reminders for invoices due in X days
     */
    private function sendBeforeDueReminders($days): int
    {
        $targetDate = Carbon::now()->addDays($days)->toDateString();

        $invoices = Invoice::where('status', 'unpaid')
            ->whereDate('due_date', $targetDate)
            ->get();

        $count = 0;
        foreach ($invoices as $invoice) {
            SendPaymentReminder::dispatch($invoice->id, 'before_due');
            $count++;
        }

        return $count;
    }

    /**
     * Send reminders for invoices due today
     */
    private function sendDueDateReminders(): int
    {
        $today = Carbon::now()->toDateString();

        $invoices = Invoice::where('status', 'unpaid')
            ->whereDate('due_date', $today)
            ->get();

        $count = 0;
        foreach ($invoices as $invoice) {
            SendPaymentReminder::dispatch($invoice->id, 'due_date');
            $count++;
        }

        return $count;
    }

    /**
     * Send reminders for overdue invoices
     */
    private function sendOverdueReminders(): int
    {
        $today = Carbon::now()->toDateString();

        $invoices = Invoice::where('status', 'unpaid')
            ->whereDate('due_date', '<', $today)
            ->get();

        $count = 0;
        foreach ($invoices as $invoice) {
            SendPaymentReminder::dispatch($invoice->id, 'overdue');
            $count++;
        }

        return $count;
    }
}
