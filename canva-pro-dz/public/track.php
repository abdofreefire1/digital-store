<?php
declare(strict_types=1);
require dirname(__DIR__) . '/includes/bootstrap.php';
$code = trim((string)($_GET['code'] ?? ''));
$token = trim((string)($_GET['token'] ?? ''));
$order = null;
if ($code !== '' && preg_match('/^CPDZ-[A-F0-9]{8}$/', $code) && preg_match('/^[a-f0-9]{64}$/', $token)) {
    $stmt = db()->prepare('SELECT id, public_code, customer_email, preferred_payment, amount_dzd, status, created_at, activation_sent_at, customer_confirmed_at, activation_url FROM orders WHERE public_code = ? AND tracking_token_hash = ?');
    $stmt->execute([$code, hash('sha256', $token)]);
    $order = $stmt->fetch() ?: null;
}
$statuses = ['NEW'=>'تم استلام الطلب','EMAIL_CHECK'=>'التحقق من البريد','ACTIVATION_PENDING'=>'التحقق من إمكانية التفعيل','ACTIVATION_SENT'=>'تم إرسال الدعوة','CUSTOMER_CONFIRMED'=>'تم تأكيد الاستلام','AWAITING_PAYMENT'=>'بانتظار الدفع','PAYMENT_SUBMITTED'=>'تم إرسال إثبات الدفع','PAYMENT_REVIEW'=>'مراجعة الدفع','PAID'=>'تم تأكيد الدفع','COMPLETED'=>'مكتمل','REJECTED'=>'مرفوض','CANCELLED'=>'ملغى','REFUND_PENDING'=>'الاسترداد قيد المراجعة','REFUNDED'=>'تم الاسترداد'];
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $order) {
    verify_csrf();
    if (($_POST['action'] ?? '') === 'confirm_activation' && $order['status'] === 'ACTIVATION_SENT') {
        $u = db()->prepare("UPDATE orders SET status='CUSTOMER_CONFIRMED', customer_confirmed_at=NOW() WHERE id=? AND status='ACTIVATION_SENT'");
        $u->execute([$order['id']]);
        add_order_event((int)$order['id'], 'CUSTOMER', 'ACTIVATION_CONFIRMED', 'CUSTOMER_CONFIRMED', 'Customer confirmed receipt');
        header('Location: /track.php?code=' . rawurlencode($code) . '&token=' . rawurlencode($token), true, 303); exit;
    }
    if (($_POST['action'] ?? '') === 'upload_proof') {
        $method = (string)$order['preferred_payment'];
        if (!in_array($order['status'], ['CUSTOMER_CONFIRMED','AWAITING_PAYMENT','PAYMENT_SUBMITTED','PAYMENT_REVIEW'], true)) { $error = 'لا يمكن رفع إثبات في المرحلة الحالية.'; }
        elseif (!isset($_FILES['proof']) || $_FILES['proof']['error'] !== UPLOAD_ERR_OK) { $error = 'تعذر رفع الملف.'; }
        else {
            $file = $_FILES['proof'];
            if ($file['size'] > 4 * 1024 * 1024 || $file['size'] < 1 || !is_uploaded_file($file['tmp_name'])) { $error = 'الحد الأقصى 4 ميغابايت.'; }
            else {
                $finfo = new finfo(FILEINFO_MIME_TYPE); $mime = $finfo->file($file['tmp_name']);
                $allowed = ['image/jpeg'=>'jpg','image/png'=>'png','application/pdf'=>'pdf'];
                if (!isset($allowed[$mime])) { $error = 'الصيغ المسموحة: JPG أو PNG أو PDF.'; }
                else {
                    $dir = rtrim((string)$config['proof_dir'], '/');
                    if (!is_dir($dir) && !mkdir($dir, 0700, true) && !is_dir($dir)) { $error = 'التخزين الخاص غير متاح.'; }
                    elseif (!is_writable($dir)) { $error = 'التخزين الخاص غير قابل للكتابة.'; }
                    else {
                        $stored = bin2hex(random_bytes(24)) . '.' . $allowed[$mime];
                        if (move_uploaded_file($file['tmp_name'], $dir . '/' . $stored)) {
                            $ref = trim((string)($_POST['reference_text'] ?? ''));
                            $ins = db()->prepare("INSERT INTO payment_proofs (order_id, storage_name, mime_type, file_size, reference_text) VALUES (?, ?, ?, ?, ?)");
                            $ins->execute([$order['id'], $stored, $mime, (int)$file['size'], $ref !== '' ? mb_substr($ref,0,160) : null]);
                            db()->prepare("UPDATE orders SET status='PAYMENT_SUBMITTED' WHERE id=?")->execute([$order['id']]);
                            add_order_event((int)$order['id'], 'CUSTOMER', 'PROOF_SUBMITTED', 'PAYMENT_SUBMITTED', 'Proof uploaded; not proof of settlement');
                            header('Location: /track.php?code=' . rawurlencode($code) . '&token=' . rawurlencode($token), true, 303); exit;
                        } else { $error = 'فشل حفظ الملف.'; }
                    }
                }
            }
        }
    }
    $stmt = db()->prepare('SELECT id, public_code, customer_email, preferred_payment, amount_dzd, status, created_at, activation_sent_at, customer_confirmed_at, activation_url FROM orders WHERE id=?');
    $stmt->execute([$order['id']]); $order = $stmt->fetch() ?: $order;
}
?><!doctype html><html lang="ar" dir="rtl"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>متابعة الطلب — Canva Pro DZ</title><link rel="stylesheet" href="/assets/app.css"></head><body><header class="topbar"><a class="brand" href="/">Canva <span>Pro DZ</span></a><a href="/">الصفحة الرئيسية</a></header><main class="track-wrap">
<?php if (!$order): ?>
<div class="form-card"><h1>متابعة الطلب</h1><p>أدخل رابط المتابعة الكامل الذي حصلت عليه عند إنشاء الطلب. نحتاج إلى الرمز السري الموجود في الرابط لحماية بياناتك.</p><form method="get"><label>رقم الطلب</label><input name="code" placeholder="CPDZ-XXXXXXXX" required><label>رمز المتابعة السري</label><input name="token" minlength="64" maxlength="64" required><button class="button button-primary button-full">عرض الطلب</button></form></div>
<?php else: ?>
<div class="track-head"><div class="eyebrow">رقم الطلب</div><h1><?= e($order['public_code']) ?></h1><p>أنشئ في <?= e($order['created_at']) ?></p></div>
<div class="status-card"><span class="status-pill"><?= e($statuses[$order['status']] ?? $order['status']) ?></span><h2>تفاصيل الطلب</h2><div class="detail-row"><span>البريد</span><strong><?= e($order['customer_email']) ?></strong></div><div class="detail-row"><span>طريقة الدفع المختارة</span><strong><?= $order['preferred_payment']==='BINANCE'?'Binance / USDT':'Flexy Normal' ?></strong></div><div class="detail-row"><span>المبلغ</span><strong><?= e(number_format((float)$order['amount_dzd'],0)) ?> دج</strong></div>
<?php if ($order['status']==='ACTIVATION_SENT'): ?><div class="notice"><strong>تم إرسال الدعوة.</strong><p>افحص بريدك وتأكد من استلام الدعوة.<?php if (!empty($order['activation_url'])): ?> <a href="<?= e($order['activation_url']) ?>" rel="noopener noreferrer nofollow" target="_blank">فتح رابط الدعوة</a>.<?php endif; ?> بعد التحقق فقط، اضغط لتأكيد الاستلام.</p><form method="post"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="confirm_activation"><button class="button button-primary">أؤكد استلام الدعوة</button></form></div><?php endif; ?>
<?php if (in_array($order['status'], ['CUSTOMER_CONFIRMED','AWAITING_PAYMENT','PAYMENT_SUBMITTED','PAYMENT_REVIEW'], true)): ?><div class="notice"><h3>إرسال إثبات الدفع</h3><p>ارفع إيصال العملية فقط. سيتم مراجعة العملية بشكل مستقل؛ الصورة وحدها لا تؤكد وصول الأموال.</p><?php if ($error!==''): ?><p class="error"><?= e($error) ?></p><?php endif; ?><form method="post" enctype="multipart/form-data"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="upload_proof"><label>مرجع العملية (اختياري)</label><input name="reference_text" maxlength="160"><label>الإيصال (JPG, PNG, PDF — حتى 4MB)</label><input type="file" name="proof" accept=".jpg,.jpeg,.png,.pdf" required><button class="button button-primary button-full">إرسال للمراجعة</button></form></div><?php endif; ?>
<p class="small-note">احتفظ بهذا الرابط سريًا. لا ترسل كلمة مرور Canva أو رمز تسجيل الدخول لأي شخص.</p></div>
<?php endif; ?></main><footer><span>© <?= date('Y') ?> Canva Pro DZ</span></footer></body></html>
