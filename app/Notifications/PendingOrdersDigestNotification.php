<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PendingOrdersDigestNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly int $pendingCount,
    ) {
        $this->onQueue('notifications');
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("كشك الورد — ملخص الطلبات المعلقة ({$this->pendingCount} طلب)")
            ->greeting("صباح الخير،")
            ->line("يوجد حالياً {$this->pendingCount} طلب في حالة معلقة بانتظار التأكيد.")
            ->action('فتح لوحة التحكم', url('/admin/orders?status=pending'))
            ->salutation('فريق كشك الورد');
    }

    /**
     * Get the in-app (database) representation of the notification.
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'pending_orders_digest',
            'title' => 'ملخص الطلبات المعلقة',
            'message' => "يوجد {$this->pendingCount} طلب بانتظار التأكيد.",
            'action_url' => '/admin/orders?status=pending',
        ];
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return $this->toDatabase($notifiable);
    }
}
