<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ContactController extends Controller
{
    /**
     * Display the Contact Us page.
     */
    public function index(): View
    {
        $whatsappNumber = Setting::get('whatsapp_number', '963932534193');
        $instagramUrl = Setting::get('instagram_url', 'https://instagram.com');
        $storePhone = Setting::get('store_phone', '+963 932 534 193');
        $storeEmail = Setting::get('store_email', 'info@kashk-alward.sy');
        $storeAddress = Setting::get('store_address', 'سوريا - دمشق');

        return view('contact', compact(
            'whatsappNumber',
            'instagramUrl',
            'storePhone',
            'storeEmail',
            'storeAddress'
        ));
    }

    /**
     * Handle incoming contact form submission.
     */
    public function send(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'contact' => ['required', 'string', 'max:150'],
            'subject' => ['nullable', 'string', 'max:200'],
            'message' => ['required', 'string', 'max:2000'],
        ], [
            'name.required' => 'يرجى إدخال اسمك الكريم.',
            'contact.required' => 'يرجى إدخال رقم الهاتف أو البريد الإلكتروني للتواصل.',
            'message.required' => 'يرجى كتابة نص الرسالة أو الاستفسار.',
        ]);

        // You can log or send notification here
        // TODO: Notification / Email sending if configured

        return back()->with('success', 'شكراً لتواصلك معنا! تم استلام رسالتك بنجاح وسنقوم بالرد عليك في أقرب وقت.');
    }
}
