<?php

namespace App\Http\Controllers;

use App\Models\ContractReminder;
use App\Models\WhatsAppSetting;
use App\WhatsAppWebClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

class SettingsController extends Controller
{
    public function edit(): View
    {
        return view('settings.edit', [
            'whatsappSettings' => WhatsAppSetting::current(),
            'reminders' => ContractReminder::with('market')->latest()->limit(30)->get(),
        ]);
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $validatedMessages = [
            'current_password.required' => 'أدخل كلمة المرور الحالية.',
            'current_password.current_password' => 'كلمة المرور الحالية غير صحيحة.',
            'password.required' => 'أدخل كلمة المرور الجديدة.',
            'password.min' => 'يجب أن تتكون كلمة المرور من 8 أحرف على الأقل.',
            'password.confirmed' => 'تأكيد كلمة المرور الجديدة غير مطابق.',
        ];

        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], $validatedMessages, [
            'current_password' => 'كلمة المرور الحالية',
            'password' => 'كلمة المرور الجديدة',
        ]);

        $request->user()->update([
            'password' => $validated['password'],
        ]);

        return to_route('settings.edit')->with('success', 'تم تغيير كلمة المرور بنجاح.');
    }

    public function updateWhatsApp(Request $request): RedirectResponse
    {
        $numbers = array_values(array_filter(array_map(
            fn ($number): string => is_string($number) ? preg_replace('/[\s()+-]/', '', $number) : '',
            (array) $request->input('additional_numbers', []),
        ), fn (string $number): bool => $number !== ''));
        $request->merge(['additional_numbers' => $numbers]);
        $data = $request->validate([
            'enabled' => ['nullable', 'boolean'],
            'additional_numbers' => ['array', 'max:4'],
            'additional_numbers.*' => ['required', 'string', 'regex:/^[1-9][0-9]{7,14}$/', 'distinct', 'not_in:'.WhatsAppSetting::PRIMARY_NUMBER],
        ], [
            'additional_numbers.max' => 'يمكن إضافة أربعة أرقام أخرى فقط.',
            'additional_numbers.*.regex' => 'أدخل الرقم بالصيغة الدولية، مثل +962770911991.',
            'additional_numbers.*.distinct' => 'لا يمكن تكرار رقم المستلم.',
            'additional_numbers.*.not_in' => 'الرقم الأساسي موجود بالفعل.',
        ]);
        WhatsAppSetting::updateOrCreate(['id' => 1], [
            'enabled' => $request->boolean('enabled'),
            'additional_numbers' => $data['additional_numbers'],
        ]);

        return to_route('settings.edit')->with('success', 'تم حفظ إعدادات تنبيهات العقود.');
    }

    public function whatsappStatus(WhatsAppWebClient $client): JsonResponse
    {
        try {
            $status = $client->status();

            return response()->json($status)->header('Cache-Control', 'no-store');
        } catch (Throwable $exception) {
            report($exception);

            return response()->json(['connected' => false, 'status' => 'unavailable', 'message' => 'خدمة واتساب غير متاحة. راجع تشغيل الخدمة وإعداداتها.'], 503);
        }
    }

    public function whatsappQr(WhatsAppWebClient $client): JsonResponse
    {
        try {
            return response()->json($client->qr())->header('Cache-Control', 'no-store');
        } catch (Throwable $exception) {
            report($exception);

            return response()->json(['message' => 'تعذر تحميل رمز QR. أعد المحاولة بعد تشغيل الخدمة.'], 503);
        }
    }

    public function connectWhatsApp(WhatsAppWebClient $client): RedirectResponse
    {
        try {
            $client->connect();

            return to_route('settings.edit')->with('success', 'افتح واتساب على الهاتف ← الأجهزة المرتبطة ← ربط جهاز، ثم امسح الرمز.');
        } catch (Throwable $exception) {
            report($exception);

            return to_route('settings.edit')->withErrors(['whatsapp' => 'تعذر الاتصال بخدمة واتساب.']);
        }
    }

    public function disconnectWhatsApp(WhatsAppWebClient $client): RedirectResponse
    {
        try {
            $client->disconnect();

            return to_route('settings.edit')->with('success', 'تم فصل جلسة واتساب.');
        } catch (Throwable $exception) {
            report($exception);

            return to_route('settings.edit')->withErrors(['whatsapp' => 'تعذر فصل جلسة واتساب.']);
        }
    }
}
