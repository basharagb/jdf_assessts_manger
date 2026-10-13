@extends('layouts.app')
@section('title', 'توسعة السوق — '.$market->name)
@section('crumb', 'توسعة السوق')
@section('content')
<div class="page-intro region-intro rent-details-intro">
    <div class="intro-icon"><i data-lucide="expand"></i></div>
    <div class="intro-copy"><div class="breadcrumbs"><a href="{{ route('markets.show', $market) }}">{{ $market->name }}</a><i data-lucide="chevron-left"></i><span>توسعة السوق</span></div><h1>توسعة السوق</h1><p>سجّل كل توسعة والمبلغ الذي يُضاف إلى الإيجار السنوي بسببها اعتباراً من سنة بدئها.</p></div>
</div>

@if($market->expansions->isNotEmpty())
    <div class="rent-period-list">
        @foreach($market->expansions as $expansion)
            <article class="rent-period-card">
                <div class="rent-period-card-main"><span class="rent-period-icon"><i data-lucide="expand"></i></span><div><span class="section-kicker">التوسعة {{ $loop->iteration }}@if($expansion->section) · {{ $expansion->section->name }}@endif</span><h2>اعتباراً من {{ $expansion->start_year }}</h2><p class="rent-period-dates">من {{ \App\AnnualLease::anniversary($market, $expansion->start_year)->format('d/m/Y') }}@if($expansion->added_area) · مساحة مضافة {{ number_format((float) $expansion->added_area, 2) }} م²@endif</p>@if($expansion->notes)<p>{{ $expansion->notes }}</p>@endif</div></div>
                <div class="rent-period-value"><span>المبلغ المضاف سنوياً</span><b>+{{ number_format((float) $expansion->added_annual_rent, 2) }} <small>د.أ</small></b></div>
                <div class="rent-period-actions"><form method="POST" action="{{ route('markets.expansions.destroy', [$market, $expansion]) }}" onsubmit="return confirm('حذف هذه التوسعة؟')">@csrf @method('DELETE')<button class="button button-danger-light button-small" type="submit"><i data-lucide="trash-2"></i> حذف</button></form></div>
            </article>
        @endforeach
    </div>
@endif

<form class="detail-card rent-period-form expansion-form" method="POST" action="{{ route('markets.expansions.store', $market) }}">
    @csrf
    <h2>إضافة توسعة</h2>
    @if($market->hasSections())
        <label class="field"><span>السوق الذي تمت توسعته <b>*</b></span><select name="market_section_id" required>@foreach($market->sections as $section)<option value="{{ $section->id }}" @selected((int) old('market_section_id') === $section->id)>{{ $section->name }}</option>@endforeach</select>@error('market_section_id')<small class="field-error">{{ $message }}</small>@enderror</label>
    @endif
    @include('partials.year-picker', ['pickerId' => 'expansionStartYear', 'name' => 'start_year', 'label' => 'تبدأ التوسعة من سنة إيجار', 'value' => old('start_year', $defaultYear), 'min' => $firstYear, 'max' => $lastYear, 'required' => true])
    @error('start_year')<small class="field-error">{{ $message }}</small>@enderror
    <label class="field"><span>المبلغ المضاف إلى الإيجار السنوي <b>*</b></span><div class="input-icon input-suffix"><i data-lucide="wallet"></i><input type="number" name="added_annual_rent" value="{{ old('added_annual_rent') }}" min="0" step="0.01" placeholder="0.00" required><em>د.أ</em></div><small class="field-help">يُضاف هذا المبلغ إلى إيجار كل سنة اعتباراً من سنة بدء التوسعة.</small>@error('added_annual_rent')<small class="field-error">{{ $message }}</small>@enderror</label>
    <label class="field"><span>المساحة المضافة</span><div class="input-icon input-suffix"><i data-lucide="move-diagonal"></i><input type="number" name="added_area" value="{{ old('added_area') }}" min="0" step="0.01" placeholder="0.00"><em>م²</em></div>@error('added_area')<small class="field-error">{{ $message }}</small>@enderror</label>
    <label class="field"><span>ملاحظات</span><textarea name="notes" rows="3" maxlength="2000" placeholder="تفاصيل التوسعة...">{{ old('notes') }}</textarea>@error('notes')<small class="field-error">{{ $message }}</small>@enderror</label>
    <div class="rent-period-form-actions"><a class="button button-quiet" href="{{ route('markets.show', $market) }}">العودة للسوق</a><button class="button button-primary" type="submit"><i data-lucide="plus"></i> إضافة التوسعة</button></div>
</form>
@endsection
