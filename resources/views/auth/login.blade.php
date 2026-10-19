<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>تسجيل الدخول — إدارة أصول JDF</title>
    <link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800&display=swap" rel="stylesheet"><link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <script defer src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js"></script>
</head>
<body class="login-page">
    <main class="login-layout">
        <section class="login-art">
            <div class="login-art-top"><img src="{{ asset('images/jdf_logo.png') }}" alt="الأسواق الحرة الأردنية"><span>JDF <small>Jordan Duty Free Shops</small></span></div>
            <div class="login-message"><span class="welcome-kicker"><span class="sparkle">✦</span> نظام الشؤون الادارية- توثيق وترخيص ممتلكات</span><h1>كل سوق،<br><em>في مكانه.</em></h1><p>واجهة واحدة لمتابعة المناطق والأسواق والعقود والإيجارات.</p></div>
            <div class="login-graphic"><div class="graphic-circle"></div><div class="login-building"><span></span><span></span><span></span><span></span><span></span><span></span><i data-lucide="store"></i></div><div class="graphic-line"></div></div>
            <span class="login-art-footer">الأسواق الحرة الأردنية <b>•</b> الشؤون الادارية- توثيق وترخيص ممتلكات</span>
        </section>
        <section class="login-form-side">
            <div class="login-card">
                <div class="login-mobile-logo"><img src="{{ asset('images/jdf_logo.png') }}" alt="شعار JDF"></div>
                <span class="login-kicker">مرحباً بعودتك</span><h2>تسجيل الدخول</h2><p class="login-subtitle">أدخل بيانات حسابك للوصول إلى لوحة الشؤون الادارية- توثيق وترخيص ممتلكات.</p>
                @if($errors->any())<div class="login-error"><i data-lucide="circle-alert"></i><span>{{ $errors->first() }}</span></div>@endif
                <form method="POST" action="{{ route('login.store') }}" class="login-form">@csrf
                    <label class="field"><span>اسم المستخدم</span><div class="input-icon"><i data-lucide="user"></i><input type="text" name="email" value="{{ old('email') }}" autocomplete="username" placeholder="أدخل اسم المستخدم" required autofocus></div></label>
                    <label class="field"><span>كلمة المرور</span><div class="input-icon"><i data-lucide="lock-keyhole"></i><input type="password" name="password" autocomplete="current-password" placeholder="أدخل كلمة المرور" required></div></label>
                    <label class="remember-row"><input type="checkbox" name="remember" value="1"><span>تذكرني على هذا الجهاز</span></label>
                    <button class="button button-primary button-full login-submit" type="submit">دخول إلى النظام <i data-lucide="arrow-left"></i></button>
                </form>
                <div class="login-security"><i data-lucide="shield-check"></i><span>اتصال آمن ومخصص لمستخدمي النظام</span></div>
            </div>
            <footer class="login-copyright">© {{ date('Y') }} الأسواق الحرة الأردنية</footer>
        </section>
    </main>
    <script>window.addEventListener('DOMContentLoaded',()=>{if(window.lucide)window.lucide.createIcons()})</script>
</body>
</html>
