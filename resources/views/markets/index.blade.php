@extends('layouts.app')
@section('title', 'جميع الأسواق')
@section('crumb', 'جميع الأسواق')
@section('content')
<div class="page-title-row"><div><div class="breadcrumbs"><a href="{{ route('dashboard') }}">لوحة التحكم</a><i data-lucide="chevron-left"></i><span>جميع الأسواق</span></div><h1>جميع الأسواق <span class="title-count">{{ $markets->total() }}</span></h1><p>ابحث في الأسواق المسجلة أو صفِّ النتائج حسب المنطقة.</p></div><a href="{{ route('markets.choose-group') }}" class="button button-primary"><i data-lucide="plus"></i> إضافة سوق</a></div>

<form class="filter-panel" action="{{ route('markets.index') }}" method="GET">
    <label class="filter-search"><i data-lucide="search"></i><input type="search" name="search" value="{{ $search }}" placeholder="اسم السوق أو الجهة المؤجرة"></label>
    <button class="button button-primary" type="submit"><i data-lucide="search"></i> بحث</button>
    @if($search)<a class="button button-quiet" href="{{ route('markets.index', $selectedGroupId ? ['group_id' => $selectedGroupId] : []) }}">مسح البحث</a>@endif
</form>

<nav class="region-filter-tabs" aria-label="تصفية الأسواق حسب المنطقة">
    <a href="{{ route('markets.index', $search !== '' ? ['search' => $search] : []) }}" class="region-filter-tab {{ ! $selectedGroupId ? 'is-active' : '' }}" @if(! $selectedGroupId) aria-current="page" @endif>
        <i data-lucide="layers-3"></i><span>كل الأسواق</span>
    </a>
    @foreach($groups as $group)
        <a href="{{ route('markets.index', array_filter(['group_id' => $group->id, 'search' => $search], fn ($value) => $value !== '')) }}" class="region-filter-tab {{ $selectedGroupId === $group->id ? 'is-active' : '' }}" style="--accent:{{ $group->accent }}" @if($selectedGroupId === $group->id) aria-current="page" @endif>
            <span class="region-filter-dot"></span><span>{{ $group->name }}</span><small>{{ $group->markets_count }}</small>
        </a>
    @endforeach
</nav>

@if($markets->isNotEmpty())
    <div class="market-grid">@foreach($markets as $market) @include('partials.market-card', ['market' => $market]) @endforeach</div>
    <div class="pagination-wrap">{{ $markets->links() }}</div>
@else
    <div class="empty-state"><span class="empty-icon"><i data-lucide="search-x"></i></span><h3>لم نعثر على أسواق مطابقة</h3><p>جرّب البحث باسم مختلف أو امسح الفلاتر الحالية.</p><a class="button button-outline" href="{{ route('markets.index') }}">عرض كل الأسواق</a></div>
@endif
@endsection
