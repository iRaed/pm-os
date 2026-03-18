<?php

declare(strict_types=1);

namespace App\Jobs\Tenant;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\Collection\Models\Invoice;

/**
 * فحص الفواتير المتأخرة وتحديث حالتها + إنشاء تنبيهات
 */
class CheckOverdueInvoices implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $queue = 'default';

    public function handle(): void
    {
        // تحديث حالة الفواتير المتأخرة
        $overdueInvoices = Invoice::whereIn('status', [
                Invoice::STATUS_ISSUED,
                Invoice::STATUS_SENT,
                Invoice::STATUS_PARTIALLY_PAID,
            ])
            ->where('due_date', '<', now())
            ->get();

        foreach ($overdueInvoices as $invoice) {
            $invoice->update(['status' => Invoice::STATUS_OVERDUE]);

            // إنشاء تنبيه
            $invoice->alerts()->create([
                'type' => 'payment_overdue',
                'severity' => $this->getSeverity($invoice),
                'title' => "فاتورة متأخرة: {$invoice->invoice_number}",
                'message' => "الفاتورة رقم {$invoice->invoice_number} بمبلغ {$invoice->balance} ر.س متأخرة منذ {$invoice->days_overdue} يوم",
                'trigger_date' => now(),
                'is_auto_generated' => true,
                'metadata' => [
                    'invoice_id' => $invoice->id,
                    'resident_id' => $invoice->resident_id,
                    'amount' => $invoice->balance,
                    'days_overdue' => $invoice->days_overdue,
                ],
            ]);
        }

        logger()->info("CheckOverdueInvoices: Updated {$overdueInvoices->count()} invoices");
    }

    private function getSeverity(Invoice $invoice): string
    {
        $days = $invoice->days_overdue;
        if ($days > 90) return 'critical';
        if ($days > 30) return 'warning';
        return 'info';
    }
}
