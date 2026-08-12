<?php

namespace App\Notifications;

use App\Models\Order;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewOrderAdminNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public Order $order;

    public function __construct(Order $order)
    {
        $this->order = $order;
        $this->onQueue('notifications');
    }

    public function via(object $notifiable): array
    {
        $channels = ['database'];
        if ($notifiable->email) {
            $channels[] = 'mail';
        }
        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $order = $this->order->loadMissing(['items', 'user', 'deliveryArea']);

        return (new MailMessage)
            ->subject("طلب جديد #{$order->order_number} — كشك الورد")
            ->greeting("مرحباً {$notifiable->name}،")
            ->line("وردنا طلب جديد يحتاج للمتابعة.")
            ->line("**رقم الطلب:** {$order->order_number}")
            ->line("**العميل:** " . ($order->user?->name ?? 'زائر'))
            ->line("**المستلم:** {$order->recipient_name} ({$order->recipient_phone})")
            ->line("**المجموع الكلي:** {$order->formatted_total}")
            ->line("**تاريخ التوصيل:** {$order->delivery_date->format('Y-m-d')} — {$order->delivery_time_slot}")
            ->action('إدارة الطلب', url("/admin/orders/{$order->id}"))
            ->line('يرجى معالجة الطلب في أقرب وقت ممكن.');
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'new_order',
            'order_id' => $this->order->id,
            'order_number' => $this->order->order_number,
            'total' => $this->order->total,
            'formatted_total' => $this->order->formatted_total,
            'recipient_name' => $this->order->recipient_name,
            'message_ar' => "طلب جديد {$this->order->order_number} بقيمة {$this->order->formatted_total}.",
            'url' => "/admin/orders/{$this->order->id}",
        ];
    }
}
