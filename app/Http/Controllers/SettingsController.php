<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function edit(): View
    {
        return view('settings.edit');
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
}
