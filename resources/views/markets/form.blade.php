@extends('layouts.app')
@section('title', $market->exists ? 'تعديل '.$market->name : 'إضافة سوق جديد')
@section('crumb', $market->exists ? 'تعديل السوق' : 'إضافة سوق')
@section('content')
<div class="page-title-row"><div><div class="breadcrumbs"><a href="{{ route('dashboard') }}">لوحة التحكم</a><i data-lucide="chevron-left"></i><a href="{{ route('groups.show', $marketGroup) }}">{{ $marketGroup->name }}</a><i data-lucide="chevron-left"></i><span>{{ $market->exists ? 'تعديل السوق' : 'إضافة سوق' }}</span></div><h1>{{ $market->exists ? 'تعديل بيانات السوق' : 'إضافة سوق جديد' }}</h1><p>أدخل تفاصيل الموقع والعقد حتى يسهل متابعة الإيجار والفترات.</p></div></div>

<form class="form-shell" action="{{ $market->exists ? route('markets.update', $market) : route('markets.store', $marketGroup) }}" method="POST" enctype="multipart/form-data">
    @csrf @if($market->exists) @method('PUT') @endif
    <div class="form-card">
        <div class="form-card-heading"><span class="form-step">١</span><div><h2>معلومات السوق</h2><p>البيانات الأساسية للموقع والجهة المؤجرة.</p></div></div>
        <div class="form-grid">
            <label class="field field-span-2"><span>اسم السوق <b>*</b></span><div class="input-icon"><i data-lucide="store"></i><input name="name" value="{{ old('name', $market->name) }}" placeholder="مثال: سوق جابر" required maxlength="160"></div>@error('name')<small class="field-error">{{ $message }}</small>@enderror</label>
            <label class="field"><span>المنطقة</span>@if($market->exists)<div class="input-icon"><i data-lucide="map-pin"></i><select name="market_group_id">@foreach($groups as $group)<option value="{{ $group->id }}" @selected((int) old('market_group_id', $market->market_group_id) === $group->id)>{{ $group->name }}</option>@endforeach</select></div>@error('market_group_id')<small class="field-error">{{ $message }}</small>@enderror @else<div class="input-icon is-disabled"><i data-lucide="map-pin"></i><input value="{{ $marketGroup->name }}" disabled></div>@endif</label>
            <label class="field"><span>مساحة السوق <b>*</b></span><div class="input-icon input-suffix"><i data-lucide="move-diagonal"></i><input type="number" name="area" value="{{ old('area', $market->area) }}" min="0.01" step="0.01" placeholder="0.00" required><em>م²</em></div>@error('area')<small class="field-error">{{ $message }}</small>@enderror</label>
            <label class="field field-span-2"><span>الجهة المؤجرة <b>*</b></span><div class="input-icon"><i data-lucide="building-2"></i><input name="lessor" value="{{ old('lessor', $market->lessor) }}" placeholder="اسم الجهة أو الشركة المؤجرة" required maxlength="180"></div>@error('lessor')<small class="field-error">{{ $message }}</small>@enderror</label>
            <label class="field field-span-2"><span>صورة السوق</span><div class="file-field"><input id="market-image" type="file" name="image" accept="image/jpeg,image/png,image/webp"><label for="market-image"><i data-lucide="image-plus"></i><span><b>اختر صورة</b><small>JPG أو PNG أو WEBP · حتى 5 ميغابايت</small></span><i data-lucide="upload-cloud" class="upload-arrow"></i></label></div>@if($market->image_path)<small class="current-image">الصورة الحالية محفوظة. ارفع صورة جديدة لاستبدالها.</small>@endif @error('image')<small class="field-error">{{ $message }}</small>@enderror</label>
            <label class="field field-span-2"><span>ملاحظات إضافية</span><textarea name="description" rows="4" placeholder="تفاصيل الموقع أو ملاحظات العقد...">{{ old('description', $market->description) }}</textarea>@error('description')<small class="field-error">{{ $message }}</small>@enderror</label>
        </div>
    </div>

    <div class="form-card">
        <div class="form-card-heading"><span class="form-step form-step-green">٢</span><div><h2>بيانات عقد الإيجار</h2><p>يستخدم النظام هذه المعلومات لحساب الإجمالي حسب الفترة التي تحددها.</p></div></div>
        <div class="form-grid">
            <label class="field"><span>الإيجار الشهري <b>*</b></span><div class="input-icon input-suffix"><i data-lucide="wallet"></i><input type="number" name="monthly_rent" value="{{ old('monthly_rent', $market->monthly_rent) }}" min="0" step="0.01" placeholder="0.00" required><em>د.أ</em></div>@error('monthly_rent')<small class="field-error">{{ $message }}</small>@enderror</label>
            <label class="field"><span>تاريخ بداية الإيجار <b>*</b></span><div class="input-icon"><i data-lucide="calendar-days"></i><input type="date" name="lease_start_date" value="{{ old('lease_start_date', $market->lease_start_date?->format('Y-m-d')) }}" required></div>@error('lease_start_date')<small class="field-error">{{ $message }}</small>@enderror</label>
            <label class="field"><span>تاريخ نهاية العقد</span><div class="input-icon"><i data-lucide="calendar-check-2"></i><input type="date" name="lease_end_date" value="{{ old('lease_end_date', $market->lease_end_date?->format('Y-m-d')) }}"></div><small class="field-help">اتركه فارغاً إذا لم يحدد العقد تاريخ نهاية.</small>@error('lease_end_date')<small class="field-error">{{ $message }}</small>@enderror</label>
            <div class="field field-switch"><span>حالة السوق</span><label class="switch-row"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $market->exists ? $market->is_active : true))><span class="switch-track"></span><span><b>سوق نشط</b><small>يظهر ضمن الأسواق النشطة والإحصائيات</small></span></label></div>
            <div class="field field-switch"><span>مصدر البيانات</span><label class="switch-row"><input type="checkbox" name="is_demo" value="1" @checked(old('is_demo', $market->exists ? $market->is_demo : false))><span class="switch-track"></span><span><b>بيانات ديمو تحتاج مراجعة</b><small>أزل العلامة بعد استبدال القيم الافتراضية بمعلومات مؤكدة</small></span></label></div>
        </div>
        <div class="rent-hint"><span><i data-lucide="calculator"></i></span><p><b>حساب الإيجار حسب المدة</b><small>قيمة الإيجار هنا هي القيمة الأساسية. أضف من صفحة «تفاصيل الإيجار» الفترات التي تغيّر فيها المبلغ أو حالة السداد أو الإخلاء.</small></p></div>
    </div>

    <div class="form-actions"><a class="button button-quiet" href="{{ $market->exists ? route('markets.show', $market) : route('groups.show', $marketGroup) }}">إلغاء</a><button class="button button-primary" type="submit"><i data-lucide="check"></i>{{ $market->exists ? 'حفظ التعديلات' : 'حفظ السوق' }}</button></div>
</form>
@endsection
