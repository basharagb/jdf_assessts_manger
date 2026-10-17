@extends('layouts.app')
@section('title', $market->name)
@section('crumb', $market->name)
@section('content')
<div class="market-detail-heading"><div><div class="breadcrumbs"><a href="{{ route('dashboard') }}">لوحة التحكم</a><i data-lucide="chevron-left"></i><a href="{{ route('groups.show', $market->group) }}">{{ $market->group->name }}</a><i data-lucide="chevron-left"></i><span>{{ $market->name }}</span></div><div class="detail-title"><h1>{{ $market->name }}</h1><span class="market-status {{ $market->is_active ? 'status-active' : 'status-muted' }}"><i></i>{{ $market->is_active ? 'نشط' : 'مؤرشف' }}</span>@if($market->is_demo)<span class="demo-flag">بيانات ديمو قابلة للتعديل</span>@endif</div><p>تفاصيل الأصل وعقد الإيجار ومتابعة إجمالي المستحق للفترة.</p></div><div class="detail-actions"><a class="button button-outline" href="{{ route('markets.rent-details.index', $market) }}"><i data-lucide="list-checks"></i> تفاصيل الإيجار</a><a class="button button-outline" href="{{ route('markets.expansions.index', $market) }}"><i data-lucide="expand"></i> توسعة السوق</a><a class="button button-outline" href="{{ route('markets.edit', $market) }}"><i data-lucide="pencil"></i> تعديل البيانات</a><a class="button button-quiet" href="{{ route('groups.show', $market->group) }}"><i data-lucide="arrow-right"></i> العودة للمنطقة</a></div></div>

<div class="detail-layout">
    <div class="detail-main">
        @php($displayImagePath = $market->image_path ?: 'markets/demo/jdf-generic-shop.jpg')
        <div class="detail-cover" style="--accent:{{ $market->group->accent }};--cover-image:url('{{ asset('storage/'.$displayImagePath) }}')">
            <a class="detail-cover-open" href="{{ asset('storage/'.$displayImagePath) }}" target="_blank" rel="noopener" title="عرض الصورة بالحجم الكامل"><i data-lucide="maximize-2"></i></a>
            <img src="{{ asset('storage/'.$displayImagePath) }}" alt="{{ $displayImagePath === 'markets/demo/jdf-generic-shop.jpg' ? 'صورة توضيحية لمتجر الأسواق الحرة الأردنية' : 'صورة '.$market->name }}">
            <div class="cover-label"><span>{{ $market->group->name }}</span><b>{{ $market->name }}</b></div>
            @if($displayImagePath === 'markets/demo/jdf-generic-shop.jpg')<span class="cover-image-note">صورة توضيحية من الملف التعريفي الرسمي لـ JDF</span>@endif
        </div>
        <div class="detail-card">
            <div class="card-title-row"><div><span class="section-kicker">بيانات الأصل</span><h2>معلومات السوق</h2></div><span class="subtle-icon"><i data-lucide="clipboard-list"></i></span></div>
            <div class="info-grid">
                <div class="info-cell"><span class="info-icon info-blue"><i data-lucide="map-pin"></i></span><span><small>المنطقة</small><b>{{ $market->group->name }}</b></span></div>
                <div class="info-cell"><span class="info-icon info-green"><i data-lucide="move-diagonal"></i></span><span><small>{{ $market->hasSections() ? 'المساحة الإجمالية' : 'المساحة' }}</small><b>{{ number_format((float) $market->area, 2) }} م²</b></span></div>
                <div class="info-cell"><span class="info-icon info-violet"><i data-lucide="building-2"></i></span><span><small>الجهة المؤجرة</small><b>{{ $market->lessor }}</b></span></div>
                <div class="info-cell"><span class="info-icon info-amber"><i data-lucide="calendar-days"></i></span><span><small>بداية الإيجار</small><b>{{ $market->lease_start_date->format('Y/m/d') }}</b></span></div>
                <div class="info-cell"><span class="info-icon info-blue"><i data-lucide="calendar-check-2"></i></span><span><small>نهاية العقد</small><b>{{ $market->lease_end_date?->format('Y/m/d') ?? 'غير محدد' }}</b></span></div>
                <div class="info-cell"><span class="info-icon info-green"><i data-lucide="wallet"></i></span><span><small>{{ $market->leaseHasEnded() ? 'الإيجار السنوي الأخير (العقد منتهي)' : 'الإيجار السنوي الحالي' }}</small><b>{{ number_format(array_sum($currentRents), 2) }} د.أ</b></span></div>
                <div class="info-cell"><span class="info-icon info-violet"><i data-lucide="layout-panel-left"></i></span><span><small>تكوين السوق</small><b>{{ $market->hasSections() ? $market->sections->count().' أقسام منفصلة' : 'وحدة واحدة' }}</b></span></div>
            </div>
            @if($market->hasSections())
                <div class="section-list">
                    <span class="section-list-title">أقسام السوق</span>
                    @foreach($market->sections as $section)
                        <div class="section-row"><b>{{ $section->name }}</b><span><small>المساحة</small>{{ number_format((float) $section->area, 2) }} م²</span><span><small>الإيجار الأساسي</small>{{ number_format((float) $section->annual_rent, 2) }} د.أ</span><span><small>{{ $market->leaseHasEnded() ? 'الإيجار الأخير' : 'الإيجار الحالي' }}</small>{{ number_format($currentRents[$section->id] ?? 0, 2) }} د.أ</span></div>
                    @endforeach
                </div>
            @endif
            @if($market->description)<div class="description-box"><span>ملاحظات</span><p>{{ $market->description }}</p></div>@endif
        </div>
    </div>

    <aside class="rent-panel">
        <div class="rent-panel-head"><span class="rent-panel-icon"><i data-lucide="calculator"></i></span><span class="eyebrow">حاسبة الإيجار السنوي</span><h2>احسب الإجمالي</h2><p>كل سنة إيجار تبدأ في {{ $market->lease_start_date->format('d/m') }}. من 2025 إلى 2026 = سنة واحدة.</p></div>
        @if($firstYear <= $lastYear)
        <form class="rent-form" action="{{ route('markets.show', $market) }}" method="GET">
            @include('partials.year-picker', ['pickerId' => 'rentFrom', 'name' => 'from', 'label' => 'من سنة إيجار', 'value' => $from, 'min' => $firstYear, 'max' => $lastYear, 'required' => true])
            @include('partials.year-picker', ['pickerId' => 'rentTo', 'name' => 'to', 'label' => 'إلى سنة إيجار', 'value' => $to, 'min' => $firstYear + 1, 'max' => $lastYear + 1, 'required' => true])
            <button class="button button-primary button-full" type="submit"><i data-lucide="calculator"></i> احسب الإيجار</button>
        </form>
        @endif
        @if($rangeError)<div class="range-error">{{ $rangeError }}</div>@endif
        <div class="rent-result">
            <div class="result-label"><span>مدة الحساب</span><b>{{ $totals['years'] }} سنة</b></div>
            @unless($rangeError)<p class="result-dates">من {{ $anniversaries[$from] ?? $from }} إلى {{ $anniversaries[$to] ?? $to }}</p>@endunless
            <div class="result-total"><small>إجمالي قيمة الإيجار السنوي للفترة</small><strong>{{ number_format($totals['total'], 2) }} <em>د.أ</em></strong></div>
            <div class="rent-breakdown">
                <div><span>مدفوع</span><b>{{ number_format($totals['paid'], 2) }} د.أ</b></div>
                <div><span>غير مدفوع</span><b>{{ number_format($totals['unpaid'], 2) }} د.أ</b></div>
                <div><span>إخلاء</span><b>{{ $totals['vacant'] }} سنة</b></div>
                <div><span>بلا تفاصيل سداد</span><b>{{ number_format($totals['untracked'], 2) }} د.أ</b></div>
            </div>
        </div>
        <p class="rent-footnote">تحتسب كل سنة إيجار مرة واحدة. آخر سنة تنتهي عند نهاية العقد، ولا تُحسب سنة جديدة تبدأ في يوم انتهاء العقد. اختيار السنوات السابقة متاح حتى خمس سنوات فقط.</p>
        <a class="button button-outline button-full" href="{{ route('reports.market', $market) }}"><i data-lucide="file-text"></i> عرض تقرير السوق وطباعته</a>
    </aside>
</div>

<div class="delete-row"><span><b>إدارة السجل</b><small>حذف السوق يزيل بياناته من النظام نهائياً.</small></span><form method="POST" action="{{ route('markets.destroy', $market) }}" onsubmit="return confirm('هل تريد حذف هذا السوق نهائياً؟')">@csrf @method('DELETE')<button class="button button-danger-light" type="submit"><i data-lucide="trash-2"></i> حذف السوق</button></form></div>

@endsection
