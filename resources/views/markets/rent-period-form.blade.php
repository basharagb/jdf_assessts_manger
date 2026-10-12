@extends('layouts.app')
@section('title', ($rentPeriod->exists ? 'تعديل فترة الإيجار' : 'إضافة فترة إيجار').' — '.$market->name)
@section('crumb', 'تفاصيل الإيجار')
@section('content')
<div class="page-intro region-intro rent-details-intro"><div class="intro-icon"><i data-lucide="calendar-range"></i></div><div class="intro-copy"><div class="breadcrumbs"><a href="{{ route('markets.show', $market) }}">{{ $market->name }}</a><i data-lucide="chevron-left"></i><a href="{{ route('markets.rent-details.index', $market) }}">تفاصيل الإيجار</a><i data-lucide="chevron-left"></i><span>{{ $rentPeriod->exists ? 'تعديل الفترة' : 'إضافة فترة' }}</span></div><h1>{{ $rentPeriod->exists ? 'تعديل فترة الإيجار' : 'إضافة فترة إيجار' }}</h1><p>{{ $market->name }}</p></div></div>

<form class="detail-card rent-period-form" method="POST" action="{{ $rentPeriod->exists ? route('markets.rent-details.update', [$market, $rentPeriod]) : route('markets.rent-details.store', $market) }}">
    @csrf @if($rentPeriod->exists) @method('PUT') @endif
    <p class="field-help">كل سنة إيجار تبدأ في {{ $market->lease_start_date->format('d/m') }} وتنتهي في نفس التاريخ من السنة التالية. مثال: من 2022 إلى 2023 = سنة إيجار واحدة، والفترة التالية تبدأ من 2023. الرجوع متاح حتى خمس سنوات فقط.</p>
    @if($market->hasSections())
        <label class="field"><span>القسم <b>*</b></span><select name="market_section_id" required>@foreach($market->sections as $section)<option value="{{ $section->id }}" @selected((int) old('market_section_id', $rentPeriod->market_section_id) === $section->id)>{{ $section->name }} — {{ number_format((float) $section->area, 2) }} م²</option>@endforeach</select><small class="field-help">لكل قسم فتراته وإيجاره الخاص.</small>@error('market_section_id')<small class="field-error">{{ $message }}</small>@enderror</label>
    @endif
    @include('partials.year-picker', ['pickerId' => 'periodStartYear', 'name' => 'start_year', 'label' => 'من سنة إيجار', 'value' => old('start_year', $rentPeriod->start_year), 'min' => $pickerMin, 'max' => $pickerMax, 'required' => true])
    @include('partials.year-picker', ['pickerId' => 'periodEndYear', 'name' => 'end_year', 'label' => 'إلى سنة إيجار (اتركه «مستمرة» للفترة المفتوحة)', 'value' => old('end_year', $rentPeriod->end_year), 'min' => $pickerMin + 1, 'max' => $pickerMax + 1, 'required' => false])
    <p class="period-preview" id="periodPreview" aria-live="polite"></p>
    @error('start_year')<small class="field-error">{{ $message }}</small>@enderror
    @error('end_year')<small class="field-error">{{ $message }}</small>@enderror

    <label class="field"><span>حالة الإيجار</span><select id="rentPeriodStatus" name="status" required>@foreach($statusLabels as $key => $label)<option value="{{ $key }}" @selected(old('status', $rentPeriod->status ?: 'paid') === $key)>{{ $label }}</option>@endforeach</select><small class="field-help">الإخلاء يستثني الفترة من المستحقات. غير المدفوع يبقى ضمن المستحق ويظهر كمتأخر.</small>@error('status')<small class="field-error">{{ $message }}</small>@enderror</label>

    <label class="field" id="rentPeriodAmountField"><span>الإيجار السنوي <b>*</b></span><div class="input-icon input-suffix"><i data-lucide="wallet"></i><input id="rentPeriodAmount" type="number" name="annual_rent" value="{{ old('annual_rent', $rentPeriod->annual_rent ?? $market->annual_rent) }}" min="0" step="0.01" required><em>د.أ</em></div>@error('annual_rent')<small class="field-error">{{ $message }}</small>@enderror</label>

    <label class="field"><span>ملاحظات</span><textarea name="notes" rows="3" maxlength="2000" placeholder="سبب الإخلاء أو ملاحظات السداد...">{{ old('notes', $rentPeriod->notes) }}</textarea>@error('notes')<small class="field-error">{{ $message }}</small>@enderror</label>

    <div class="rent-period-form-actions"><a class="button button-quiet" href="{{ route('markets.rent-details.index', $market) }}">إلغاء</a><button class="button button-primary" type="submit"><i data-lucide="save"></i> حفظ الفترة</button></div>
</form>
@push('scripts')
<script>
    window.addEventListener('DOMContentLoaded', () => {
        const status = document.getElementById('rentPeriodStatus');
        const amount = document.getElementById('rentPeriodAmount');
        const amountField = document.getElementById('rentPeriodAmountField');
        const updateAmount = () => {
            const isVacant = status.value === 'vacant';
            amountField.hidden = isVacant;
            amount.required = !isVacant;
            amount.disabled = isVacant;
        };
        status.addEventListener('change', updateAmount);
        updateAmount();

        const startYear = document.getElementById('periodStartYear');
        const endYear = document.getElementById('periodEndYear');
        const preview = document.getElementById('periodPreview');
        const anniversaries = @json($anniversaries);
        const updatePreview = () => {
            const start = Number(startYear.value);
            if (!endYear.value) {
                preview.textContent = `تبدأ في ${anniversaries[start] ?? start} وتستمر حتى نهاية العقد أو حتى تسجيل فترة لاحقة.`;
                preview.classList.remove('is-invalid');
                return;
            }
            const years = Number(endYear.value) - start;
            preview.textContent = years > 0
                ? `المدة: ${years === 1 ? 'سنة إيجار واحدة' : years + ' سنوات إيجار'} — من ${anniversaries[start] ?? start} إلى ${anniversaries[endYear.value] ?? endYear.value}`
                : 'سنة النهاية يجب أن تكون بعد سنة البداية.';
            preview.classList.toggle('is-invalid', years <= 0);
        };
        startYear.addEventListener('change', () => {
            if (endYear.value && Number(endYear.value) <= Number(startYear.value)) {
                const next = endYear.querySelector(`option[value="${Number(startYear.value) + 1}"]`);
                if (next) { endYear.value = next.value; }
            }
            updatePreview();
        });
        endYear.addEventListener('change', updatePreview);
        updatePreview();
    });
</script>
@endpush
@endsection
