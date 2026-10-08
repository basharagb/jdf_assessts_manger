@extends('layouts.app')
@section('title', 'إدارة مناطق الأسواق')
@section('crumb', 'إدارة المناطق')
@section('content')
<div class="page-intro region-intro"><div class="intro-icon"><i data-lucide="map"></i></div><div class="intro-copy"><span class="eyebrow">إعدادات المناطق</span><h1>إدارة مناطق الأسواق</h1><p>يمكنك نقل أسواق المنطقة إلى منطقة أخرى أثناء حذفها للمحافظة على السجلات.</p></div><a class="button button-primary" href="{{ route('groups.create') }}"><i data-lucide="plus"></i> إضافة منطقة</a></div>
<div class="group-admin-list">
    @forelse($groups as $group)
        <article class="group-admin-card" style="--accent:{{ $group->accent }}">
            <span class="nav-color"></span><div class="group-admin-copy"><h2>{{ $group->name }}</h2><p dir="ltr">{{ $group->slug }} · {{ $group->markets_count }} سوق</p></div>
            <a class="button button-outline" href="{{ route('groups.edit', $group) }}"><i data-lucide="pencil"></i> تعديل</a>
            <form class="group-delete-form" method="POST" action="{{ route('groups.destroy', $group) }}" onsubmit="return confirm('حذف المنطقة {{ $group->name }}؟')">
                @csrf @method('DELETE')
                @if($group->markets_count > 0)
                    <select name="transfer_to_group" required aria-label="انقل أسواق {{ $group->name }} إلى"><option value="">انقل {{ $group->markets_count }} سوق إلى…</option>@foreach($otherGroups->where('id', '!=', $group->id) as $targetGroup)<option value="{{ $targetGroup->id }}">{{ $targetGroup->name }}</option>@endforeach</select>
                @endif
                <button class="button button-danger-light" type="submit"><i data-lucide="trash-2"></i> حذف المنطقة</button>
            </form>
        </article>
    @empty
        <div class="empty-state"><h3>لا توجد مناطق</h3><p>أضف منطقة لبدء تصنيف الأسواق.</p><a class="button button-primary" href="{{ route('groups.create') }}">إضافة منطقة</a></div>
    @endforelse
</div>
@endsection
