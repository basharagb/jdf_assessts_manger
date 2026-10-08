@extends('layouts.app')
@section('title', ($rentPeriod->exists ? 'تعديل فترة الإيجار' : 'إضافة فترة إيجار').' — '.$market->name)
@section('crumb', 'تفاصيل الإيجار')
@section('content')
<div class="page-intro region-intro rent-details-intro"><div class="intro-icon"><i data-lucide="calendar-range"></i></div><div class="intro-copy"><div class="breadcrumbs"><a href="{{ route('markets.show', $market) }}">{{ $market->name }}</a><i data-lucide="chevron-left"></i><a href="{{ route('markets.rent-details.index', $market) }}">تفاصيل الإيجار</a><i data-lucide="chevron-left"></i><span>{{ $rentPeriod->exists ? 'تعديل الفترة' : 'إضافة فترة' }}</span></div><h1>{{ $rentPeriod->exists ? 'تعديل فترة الإيجار' : 'إضافة فترة إيجار' }}</h1><p>{{ $market->name }}</p></div></div>

<form class="detail-card rent-period-form" method="POST" action="{{ $rentPeriod->exists ? route('markets.rent-details.update', [$market, $rentPeriod]) : route('markets.rent-details.store', $market) }}">
    @csrf @if($rentPeriod->exists) @method('PUT') @endif
    @include('partials.month-picker', ['pickerId' => 'periodStartMonth', 'name' => 'start_month', 'label' => 'من شهر', 'value' => old('start_month', $rentPeriod->start_month), 'min' => $pickerMin, 'max' => $pickerMax, 'required' => true])
    @include('partials.month-picker', ['pickerId' => 'periodEndMonth', 'name' => 'end_month', 'label' => 'إلى شهر (اتركه فارغاً للفترة المستمرة)', 'value' => old('end_month', $rentPeriod->end_month), 'min' => $pickerMin, 'max' => $pickerMax, 'required' => false])
    @error('start_month')<small class="field-error">{{ $message }}</small>@enderror
    @error('end_month')<small class="field-error">{{ $message }}</small>@enderror

    <label class="field"><span>حالة الإيجار</span><select id="rentPeriodStatus" name="status" required>@foreach($statusLabels as $key => $label)<option value="{{ $key }}" @selected(old('status', $rentPeriod->status ?: 'paid') === $key)>{{ $label }}</option>@endforeach</select><small class="field-help">الإخلاء يستثني الفترة من المستحقات. غير المدفوع يبقى ضمن المستحق ويظهر كمتأخر.</small>@error('status')<small class="field-error">{{ $message }}</small>@enderror</label>

    <label class="field" id="rentPeriodAmountField"><span>الإيجار الشهري <b>*</b></span><div class="input-icon input-suffix"><i data-lucide="wallet"></i><input id="rentPeriodAmount" type="number" name="monthly_rent" value="{{ old('monthly_rent', $rentPeriod->monthly_rent ?? $market->monthly_rent) }}" min="0" step="0.01" required><em>د.أ</em></div>@error('monthly_rent')<small class="field-error">{{ $message }}</small>@enderror</label>

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
    });
</script>
@endpush
@endsection
