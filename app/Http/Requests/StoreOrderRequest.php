<?php

namespace App\Http\Requests;

use App\Enums\PaymentMethod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOrderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'recipient_name' => ['required', 'string', 'max:255'],
            'recipient_phone' => ['required', 'string', 'regex:/^\+9639\d{8}$/'],
            'delivery_area_id' => ['required', 'exists:delivery_areas,id'],
            'delivery_address' => ['required', 'string', 'max:1000'],
            'delivery_date' => ['required', 'date', 'after_or_equal:today'],
            'delivery_time_slot' => ['required', 'string', Rule::in([
                '09:00 - 12:00',
                '12:00 - 15:00',
                '15:00 - 18:00',
                '18:00 - 21:00',
            ])],
            'card_message' => ['nullable', 'string', 'max:500'],
            'payment_method' => ['required', Rule::enum(PaymentMethod::class)],
            'idempotency_key' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * Custom validation logic.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $deliveryDate = $this->input('delivery_date');
            if ($deliveryDate) {
                $today = now()->setTimezone('Asia/Damascus')->format('Y-m-d');
                if ($deliveryDate === $today) {
                    $currentHour = now()->setTimezone('Asia/Damascus')->hour;
                    // Cutoff time of 18:00 (6 PM) for same-day delivery
                    if ($currentHour >= 18) {
                        $validator->errors()->add('delivery_date', 'عذراً، انتهى وقت قبول طلبات التوصيل لنفس اليوم (بعد الساعة 6 مساءً). يرجى اختيار تاريخ مستقبلي.');
                    }
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'recipient_phone.regex' => 'رقم هاتف المستلم يجب أن يكون بصيغة +9639xxxxxxxx.',
            'delivery_date.after_or_equal' => 'تاريخ التوصيل يجب أن يكون اليوم أو في المستقبل.',
            'delivery_time_slot.in' => 'الرجاء اختيار وقت توصيل صحيح من الخيارات المتاحة.',
        ];
    }
}
