@extends('layouts.app')
@section('title', $editing ? 'تعديل المنطقة' : 'إضافة منطقة')
@section('crumb', $editing ? 'تعديل المنطقة' : 'إضافة منطقة')
@section('content')
<div class="page-intro region-intro"><div class="intro-icon"><i data-lucide="map-pin"></i></div><div class="intro-copy"><span class="eyebrow">إعدادات المناطق</span><h1>{{ $editing ? 'تعديل المنطقة' : 'إضافة منطقة جديدة' }}</h1><p>اسم المنطقة ظاهر للمستخدمين، والمعرّف اللاتيني يُستخدم في رابطها.</p></div></div>
<form class="detail-card group-form" method="POST" action="{{ $editing ? route('groups.update', $marketGroup) : route('groups.store') }}">
    @csrf @if($editing) @method('PUT') @endif
    <label class="field"><span>اسم المنطقة</span><input name="name" value="{{ old('name', $marketGroup->name) }}" maxlength="100" required placeholder="مثال: أسواق الشمال"></label>
    <label class="field"><span>معرّف الرابط (أحرف إنجليزية وأرقام وشرطات)</span><input name="slug" value="{{ old('slug', $marketGroup->slug) }}" dir="ltr" pattern="[a-z0-9]+(-[a-z0-9]+)*" maxlength="100" required placeholder="north-markets"></label>
    <label class="field group-color-field"><span>لون المنطقة</span><input type="color" name="accent" value="{{ old('accent', $marketGroup->accent ?: '#2563eb') }}" required></label>
    <div class="group-form-actions"><a class="button button-quiet" href="{{ route('groups.index') }}">إلغاء</a><button class="button button-primary" type="submit"><i data-lucide="save"></i> {{ $editing ? 'حفظ التعديلات' : 'إضافة المنطقة' }}</button></div>
</form>
@endsection
