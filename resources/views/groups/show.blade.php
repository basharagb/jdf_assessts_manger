@extends('layouts.app')
@section('title', $marketGroup->name)
@section('crumb', $marketGroup->name)
@section('content')
<div class="page-intro region-intro" style="--accent:{{ $marketGroup->accent }}">
    <div class="intro-icon"><i data-lucide="map-pin"></i></div>
    <div class="intro-copy"><div class="breadcrumbs"><a href="{{ route('dashboard') }}">لوحة التحكم</a><i data-lucide="chevron-left"></i><span>{{ $marketGroup->name }}</span></div><h1>{{ $marketGroup->name }}</h1><p>إدارة الأسواق والأصول والعقود التابعة لهذه المنطقة.</p></div>
    <a class="button button-outline" href="{{ route('reports.group', $marketGroup) }}" target="_blank" rel="noopener"><i data-lucide="file-text"></i> تقرير المنطقة</a>
    <a class="button button-primary" href="{{ route('markets.create', $marketGroup) }}"><i data-lucide="plus"></i> إضافة سوق جديد</a>
</div>

<div class="listing-toolbar">
    <div><span class="section-kicker">قائمة المنطقة</span><h2>{{ $marketGroup->markets()->count() }} <small>سوق</small></h2></div>
    <a href="{{ route('markets.index', ['group_id' => $marketGroup->id]) }}" class="button button-outline"><i data-lucide="list-filter"></i> عرض كقائمة</a>
</div>

@if($markets->isNotEmpty())
    <div class="market-grid">@foreach($markets as $market) @include('partials.market-card', ['market' => $market]) @endforeach</div>
    <div class="pagination-wrap">{{ $markets->links() }}</div>
@else
    <div class="empty-state"><span class="empty-icon"><i data-lucide="store"></i></span><h3>لا توجد أسواق في هذه المنطقة بعد</h3><p>أضف السوق الأول لتسجيل المساحة والجهة المؤجرة وتفاصيل الإيجار.</p><a class="button button-primary" href="{{ route('markets.create', $marketGroup) }}"><i data-lucide="plus"></i> إضافة أول سوق</a></div>
@endif
@endsection
