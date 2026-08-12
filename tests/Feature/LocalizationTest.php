<?php

use App\Models\User;
use App\Models\DeliveryArea;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

test('timezone is set to Asia/Damascus', function () {
    expect(config('app.timezone'))->toBe('Asia/Damascus')
        ->and(now()->getTimezone()->getName())->toBe('Asia/Damascus');
});

test('validation errors are translated to Arabic', function () {
    Role::firstOrCreate(['name' => 'customer', 'guard_name' => 'web']);
    $user = User::create([
        'name' => 'مستخدم تجريبي',
        'phone' => '+963911111111',
        'password' => bcrypt('password'),
    ]);
    $user->assignRole('customer');

    $response = $this->actingAs($user)->postJson('/orders', [
        'recipient_name' => '', // missing field
        'recipient_phone' => '12345', // invalid format
    ]);

    $response->assertStatus(422);

    // Verify errors are in Arabic
    $errors = $response->json('errors');
    expect($errors['recipient_name'][0])->toBe('الحقل اسم المستلم مطلوب.')
        ->and($errors['recipient_phone'][0])->toBe('رقم هاتف المستلم يجب أن يكون بصيغة +9639xxxxxxxx.');
});

test('Western Arabic numerals (0-9) are used in price responses', function () {
    $amount = 125000;
    $formatted = format_money($amount);

    // Verify it uses Western numerals (0-9) and correct currency suffix (ل.س.)
    expect($formatted)->toBe('125,000 ل.س.')
        ->and($formatted)->not->toContain('١')
        ->and($formatted)->not->toContain('٢')
        ->and($formatted)->not->toContain('٥');
});
