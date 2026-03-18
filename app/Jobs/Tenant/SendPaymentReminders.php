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
 * إرسال تذكيرات الدفع عبر SMS/WhatsApp/Email
 */
class SendPaymentReminders implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        // تذكير قبل 3 أيام من الاستحقاق
        $this->sendReminders(
            Invoice::whereIn('status', ['issued', 'sent'])
                ->whereDate('due_date', now()->addDays(3)->toDateString())
                ->with('resident')
                ->get(),
            'تذكير: فاتورة مستحقة خلال 3 أيام',
            'reminder_before_due'
        );

        // تذكير يوم الاستحقاق
        $this->sendReminders(
            Invoice::whereIn('status', ['issued', 'sent'])
                ->whereDate('due_date', now()->toDateString())
                ->with('resident')
                ->get(),
            'تذكير: فاتورة مستحقة اليوم',
            'reminder_due_today'
        );

        // تذكير بعد 3 أيام
        $this->sendReminders(
            Invoice::where('status', 'overdue')
                ->whereDate('due_date', now()->subDays(3)->toDateString())
                ->with('resident')
                ->get(),
            'تنبيه: فاتورة متأخرة 3 أيام',
            'reminder_overdue_3'
        );

        // تذكير بعد 7 أيام
        $this->sendReminders(
            Invoice::where('status', 'overdue')
                ->whereDate('due_date', now()->subDays(7)->toDateString())
                ->with('resident')
                ->get(),
            'تنبيه: فاتورة متأخرة 7 أيام',
            'reminder_overdue_7'
        );

        // تذكير بعد 14 يوم
        $this->sendReminders(
            Invoice::where('status', 'overdue')
                ->whereDate('due_date', now()->subDays(14)->toDateString())
                ->with('resident')
                ->get(),
            'إنذار: فاتورة متأخرة 14 يوم',
            'reminder_overdue_14'
        );

        // تذكير بعد 30 يوم — تصعيد
        $this->sendReminders(
            Invoice::where('status', 'overdue')
                ->whereDate('due_date', now()->subDays(30)->toDateString())
                ->with('resident')
                ->get(),
            'إنذار نهائي: فاتورة متأخرة 30 يوم',
            'reminder_overdue_30'
        );
    }

    private function sendReminders($invoices, string $subject, string $templateSlug): void
    {
        foreach ($invoices as $invoice) {
            if (!$invoice->resident) continue;

            // تسجيل المراسلة
            $invoice->resident->communications()->create([
                'channel' => 'sms',
                'direction' => 'outbound',
                'subject' => $subject,
                'body' => $this->buildMessage($invoice, $templateSlug),
                'template_id' => $templateSlug,
                'recipient_type' => 'resident',
                'recipient_id' => $invoice->resident->id,
                'recipient_contact' => $invoice->resident->phone,
                'status' => 'queued',
            ]);

            // TODO: dispatch actual SMS/WhatsApp via Unifonic/WhatsApp Business API
            // dispatch(new SendSms($invoice->resident->phone, $message));
        }

        if ($invoices->count() > 0) {
            logger()->info("SendPaymentReminders [{$templateSlug}]: Sent {$invoices->count()} reminders");
        }
    }

    private function buildMessage(Invoice $invoice, string $template): string
    {
        $name = $invoice->resident->name;
        $amount = number_format((float) $invoice->balance, 2);
        $number = $invoice->invoice_number;
        $dueDate = $invoice->due_date->format('Y/m/d');

        return match (true) {
            str_contains($template, 'before') =>
                "السلام عليكم {$name}، نذكّرك بأن الفاتورة رقم {$number} بمبلغ {$amount} ر.س تستحق بتاريخ {$dueDate}. نرجو السداد في الموعد.",

            str_contains($template, 'today') =>
                "السلام عليكم {$name}، الفاتورة رقم {$number} بمبلغ {$amount} ر.س مستحقة اليوم. نرجو المبادرة بالسداد.",

            str_contains($template, 'overdue_30') =>
                "تنبيه أخير: {$name}، الفاتورة رقم {$number} بمبلغ {$amount} ر.س متأخرة 30 يوم. سيتم اتخاذ الإجراءات النظامية في حال عدم السداد.",

            default =>
                "تذكير: {$name}، الفاتورة رقم {$number} بمبلغ {$amount} ر.س متأخرة عن موعد الاستحقاق. نرجو المبادرة بالسداد.",
        };
    }
}
