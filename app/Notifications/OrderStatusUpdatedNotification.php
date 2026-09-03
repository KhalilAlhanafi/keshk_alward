<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderStatusUpdatedNotification extends Notification
{
    use Queueable;

    public Order $order;
    public string $messageAr;
    public string $type;

    public function __construct(Order $order, string $messageAr, string $type = 'status_updated')
    {
        $this->order = $order;
        $this->messageAr = $messageAr;
        $this->type = $type;
    }

    /**
     * Delivery channels: database for in-app bell icon, mail for email if available.
     */
    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if (!empty($notifiable->email)) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    /**
     * Mail representation.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $statusLabel = $this->order->status?->labelAr() ?? 'محدث';

        return (new MailMessage)
            ->subject("تحديث بخصوص طلبك #{$this->order->order_number} — كشك الورد")
            ->greeting("أهلاً {$notifiable->name}،")
            ->line($this->messageAr)
            ->line("**رقم الطلب:** {$this->order->order_number}")
            ->line("**الحالة الحالية:** {$statusLabel}")
            ->action('عرض تفاصيل الطلب', url("/orders/{$this->order->id}"))
            ->line('شكراً لتسوقك من كشك الورد 🌹');
    }

    /**
     * Database notification payload for the in-app notification bell.
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => $this->type,
            'order_id' => $this->order->id,
            'order_number' => $this->order->order_number,
            'status' => $this->order->status?->value ?? (string)$this->order->status,
            'payment_status' => $this->order->payment_status?->value ?? (string)$this->order->payment_status,
            'rejection_reason' => $this->order->rejection_reason,
            'message_ar' => $this->messageAr,
            'url' => "/orders/{$this->order->id}",
        ];
    }
}
