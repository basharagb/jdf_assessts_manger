<a class="market-card" href="{{ route('markets.show', $market) }}" style="--accent:{{ $market->group->accent ?? '#2563eb' }}">
    @php($displayImagePath = $market->image_path ?: 'markets/demo/jdf-generic-shop.jpg')
    <div class="market-card-media" style="--cover-image:url('{{ asset('storage/'.$displayImagePath) }}')">
        <img src="{{ asset('storage/'.$displayImagePath) }}" alt="{{ $displayImagePath === 'markets/demo/jdf-generic-shop.jpg' ? 'صورة توضيحية لمتجر الأسواق الحرة الأردنية' : 'صورة '.$market->name }}" loading="lazy">
        <span class="market-status {{ $market->is_active ? 'status-active' : 'status-muted' }}"><i></i>{{ $market->is_active ? 'نشط' : 'مؤرشف' }}</span>
        @if($market->is_demo)<span class="demo-flag">بيانات ديمو</span>@endif
        @if($displayImagePath === 'markets/demo/jdf-generic-shop.jpg')<span class="market-image-note">صورة توضيحية</span>@endif
        <span class="market-area-pill"><i data-lucide="move-diagonal"></i>{{ number_format((float) $market->area, 0) }} م²</span>
    </div>
    <div class="market-card-body">
        <div class="market-card-top"><span class="eyebrow">{{ $market->group->name ?? '' }}</span><span class="market-card-arrow"><i data-lucide="arrow-up-left"></i></span></div>
        <h3>{{ $market->name }}</h3>
        <p class="market-lessor"><i data-lucide="building-2"></i><span>{{ $market->lessor }}</span></p>
        @if($market->sections->isNotEmpty())<p class="market-sections"><i data-lucide="layout-panel-left"></i><span>{{ $market->sections->count() }} أقسام: {{ $market->sections->pluck('name')->join(' · ') }}</span></p>@endif
        <div class="market-card-bottom"><span class="rent-caption">الإيجار السنوي</span><strong>{{ number_format($market->currentAnnualRent(), 2) }} <small>د.أ</small></strong></div>
    </div>
</a>
