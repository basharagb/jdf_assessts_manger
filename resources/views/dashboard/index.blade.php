@extends('layouts.app')
@section('title', 'لوحة التحكم')
@section('crumb', 'لوحة التحكم')
@section('content')
<div class="welcome-banner">
    <div class="welcome-copy">
        <span class="welcome-kicker"><span class="sparkle">✦</span> لوحة متابعة الأصول</span>
        <h1>مرحباً {{ auth()->user()->name }}، <em>أهلاً بك</em></h1>
        <p>تابع الأسواق والعقود والإيجارات من مكان واحد.</p>
        <a href="{{ route('markets.index') }}" class="button button-light">استعراض الأسواق <i data-lucide="arrow-left"></i></a>
    </div>
    <div class="banner-art" aria-hidden="true">
        <div class="art-ring ring-large"></div><div class="art-ring ring-small"></div>
        <div class="art-building"><div class="building-top"></div><div class="building-body"><span></span><span></span><span></span><span></span><span></span><span></span></div></div>
        <div class="art-stat"><i data-lucide="trending-up"></i><span>متابعة واضحة</span></div>
    </div>
</div>

<div class="section-heading section-heading-stats">
    <div><span class="section-kicker">نظرة سريعة</span><h2>ملخص الأصول</h2></div>
    <span class="updated-label"><i data-lucide="clock-3"></i> الأرقام محدثة من قاعدة البيانات</span>
</div>
<div class="stats-grid">
    <article class="stat-card stat-blue"><div class="stat-card-top"><span class="stat-icon"><i data-lucide="store"></i></span><span class="stat-trend">كل المناطق</span></div><strong>{{ number_format($marketCount) }}</strong><span class="stat-label">إجمالي الأسواق المسجلة</span><div class="stat-accent"><i data-lucide="arrow-up-left"></i></div></article>
    <article class="stat-card stat-green"><div class="stat-card-top"><span class="stat-icon"><i data-lucide="circle-check"></i></span><span class="stat-trend">قيد التشغيل</span></div><strong>{{ number_format($activeCount) }}</strong><span class="stat-label">الأسواق النشطة</span><div class="stat-accent"><i data-lucide="badge-check"></i></div></article>
    <article class="stat-card stat-violet"><div class="stat-card-top"><span class="stat-icon"><i data-lucide="wallet"></i></span><span class="stat-trend">سنوياً</span></div><strong>{{ number_format((float) $annualRent, 2) }} <small>د.أ</small></strong><span class="stat-label">إجمالي الإيجار السنوي</span><div class="stat-accent"><i data-lucide="coins"></i></div></article>
    <article class="stat-card stat-amber"><div class="stat-card-top"><span class="stat-icon"><i data-lucide="calendar-clock"></i></span><span class="stat-trend">خلال ٩٠ يوماً</span></div><strong>{{ number_format($renewalsSoon) }}</strong><span class="stat-label">عقود تقترب من الانتهاء</span><div class="stat-accent"><i data-lucide="bell-ring"></i></div></article>
</div>

@if($demoCount > 0)
<div class="demo-notice"><span class="demo-notice-icon"><i data-lucide="circle-alert"></i></span><div><b>{{ $demoCount }} سجل ديمو للمراجعة</b><p>الإيجار الافتراضي ٥٬٠٠٠ د.أ سنوياً، وتاريخ البداية قبل خمس سنوات. توزيع المناطق مبدئي ويمكن تغييره عند تعديل السوق.</p></div><a href="{{ route('markets.index') }}">مراجعة الأسواق <i data-lucide="arrow-left"></i></a></div>
@endif

<div class="section-heading"><div><span class="section-kicker">تصفح حسب المنطقة</span><h2>مناطق الأسواق</h2></div><span class="section-side-note">اختر المنطقة لعرض أسواقها وإدارتها</span></div>
<div class="region-grid">
    @foreach($groups as $group)
        <a class="region-card" href="{{ route('groups.show', $group) }}" style="--accent:{{ $group->accent }}">
            <div class="region-card-head"><span class="region-icon"><i data-lucide="map-pin"></i></span><span class="region-arrow"><i data-lucide="arrow-up-left"></i></span></div>
            <div class="region-copy"><span class="eyebrow">إدارة المنطقة</span><h3>{{ $group->name }}</h3><p>{{ $group->markets_count }} سوق مسجل</p></div>
            <div class="region-card-foot"><span>إجمالي الإيجار السنوي النشط</span><b>{{ number_format((float) ($group->active_rent_sum ?? 0), 2) }} <small>د.أ</small></b></div>
            <span class="region-decoration"><i data-lucide="map"></i></span>
        </a>
    @endforeach
</div>

<div class="section-heading recent-heading"><div><span class="section-kicker">آخر الإضافات</span><h2>أسواق تمت إضافتها مؤخراً</h2></div><a class="text-link" href="{{ route('markets.index') }}">عرض كل الأسواق <i data-lucide="arrow-left"></i></a></div>
@if($recentMarkets->isNotEmpty())
    <div class="market-grid market-grid-3">@foreach($recentMarkets as $market) @include('partials.market-card', ['market' => $market]) @endforeach</div>
@else
    <div class="empty-state empty-wide"><span class="empty-icon"><i data-lucide="store"></i></span><h3>ابدأ بإضافة أول سوق</h3><p>اختر منطقة من الأعلى، ثم أضف بيانات السوق وعقد الإيجار والصورة.</p><a class="button button-primary" href="{{ $groups->isNotEmpty() ? route('groups.show', $groups->first()) : route('groups.index') }}">{{ $groups->isNotEmpty() ? 'استعراض الأسواق' : 'إضافة منطقة' }} <i data-lucide="arrow-left"></i></a></div>
@endif
@endsection
