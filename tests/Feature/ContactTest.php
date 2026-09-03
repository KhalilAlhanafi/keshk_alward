<?php

use App\Models\Setting;
use Tests\TestCase;

/** @var TestCase $this */

test('contact page loads successfully with 200', function () {
    $response = $this->get('/contact');

    $response->assertStatus(200);
    $response->assertSee('اتصل بنا');
    $response->assertSee('معلومات التواصل');
    $response->assertSee('أرسل لنا رسالة');
});

test('contact form validates required fields', function () {
    $response = $this->from('/contact')->post('/contact', []);

    $response->assertRedirect('/contact');
    $response->assertSessionHasErrors(['name', 'contact', 'message']);
});

test('contact form submits successfully and returns success flash message', function () {
    $response = $this->from('/contact')->post('/contact', [
        'name' => 'سامر الأحمد',
        'contact' => '0999123456',
        'subject' => 'استفسار عن باقة أو منتج',
        'message' => 'مرحباً، أود معرفة ما إذا كان بالإمكان إضافة بطاقة إهداء مخصصة مع الباقة.',
    ]);

    $response->assertRedirect('/contact');
    $response->assertSessionHas('success');
});
