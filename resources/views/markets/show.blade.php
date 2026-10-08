@extends('layouts.app')
@section('title', $market->name)
@section('crumb', $market->name)
@section('content')
<div class="market-detail-heading"><div><div class="breadcrumbs"><a href="{{ route('dashboard') }}">لوحة التحكم</a><i data-lucide="chevron-left"></i><a href="{{ route('groups.show', $market->group) }}">{{ $market->group->name }}</a><i data-lucide="chevron-left"></i><span>{{ $market->name }}</span></div><div class="detail-title"><h1>{{ $market->name }}</h1><span class="market-status {{ $market->is_active ? 'status-active' : 'status-muted' }}"><i></i>{{ $market->is_active ? 'نشط' : 'مؤرشف' }}</span>@if($market->is_demo)<span class="demo-flag">بيانات ديمو قابلة للتعديل</span>@endif</div><p>تفاصيل الأصل وعقد الإيجار ومتابعة إجمالي المستحق للفترة.</p></div><div class="detail-actions"><a class="button button-outline" href="{{ route('markets.rent-details.index', $market) }}"><i data-lucide="list-checks"></i> تفاصيل الإيجار</a><a class="button button-outline" href="{{ route('markets.edit', $market) }}"><i data-lucide="pencil"></i> تعديل البيانات</a><a class="button button-quiet" href="{{ route('groups.show', $market->group) }}"><i data-lucide="arrow-right"></i> العودة للمنطقة</a></div></div>

<div class="detail-layout">
    <div class="detail-main">
        @php($displayImagePath = $market->image_path ?: 'markets/demo/jdf-generic-shop.jpg')
        <div class="detail-cover" style="--accent:{{ $market->group->accent }}">
            <img src="{{ asset('storage/'.$displayImagePath) }}" alt="{{ $displayImagePath === 'markets/demo/jdf-generic-shop.jpg' ? 'صورة توضيحية لمتجر الأسواق الحرة الأردنية' : 'صورة '.$market->name }}">
            <div class="cover-label"><span>{{ $market->group->name }}</span><b>{{ $market->name }}</b></div>
            @if($displayImagePath === 'markets/demo/jdf-generic-shop.jpg')<span class="cover-image-note">صورة توضيحية من الملف التعريفي الرسمي لـ JDF</span>@endif
        </div>
        <div class="detail-card">
            <div class="card-title-row"><div><span class="section-kicker">بيانات الأصل</span><h2>معلومات السوق</h2></div><span class="subtle-icon"><i data-lucide="clipboard-list"></i></span></div>
            <div class="info-grid">
                <div class="info-cell"><span class="info-icon info-blue"><i data-lucide="map-pin"></i></span><span><small>المنطقة</small><b>{{ $market->group->name }}</b></span></div>
                <div class="info-cell"><span class="info-icon info-green"><i data-lucide="move-diagonal"></i></span><span><small>المساحة</small><b>{{ number_format((float) $market->area, 2) }} م²</b></span></div>
                <div class="info-cell"><span class="info-icon info-violet"><i data-lucide="building-2"></i></span><span><small>الجهة المؤجرة</small><b>{{ $market->lessor }}</b></span></div>
                <div class="info-cell"><span class="info-icon info-amber"><i data-lucide="calendar-days"></i></span><span><small>بداية الإيجار</small><b>{{ $market->lease_start_date->format('Y/m/d') }}</b></span></div>
                <div class="info-cell"><span class="info-icon info-blue"><i data-lucide="calendar-check-2"></i></span><span><small>نهاية العقد</small><b>{{ $market->lease_end_date?->format('Y/m/d') ?? 'غير محدد' }}</b></span></div>
                <div class="info-cell"><span class="info-icon info-green"><i data-lucide="wallet"></i></span><span><small>الإيجار الشهري الحالي</small><b>{{ number_format($market->rentForMonth(), 2) }} د.أ</b></span></div>
            </div>
            @if($market->description)<div class="description-box"><span>ملاحظات</span><p>{{ $market->description }}</p></div>@endif
        </div>
    </div>

    <aside class="rent-panel">
        <div class="rent-panel-head"><span class="rent-panel-icon"><i data-lucide="calculator"></i></span><span class="eyebrow">حاسبة الإيجار</span><h2>احسب الإجمالي</h2><p>اختر شهر البداية والنهاية لحساب الإيجار للفترة.</p></div>
        <form class="rent-form" action="{{ route('markets.show', $market) }}" method="GET">
            @php($pickerMaxMonth = $contractEndMonth ?: now()->addYears(10)->format('Y-m'))
            @include('partials.month-picker', ['pickerId' => 'rentFrom', 'name' => 'from', 'label' => 'من شهر', 'value' => $from, 'min' => $contractStartMonth, 'max' => $pickerMaxMonth, 'required' => true])
            @include('partials.month-picker', ['pickerId' => 'rentTo', 'name' => 'to', 'label' => 'إلى شهر', 'value' => $to, 'min' => $contractStartMonth, 'max' => $pickerMaxMonth, 'required' => true])
            <button class="button button-primary button-full" type="submit"><i data-lucide="calculator"></i> احسب الإيجار</button>
        </form>
        <div class="range-error" id="rentRangeError" @if(!$rangeError)hidden @endif>{{ $rangeError }}</div>
        <div class="rent-result">
            <div class="result-label"><span><i data-lucide="calendar-range"></i> مدة الحساب</span><b id="rentMonths">{{ $months }} {{ $months === 1 ? 'شهر' : 'أشهر' }}</b></div>
            <div class="result-total"><small>إجمالي قيمة الإيجار للفترة</small><strong><span id="rentTotal">{{ number_format($rentTotal, 2) }}</span> <em>د.أ</em></strong></div>
            <div class="rent-breakdown" id="rentBreakdown" @if($rentPeriods->isEmpty())hidden @endif>
                <div><span>مدفوع</span><b><span id="rentPaidTotal">{{ number_format($paidTotal, 2) }}</span> د.أ</b></div>
                <div><span>غير مدفوع</span><b><span id="rentUnpaidTotal">{{ number_format($unpaidTotal, 2) }}</span> د.أ</b></div>
                <div><span>إخلاء</span><b><span id="rentVacantMonths">{{ $vacantMonths }}</span> شهر</b></div>
                <div><span>بلا تفاصيل مسجلة</span><b><span id="rentUntrackedTotal">{{ number_format($untrackedTotal, 2) }}</span> د.أ</b></div>
            </div>
            <div class="result-equation"><span id="rentEquationDescription">{{ $rentPeriods->isEmpty() ? 'الإيجار الأساسي' : 'حسب الفترات المسجلة' }}</span><i data-lucide="calendar-range"></i><span><b id="rentEquationMonths">{{ $months }}</b> شهر</span></div>
            <p class="no-overlap" id="rentNoOverlap" @if($months > 0 || $rangeError)hidden @endif>الفترة المختارة خارج مدة العقد ولا يوجد إيجار مستحق.</p>
        </div>
        <p class="rent-footnote"><i data-lucide="info"></i> يحتسب كل شهر مختار بالكامل، وتدخل في الإجمالي الأشهر التي تتقاطع مع مدة العقد فقط.</p>
    </aside>
</div>

<div class="delete-row"><span><b>إدارة السجل</b><small>حذف السوق يزيل بياناته من النظام نهائياً.</small></span><form method="POST" action="{{ route('markets.destroy', $market) }}" onsubmit="return confirm('هل تريد حذف هذا السوق نهائياً؟')">@csrf @method('DELETE')<button class="button button-danger-light" type="submit"><i data-lucide="trash-2"></i> حذف السوق</button></form></div>

@push('scripts')
<script>
    window.addEventListener('DOMContentLoaded', () => {
        const from = document.getElementById('rentFrom');
        const to = document.getElementById('rentTo');
        const total = document.getElementById('rentTotal');
        const monthsLabel = document.getElementById('rentMonths');
        const equationMonths = document.getElementById('rentEquationMonths');
        const rangeError = document.getElementById('rentRangeError');
        const noOverlap = document.getElementById('rentNoOverlap');
        const contractStart = @json($contractStartMonth);
        const contractEnd = @json($contractEndMonth);
        const rentPeriods = @json($rentPeriodData);
        const paidTotal = document.getElementById('rentPaidTotal');
        const unpaidTotal = document.getElementById('rentUnpaidTotal');
        const vacantMonths = document.getElementById('rentVacantMonths');
        const untrackedTotal = document.getElementById('rentUntrackedTotal');
        const breakdown = document.getElementById('rentBreakdown');
        const equationDescription = document.getElementById('rentEquationDescription');
        const baselineRent = Number(@json((float) $market->monthly_rent));
        const formatMoney = amount => new Intl.NumberFormat('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        }).format(amount);

        const updateRent = () => {
            if (!from.value || !to.value) return;

            if (from.value > to.value) {
                rangeError.textContent = 'يجب أن يكون شهر البداية قبل شهر النهاية أو مساوياً له.';
                rangeError.hidden = false;
                monthsLabel.textContent = '—';
                equationMonths.textContent = '—';
                total.textContent = '0.00';
                if (paidTotal) paidTotal.textContent = '0.00';
                if (unpaidTotal) unpaidTotal.textContent = '0.00';
                if (vacantMonths) vacantMonths.textContent = '0';
                if (untrackedTotal) untrackedTotal.textContent = '0.00';
                noOverlap.hidden = true;
                return;
            }

            rangeError.hidden = true;
            const overlapStart = from.value > contractStart ? from.value : contractStart;
            const overlapEnd = contractEnd && to.value > contractEnd ? contractEnd : to.value;
            let months = 0;
            let due = 0;
            let paid = 0;
            let unpaid = 0;
            let vacant = 0;
            let untracked = 0;

            if (overlapStart <= overlapEnd) {
                const [startYear, startMonth] = overlapStart.split('-').map(Number);
                const [endYear, endMonth] = overlapEnd.split('-').map(Number);
                months = ((endYear - startYear) * 12) + endMonth - startMonth + 1;
                let [year, month] = [startYear, startMonth];
                while (year < endYear || (year === endYear && month <= endMonth)) {
                    const monthKey = `${year}-${String(month).padStart(2, '0')}`;
                    const period = rentPeriods.find(item => item.start <= monthKey && (!item.end || item.end >= monthKey));
                    if (!period) {
                        due += baselineRent;
                        untracked += baselineRent;
                    } else if (period.status === 'vacant') {
                        vacant++;
                    } else {
                        due += period.rent;
                        if (period.status === 'paid') paid += period.rent;
                        else unpaid += period.rent;
                    }
                    month++;
                    if (month > 12) { year++; month = 1; }
                }
            }

            monthsLabel.textContent = `${months} ${months === 1 ? 'شهر' : 'أشهر'}`;
            equationMonths.textContent = months;
            total.textContent = formatMoney(due);
            if (paidTotal) paidTotal.textContent = formatMoney(paid);
            if (unpaidTotal) unpaidTotal.textContent = formatMoney(unpaid);
            if (vacantMonths) vacantMonths.textContent = vacant;
            if (untrackedTotal) untrackedTotal.textContent = formatMoney(untracked);
            if (breakdown) breakdown.hidden = rentPeriods.length === 0;
            if (equationDescription) equationDescription.textContent = rentPeriods.length === 0 ? 'الإيجار الأساسي' : 'حسب الفترات المسجلة';
            noOverlap.hidden = months > 0;
        };

        from.addEventListener('input', updateRent);
        from.addEventListener('change', updateRent);
        to.addEventListener('input', updateRent);
        to.addEventListener('change', updateRent);
        updateRent();
    });
</script>
@endpush
@endsection
