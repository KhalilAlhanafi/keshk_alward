<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramNotifierService
{
    private string $apiBase;
    private ?string $chatId;

    public function __construct()
    {
        $token = config('services.telegram.bot_token');
        $this->chatId = config('services.telegram.chat_id');
        $this->apiBase = "https://api.telegram.org/bot{$token}";
    }

    /**
     * Send a new-order alert to the store owner's Telegram chat.
     * Silently logs and returns false on any failure — never throws.
     */
    public function sendOrderAlert(Order $order): bool
    {
        if (empty($this->chatId) || empty(config('services.telegram.bot_token'))) {
            Log::warning('TelegramNotifier: bot_token or chat_id not configured, skipping alert.');
            return false;
        }

        try {
            $text = $this->buildMessage($order);

            $response = Http::timeout(10)->post("{$this->apiBase}/sendMessage", [
                'chat_id'    => $this->chatId,
                'text'       => $text,
                'parse_mode' => 'HTML',
            ]);

            if (! $response->successful()) {
                Log::error('TelegramNotifier: API call failed.', [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                    'order'  => $order->order_number,
                ]);
                return false;
            }

            return true;

        } catch (\Throwable $e) {
            Log::error('TelegramNotifier: Exception while sending alert.', [
                'message' => $e->getMessage(),
                'order'   => $order->order_number,
            ]);
            return false;
        }
    }

    public function sendOrderCancelledAlert(Order $order): bool
    {
        if (empty($this->chatId) || empty(config('services.telegram.bot_token'))) {
            return false;
        }

        try {
            $text = implode("\n", [
                '🚫 <b>إلغاء طلب — كشك الورد</b>',
                '',
                "تم إلغاء الطلب رقم: <b>{$order->order_number}</b>",
            ]);

            $response = Http::timeout(10)->post("{$this->apiBase}/sendMessage", [
                'chat_id'    => $this->chatId,
                'text'       => $text,
                'parse_mode' => 'HTML',
            ]);

            return $response->successful();
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Send an alert when a customer uploads proof of payment.
     */
    public function sendPaymentProofUploadedAlert(Order $order): bool
    {
        if (empty($this->chatId) || empty(config('services.telegram.bot_token'))) {
            return false;
        }

        try {
            $lines = [
                '🧾 <b>إشعار إثبات دفع — كشك الورد</b>',
                '',
                "قام العميل برفع إثبات دفع للطلب رقم: <b>{$order->order_number}</b>",
                "المستلم: <b>{$order->recipient_name}</b>",
                "المبلغ المطلوب: <b>" . format_money($order->total) . "</b>",
            ];

            if ($order->transaction_number) {
                $lines[] = "رقم العملية: <code>{$order->transaction_number}</code>";
            }
            if ($order->payment_proof) {
                $lines[] = "صورة الإيصال: تم إرفاقها بنجاح";
            }

            $response = Http::timeout(10)->post("{$this->apiBase}/sendMessage", [
                'chat_id'    => $this->chatId,
                'text'       => implode("\n", $lines),
                'parse_mode' => 'HTML',
            ]);

            return $response->successful();
        } catch (\Throwable $e) {
            Log::error('TelegramNotifier: Exception while sending proof alert.', [
                'message' => $e->getMessage(),
                'order'   => $order->order_number,
            ]);
            return false;
        }
    }

    /**
     * Build the Telegram message text for a new order.
     * Follows Global Conventions: Western numerals + ل.س currency.
     */
    private function buildMessage(Order $order): string
    {
        // Load relations if not already loaded
        $order->loadMissing(['deliveryArea', 'items']);

        // Build items list
        $itemsText = $order->items->map(function ($item) {
            $size = $item->size_label_snapshot ? " ({$item->size_label_snapshot})" : '';
            return "• {$item->product_name_snapshot}{$size} × {$item->quantity}";
        })->implode("\n");

        $deliveryArea = $order->deliveryArea
            ? "{$order->deliveryArea->city_ar} - {$order->deliveryArea->area_ar}"
            : '—';

        $paymentMethod = match ($order->payment_method?->value ?? (string) $order->payment_method) {
            'cod', 'cash_on_delivery' => 'الدفع عند الاستلام',
            'sham_cash'               => 'شام كاش 📲',
            default                   => $order->payment_method?->labelAr() ?? (string) $order->payment_method,
        };

        $paymentStatus = match ($order->payment_status?->value ?? (string) $order->payment_status) {
            'pending'               => 'قيد الانتظار ⏳',
            'awaiting_verification' => 'بانتظار التحقق من الإيصال ⏳',
            'verified', 'paid'      => 'تم التحقق والدفع ✅',
            'rejected', 'failed'    => 'مرفوض / فشل الدفع ❌',
            default                 => $order->payment_status?->labelAr() ?? (string) $order->payment_status,
        };

        $deliveryDate = $order->delivery_date
            ? $order->delivery_date->format('Y-m-d')
            : '—';

        return implode("\n", [
            '🌹 <b>طلب جديد — كشك الورد</b>',
            '',
            "📋 <b>رقم الطلب:</b> {$order->order_number}",
            "👤 <b>المستلم:</b> {$order->recipient_name}",
            "📞 <b>هاتف:</b> {$order->recipient_phone}",
            "📍 <b>المنطقة:</b> {$deliveryArea}",
            '',
            '🛒 <b>المنتجات:</b>',
            $itemsText,
            '',
            "💰 <b>الإجمالي:</b> " . format_money($order->total),
            "💳 <b>الدفع:</b> {$paymentMethod}",
            "🔖 <b>حالة الدفع:</b> {$paymentStatus}",
            '',
            "📅 <b>التوصيل:</b> {$deliveryDate}",
            "⏰ <b>الوقت:</b> {$order->delivery_time_slot}",
        ]);
    }
}
