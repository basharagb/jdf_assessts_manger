<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'لوحة الأصول') — أسواق JDF</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <link rel="stylesheet" href="{{ asset('css/markets.css') }}">
    <link rel="stylesheet" href="{{ asset('css/settings.css') }}">
    <link rel="icon" href="{{ asset('images/jdf_logo.png') }}" type="image/png">
    <script defer src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js"></script>
    <script defer src="{{ asset('js/month-picker.js') }}?v={{ filemtime(public_path('js/month-picker.js')) }}"></script>
</head>
<body>
    @if(session('success') || $errors->any())
        <div class="flash-stack" id="flashStack">
            @if(session('success'))<div class="flash flash-success"><i data-lucide="circle-check"></i>{{ session('success') }}</div>@endif
            @if($errors->any())<div class="flash flash-error"><i data-lucide="circle-alert"></i>{{ $errors->first() }}</div>@endif
        </div>
    @endif

    <div class="app-shell">
        <div class="sidebar-backdrop" id="sidebarBackdrop" onclick="closeSidebar()"></div>
        <aside class="sidebar" id="sidebar">
            <a class="brand" href="{{ route('dashboard') }}">
                <span class="brand-logo"><img src="{{ asset('images/jdf_logo.png') }}" alt="شعار الأسواق الحرة الأردنية"></span>
                <span class="brand-copy"><b>إدارة الأصول</b><small>الأسواق الحرة الأردنية</small></span>
            </a>

            <div class="nav-caption">القائمة الرئيسية</div>
            <nav class="side-nav">
                <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                    <i data-lucide="layout-dashboard"></i><span>لوحة التحكم</span>
                </a>
                <a href="{{ route('markets.index') }}" class="nav-link {{ request()->routeIs('markets.index') || request()->routeIs('markets.show') || request()->routeIs('markets.edit') ? 'active' : '' }}">
                    <i data-lucide="store"></i><span>جميع الأسواق</span><span class="nav-count">{{ \App\Models\Market::count() }}</span>
                </a>
                <a href="{{ route('settings.edit') }}" class="nav-link {{ request()->routeIs('settings.*') ? 'active' : '' }}">
                    <i data-lucide="settings-2"></i><span>الإعدادات</span>
                </a>
            </nav>

            <div class="nav-caption nav-caption-spaced">مناطق الأسواق</div>
            <a href="{{ route('groups.index') }}" class="nav-link {{ request()->routeIs('groups.index', 'groups.create', 'groups.edit') ? 'active' : '' }}"><i data-lucide="list-plus"></i><span>إدارة المناطق</span></a>
            <nav class="side-nav">
                @foreach(\App\Models\MarketGroup::orderBy('id')->get() as $navGroup)
                    <a href="{{ route('groups.show', $navGroup) }}" class="nav-link {{ request()->routeIs('groups.show') && request()->route('marketGroup')?->slug === $navGroup->slug ? 'active' : '' }}">
                        <span class="nav-color" style="--accent:{{ $navGroup->accent }}"></span>
                        <span>{{ $navGroup->name }}</span>
                        <span class="nav-count">{{ $navGroup->markets_count ?? $navGroup->markets()->count() }}</span>
                    </a>
                @endforeach
            </nav>

            <div class="sidebar-bottom">
                <div class="sidebar-note"><span class="note-icon"><i data-lucide="shield-check"></i></span><span><b>مساحتك في أمان</b><small>بيانات الأصول محفوظة في النظام</small></span></div>
                <div class="sidebar-user">
                    <span class="user-avatar">{{ mb_substr(auth()->user()->name, 0, 1) }}</span>
                    <span class="user-copy"><b>{{ auth()->user()->name }}</b><small>مدير النظام</small></span>
                    <form method="POST" action="{{ route('logout') }}">@csrf<button class="logout-icon" type="submit" title="تسجيل الخروج"><i data-lucide="log-out"></i></button></form>
                </div>
            </div>
        </aside>

        <main class="main-area">
            <header class="topbar">
                <div class="topbar-start">
                    <button class="mobile-menu" onclick="openSidebar()" aria-label="فتح القائمة"><i data-lucide="menu"></i></button>
                    <div class="breadcrumb">إدارة الأصول <span>/</span> @yield('crumb', 'الرئيسية')</div>
                </div>
                <div class="topbar-end">
                    <form class="top-search" action="{{ route('markets.index') }}" method="GET">
                        <i data-lucide="search"></i><input name="search" value="{{ request('search') }}" placeholder="ابحث عن سوق أو جهة مؤجرة" aria-label="بحث عن سوق">
                        <kbd>⌘ K</kbd>
                    </form>
                    <div class="today-chip"><span class="today-dot"></span><span>نظام الأصول</span></div>
                </div>
            </header>

            <section class="page-wrap">
                @yield('content')
            </section>
            <footer class="footer"><span>الأسواق الحرة الأردنية</span><span>إدارة الأصول والعقود <b>•</b> JDF</span></footer>
        </main>
    </div>

    <script>
        window.addEventListener('DOMContentLoaded', () => {
            if (window.lucide) window.lucide.createIcons();
            const flash = document.getElementById('flashStack');
            if (flash) setTimeout(() => { flash.style.opacity = '0'; setTimeout(() => flash.remove(), 400); }, 4200);
        });
        window.addEventListener('keydown', event => {
            if ((event.metaKey || event.ctrlKey) && event.key.toLowerCase() === 'k') {
                event.preventDefault(); document.querySelector('.top-search input')?.focus();
            }
        });
        function openSidebar(){document.getElementById('sidebar').classList.add('is-open');document.getElementById('sidebarBackdrop').classList.add('is-visible')}
        function closeSidebar(){document.getElementById('sidebar').classList.remove('is-open');document.getElementById('sidebarBackdrop').classList.remove('is-visible')}
    </script>
    @stack('scripts')
</body>
</html>
