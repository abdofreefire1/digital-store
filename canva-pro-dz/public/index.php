<?php
declare(strict_types=1);
require dirname(__DIR__) . '/includes/bootstrap.php';
$price = (float) setting('price_dzd', '300');
$binanceReady = trim(setting('payment_binance_instructions')) !== '';
$flexyReady = trim(setting('payment_flexy_instructions')) !== '' && trim(setting('flexy_number')) !== '';
?><!doctype html>
<html lang="ar" dir="rtl">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="description" content="Canva Pro DZ — معلومات الخدمة وطلب التفعيل ومتابعة الطلب.">
<title><?= e(setting('site_name', 'Canva Pro DZ')) ?></title>
<link rel="stylesheet" href="/assets/app.css">
<script defer src="/assets/app.js"></script>
</head>
<body>
<header class="topbar"><a class="brand" href="/">Canva <span>Pro DZ</span></a><div class="header-actions"><button class="lang-btn" id="language-toggle" type="button">Français</button><a class="button button-ghost" href="#order" data-i18n="order_cta">اطلب الآن</a></div></header>
<main>
<section class="hero">
<div class="hero-copy"><div class="eyebrow" data-i18n="eyebrow">خدمة رقمية • الجزائر</div>
<h1 data-i18n="hero_title">ابدأ تصاميمك بخطوة بسيطة.</h1>
<p class="lead" data-i18n="hero_text">قدّم طلبك، انتظر التحقق من إمكانية التفعيل، ثم أكّد استلام الدعوة قبل الانتقال إلى الدفع. لا تطلب الخدمة كلمة مرور Canva أو رمز التحقق الخاص بك.</p>
<div class="hero-actions"><a class="button button-primary" href="#order" data-i18n="order_cta">اطلب الآن</a><a class="text-link" href="#steps" data-i18n="how_it_works">كيف تعمل الخدمة؟</a></div>
<div class="trust-note"><span class="dot"></span><span data-i18n="manual_note">الطلبات والمدفوعات تتم مراجعتها يدويًا؛ رفع صورة لا يعني تأكيد الدفع.</span></div>
</div>
<div class="price-card"><div class="price-label" data-i18n="starting_price">السعر المعلن</div><div class="price"><strong><?= e(number_format($price, 0)) ?></strong><span>دج</span></div><div class="price-detail" data-i18n="duration_note"><?= e(setting('service_duration', 'مدة الخدمة يجب تأكيدها قبل النشر')) ?></div><div class="price-divider"></div><div class="mini-row"><span data-i18n="activation">التفعيل</span><strong data-i18n="before_payment">قبل الدفع وبعد التحقق</strong></div><div class="mini-row"><span data-i18n="support">الدعم</span><strong data-i18n="human_review">مراجعة بشرية</strong></div></div>
</section>
<section class="section" id="steps"><div class="section-heading"><div class="eyebrow" data-i18n="process">الخطوات</div><h2 data-i18n="steps_title">واضح من البداية إلى النهاية</h2></div><div class="steps-grid">
<article class="step"><span class="step-number">01</span><h3 data-i18n="step1_title">أرسل الطلب</h3><p data-i18n="step1_text">أدخل بريدك الصحيح واختر طريقة الدفع المفضلة.</p></article>
<article class="step"><span class="step-number">02</span><h3 data-i18n="step2_title">تحقق وتفعيل</h3><p data-i18n="step2_text">تتم مراجعة إمكانية التفعيل بالطريقة المصرح بها، ثم تُرسل الدعوة عند توفرها.</p></article>
<article class="step"><span class="step-number">03</span><h3 data-i18n="step3_title">أكد الاستلام</h3><p data-i18n="step3_text">افتح رابط المتابعة وأكد أنك استلمت الدعوة قبل الدفع.</p></article>
<article class="step"><span class="step-number">04</span><h3 data-i18n="step4_title">ادفع وتابع</h3><p data-i18n="step4_text">أرسل مرجع الدفع أو الإيصال؛ يؤكد المسؤول وصول الأموال بشكل مستقل.</p></article>
</div></section>
<section class="section order-section" id="order"><div class="order-intro"><div class="eyebrow" data-i18n="new_order">طلب جديد</div><h2 data-i18n="form_title">أنشئ طلبك</h2><p data-i18n="form_text">سيظهر لك رابط خاص لمتابعة الطلب. احتفظ به ولا تشاركه مع الآخرين.</p><div class="payment-options"><div class="payment-option"><strong>Binance / USDT</strong><span><?= $binanceReady ? e(setting('payment_binance_instructions')) : 'تعليمات الدفع لم تُضبط بعد' ?></span></div><div class="payment-option"><strong>Flexy Normal</strong><span><?= e(setting('flexy_number', '')) ?> — <?= $flexyReady ? e(setting('payment_flexy_instructions')) : 'تعليمات الدفع لم تُضبط بعد' ?></span></div></div><p class="small-note" data-i18n="payment_notice">هذه المعلومات تعريفية؛ لا تحول أي مبلغ حتى يظهر لك تأكيد واضح وتعليمات دفع صالحة لطلبك.</p></div>
<form class="form-card" method="post" action="/create-order.php">
<input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
<label for="customer_name" data-i18n="name_label">الاسم (اختياري)</label><input id="customer_name" name="customer_name" maxlength="120" autocomplete="name">
<label for="customer_email" data-i18n="email_label">البريد الإلكتروني</label><input id="customer_email" name="customer_email" type="email" maxlength="254" autocomplete="email" required>
<label for="preferred_payment" data-i18n="payment_method">طريقة الدفع المفضلة</label><select id="preferred_payment" name="preferred_payment" required><option value="BINANCE">Binance / USDT</option><option value="FLEXY">Flexy Normal</option></select>
<label class="checkline"><input type="checkbox" name="terms" value="1" required><span data-i18n="consent">أوافق على أن طلبي يخضع للمراجعة، وأن صورة الإيصال لا تثبت وصول الأموال.</span></label>
<button class="button button-primary button-full" type="submit" data-i18n="submit_order">إنشاء الطلب</button>
<p class="form-foot" data-i18n="privacy_note">لا تدخل كلمة مرور Canva أو رموز تسجيل الدخول أو بيانات بطاقتك.</p>
</form></section>
<section class="footer-disclaimer"><strong data-i18n="important">مهم</strong><p data-i18n="disclaimer">Canva Pro DZ خدمة مستقلة ولا تدّعي أنها تابعة لـ Canva. يجب التأكد من الأهلية ومدة الخدمة وشروط الاسترداد قبل الدفع. استخدم فقط وسائل التفعيل التي تملك صلاحية تقديمها.</p></section>
</main><footer><span>© <?= date('Y') ?> Canva Pro DZ</span><a href="/track.php" data-i18n="track_order">متابعة طلب موجود</a><a href="mailto:<?= e(setting('support_email')) ?>"><?= e(setting('support_email')) ?></a></footer>
</body></html>
