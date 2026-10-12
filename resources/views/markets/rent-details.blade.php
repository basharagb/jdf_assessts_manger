@extends('layouts.app')
@section('title', 'تفاصيل الإيجار — '.$market->name)
@section('crumb', 'تفاصيل الإيجار')
@section('content')
<div class="page-intro region-intro rent-details-intro">
    <div class="intro-icon"><i data-lucide="receipt-text"></i></div>
    <div class="intro-copy"><div class="breadcrumbs"><a href="{{ route('markets.show', $market) }}">{{ $market->name }}</a><i data-lucide="chevron-left"></i><span>تفاصيل الإيجار</span></div><h1>تفاصيل الإيجار</h1><p>سجّل تغيّر قيمة الإيجار، السنوات المدفوعة أو غير المدفوعة، وفترات الإخلاء.</p></div>
    <a class="button button-primary" href="{{ route('markets.rent-details.create', $market) }}"><i data-lucide="plus"></i> إضافة فترة</a>
</div>

<div class="rent-history-note"><i data-lucide="circle-info"></i><p><b>طريقة التسجيل</b><span>أدخل نطاقًا لكل فترة. مثال: سنة الإيجار 2024 بإيجار 4,000 د.أ، ثم أضف الفترة التالية بإيجار 5,000 د.أ. اختر «إخلاء» للسنوات التي لا يستحق عنها إيجار، أو «غير مدفوع» للمبالغ المستحقة غير المحصلة. لإنشاء فترة لاحقة، حدّد نهاية الفترة الحالية أولاً.</span></p></div>

<div class="rent-history-summary"><span><i data-lucide="wallet"></i> الإيجار الأساسي المسجل للسوق</span><b>{{ number_format((float) $market->annual_rent, 2) }} د.أ <small>سنوياً</small></b><a href="{{ route('markets.show', $market) }}">العودة للسوق <i data-lucide="arrow-left"></i></a></div>

@if($periods->isNotEmpty())
    <div class="rent-period-list">
        @foreach($periods as $period)
            <article class="rent-period-card">
                <div class="rent-period-card-main"><span class="rent-period-icon"><i data-lucide="calendar-range"></i></span><div><span class="section-kicker">الفترة {{ $loop->iteration }}</span><h2>{{ $period->start_year }} <i data-lucide="arrow-left"></i> {{ $period->end_year ?? 'مستمرة' }}</h2>@if($period->notes)<p>{{ $period->notes }}</p>@endif</div></div>
                <div class="rent-period-value"><span>الإيجار السنوي</span><b>{{ $period->status === 'vacant' ? '0.00' : number_format((float) $period->annual_rent, 2) }} <small>د.أ</small></b></div>
                <span class="rent-period-status rent-status-{{ $period->status }}">{{ $statusLabels[$period->status] }}</span>
                <div class="rent-period-actions"><a class="button button-outline button-small" href="{{ route('markets.rent-details.edit', [$market, $period]) }}"><i data-lucide="pencil"></i> تعديل</a><form method="POST" action="{{ route('markets.rent-details.destroy', [$market, $period]) }}" onsubmit="return confirm('حذف فترة الإيجار هذه؟')">@csrf @method('DELETE')<button class="button button-danger-light button-small" type="submit"><i data-lucide="trash-2"></i> حذف</button></form></div>
            </article>
        @endforeach
    </div>
@else
    <div class="empty-state rent-history-empty"><span class="empty-icon"><i data-lucide="calendar-plus"></i></span><h3>لا توجد فترات إيجار تفصيلية بعد</h3><p>يستخدم النظام حالياً الإيجار الأساسي للسوق. أضف الفترات لتسجيل تغيّر المبلغ أو الإخلاء أو عدم السداد.</p><a class="button button-primary" href="{{ route('markets.rent-details.create', $market) }}"><i data-lucide="plus"></i> إضافة أول فترة</a></div>
@endif
@endsection
