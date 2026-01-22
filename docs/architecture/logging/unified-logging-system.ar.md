# 📘 النسخة العربية

## **Unified Logging System — الوثيقة المعمارية النهائية (Source of Truth)**

**الحالة:** Canonical / Approved
**الغرض:** المرجع الوحيد المُلزم للتصميم والتنفيذ والمراجعة

---

## 1. هدف النظام

بناء نظام Logging معماري صارم يمنع خلط الدلالات (Semantic Mixing)، ويضمن:

* سرعة التحقيقات الأمنية
* دقة المراجعات القانونية والتنظيمية
* وضوح تحليل السلوك
* استقرار تحسين الأداء

### المخرجات الأساسية

* كل حدث يُسجَّل في **دومين واحد فقط** (One-Domain Rule)
* كل دومين يمتلك جدول MySQL مستقل قابل للبحث بالأعمدة
* منع تسجيل أي أسرار أو بيانات حساسة
* تصميم قابل للاستخراج لاحقًا كمكتبات مستقلة

---

## 2. القاعدة الذهبية: One-Domain Rule

أي Logged Event يجب أن ينتمي إلى **دومين واحد فقط** بناءً على **النية الأساسية** للحدث.

### عند وجود تداخل ظاهري

* ❌ ممنوع تكرار نفس الحدث في أكثر من دومين
* ✅ يُعتبر الحدث أكثر من **نية مستقلة**
* يتم تسجيل **أحداث متعددة منفصلة** ببيانات minimal

> الهدف: منع تلوث السجلات وتضارب التقارير.

---

## 3. الدومينات المعتمدة (نهائية)

لا يُسمح إلا بـ **6 دومينات فقط**:

1. Authoritative Audit
2. Audit Trail
3. Security Signals
4. Operational Activity
5. Diagnostics Telemetry
6. Delivery Operations

---

## 4. تعريف الدومينات

### 4.1 Authoritative Audit (Fail-Closed / Governance)

* سجل حاكم وموثوق لأي تغيير في:

    * security posture
    * الصلاحيات
    * السياسات الحاكمة
* **مصدر الحقيقة:** `authoritative_audit_outbox` (Transactional)
* `authoritative_audit_log` = materialized view فقط

❌ ممنوع:

* login failures
* permission denied
* exceptions
* notifications

---

### 4.2 Audit Trail (Data Exposure)

* الإجابة على: *مين شاف إيه ومتى؟*
* خاص بعمليات العرض والتنزيل والتصدير

❌ ممنوع:

* create/update/delete

---

### 4.3 Security Signals (Best-effort)

* إشارات أمنية للمراقبة والتحقيق
* لا تؤثر على control-flow
* غير transactional

---

### 4.4 Operational Activity (Mutations Only)

* تتبع التغييرات التشغيلية اليومية
* create / update / delete فقط

❌ ممنوع:

* read / view / export

---

### 4.5 Diagnostics Telemetry (Technical)

* مراقبة الأداء والصحة التقنية
* بدون أسرار وبدون PII
* best-effort

---

### 4.6 Delivery Operations (Async Lifecycle)

* تتبع:

    * email / sms / webhook
    * jobs / retries / failures

---

## 5. الـ Pipeline الموحد

```
HTTP/UI
 → Recorder
   → Writer/Logger
     → MySQL Storage
```

### توزيع المسؤوليات (ملزم)

* **Recorder**

    * يبني DTO
    * يجمع الـ Context
    * يطبق الـ Policy
* **Writer / Logger**

    * يكتب DTO فقط
    * لا يبني DTO
    * لا يقرر policy

❌ ممنوع:

* Controllers أو Services تكتب Logs مباشرة
* بناء DTO يدوي خارج Recorder

---

## 6. الحقول المشتركة (Normalized Context)

* event_id (UUID)
* actor_type
* actor_id
* correlation_id
* request_id
* route_name
* ip_address
* user_agent
* occurred_at DATETIME(6)

### سياسة الوقت

* **occurred_at MUST be UTC**
* التحويل للـ timezone يتم في طبقة العرض فقط

---

## 7. تعريف request_id و correlation_id

* **request_id**

    * معرف فريد لكل HTTP request واحد
* **correlation_id**

    * يربط عدة requests ضمن نفس الـ workflow أو العملية التجارية

---

## 8. قواعد الأمان (Hard Rules)

ممنوع تسجيل:

* passwords
* OTP
* access tokens
* session secrets
* encryption keys

### URLs

* تخزين path فقط
* بدون query strings

### referrer_path

* يجب:

    * إزالة query
    * **إخفاء أو mask أي token أو secret داخل path**
    * مثال:

        * ❌ `/reset-password/abc123`
        * ✅ `/reset-password/{masked}`

---

## 9. سياسة metadata JSON

* Structured فقط
* Minimal keys
* **الحد الأقصى: 64KB**
* enforcement في application layer

---

## 10. actor_type — القيم المسموحة

قائمة مغلقة (enum-like):

* SYSTEM
* ADMIN
* USER
* SERVICE
* API_CLIENT
* ANONYMOUS

❗ أي قيمة خارج القائمة تُرفض في التطبيق.

---

## 11. التخزين (Baseline)

* MySQL 5.7+
* جداول منفصلة لكل دومين
* paging ثابت: `(occurred_at, id)`

---

## 12. الأرشفة (Mode B — اختياري)

* MySQL → MySQL
* جداول `*_archive`
* نفس الأعمدة والفهارس
* بدون Foreign Keys
* ملف SQL منفصل

### قاعدة صارمة

> لا حذف من hot table إلا بعد نجاح النقل للأرشيف.

---

## 13. سياسات تشغيل افتراضية (Operational Policies)

### 13.1 Outbox Processing

* retry: exponential backoff
* max attempts: configurable (افتراضي 10)
* بعد max → dead letter / manual intervention
* alert لو lag > threshold

### 13.2 Delivery Operations Retry

* max attempts: 5
* backoff تدريجي
* status نهائي: `failed_permanent`

### 13.3 Archiving Trigger

* افتراضي:

    * records أقدم من 90 يوم
    * batch size: 10K
    * تشغيل يومي
* قابل للتغيير

---

## 14. أمثلة تطبيقية

### login_failed

→ Security Signals

### create_admin

حدثان منفصلان:

1. Authoritative Audit
2. Operational Activity

---

## 15. حالة الوثيقة

✅ **Approved — Source of Truth**
أي تغيير مستقبلي = Architectural Change ويتطلب Review جديدة.

---
