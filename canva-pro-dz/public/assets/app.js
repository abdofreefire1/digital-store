(() => {
  const dict = {
    ar: {
      order_cta:'اطلب الآن', eyebrow:'خدمة رقمية • الجزائر', hero_title:'ابدأ تصاميمك بخطوة بسيطة.',
      hero_text:'قدّم طلبك، انتظر التحقق من إمكانية التفعيل، ثم أكّد استلام الدعوة قبل الانتقال إلى الدفع. لا تطلب الخدمة كلمة مرور Canva أو رمز التحقق الخاص بك.',
      how_it_works:'كيف تعمل الخدمة؟', manual_note:'الطلبات والمدفوعات تتم مراجعتها يدويًا؛ رفع صورة لا يعني تأكيد الدفع.',
      starting_price:'السعر المعلن', duration_note:'مدة الخدمة يجب تأكيدها قبل النشر', activation:'التفعيل', before_payment:'قبل الدفع وبعد التحقق',
      support:'الدعم', human_review:'مراجعة بشرية', process:'الخطوات', steps_title:'واضح من البداية إلى النهاية',
      step1_title:'أرسل الطلب', step1_text:'أدخل بريدك الصحيح واختر طريقة الدفع المفضلة.',
      step2_title:'تحقق وتفعيل', step2_text:'تتم مراجعة إمكانية التفعيل بالطريقة المصرح بها، ثم تُرسل الدعوة عند توفرها.',
      step3_title:'أكد الاستلام', step3_text:'افتح رابط المتابعة وأكد أنك استلمت الدعوة قبل الدفع.',
      step4_title:'ادفع وتابع', step4_text:'أرسل مرجع الدفع أو الإيصال؛ يؤكد المسؤول وصول الأموال بشكل مستقل.',
      new_order:'طلب جديد', form_title:'أنشئ طلبك', form_text:'سيظهر لك رابط خاص لمتابعة الطلب. احتفظ به ولا تشاركه مع الآخرين.',
      name_label:'الاسم (اختياري)', email_label:'البريد الإلكتروني', payment_method:'طريقة الدفع المفضلة',
      consent:'أوافق على أن طلبي يخضع للمراجعة، وأن صورة الإيصال لا تثبت وصول الأموال.', submit_order:'إنشاء الطلب',
      privacy_note:'لا تدخل كلمة مرور Canva أو رموز تسجيل الدخول أو بيانات بطاقتك.',
      payment_notice:'هذه المعلومات تعريفية؛ لا تحول أي مبلغ حتى يظهر لك تأكيد واضح وتعليمات دفع صالحة لطلبك.',
      important:'مهم', disclaimer:'Canva Pro DZ خدمة مستقلة ولا تدّعي أنها تابعة لـ Canva. يجب التأكد من الأهلية ومدة الخدمة وشروط الاسترداد قبل الدفع. استخدم فقط وسائل التفعيل التي تملك صلاحية تقديمها.',
      track_order:'متابعة طلب موجود'
    },
    fr: {
      order_cta:'Commander', eyebrow:'Service numérique • Algérie', hero_title:'Lancez vos créations en toute simplicité.',
      hero_text:'Envoyez votre demande, attendez la vérification de l’éligibilité, puis confirmez la réception de l’invitation avant le paiement. Nous ne demandons jamais votre mot de passe Canva ni vos codes de connexion.',
      how_it_works:'Comment ça marche ?', manual_note:'Les commandes et paiements sont vérifiés manuellement ; une image ne confirme pas le paiement.',
      starting_price:'Prix affiché', duration_note:'La durée doit être précisée avant publication', activation:'Activation', before_payment:'Avant paiement, après vérification',
      support:'Assistance', human_review:'Vérification humaine', process:'Étapes', steps_title:'Un parcours clair',
      step1_title:'Envoyez la demande', step1_text:'Saisissez votre adresse e-mail et choisissez le moyen de paiement.',
      step2_title:'Vérification et activation', step2_text:'L’éligibilité est vérifiée via une méthode autorisée ; l’invitation est envoyée si disponible.',
      step3_title:'Confirmez la réception', step3_text:'Ouvrez le lien de suivi et confirmez la réception avant de payer.',
      step4_title:'Payez et suivez', step4_text:'Envoyez la référence ou le reçu ; le responsable vérifie séparément l’arrivée des fonds.',
      new_order:'Nouvelle commande', form_title:'Créer une commande', form_text:'Un lien privé permettra de suivre la commande. Gardez-le confidentiel.',
      name_label:'Nom (facultatif)', email_label:'Adresse e-mail', payment_method:'Moyen de paiement préféré',
      consent:'J’accepte la vérification manuelle ; une capture ne prouve pas la réception des fonds.', submit_order:'Créer la commande',
      privacy_note:'Ne saisissez pas votre mot de passe Canva, vos codes de connexion ni vos données de carte.',
      payment_notice:'Ces informations sont indicatives. Ne transférez rien sans instructions valides et confirmées pour votre commande.',
      important:'Important', disclaimer:'Canva Pro DZ est un service indépendant et ne prétend pas être affilié à Canva. Vérifiez l’éligibilité, la durée et les conditions de remboursement avant paiement. Utilisez uniquement des méthodes d’activation que vous êtes autorisé à proposer.',
      track_order:'Suivre une commande'
    }
  };
  let lang = 'ar';
  const button = document.getElementById('language-toggle');
  function apply(next) {
    lang = next;
    document.documentElement.lang = next;
    document.documentElement.dir = next === 'ar' ? 'rtl' : 'ltr';
    document.querySelectorAll('[data-i18n]').forEach(el => {
      const key = el.dataset.i18n;
      if (dict[next][key]) el.textContent = dict[next][key];
    });
    if (button) button.textContent = next === 'ar' ? 'Français' : 'العربية';
  }
  button?.addEventListener('click', () => apply(lang === 'ar' ? 'fr' : 'ar'));
})();
