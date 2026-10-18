@extends('layouts.app')
@section('title', 'التقارير')
@section('crumb', 'التقارير')
@section('content')
<div class="page-title-row"><div><h1>التقارير</h1><p>تقارير كاملة بالصور ومعلومات العقود وسنوات الإيجار والسداد، جاهزة للعرض والطباعة وPDF.</p></div><a class="button button-primary" href="{{ route('reports.all') }}" target="_blank" rel="noopener"><i data-lucide="file-chart-column"></i> التقرير الشامل</a></div>
<div class="form-card"><h2>تقارير المناطق والأقسام</h2><div class="form-grid">
@foreach($groups as $group)<div class="detail-card"><h3>{{ $group->name }}</h3><p>{{ $group->markets_count }} سوق</p><a class="button button-outline" href="{{ route('reports.group', $group) }}" target="_blank" rel="noopener">عرض وطباعة</a> <a class="button button-primary" href="{{ route('reports.group', ['marketGroup' => $group, 'format' => 'pdf']) }}" target="_blank" rel="noopener">PDF</a></div>@endforeach
</div></div>
<div class="form-card"><h2>تقرير كل سوق</h2><div class="form-grid">
@forelse($markets as $market)<div class="detail-card"><h3>{{ $market->name }}</h3><p>{{ $market->group->name }}</p><a class="button button-outline" href="{{ route('reports.market', $market) }}" target="_blank" rel="noopener">عرض وطباعة</a> <a class="button button-primary" href="{{ route('reports.market', ['market' => $market, 'format' => 'pdf']) }}" target="_blank" rel="noopener">PDF</a>@foreach($market->sections as $section)<p>سوق {{ $section->name }} فقط: <a class="button button-outline" href="{{ route('reports.market-section', [$market, $section]) }}" target="_blank" rel="noopener">عرض وطباعة</a> <a class="button button-primary" href="{{ route('reports.market-section', ['market' => $market, 'section' => $section, 'format' => 'pdf']) }}" target="_blank" rel="noopener">PDF</a></p>@endforeach</div>@empty<p>لا توجد أسواق مسجلة.</p>@endforelse
</div></div>
@endsection
