<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>{{ $title }}</title>
<style>
@if($pdf)
body{font-family:cairo;font-size:9pt;line-height:1.4;direction:rtl;text-align:right;color:#1e293b;background:white}
.paper{padding:0;margin:0}.brand-bar{background:#0f172a;color:white;padding:10px 14px}.brand-logo{width:22mm;background:white;padding:4px;vertical-align:middle;margin-left:10px}.brand-copy{font-size:12pt}.title-bar{background:#f1f5f9;border-right:4px solid #2563eb;border-left:4px solid #2563eb;text-align:center;padding:12px}.title-bar h1{font-size:20pt;color:#1e40af;margin:0}.meta-bar{border:1px solid #e2e8f0;padding:8px;margin-bottom:12px;font-size:8pt;color:#64748b}.stats{width:100%;border-spacing:4px;margin-bottom:12px}.stats td{text-align:center;padding:10px 4px;color:white;font-size:8pt}.stats strong{font-size:14pt}.stats strong.store-name{font-size:11pt}.blue{background:#2563eb}.green{background:#059669}.purple{background:#6d28d9}.orange{background:#d97706}.section-title{background:#f1f5f9;border-right:4px solid #2563eb;padding:8px 12px;font-size:12pt}.data{width:100%;border-collapse:collapse;margin:10px 0;font-size:8pt}.data th{background:#f1f5f9;color:#475569}.data th,.data td{padding:6px 4px;text-align:right;border-bottom:1px solid #e2e8f0}.market-title{text-align:center;font-size:22pt;color:#1e40af;margin:10px 0 2px}.market-group{text-align:center;color:#64748b;margin:0 0 12px}.cover-box{text-align:center;background:#f1f5f9;padding:6px}.note{background:#eff6ff;border-right:3px solid #2563eb;padding:9px 12px;margin:12px 0;font-size:8pt}.totals{background:#f1f5f9;padding:9px 12px;color:#1e40af;font-size:8pt}.footer{text-align:center;margin-top:15px;color:#64748b;font-size:8pt}.unit-title{background:#1e40af;color:white;margin-top:14px}.market-total{font-size:11pt;margin-top:10px}
@else
@if(!$pdf)@font-face{font-family:cairo;src:url('{{ asset('fonts/Cairo-Regular.ttf') }}')}@font-face{font-family:cairo;src:url('{{ asset('fonts/Cairo-Bold.ttf') }}');font-weight:bold}@endif
@page{size:A4;margin:12mm 10mm 16mm}*{box-sizing:border-box}body{font-family:cairo,DejaVu Sans,sans-serif;direction:rtl;text-align:right;color:#1e293b;line-height:1.6;font-size:11px;background:#eef2f7;margin:0}.paper{max-width:900px;margin:24px auto;background:white;padding:30px}.brand-bar{background:#0f172a;color:white;padding:14px;border-radius:8px 8px 0 0}.brand-logo{width:70px;background:white;padding:4px;vertical-align:middle;margin-left:15px}.brand-copy{font-size:14px}.title-bar{background:#f1f5f9;border-right:5px solid #2563eb;border-left:5px solid #2563eb;text-align:center;padding:15px}.title-bar h1{font-size:24px;color:#1e40af;margin:0}.meta-bar{border:1px solid #e2e8f0;padding:10px 14px;margin-bottom:18px;color:#64748b}.stats{width:100%;border-spacing:6px;margin-bottom:18px;table-layout:fixed}.stats td{text-align:center;padding:12px 6px;border-radius:8px;color:white}.stats strong{display:block;font-size:19px}.stats strong.store-name{font-size:15px;line-height:1.5}.blue{background:#2563eb}.green{background:#059669}.purple{background:#6d28d9}.orange{background:#d97706}.section-title{background:#f1f5f9;border-right:4px solid #2563eb;padding:8px 14px;font-size:15px;color:#1e293b}.data{width:100%;border-collapse:collapse;margin:12px 0;table-layout:fixed}.data th{background:#f1f5f9;color:#475569}.data th,.data td{padding:8px 6px;text-align:right;border-bottom:1px solid #e2e8f0;overflow-wrap:anywhere}.data tr:nth-child(even){background:#f8fafc}.market-cover{display:block;width:auto;height:auto;max-width:100%;max-height:460px;margin:0 auto;object-fit:contain;border-radius:8px}.cover-box{text-align:center;background:#f1f5f9;padding:10px;border-radius:8px}.market-title{text-align:center;font-size:25px;color:#1e40af;margin:12px 0 3px}.market-group{text-align:center;color:#64748b;margin:0 0 16px}.note{background:#eff6ff;border-right:3px solid #2563eb;padding:10px 14px;margin:14px 0;white-space:pre-line}.market-report{break-before:page;page-break-before:always}.market-report:first-of-type{break-before:auto;page-break-before:auto}.totals{background:#f1f5f9;padding:10px 14px;color:#1e40af}.toolbar{display:flex;flex-wrap:wrap;justify-content:center;gap:10px;padding:16px}.toolbar a,.toolbar button{font:inherit;color:white;background:#2563eb;border:0;border-radius:8px;padding:10px 20px;text-decoration:none;cursor:pointer}thead{display:table-header-group}tr{break-inside:avoid;page-break-inside:avoid}h2,h3{break-after:avoid;page-break-after:avoid}.footer{text-align:center;margin-top:20px;color:#64748b;font-size:10px}.unit-title{background:#1e40af;color:white;border-right-color:#0f172a;margin-top:22px}.totals{margin:8px 0}.market-total{font-size:15px;font-weight:bold;margin-top:16px}
@media print{body{background:white}.paper{margin:0;padding:0;max-width:none}.toolbar{display:none}.stats td,.brand-bar,.title-bar,.section-title{print-color-adjust:exact;-webkit-print-color-adjust:exact}}@media(max-width:600px){.paper{margin:0;padding:14px}.data{font-size:9px}.stats strong{font-size:14px}.brand-copy{font-size:11px}}
@endif
</style></head>
<body>
@if(!$pdf)<div class="toolbar"><a href="{{ route('reports.index') }}">العودة للتقارير</a><button onclick="window.print()">طباعة</button><a href="{{ request()->fullUrlWithQuery(['format' => 'pdf']) }}">تحميل / عرض PDF</a></div>@endif
<div class="paper">
<div class="brand-bar">@if($logo)<img class="brand-logo" src="{{ $logo }}" alt="شعار JDF">@endif<span class="brand-copy">الشؤون الادارية- توثيق وترخيص ممتلكات<br>الأسواق الحرة الأردنية</span></div>
<div class="title-bar"><h1>{{ $title }}</h1></div>
<div class="meta-bar">تاريخ التقرير: {{ now()->timezone('Asia/Amman')->format('d/m/Y H:i') }} | إعداد: {{ auth()->user()->name }}</div>
<table class="stats"><tr>@if($isSingleMarket)<td class="blue">السوق<br><strong class="store-name">{{ $entries->first()['market']->name }}</strong></td>@php($reportMarket = $entries->first()['market'])<td class="green">نوع السوق<br><strong class="store-name">@if($reportMarket->hasSections()){{ $reportMarket->sections->count() === 2 ? 'سوقان' : $reportMarket->sections->count().' أسواق' }}: {{ $reportMarket->sections->pluck('name')->join(' و') }}@else سوق واحد @endif</strong></td>@else<td class="blue">عدد الأسواق<br><strong>{{ $summary['count'] }}</strong></td><td class="green">الأسواق النشطة<br><strong>{{ $summary['active'] }}</strong></td>@endif<td class="purple">الإيجار السنوي الحالي<br><strong>{{ number_format($summary['annual'], 2) }}</strong> د.أ</td><td class="orange">إجمالي إيجارات السنوات<br><strong>{{ number_format($summary['total'], 2) }}</strong> د.أ</td></tr></table>
@if($entries->count() > 1)
<h2 class="section-title">ملخص المناطق والأقسام</h2>
<table class="data"><thead><tr><th>المنطقة</th><th>الأسواق</th><th>المساحة م²</th><th>الإيجار السنوي الحالي د.أ</th><th>إجمالي السنوات د.أ</th></tr></thead><tbody>@foreach($groupSummaries as $group)<tr><td>{{ $group['name'] }}</td><td>{{ $group['count'] }}</td><td>{{ number_format($group['area'], 2) }}</td><td>{{ number_format($group['annual'], 2) }}</td><td>{{ number_format($group['total'], 2) }}</td></tr>@endforeach</tbody></table>
@endif
@unless($isSingleMarket)
<div class="totals">المدفوع: {{ number_format($summary['paid'], 2) }} د.أ | غير المدفوع: {{ number_format($summary['unpaid'], 2) }} د.أ | بلا تفاصيل سداد: {{ number_format($summary['untracked'], 2) }} د.أ</div>
<p class="note">المبالغ محسوبة سنوياً حسب ذكرى بداية العقد. للعقود محددة النهاية يعرض التقرير كامل سنوات العقد؛ للعقود المفتوحة يعرض حتى سنة الإيجار الحالية. «بلا تفاصيل سداد» لا تعني مدفوعاً. يشمل التقرير السجلات المؤرشفة مع توضيح حالتها.</p>
@endunless
@foreach($entries as $entry)
@php($market = $entry['market'])
<article class="market-report" style="page-break-before:{{ $entries->count() > 1 ? 'always' : 'auto' }}">
@if($entry['image'])<div class="cover-box"><img class="market-cover" src="{{ $entry['image'] }}" alt="صورة {{ $market->name }}" @if($pdf)style="max-width:185mm;max-height:105mm"@endif></div>@else<div class="cover-box">لا توجد صورة محفوظة لهذا السوق</div>@endif
<h2 class="market-title">{{ $market->name }}</h2><p class="market-group">{{ $market->group->name }}</p>
@php($statusNames = ['paid' => 'مدفوع', 'unpaid' => 'غير مدفوع', 'vacant' => 'إخلاء', 'untracked' => 'بلا تفاصيل سداد'])
<table class="data"><tbody>
<tr><th>الجهة المؤجرة</th><td>{{ $market->lessor }}</td><th>المساحة</th><td>{{ number_format((float) $market->area, 2) }} م²</td></tr>
<tr><th>بداية الإيجار</th><td dir="ltr">{{ $market->lease_start_date->format('d/m/Y') }}</td><th>نهاية العقد</th><td dir="ltr">{{ $market->lease_end_date?->format('d/m/Y') ?? 'غير محددة' }}</td></tr>
<tr><th>الإيجار السنوي قبل خمس سنوات</th><td>{{ number_format((float) $market->annual_rent, 2) }} د.أ</td><th>الإيجار السنوي الحالي</th><td>{{ number_format($market->currentAnnualRent(), 2) }} د.أ</td></tr>
<tr><th>الحالة</th><td colspan="3">{{ $market->is_active ? 'نشط' : 'مؤرشف' }}</td></tr>
</tbody></table>
@if($market->description)<div class="note"><b>ملاحظات السوق والعقد</b><br>{{ $market->description }}</div>@endif
@foreach($market->hasSections() ? $market->sections : [null] as $section)
@php($unitId = $section?->id)
@php($unitRows = array_values(array_filter($entry['rows'], fn (array $row): bool => $row['section_id'] === $unitId)))
@php($unitPeriods = $market->rentPeriods->where('market_section_id', $unitId)->sortBy('start_year'))
@php($unitExpansions = $market->expansions->where('market_section_id', $unitId))
@if($section)
<h3 class="section-title unit-title">سوق {{ $section->name }}</h3>
<table class="data"><tbody>
<tr><th>المساحة</th><td>{{ number_format((float) $section->area, 2) }} م²</td><th>الإيجار السنوي قبل خمس سنوات</th><td>{{ number_format((float) $section->annual_rent, 2) }} د.أ</td></tr>
<tr><th>الإيجار السنوي الحالي</th><td colspan="3">{{ number_format($entry['currentRents'][$section->id] ?? 0, 2) }} د.أ</td></tr>
</tbody></table>
@endif
<h3 class="section-title">سنوات الإيجار وحالة السداد{{ $section ? ' — '.$section->name : '' }}</h3>
<table class="data"><thead><tr><th style="width:13%">سنة الإيجار</th><th style="width:16%">البداية</th><th style="width:16%">النهاية</th><th style="width:15%">الإيجار د.أ</th><th style="width:17%">السداد</th><th>ملاحظات</th></tr></thead><tbody>
@forelse($unitRows as $row)<tr><td>{{ $row['year'] }}</td><td dir="ltr">{{ \Illuminate\Support\Carbon::parse($row['start'])->format('d/m/Y') }}</td><td dir="ltr">{{ \Illuminate\Support\Carbon::parse($row['end'])->format('d/m/Y') }}</td><td>{{ number_format($row['amount'], 2) }}@if($row['expansion'] > 0) <small>(منها توسعة {{ number_format($row['expansion'], 2) }})</small>@endif</td><td>{{ $statusNames[$row['status']] }}</td><td>{{ $row['notes'] ?? '—' }}</td></tr>@empty<tr><td colspan="6">لم تبدأ سنة الإيجار الأولى بعد.</td></tr>@endforelse
</tbody></table>
@if($unitPeriods->isNotEmpty())
<h3 class="section-title">الفترات المسجلة{{ $section ? ' — '.$section->name : '' }}</h3><table class="data"><thead><tr><th>من</th><th>إلى</th><th>الإيجار السنوي د.أ</th><th>الحالة</th><th>ملاحظات</th></tr></thead><tbody>@foreach($unitPeriods as $period)<tr><td>{{ $period->start_year }} <small dir="ltr">({{ \App\AnnualLease::anniversary($market, $period->start_year)->format('d/m/Y') }})</small></td><td>@if($period->end_year){{ $period->end_year }} <small dir="ltr">({{ \App\AnnualLease::anniversary($market, $period->end_year)->format('d/m/Y') }})</small>@else مستمرة @endif</td><td>{{ number_format((float) $period->annual_rent, 2) }}</td><td>{{ $statusNames[$period->status] }}</td><td>{{ $period->notes }}</td></tr>@endforeach</tbody></table>
@endif
@if($unitExpansions->isNotEmpty())
<h3 class="section-title">توسعات السوق{{ $section ? ' — '.$section->name : '' }}</h3><table class="data"><thead><tr><th>اعتباراً من</th><th>المبلغ المضاف سنوياً د.أ</th><th>المساحة المضافة م²</th><th>ملاحظات</th></tr></thead><tbody>@foreach($unitExpansions as $expansion)<tr><td>{{ $expansion->start_year }} <small dir="ltr">({{ \App\AnnualLease::anniversary($market, $expansion->start_year)->format('d/m/Y') }})</small></td><td>{{ number_format((float) $expansion->added_annual_rent, 2) }}</td><td>{{ $expansion->added_area ? number_format((float) $expansion->added_area, 2) : '—' }}</td><td>{{ $expansion->notes }}</td></tr>@endforeach</tbody></table>
@endif
@if($section)<div class="totals">إجمالي سوق {{ $section->name }}: {{ number_format(\App\AnnualLease::totals($unitRows)['total'], 2) }} د.أ</div>@endif
@endforeach
<div class="totals market-total">إجمالي السوق: {{ number_format($entry['totals']['total'], 2) }} د.أ</div>
</article>
@endforeach
@if($entries->isEmpty())<p>لا توجد أسواق ضمن هذا التقرير.</p>@endif
<div class="footer">الشؤون الادارية- توثيق وترخيص ممتلكات | الأسواق الحرة الأردنية</div>
</div></body></html>
