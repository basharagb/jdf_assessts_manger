@extends('layouts.app')

@section('title', 'الإعدادات')
@section('crumb', 'الإعدادات')

@section('content')
    <div class="settings-heading">
        <span class="section-kicker">الحساب والأمان</span>
        <h1>الإعدادات</h1>
        <p>غيّر كلمة المرور الخاصة بحسابك.</p>
    </div>

    <section class="settings-card">
        <div class="settings-card-heading">
            <span class="settings-icon"><i data-lucide="key-round"></i></span>
            <div>
                <h2>تغيير كلمة المرور</h2>
                <p>أدخل كلمة المرور الحالية ثم كلمة المرور الجديدة مرتين.</p>
            </div>
        </div>

        <form class="settings-form" method="POST" action="{{ route('settings.password.update') }}">
            @csrf
            @method('PUT')

            <label class="field">
                <span>كلمة المرور الحالية</span>
                <input type="password" name="current_password" autocomplete="current-password" required>
                @error('current_password')<small class="field-error">{{ $message }}</small>@enderror
            </label>

            <label class="field">
                <span>كلمة المرور الجديدة</span>
                <input type="password" name="password" autocomplete="new-password" minlength="8" required>
                <small class="settings-hint">يجب أن تتكون من 8 أحرف على الأقل.</small>
                @error('password')<small class="field-error">{{ $message }}</small>@enderror
            </label>

            <label class="field">
                <span>تأكيد كلمة المرور الجديدة</span>
                <input type="password" name="password_confirmation" autocomplete="new-password" minlength="8" required>
            </label>

            <div class="settings-actions">
                <button class="button button-primary" type="submit"><i data-lucide="save"></i> حفظ كلمة المرور</button>
            </div>
        </form>
    </section>
@endsection
