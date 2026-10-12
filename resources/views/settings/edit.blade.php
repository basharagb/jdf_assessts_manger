@extends('layouts.app')

@section('title', 'الإعدادات')
@section('crumb', 'الإعدادات')

@section('content')
    <div class="settings-heading">
        <span class="section-kicker">الحساب والأمان</span>
        <h1>الإعدادات</h1>
        <p>إدارة الحساب وربط واتساب وتنبيهات انتهاء العقود.</p>
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
    <section class="settings-card" style="margin-top:24px">
        <div class="settings-card-heading"><span class="settings-icon"><i data-lucide="message-circle"></i></span><div><h2>تنبيهات واتساب</h2><p>تنبيه واحد لكل عقد ولكل مستلم قبل الانتهاء بـ ٩٠ يوماً. عند الربط المتأخر تُرسل التنبيهات للعقود المتبقية ضمن ٩٠ يوماً.</p></div></div>
        <form class="settings-form" method="POST" action="{{ route('settings.whatsapp.update') }}">
            @csrf @method('PUT')
            <label class="switch-row"><input type="checkbox" name="enabled" value="1" @checked(old('enabled', $whatsappSettings->enabled))><span class="switch-track"></span><span><b>تفعيل تنبيهات العقود</b></span></label>
            <label class="field"><span>المستلم الأساسي</span><input value="+962 7 7091 1991" readonly dir="ltr"><small>رقم ثابت لاستلام جميع تنبيهات العقود.</small></label>
            @for($index = 0; $index < 4; $index++)
                <label class="field"><span>مستلم إضافي {{ $index + 1 }} (اختياري)</span><input type="tel" name="additional_numbers[]" value="{{ old('additional_numbers.'.$index, $whatsappSettings->additional_numbers[$index] ?? '') }}" placeholder="+9627XXXXXXXX" dir="ltr"></label>
            @endfor
            <button class="button button-primary" type="submit">حفظ إعدادات التنبيهات</button>
        </form>
        <div style="margin-top:24px"><h3>ربط واتساب ويب</h3><p id="whatsappStatus" role="status">جارٍ التحقق من الاتصال…</p><img id="whatsappQr" alt="رمز QR لربط واتساب" width="256" height="256" hidden><p>واتساب على الهاتف ← الأجهزة المرتبطة ← ربط جهاز. اختر حساب الإرسال بمسح الرمز.</p>
            <div class="settings-actions"><form method="POST" action="{{ route('settings.whatsapp.connect') }}">@csrf<button class="button button-primary">ربط / تحديث QR</button></form><form method="POST" action="{{ route('settings.whatsapp.disconnect') }}" onsubmit="return confirm('فصل حساب واتساب المرتبط؟')">@csrf<button class="button button-outline">فصل الحساب</button></form></div>
        </div>
    </section>
    <section class="settings-card" style="margin-top:24px"><h2>سجل تنبيهات العقود</h2>
        @forelse($reminders as $reminder)
            <p>{{ $reminder->market?->name }} — <b dir="ltr">+{{ $reminder->recipient }}</b> — نهاية العقد: {{ $reminder->lease_end_date->format('d/m/Y') }} — {{ ['sent' => 'تم الإرسال', 'failed' => 'تعذر الإرسال؛ ستتم إعادة المحاولة', 'pending' => 'بانتظار الإرسال', 'uncertain' => 'يلزم التحقق من الإرسال قبل إعادة المحاولة'][$reminder->status] ?? $reminder->status }} @if($reminder->sent_at)({{ $reminder->sent_at->timezone('Asia/Amman')->format('d/m/Y H:i') }})@endif</p>
        @empty<p>لا توجد تنبيهات مسجلة بعد.</p>@endforelse
    </section>
@push('scripts')
<script>
window.addEventListener('DOMContentLoaded', () => {
    const status = document.getElementById('whatsappStatus');
    const qr = document.getElementById('whatsappQr');
    async function refresh() {
        try {
            const response = await fetch(@json(route('settings.whatsapp.status')), {headers: {'Accept': 'application/json'}});
            const data = await response.json();
            qr.hidden = true;
            if (!response.ok) { status.textContent = data.message || 'خدمة واتساب غير متاحة.'; return; }
            status.textContent = data.connected ? `متصل بحساب ${data.info?.phone || ''}` : 'غير متصل — امسح الرمز بعد الضغط على ربط.';
            if (!data.connected && ['qr_ready', 'connecting'].includes(data.status)) {
                const qrResponse = await fetch(@json(route('settings.whatsapp.qr')), {headers: {'Accept': 'application/json'}});
                const result = await qrResponse.json();
                if (typeof result.qr === 'string' && result.qr.startsWith('data:image/png;base64,')) { qr.src = result.qr; qr.hidden = false; }
            }
        } catch { status.textContent = 'تعذر التحقق من اتصال واتساب.'; }
        finally { window.setTimeout(refresh, 15000); }
    }
    refresh();
});
</script>
@endpush
@endsection
