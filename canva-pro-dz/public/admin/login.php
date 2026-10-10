<?php
declare(strict_types=1);
require dirname(__DIR__, 2) . '/includes/bootstrap.php';
if (!empty($_SESSION['admin_id'])) { header('Location: /admin/index.php'); exit; }
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $now = time();
    $_SESSION['login_tries'] = array_values(array_filter($_SESSION['login_tries'] ?? [], fn($t) => $t > $now - 900));
    if (count($_SESSION['login_tries']) >= 8) { $error = 'محاولات كثيرة. انتظر 15 دقيقة ثم أعد المحاولة.'; }
    else {
        $_SESSION['login_tries'][] = $now;
        $stmt = db()->prepare('SELECT id, password_hash FROM admins WHERE username=? AND active=1 LIMIT 1');
        $stmt->execute([trim((string)($_POST['username'] ?? ''))]); $admin = $stmt->fetch();
        if ($admin && password_verify((string)($_POST['password'] ?? ''), $admin['password_hash'])) {
            session_regenerate_id(true); $_SESSION['admin_id'] = (int)$admin['id']; $_SESSION['login_tries'] = [];
            db()->prepare('UPDATE admins SET last_login_at=NOW() WHERE id=?')->execute([$admin['id']]);
            admin_log('LOGIN'); header('Location: /admin/index.php'); exit;
        }
        $error = 'بيانات الدخول غير صحيحة.';
    }
}
?><!doctype html><html lang="ar" dir="rtl"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>دخول الإدارة</title><link rel="stylesheet" href="/assets/app.css"></head><body><main class="track-wrap"><form class="form-card" method="post"><h1>دخول الإدارة</h1><?php if($error!==''): ?><p class="error"><?= e($error) ?></p><?php endif; ?><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><label>اسم المستخدم</label><input name="username" autocomplete="username" required maxlength="80"><label>كلمة المرور</label><input type="password" name="password" autocomplete="current-password" required><button class="button button-primary button-full">دخول</button><p class="small-note">لا توجد كلمة مرور افتراضية. أنشئ حساب الإدارة يدويًا وفق ملف التثبيت.</p></form></main></body></html>
