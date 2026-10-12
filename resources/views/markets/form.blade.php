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
            @php($sectionCount = (int) old('section_count', $market->exists && $market->sections->isNotEmpty() ? $market->sections->count() : 1))
            @php($defaultSectionNames = ['القادمون', 'المغادرون', 'الشحن'])
            <div class="field field-span-2"><span>تكوين السوق <b>*</b></span>
                <div class="unit-choice" role="radiogroup">
                    @foreach([1 => ['وحدة واحدة', 'مساحة وإيجار واحد، مثل سوق البوليفارد — العبدلي'], 2 => ['قسمان منفصلان', 'مثل سوق جابر: القادمون والمغادرون'], 3 => ['ثلاثة أقسام', 'مثل سوق العمري: القادمون والمغادرون والشحن']] as $count => [$title, $hint])
                        <label class="unit-option"><input type="radio" name="section_count" value="{{ $count }}" @checked($sectionCount === $count)><span><b>{{ $title }}</b><small>{{ $hint }}</small></span></label>
                    @endforeach
                </div>
                @error('section_count')<small class="field-error">{{ $message }}</small>@enderror
            </div>
            <div class="field field-span-2 section-fields" id="sectionFields" @if($sectionCount === 1) hidden @endif>
                <span>أقسام السوق <small class="field-help">لكل قسم مساحته وإيجاره السنوي، ويُحسب إجمالي السوق تلقائياً.</small></span>
                @for($index = 0; $index < 3; $index++)
                    @php($existingSection = $market->exists ? $market->sections->get($index) : null)
                    <div class="section-input-row" data-section-row="{{ $index }}">
                        <input type="hidden" name="sections[{{ $index }}][id]" value="{{ old("sections.$index.id", $existingSection?->id) }}">
                        <label class="field"><span>اسم القسم</span><div class="input-icon"><i data-lucide="layout-panel-left"></i><input name="sections[{{ $index }}][name]" value="{{ old("sections.$index.name", $existingSection?->name ?? $defaultSectionNames[$index]) }}" maxlength="80" required></div>@error("sections.$index.name")<small class="field-error">{{ $message }}</small>@enderror</label>
                        <label class="field"><span>المساحة</span><div class="input-icon input-suffix"><i data-lucide="move-diagonal"></i><input type="number" name="sections[{{ $index }}][area]" value="{{ old("sections.$index.area", $existingSection?->area) }}" min="0.01" step="0.01" placeholder="0.00" required data-section-area><em>م²</em></div>@error("sections.$index.area")<small class="field-error">{{ $message }}</small>@enderror</label>
                        <label class="field"><span>الإيجار السنوي</span><div class="input-icon input-suffix"><i data-lucide="wallet"></i><input type="number" name="sections[{{ $index }}][annual_rent]" value="{{ old("sections.$index.annual_rent", $existingSection?->annual_rent) }}" min="0" step="0.01" placeholder="0.00" required data-section-rent><em>د.أ</em></div>@error("sections.$index.annual_rent")<small class="field-error">{{ $message }}</small>@enderror</label>
                    </div>
                @endfor
                <p class="section-totals" id="sectionTotals"></p>
                @error('sections')<small class="field-error">{{ $message }}</small>@enderror
            </div>
            <label class="field" id="singleAreaField"><span>مساحة السوق <b>*</b></span><div class="input-icon input-suffix"><i data-lucide="move-diagonal"></i><input type="number" name="area" value="{{ old('area', $market->area) }}" min="0.01" step="0.01" placeholder="0.00" required><em>م²</em></div>@error('area')<small class="field-error">{{ $message }}</small>@enderror</label>
            <label class="field field-span-2"><span>الجهة المؤجرة <b>*</b></span><div class="input-icon"><i data-lucide="building-2"></i><input name="lessor" value="{{ old('lessor', $market->lessor) }}" placeholder="اسم الجهة أو الشركة المؤجرة" required maxlength="180"></div>@error('lessor')<small class="field-error">{{ $message }}</small>@enderror</label>
            <label class="field field-span-2"><span>صورة السوق</span><div class="file-field"><input id="market-image" type="file" name="image" accept="image/jpeg,image/png,image/webp"><label for="market-image"><i data-lucide="image-plus"></i><span><b>اختر صورة</b><small>JPG أو PNG أو WEBP · حتى 5 ميغابايت</small></span><i data-lucide="upload-cloud" class="upload-arrow"></i></label></div>@if($market->image_path)<small class="current-image">الصورة الحالية محفوظة. ارفع صورة جديدة لاستبدالها.</small>@endif @error('image')<small class="field-error">{{ $message }}</small>@enderror</label>
            <label class="field field-span-2"><span>ملاحظات إضافية</span><textarea name="description" rows="4" placeholder="تفاصيل الموقع أو ملاحظات العقد...">{{ old('description', $market->description) }}</textarea>@error('description')<small class="field-error">{{ $message }}</small>@enderror</label>
        </div>
    </div>

    <div class="form-card">
        <div class="form-card-heading"><span class="form-step form-step-green">٢</span><div><h2>بيانات عقد الإيجار</h2><p>يستخدم النظام هذه المعلومات لحساب الإجمالي حسب الفترة التي تحددها.</p></div></div>
        <div class="form-grid">
            <label class="field" id="singleRentField"><span>الإيجار السنوي <b>*</b></span><div class="input-icon input-suffix"><i data-lucide="wallet"></i><input type="number" name="annual_rent" value="{{ old('annual_rent', $market->annual_rent) }}" min="0" step="0.01" placeholder="0.00" required><em>د.أ</em></div>@error('annual_rent')<small class="field-error">{{ $message }}</small>@enderror</label>
            <label class="field"><span>تاريخ بداية الإيجار <b>*</b></span><div class="input-icon"><i data-lucide="calendar-days"></i><input type="date" id="leaseStartDate" name="lease_start_date" min="{{ $market->exists && $market->lease_start_date->lt(\App\AnnualLease::earliestDate()) ? $market->lease_start_date->toDateString() : \App\AnnualLease::earliestDate()->toDateString() }}" value="{{ old('lease_start_date', $market->lease_start_date?->format('Y-m-d')) }}" required></div>@error('lease_start_date')<small class="field-error">{{ $message }}</small>@enderror</label>
            <label class="field"><span>سنة نهاية العقد</span><div class="input-icon"><i data-lucide="calendar-check-2"></i><select id="leaseEndYear" name="lease_end_year" data-selected="{{ old('lease_end_year', $market->lease_end_date?->year) }}"><option value="">غير محددة</option></select></div><small class="field-help" id="leaseEndPreview">نهاية العقد في نفس يوم وشهر البداية؛ اختر السنة فقط.</small>@error('lease_end_year')<small class="field-error">{{ $message }}</small>@enderror</label>
            <div class="field field-switch"><span>حالة السوق</span><label class="switch-row"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $market->exists ? $market->is_active : true))><span class="switch-track"></span><span><b>سوق نشط</b><small>يظهر ضمن الأسواق النشطة والإحصائيات</small></span></label></div>
            <div class="field field-switch"><span>مصدر البيانات</span><label class="switch-row"><input type="checkbox" name="is_demo" value="1" @checked(old('is_demo', $market->exists ? $market->is_demo : false))><span class="switch-track"></span><span><b>بيانات ديمو تحتاج مراجعة</b><small>أزل العلامة بعد استبدال القيم الافتراضية بمعلومات مؤكدة</small></span></label></div>
        </div>
        <div class="rent-hint"><span><i data-lucide="calculator"></i></span><p><b>حساب الإيجار حسب المدة</b><small>قيمة الإيجار هنا هي القيمة الأساسية. أضف من صفحة «تفاصيل الإيجار» الفترات التي تغيّر فيها المبلغ أو حالة السداد أو الإخلاء.</small></p></div>
    </div>

    <div class="form-actions"><a class="button button-quiet" href="{{ $market->exists ? route('markets.show', $market) : route('groups.show', $marketGroup) }}">إلغاء</a><button class="button button-primary" type="submit"><i data-lucide="check"></i>{{ $market->exists ? 'حفظ التعديلات' : 'حفظ السوق' }}</button></div>
</form>
@push('scripts')
<script>
window.addEventListener('DOMContentLoaded', () => {
    const start = document.getElementById('leaseStartDate');
    const end = document.getElementById('leaseEndYear');
    const preview = document.getElementById('leaseEndPreview');
    function updateYears() {
        const selected = end.value || end.dataset.selected;
        end.replaceChildren(new Option('غير محددة', ''));
        if (!/^\d{4}-\d{2}-\d{2}$/.test(start.value)) { end.disabled = true; return; }
        end.disabled = false;
        const year = Number(start.value.slice(0, 4));
        for (let next = year + 1; next <= Math.max(year + 30, Number(selected) || 0); next++) {
            end.add(new Option(String(next), String(next), false, String(next) === selected));
        }
        updatePreview();
    }
    function updatePreview() {
        if (!end.value || !start.value) { preview.textContent = 'نهاية العقد في نفس يوم وشهر البداية؛ اختر السنة فقط.'; return; }
        const month = Number(start.value.slice(5, 7));
        const day = Math.min(Number(start.value.slice(8, 10)), new Date(Number(end.value), month, 0).getDate());
        preview.textContent = `نهاية العقد: ${day}/${month}/${end.value}`;
    }
    start.addEventListener('change', updateYears);
    end.addEventListener('change', updatePreview);
    updateYears();

    const sectionFields = document.getElementById('sectionFields');
    const sectionTotals = document.getElementById('sectionTotals');
    const singleFields = [document.getElementById('singleAreaField'), document.getElementById('singleRentField')];
    const money = new Intl.NumberFormat('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    function selectedSectionCount() {
        return Number(document.querySelector('input[name="section_count"]:checked')?.value || 1);
    }
    function updateSectionTotals() {
        const count = selectedSectionCount();
        let area = 0, rent = 0;
        sectionFields.querySelectorAll('[data-section-row]').forEach(row => {
            if (Number(row.dataset.sectionRow) >= count) { return; }
            area += Number(row.querySelector('[data-section-area]').value) || 0;
            rent += Number(row.querySelector('[data-section-rent]').value) || 0;
        });
        sectionTotals.textContent = `إجمالي السوق: ${money.format(area)} م² · ${money.format(rent)} د.أ سنوياً`;
    }
    function updateSections() {
        const count = selectedSectionCount();
        const isSplit = count > 1;
        sectionFields.hidden = !isSplit;
        sectionFields.querySelectorAll('[data-section-row]').forEach(row => {
            const active = isSplit && Number(row.dataset.sectionRow) < count;
            row.hidden = !active;
            row.querySelectorAll('input').forEach(input => { input.disabled = !active; });
        });
        singleFields.forEach(field => {
            field.hidden = isSplit;
            field.querySelectorAll('input').forEach(input => { input.disabled = isSplit; });
        });
        updateSectionTotals();
    }
    document.querySelectorAll('input[name="section_count"]').forEach(input => input.addEventListener('change', updateSections));
    sectionFields.addEventListener('input', updateSectionTotals);
    updateSections();
});
</script>
@endpush
@endsection
