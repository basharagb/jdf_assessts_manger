@extends('layouts.app')
@section('title', 'اختيار المنطقة')
@section('crumb', 'إضافة سوق')
@section('content')
<div class="page-title-row"><div><div class="breadcrumbs"><a href="{{ route('dashboard') }}">لوحة التحكم</a><i data-lucide="chevron-left"></i><span>إضافة سوق جديد</span></div><h1>اختر منطقة السوق</h1><p>حدد المنطقة التي يتبع لها السوق للانتقال إلى نموذج الإضافة.</p></div></div>
<div class="choose-region-grid">
    @foreach($groups as $group)
        <a class="choose-region-card" style="--accent:{{ $group->accent }}" href="{{ route('markets.create', $group) }}">
            <span class="choose-region-icon"><i data-lucide="map-pin"></i></span><span class="choose-region-copy"><b>{{ $group->name }}</b><small>{{ $group->markets_count }} سوق مسجل</small></span><span class="choose-region-arrow"><i data-lucide="arrow-up-left"></i></span>
        </a>
    @endforeach
</div>
@endsection
