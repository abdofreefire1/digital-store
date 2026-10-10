<?php
declare(strict_types=1);
require dirname(__DIR__, 2) . '/includes/bootstrap.php'; require_admin();
$editable=['site_name','price_dzd','service_duration','flexy_number','support_email','payment_binance_instructions','payment_flexy_instructions','exchange_display_currency'];
$error='';$saved=false;
if($_SERVER['REQUEST_METHOD']==='POST'){
 verify_csrf();
 try{
  db()->beginTransaction();
  foreach($editable as $key){
   $value=trim((string)($_POST[$key]??''));
   if($key==='price_dzd' && (!is_numeric($value)||(float)$value<=0||(float)$value>10000000)) throw new RuntimeException('السعر غير صالح.');
   if($key==='support_email' && $value!=='' && !filter_var($value,FILTER_VALIDATE_EMAIL)) throw new RuntimeException('البريد الإلكتروني غير صالح.');
   $stmt=db()->prepare('INSERT INTO site_settings(setting_key,setting_value) VALUES(?,?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)');
   $stmt->execute([$key,$value]);
  }
  admin_log('SETTINGS_UPDATED'); db()->commit(); $saved=true;
 }catch(Throwable $e){if(db()->inTransaction())db()->rollBack();$error=$e instanceof RuntimeException?$e->getMessage():'تعذر حفظ الإعدادات.';}
}
?><!doctype html><html lang="ar" dir="rtl"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>إعدادات الموقع</title><link rel="stylesheet" href="/assets/app.css"></head><body><header class="topbar"><a class="brand" href="/admin/index.php">Canva <span>Pro DZ</span></a><a href="/admin/index.php">لوحة الإدارة</a></header><main class="track-wrap"><form class="form-card" method="post"><h1>إعدادات الموقع والدفع</h1><?php if($saved): ?><p class="success">تم حفظ الإعدادات.</p><?php endif; ?><?php if($error!==''): ?><p class="error"><?=e($error)?></p><?php endif; ?><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><?php $fields=[['site_name','اسم الموقع'],['price_dzd','السعر بالدينار الجزائري'],['service_duration','مدة الخدمة (اكتبها بدقة قبل النشر)'],['flexy_number','رقم Flexy'],['support_email','بريد الدعم'],['payment_binance_instructions','تعليمات Binance / USDT (لا تضعها قبل التحقق)'],['payment_flexy_instructions','تعليمات Flexy'],['exchange_display_currency','عملة تقدير السعر']]; foreach($fields as [$key,$label]): ?><label for="<?=e($key)?>"><?=e($label)?></label><?php if(str_contains($key,'instructions')): ?><textarea id="<?=e($key)?>" name="<?=e($key)?>" rows="4" maxlength="4000"><?=e(setting($key))?></textarea><?php else: ?><input id="<?=e($key)?>" name="<?=e($key)?>" value="<?=e(setting($key))?>" maxlength="250"><?php endif; ?><?php endforeach; ?><button class="button button-primary button-full">حفظ الإعدادات</button><p class="small-note">لا تُدخل أي عنوان محفظة أو رقم تحويل إلا إذا كان حقيقيًا ومملوكًا لك ومختبرًا. لا تُعرض أسعار صرف غير متحقق منها.</p></form></main></body></html>
