# دليل النشر والتشغيل على استضافة Levante (illevante.sy)
## كشك الورد — Kashk Al-Ward

يقدم هذا الدليل إرشادات تفصيلية وواضحة لنشر موقع **كشك الورد** على استضافة **شركة Levante (illevante.sy)** الرسمية، وضمان عمل الموقع بأعلى سرعة وسلاسة، مع خدمة الصور الثابتة مباشرة من خادم الويب بدون تشغيل كود PHP أو استهلاك موارد قاعدة البيانات، وتفعيل كاش المتصفح التلقائي لمدة سنة.

---

## 1. المتطلبات الأساسية للخادم (Server Requirements)

تأكد من تفعيل الإصدار والإضافات التالية من لوحة تحكم الاستضافة (cPanel / DirectAdmin / PHP Selector):
- **إصدار PHP:** `PHP 8.2` أو أحدث (`8.3`).
- **إضافات PHP المطلوبة:**
  - `pdo_mysql` أو `pdo_pgsql` (حسب نوع قاعدة البيانات المختارة)
  - `gd` أو `imagick` (لمعالجة الصور وتوليد صيغ WebP)
  - `fileinfo` (للتحقق من أنواع ملفات الصور المرفوعة)
  - `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`, `bcmath`, `zip`
  - `curl`
- **حدود الذاكرة والرفع المقترحة في php.ini:**
  - `memory_limit = 256M`
  - `upload_max_filesize = 20M`
  - `post_max_size = 25M`
  - `max_execution_time = 120`

---

## 2. هيكلية المجلدات وضبط الـ Document Root

في بيئات استضافة cPanel و Apache، يجب أن يشير مسار الموقع الرئيسي (Document Root) إلى مجلد `public` الخاص بلارافيل وليس إلى المجلد الجذري للمشروع، لضمان الحماية الكاملة للملفات الحساسة (`.env`, `storage`, `app`).

### الخيار أ (موصى به في cPanel):
1. ارفع ملفات المشروع كاملة في مجلد مستقل خارج `public_html`، مثلاً:
   `/home/username/kashk-app/`
2. من إعدادات النطاقات في cPanel (Domains / Subdomains)، قم بتعيين الـ Document Root للنطاق ليكون:
   `/home/username/kashk-app/public`

### الخيار ب (إذا كان النطاق الرئيسي مقيداً بـ `public_html`):
1. ارفع ملفات المشروع داخل مجلد خارج `public_html` (مثلاً `/home/username/kashk/`).
2. انقل محتويات مجلد `public/` فقط إلى داخل `public_html/`.
3. عدل ملف `index.php` داخل `public_html/` ليشير إلى المسار الصحيح:
   ```php
   require __DIR__.'/../kashk/vendor/autoload.php';
   $app = require_once __DIR__.'/../kashk/bootstrap/app.php';
   ```

---

## 3. إعداد الاتصال بقاعدة البيانات وملف `.env`

قم بإنشاء ملف `.env` في المجلد الرئيسي للمشروع (يمكنك نسخ `.env.production` أو `.env.example`):
```bash
cp .env.production .env
```

قم بضبط المتغيرات الرئيسية وفق بيانات حسابك لدى illevante:
```dotenv
APP_NAME="كشك الورد"
APP_ENV=production
APP_KEY= # يتم توليده بأمر php artisan key:generate
APP_DEBUG=false
APP_URL=https://kashkalward.sy

APP_TIMEZONE=Asia/Damascus
APP_LOCALE=ar
APP_FALLBACK_LOCALE=ar

# قاعدة البيانات (MySQL في الاستضافات المشتركة)
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=اسم_قاعدة_البيانات
DB_USERNAME=اسم_المستخدم
DB_PASSWORD=كلمة_المرور_القوية

# التخزين والجلسات
FILESYSTEM_DISK=public
SESSION_DRIVER=file # أو redis إن وجد
CACHE_STORE=file   # أو redis إن وجد
QUEUE_CONNECTION=database

# إشعارات بوت التلغرام للطلبات الجديدة
KASHK_TELEGRAM_BOT_TOKEN=your-telegram-bot-token
KASHK_TELEGRAM_CHAT_ID=your-telegram-chat-id
```

ثم قم بتوليد مفتاح التشفير وتشغيل الميجريشن:
```bash
php artisan key:generate
php artisan migrate --force
```

---

## 4. ربط مجلد التخزين العام (Storage Symlink)

هذه الخطوة حاسمة جداً لخدمة الصور مباشرة من خادم الويب بدون تشغيل كود PHP:

```bash
php artisan storage:link
```

> **ملاحظة في حال عدم توفر منفذ SSH في الاستضافة:**
> يمكنك إنشاء ملف PHP مؤقت في مجلد `public` باسم `link.php`:
> ```php
> <?php
> symlink(dirname(__DIR__) . '/storage/app/public', __DIR__ . '/storage');
> echo "Storage link created successfully!";
> ```
> ثم افتحه لمرة واحدة في المتصفح `https://your-domain.sy/link.php` ثم احذف الملف فوراً.

---

## 5. ترحيل أي صور سابقة من قاعدة البيانات إلى القرص

إذا كانت هناك بيانات قديمة أو تجريبية مخزنة بصيغة Base64، قم بتشغيل الأمر المخصص الذي أنشأناه لتحويلها لملفات حقيقية وتفريغ قاعدة البيانات:

```bash
php artisan kashk:migrate-images-to-files
```

---

## 6. كاش المتصفح الفوري والتلقائي وتسريع الأداء

### على خوادم Apache / LiteSpeed (وهو الافتراضي لدى Levante):
الملف [`public/.htaccess`](file:///c:/Users/User/.gemini/antigravity-ide/scratch/kashk-al-ward/public/.htaccess) مجهز بالكامل ويحتوي تلقائياً على:
- كاش مدته سنة كاملة `Cache-Control: public, max-age=31536000, immutable` لجميع الصور (`webp`, `jpeg`, `png`, `svg`, `ico`) والخطوط والملفات الثابتة.
- تفعيل ضغط Gzip / Deflate لكافة صفحات HTML وملفات النمط والسكريبتات.

### على خوادم Nginx أو VPS:
يمكنك استخدام ملف الإعداد الجاهز المرفق بالمشروع: [`nginx-levante.conf`](file:///c:/Users/User/.gemini/antigravity-ide/scratch/kashk-al-ward/nginx-levante.conf).

---

## 7. أوامر تحسين الأداء للإنتاج (Production Optimization)

عند اكتمال الرفع، قم بتشغيل هذه الأوامر لتفعيل الكاش البرمجي الكامل في لارافيل وتسريع الاستجابة بنسبة تزيد عن 400%:

```bash
# 1. كاش الإعدادات والمسارات والقوالب
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# 2. بناء ملفات الواجهة (CSS/JS) للإنتاج
npm run build
```

---

## 8. صلاحيات المجلدات (File Permissions)

تأكد من أن مجلدات التخزين والكاش قابلة للكتابة من قبل خادم الويب:
```bash
chmod -R 775 storage bootstrap/cache
```

---

## 9. معالجة طابور المهام (Queue Worker) في cPanel

لتشغيل إشعارات التلغرام والبريد الإلكتروني في الخلفية بسرعة دون تأخير المستخدم:
أضف مهمة Cron Job كل دقيقة عبر لوحة cPanel:
```bash
* * * * * cd /home/username/kashk-app && php artisan schedule:run >> /dev/null 2>&1
```
أو تشغيل الطابور:
```bash
* * * * * cd /home/username/kashk-app && php artisan queue:work --stop-when-empty >> /dev/null 2>&1
```
