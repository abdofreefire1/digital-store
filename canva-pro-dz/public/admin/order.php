<?php
declare(strict_types=1);
require dirname(__DIR__, 2) . '/includes/bootstrap.php'; require_admin();
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0;
$stmt=db()->prepare('SELECT * FROM orders WHERE id=?'); $stmt->execute([$id]); $order=$stmt->fetch();
if (!$order) { http_response_code(404); exit('Order not found.'); }
$error='';
$allowed=['EMAIL_CHECK','ACTIVATION_PENDING','ACTIVATION_SENT','AWAITING_PAYMENT','PAYMENT_REVIEW','PAID','COMPLETED','REJECTED','CANCELLED','REFUND_PENDING','REFUNDED'];
if($_SERVER['REQUEST_METHOD']==='POST'){
 verify_csrf(); $action=(string)($_POST['action']??''); $note=mb_substr(trim((string)($_POST['note']??'')),0,1000);
 if($action==='status' && in_array($_POST['status']??'', $allowed, true)){
  $new=(string)$_POST['status'];
  if($new==='PAID' || $new==='COMPLETED') {
   $hasPayment=db()->prepare("SELECT id FROM payments WHERE order_id=? AND status='CONFIRMED' LIMIT 1"); $hasPayment->execute([$id]);
   if(!$hasPayment->fetch()) $error='لا يمكن وضع الطلب كمدفوع قبل تسجيل تأكيد مستقل لوصول الأموال.';
  }
  if($error===''){
   db()->beginTransaction();
   try{
    db()->prepare('UPDATE orders SET status=? WHERE id=?')->execute([$new,$id]);
    if($new==='ACTIVATION_SENT') db()->prepare('UPDATE orders SET activation_sent_at=NOW() WHERE id=?')->execute([$id]);
    if(in_array($new,['AWAITING_PAYMENT','ACTIVATION_SENT'],true) && $order['status']!==$new) add_order_event($id,'ADMIN','STATUS_CHANGED',$new,$note ?: null);
    else add_order_event($id,'ADMIN','STATUS_CHANGED',$new,$note ?: null);
    admin_log('ORDER_STATUS','order',$id,$new.($note!==''?' — '.$note:''));
    db()->commit(); header('Location: /admin/order.php?id='.$id); exit;
   }catch(Throwable $e){db()->rollBack();throw $e;}
  }
 } elseif($action==='confirm_payment'){
  $ref=mb_substr(trim((string)($_POST['transaction_reference']??'')),0,160);
  if($ref==='') $error='أدخل مرجع العملية الذي تحققت منه.';
  else {
   db()->beginTransaction();
   try{
    db()->prepare("INSERT INTO payments (order_id,method,amount_dzd,status,transaction_reference,confirmed_by,confirmed_at) VALUES (?,?,?,'CONFIRMED',?,?,NOW())")->execute([$id,$order['preferred_payment'],$order['amount_dzd'],$ref,$_SESSION['admin_id']]);
    db()->prepare("UPDATE orders SET status='PAID' WHERE id=?")->execute([$id]);
    add_order_event($id,'ADMIN','PAYMENT_CONFIRMED','PAID','Funds independently verified; reference: '.$ref);
    admin_log('PAYMENT_CONFIRMED','order',$id,'Reference: '.$ref); db()->commit(); header('Location: /admin/order.php?id='.$id); exit;
   }catch(Throwable $e){db()->rollBack();throw $e;}
  }
 } elseif($action==='review_proof'){
  $proofId=filter_input(INPUT_POST,'proof_id',FILTER_VALIDATE_INT)?:0; $decision=(string)($_POST['decision']??'');
  $p=db()->prepare('SELECT id FROM payment_proofs WHERE id=? AND order_id=?');$p->execute([$proofId,$id]);
  if(!$p->fetch() || !in_array($decision,['ACCEPTED','REJECTED'],true)) $error='بيانات المراجعة غير صالحة.';
  else { db()->prepare('UPDATE payment_proofs SET review_status=?,review_note=?,reviewed_by=?,reviewed_at=NOW() WHERE id=?')->execute([$decision,$note ?: null,$_SESSION['admin_id'],$proofId]); if($decision==='REJECTED') add_order_event($id,'ADMIN','PROOF_REJECTED',$order['status'],$note ?: 'Proof rejected'); admin_log('PROOF_REVIEW','proof',$proofId,$decision); header('Location: /admin/order.php?id='.$id); exit; }
 }
}
$ev=db()->prepare('SELECT * FROM order_events WHERE order_id=? ORDER BY created_at DESC');$ev->execute([$id]);$events=$ev->fetchAll();
$ps=db()->prepare('SELECT * FROM payment_proofs WHERE order_id=? ORDER BY submitted_at DESC');$ps->execute([$id]);$proofs=$ps->fetchAll();
$labels=['NEW'=>'جديد','EMAIL_CHECK'=>'التحقق من البريد','ACTIVATION_PENDING'=>'بانتظار التفعيل','ACTIVATION_SENT'=>'تم إرسال الدعوة','CUSTOMER_CONFIRMED'=>'تم التأكيد','AWAITING_PAYMENT'=>'بانتظار الدفع','PAYMENT_SUBMITTED'=>'إثبات مرسل','PAYMENT_REVIEW'=>'مراجعة الدفع','PAID'=>'مدفوع','COMPLETED'=>'مكتمل','REJECTED'=>'مرفوض','CANCELLED'=>'ملغى','REFUND_PENDING'=>'استرداد قيد المراجعة','REFUNDED'=>'مسترد'];
?><!doctype html><html lang="ar" dir="rtl"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>الطلب <?=e($order['public_code'])?></title><link rel="stylesheet" href="/assets/app.css"></head><body><header class="topbar"><a class="brand" href="/admin/index.php">Canva <span>Pro DZ</span></a><a href="/admin/index.php">رجوع للطلبات</a></header><main class="admin-wrap"><div class="admin-heading"><div><div class="eyebrow"><?=e($order['public_code'])?></div><h1>تفاصيل الطلب</h1></div><span class="status-pill"><?=e($labels[$order['status']]??$order['status'])?></span></div><?php if($error!==''): ?><p class="error"><?=e($error)?></p><?php endif; ?><section class="table-card"><div class="detail-row"><span>البريد</span><strong><?=e($order['customer_email'])?></strong></div><div class="detail-row"><span>المبلغ</span><strong><?=e(number_format((float)$order['amount_dzd'],0))?> دج</strong></div><div class="detail-row"><span>طريقة الدفع</span><strong><?=e($order['preferred_payment'])?></strong></div><div class="detail-row"><span>تاريخ الإنشاء</span><strong><?=e($order['created_at'])?></strong></div><h2>تحديث الحالة</h2><form method="post" class="inline-form"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="action" value="status"><select name="status"><?php foreach($allowed as $s): ?><option value="<?=e($s)?>" <?=$order['status']===$s?'selected':''?>><?=e($labels[$s]??$s)?></option><?php endforeach; ?></select><input name="note" placeholder="ملاحظة داخلية (اختياري)" maxlength="1000"><button class="button button-primary">حفظ الحالة</button></form><h2>تأكيد الدفع بعد التحقق المستقل</h2><p class="small-note">لا تؤكد الدفع بناءً على صورة فقط. تحقق من وصول الأموال إلى الحساب/المحفظة، ثم أدخل مرجع العملية.</p><form method="post" class="inline-form"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="action" value="confirm_payment"><input name="transaction_reference" placeholder="مرجع العملية المتحقق منه" required maxlength="160"><button class="button button-primary">تأكيد وصول الأموال</button></form></section><section class="table-card"><h2>إثباتات الدفع</h2><?php foreach($proofs as $p): ?><div class="proof-row"><div><strong><?=e($p['submitted_at'])?></strong><p><?=e($p['mime_type'])?> — <?=e((string)$p['file_size'])?> bytes — <?=e($p['review_status'])?></p><p>مرجع العميل: <?=e($p['reference_text']??'—')?></p><p>الملف محفوظ في مساحة خاصة، ولا يُعرض عبر رابط عام.</p></div><?php if($p['review_status']==='PENDING'): ?><form method="post"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="action" value="review_proof"><input type="hidden" name="proof_id" value="<?=(int)$p['id']?>"><input name="note" placeholder="ملاحظة للمراجعة" maxlength="500"><button name="decision" value="ACCEPTED" class="button button-ghost">الإيصال مقروء</button><button name="decision" value="REJECTED" class="button button-ghost">رفض الإيصال</button></form><?php endif; ?></div><?php endforeach; ?><?php if(!$proofs): ?><p>لم يرفع العميل إثباتًا.</p><?php endif; ?></section><section class="table-card"><h2>سجل الأحداث</h2><?php foreach($events as $event): ?><div class="event-row"><strong><?=e($event['event_key'])?></strong><span><?=e($event['created_at'])?> · <?=e($event['actor_type'])?></span><p><?=e($event['note']??'')?></p></div><?php endforeach; ?></section></main></body></html>
