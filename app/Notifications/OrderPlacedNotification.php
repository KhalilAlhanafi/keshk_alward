<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderPlacedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public Order $order;

    public function __construct(Order $order)
    {
        $this->order = $order;
        // Use the dedicated notifications queue
        $this->onQueue('notifications');
    }

    /**
     * Delivery channels: database for in-app bell icon, mail for email receipt.
     */
    public function via(object $notifiable): array
    {
        $channels = ['database'];

        // Only send email if the user has an email address
        if ($notifiable->email) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    /**
     * Mail representation (sent to customer).
     */
    public function toMail(object $notifiable): MailMessage
    {
        $order = $this->order->loadMissing(['items.addons', 'deliveryArea']);

        return (new MailMessage)
            ->subject("تأكيد طلبك #{$order->order_number} — كشك الورد")
            ->greeting("أهلاً {$notifiable->name}،")
            ->line("تم استلام طلبك بنجاح وسيتم التحضير فوراً.")
            ->line("**رقم الطلب:** {$order->order_number}")
            ->line("**المجموع الكلي:** {$order->formatted_total}")
            ->line("**تاريخ التوصيل:** {$order->delivery_date->format('Y-m-d')}")
            ->line("**الشريحة الزمنية:** {$order->delivery_time_slot}")
            ->line("**وسيلة الدفع:** " . match($order->payment_method->value) {
                'cod' => 'الدفع عند الاستلام',
                'sham_cash' => 'شام كاش',
                default => $order->payment_method->value,
            })
            ->action('عرض تفاصيل الطلب', url("/orders/{$order->id}"))
            ->line('شكراً لتسوقك من كشك الورد 🌹');
    }

    /**
     * Database notification payload (used for in-app notification bell).
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'order_placed',
            'order_id' => $this->order->id,
            'order_number' => $this->order->order_number,
            'total' => $this->order->total,
            'formatted_total' => $this->order->formatted_total,
            'message_ar' => "تم تأكيد طلبك {$this->order->order_number} بنجاح.",
            'url' => "/orders/{$this->order->id}",
        ];
    }
}
