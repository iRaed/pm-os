# PM-OS — وثيقة التحليل المعماري والتقني
## منصة إدارة الأملاك والعقارات | Property Management Operating System
### Laravel SaaS Architecture Blueprint

---

## 1. تأكيد الفهم — ملخص تنفيذي

المنصة تتكون من **20 مجلداً رئيسياً** تغطي دورة حياة العقار المُدار بالكامل، من لحظة الاستلام إلى التوسع الاستراتيجي. بعد التحليل، تم تصنيفها إلى **4 طبقات معمارية**:

| الطبقة | المجلدات | الوظيفة |
|--------|---------|---------|
| **Foundation** | الأسس المهنية، البنية التشغيلية | إعداد النظام والهياكل التنظيمية |
| **Core Operations** | تهيئة العقار، التأجير، المستأجرين، التحصيل، الصيانة، المرافق | العمليات اليومية |
| **Governance & Finance** | الإدارة المالية، الحوكمة، جمعيات الملاك، إدارة المخاطر | الرقابة والامتثال |
| **Intelligence & Growth** | التقارير، تحسين الأداء، التسويق، التجربة، النمو، الاستراتيجية، الأنظمة والتقنية | القرارات والتوسع |

---

## 2. الهندسة المعمارية — Modular Monolith Architecture

```
app/
├── Modules/                          # كل مجلد = Module مستقل
│   ├── Foundation/                   # الأسس والبنية التشغيلية
│   │   ├── Config/
│   │   ├── Models/
│   │   ├── Services/
│   │   ├── Http/Controllers/
│   │   ├── Http/Requests/
│   │   ├── Http/Resources/
│   │   ├── Repositories/
│   │   ├── Events/
│   │   ├── Listeners/
│   │   ├── Policies/
│   │   ├── Database/Migrations/
│   │   ├── Database/Seeders/
│   │   ├── Routes/
│   │   └── Tests/
│   ├── PropertyOnboarding/           # تهيئة العقار
│   ├── Leasing/                      # التأجير وإشغال الوحدات
│   ├── TenantManagement/             # إدارة المستأجرين
│   ├── Collection/                   # التحصيل وإدارة الذمم
│   ├── Finance/                      # الإدارة المالية
│   ├── Maintenance/                  # الصيانة والتشغيل
│   ├── FacilityManagement/           # إدارة المرافق
│   ├── Governance/                   # الحوكمة والامتثال
│   ├── HOA/                          # جمعيات الملاك
│   ├── Reporting/                    # التقارير والمتابعة
│   ├── Marketing/                    # تسويق العقارات المدارة
│   ├── AssetPerformance/             # تحسين أداء الأصل
│   ├── RiskManagement/               # إدارة المخاطر
│   ├── Experience/                   # تجربة المالك والمستأجر
│   ├── Growth/                       # النمو والتوسع
│   ├── Strategy/                     # الاستراتيجية
│   └── AIEngine/                     # المحرك الذكي
│
├── Core/                             # البنية المشتركة
│   ├── MultiTenancy/                 # نظام العزل (Stancl/Tenancy)
│   ├── Auth/                         # المصادقة والصلاحيات (Spatie Permission)
│   ├── Notifications/                # محرك الإشعارات (SMS, Email, Push, WhatsApp)
│   ├── Documents/                    # محرك المستندات والتوقيع الإلكتروني
│   ├── Audit/                        # سجل المراجعة (spatie/laravel-activitylog)
│   ├── Workflow/                     # محرك سير العمل (State Machine)
│   └── Integration/                  # بوابة التكامل (إيجار، سداد، ملاك)
│
└── Api/                              # API Layer
    ├── V1/
    └── V2/
```

### Multi-Tenancy Strategy

```
┌─────────────────────────────────────────────┐
│              Load Balancer (Nginx)           │
├─────────────────────────────────────────────┤
│              Laravel Application            │
│         (Stancl/Tenancy Package)            │
├──────────┬──────────┬───────────────────────┤
│ Tenant A │ Tenant B │ Tenant C              │
│ DB: pm_a │ DB: pm_b │ DB: pm_c              │
│ Storage  │ Storage  │ Storage               │
│ /a/...   │ /b/...   │ /c/...                │
└──────────┴──────────┴───────────────────────┘

Central DB: pm_central
├── tenants          (بيانات الشركات)
├── domains          (النطاقات الفرعية)
├── plans            (خطط الاشتراك)
├── subscriptions    (الاشتراكات الفعلية)
└── global_settings  (إعدادات عامة)
```

**النهج:** Database-per-Tenant — كل شركة إدارة أملاك لها قاعدة بيانات مستقلة تماماً. هذا يضمن:
- عزل كامل للبيانات (Data Isolation)
- سهولة الـ Backup والـ Restore لكل tenant
- القدرة على تخصيص الأداء حسب حجم العميل
- الامتثال لمتطلبات حماية البيانات السعودية

---

## 3. خارطة طريق التطوير — Development Roadmap

### Phase 0: البنية التحتية (الأسابيع 1–4)
**المجلدات:** الأسس المهنية + البنية التشغيلية + الأنظمة والتقنية (جزئياً)

| المهمة | التفصيل | الأولوية |
|--------|---------|---------|
| إعداد Laravel Project | Modular Monolith scaffold, PostgreSQL, Redis | P0 |
| Multi-Tenancy | Stancl/Tenancy setup, Central DB, Tenant DB isolation | P0 |
| Auth & RBAC | Spatie Permission: أدوار (مدير عام، مدير عقار، محصّل، فني صيانة، مالك، مستأجر) | P0 |
| Audit Trail | spatie/activitylog لكل عملية | P0 |
| Notification Engine | قنوات: SMS (Unifonic), Email, Push, WhatsApp Business API | P1 |
| Document Engine | رفع، تصنيف، أرشفة المستندات مع تشفير AES-256 | P1 |
| API Foundation | Sanctum + Versioned API (v1) + Rate Limiting | P0 |
| Frontend Scaffold | Vue 3 + Inertia.js + Tailwind CSS + Arabic RTL | P0 |

**Deliverable:** نظام يعمل بتسجيل دخول، multi-tenant، RBAC، وقاعدة بيانات جاهزة.

---

### Phase 1: تهيئة العقار للإدارة (الأسابيع 5–8)
**المجلد:** تهيئة_العقار_للإدارة

| الميزة | التفصيل |
|--------|---------|
| استلام العقار | نموذج تسجيل عقار جديد مع بيانات المالك، الصك، الموقع الجغرافي |
| حصر الوثائق | Upload & Classify: صكوك، رخص بناء، مخططات، شهادات إتمام |
| بيانات المالك | ملف المالك: الهوية، IBAN، نسبة الإدارة، شروط خاصة |
| إعداد الوحدات | تسجيل كل وحدة: نوع، مساحة، طابق، مرافق، حالة |
| المعاينة الميدانية | Checklist رقمي + تصوير + تقرير حالة مع تقييم 1-5 |
| تقييم المخاطر | Risk Matrix أولية: إنشائي، كهربائي، سباكة، حريق |
| محضر التسليم | توقيع إلكتروني + PDF تلقائي |

**Deliverable:** القدرة على إضافة عقار جديد بالكامل مع كل وثائقه وتقييمه.

---

### Phase 2: التأجير وإشغال الوحدات (الأسابيع 9–14)
**المجلد:** التأجير_وإشغال_الوحدات

| الميزة | التفصيل |
|--------|---------|
| تقييم الشواغر | Dashboard للوحدات الشاغرة مع أيام الشغور وتكلفة الفرصة البديلة |
| التسعير التأجيري | محرك تسعير: مقارنة سوقية + تسعير ديناميكي + خصومات |
| تجهيز التسويق | قوالب إعلانات + تصوير احترافي + وصف تلقائي بالذكاء الاصطناعي |
| قنوات التسويق | ربط مع: عقار، حراج، منصات إيجار + إدارة القوائم |
| إدارة العملاء المحتملين | Leads Pipeline: استفسار ← معاينة ← تفاوض ← عقد |
| التحقق من المستأجرين | فحص: هوية، سجل تجاري، تاريخ ائتماني، مراجع سابقة |
| التفاوض | تتبع العروض والشروط المتفاوض عليها |
| إبرام العقود | محرر عقود ذكي + ربط مع إيجار + توقيع إلكتروني |

**Deliverable:** دورة تأجير كاملة من الإعلان إلى توقيع العقد.

---

### Phase 3: إدارة المستأجرين (الأسابيع 15–18)
**المجلد:** إدارة_المستأجرين

| الميزة | التفصيل |
|--------|---------|
| تهيئة المستأجر | Welcome Kit رقمي: دليل العقار، جهات الاتصال، اللوائح |
| الاستلام والدخول | Checklist استلام + محضر موقّع + تسليم المفاتيح |
| التواصل | بوابة مستأجر: تذاكر، إشعارات، محادثة مباشرة |
| الطلبات | نظام تذاكر: صيانة، خدمات، استفسارات مع SLA |
| الشكاوى | Workflow: تسجيل ← تصنيف ← تحقيق ← حل ← إغلاق |
| التجديدات | تنبيهات قبل 90/60/30 يوم + عرض تجديد تلقائي |
| الإخلاء | Checklist خروج + معاينة + مقاصة مالية + استرجاع المفاتيح |
| الاحتفاظ | Retention Score + حملات استبقاء تلقائية |

**Deliverable:** دورة حياة المستأجر كاملة من الدخول للخروج.

---

### Phase 4: التحصيل وإدارة الذمم (الأسابيع 19–22)
**المجلد:** التحصيل_وإدارة_الذمم

| الميزة | التفصيل |
|--------|---------|
| الفواتير | Auto-generate من العقود + دفعات مجزأة + ضريبة القيمة المضافة |
| جداول السداد | خطط سداد مرنة: شهري، ربع سنوي، سنوي، مخصص |
| متابعة التحصيل | Dashboard تحصيل: مدفوع، قيد السداد، متأخر، متعثر |
| المتأخرات | Aging Report: 30/60/90/120+ يوم مع تصنيف خطورة |
| التذكيرات | Auto-reminders: SMS + Email + WhatsApp عند الاستحقاق وبعده |
| التصعيد الإداري | Escalation Matrix: محصّل ← مشرف ← مدير ← إدارة عليا |
| التصعيد القانوني | ربط مع منصة إيجار للإخلاء + توثيق إنذارات رسمية |
| معالجة التعثر | خطط إعادة جدولة + تسوية + شطب مع موافقات |

**Deliverable:** نظام تحصيل متكامل مع تصعيد تلقائي.

---

### Phase 5: الإدارة المالية (الأسابيع 23–26)
**المجلد:** الإدارة_المالية

| الميزة | التفصيل |
|--------|---------|
| ضبط الإيرادات | تسجيل تلقائي من التحصيل + إيرادات أخرى (مواقف، إعلانات) |
| ضبط المصروفات | تصنيف: صيانة، خدمات، إدارية، رأسمالية + موافقات |
| الميزانيات | ميزانية سنوية لكل عقار + مقارنة فعلي vs مخطط |
| كشوف الملاك | Owner Statement شهري/ربع سنوي تلقائي مع التفصيل |
| التدفق النقدي | Cash Flow Forecast: متوقع vs فعلي |
| التخطيط الرأسمالي | CapEx planning: مشاريع كبرى، صناديق احتياطية |
| التقارير المالية | P&L، Balance Sheet، NOI لكل عقار ومحفظة |
| جاهزية التدقيق | Audit Trail كامل + تقارير جاهزة للمراجع الخارجي |

**Deliverable:** محرك مالي كامل مع تقارير ملاك تلقائية.

---

### Phase 6: الصيانة والتشغيل (الأسابيع 27–30)
**المجلد:** الصيانة_والتشغيل

| الميزة | التفصيل |
|--------|---------|
| الصيانة الوقائية | جداول PPM: شهرية، ربعية، سنوية لكل أصل |
| الصيانة التصحيحية | بلاغات ← أوامر عمل ← تنفيذ ← استلام |
| الاستجابة الطارئة | Priority Queue: حريق، تسريب، انقطاع كهرباء |
| أوامر العمل | Work Order Engine: إنشاء ← تعيين ← تنفيذ ← فحص ← إغلاق |
| إدارة المقاولين | سجل مقاولين + تقييم أداء + عقود إطارية |
| المواد والقطع | Inventory: مخزون، طلبات شراء، نقطة إعادة الطلب |
| الجدولة | Gantt/Calendar لجدولة الأعمال وتوزيع الفنيين |
| فحص الجودة | QA Checklist + تصوير قبل/بعد + تقييم العمل |

**Deliverable:** نظام صيانة متكامل مع أوامر عمل وإدارة مقاولين.

---

### Phase 7: إدارة المرافق (الأسابيع 31–33)
**المجلد:** إدارة_المرافق

| الميزة | التفصيل |
|--------|---------|
| النظافة | جداول نظافة + Checklist يومي/أسبوعي + تقييم |
| الأمن والسلامة | سجل حراسة + كاميرات + بلاغات أمنية + خطط إخلاء |
| العناية بالموقع | حدائق، مواقف، إنارة خارجية — جداول صيانة |
| الخدمات المشتركة | مصاعد، مولدات، خزانات مياه، تكييف مركزي |
| إدارة النفايات | جداول + موردين + امتثال بيئي |
| الأجزاء المشتركة | تتبع حالة: ردهات، ممرات، صالات، مسابح |
| كفاءة الطاقة | مراقبة استهلاك + تنبيهات تجاوز + توصيات توفير |
| قياس الجودة | KPIs للخدمات + استبيانات رضا دورية |

**Deliverable:** إدارة مرافق شاملة مع مراقبة جودة.

---

### Phase 8: الحوكمة والامتثال + جمعيات الملاك (الأسابيع 34–37)
**المجلدات:** الحوكمة_والامتثال + إدارة_جمعيات_الملاك

| الميزة | التفصيل |
|--------|---------|
| الامتثال التنظيمي | Checklist: رخص بلدية، دفاع مدني، إيجار، ملاك |
| الامتثال التعاقدي | مراقبة التزامات العقود + تنبيهات المواعيد |
| السلامة | فحوصات حريق، مصاعد، كهرباء دورية |
| التأمينات | سجل وثائق التأمين + تنبيهات التجديد |
| المخالفات | تسجيل مخالفات المستأجرين + إنذارات متدرجة |
| سجل الملاك | بيانات ملاك الوحدات + حصص الملكية |
| رسوم الاشتراكات | فوترة رسوم الخدمات المشتركة + تحصيل |
| الجمعية العمومية | دعوات + أجندة + تصويت + محاضر |

**Deliverable:** نظام حوكمة كامل متوافق مع الأنظمة السعودية.

---

### Phase 9: التقارير والمتابعة + تحسين أداء الأصل (الأسابيع 38–41)
**المجلدات:** التقارير_والمتابعة + تحسين_أداء_الأصل

| الميزة | التفصيل |
|--------|---------|
| التقارير الشهرية | Auto-generated: إشغال، تحصيل، صيانة، مالية |
| لوحات المؤشرات | Real-time Dashboards: KPIs مالية وتشغيلية |
| تقارير المالك | Owner Portal: تقارير تفاعلية + تحميل PDF |
| الملخص التنفيذي | Executive Summary تلقائي بالذكاء الاصطناعي |
| رفع الإشغال | تحليل أسباب الشغور + توصيات |
| نمو الإيرادات | تحليل Rent Roll + فرص رفع الإيجار |
| تحسين NOI | تحليل صافي الدخل التشغيلي + benchmark |
| أداء المحفظة | Portfolio Dashboard: مقارنة أداء العقارات |

**Deliverable:** منظومة تقارير ذكية مع تحليل أداء.

---

### Phase 10: التسويق + التجربة + إدارة المخاطر (الأسابيع 42–45)
**المجلدات:** تسويق_العقارات + تجربة_المالك_والمستأجر + إدارة_المخاطر

| الميزة | التفصيل |
|--------|---------|
| الهوية التسويقية | صفحة عقار عامة + صور + خريطة |
| تسويق الشواغر | نشر تلقائي على المنصات + تتبع أداء الإعلانات |
| مسارات العملاء | Funnel: إعلان ← استفسار ← معاينة ← عقد |
| زمن الاستجابة | SLA Tracker: متوسط وقت الاستجابة لكل نوع طلب |
| قياس الرضا | CSAT + NPS surveys تلقائية |
| المخاطر التشغيلية | Risk Register + Mitigation Plans |
| خطط الطوارئ | Emergency Response Plans + Communication Tree |
| استمرارية الأعمال | BCP: خطط بديلة لكل عملية حرجة |

**Deliverable:** منظومة تسويق + تجربة + مخاطر متكاملة.

---

### Phase 11: النمو والاستراتيجية (الأسابيع 46–48)
**المجلدات:** النمو_والتوسع + الاستراتيجية

| الميزة | التفصيل |
|--------|---------|
| اكتساب عقارات | Pipeline عقارات جديدة + تقييم أولي |
| التوسع الجغرافي | خرائط حرارية للفرص |
| التسعير | نماذج تسعير الخدمات + محاكاة |
| KPIs استراتيجية | Balanced Scorecard: مالي، عمليات، عملاء، نمو |
| التوسع طويل المدى | خطة 3–5 سنوات مع milestones |

**Deliverable:** أدوات التخطيط الاستراتيجي والنمو.

---

## 4. تصميم قاعدة البيانات — ERD Concept

### 4.1 الكيانات الأساسية (Core Entities)

```
┌─────────────────────────────────────────────────────────────────┐
│                     CENTRAL DATABASE                            │
├─────────────────────────────────────────────────────────────────┤
│ tenants: id, name, subdomain, plan_id, status, settings (JSON) │
│ plans: id, name, max_units, max_users, features (JSON), price  │
│ subscriptions: id, tenant_id, plan_id, starts_at, ends_at      │
└─────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────┐
│                     TENANT DATABASE (per company)               │
├─────────────────────────────────────────────────────────────────┤

[المستخدمون والصلاحيات]
users
├── id (UUID)
├── name, email, phone, national_id
├── role (enum: admin, property_manager, accountant, technician, collector)
├── is_active, last_login_at
└── settings (JSON)

roles ←→ permissions (Spatie)

[الملاك]
owners
├── id (UUID)
├── name, national_id_type, national_id
├── phone, email, iban, bank_name
├── management_fee_pct (decimal)
├── contract_start, contract_end
├── tax_registration_no
├── address (JSON)
└── notes

[العقارات — الجدول المحوري]
properties
├── id (UUID)
├── owner_id → owners
├── name, code (unique identifier)
├── type (enum: residential_compound, commercial_building, tower,
│         villa, mixed_use, land, warehouse, mall, office_building)
├── sub_type (nullable — للتصنيف الفرعي)
├── status (enum: onboarding, active, suspended, archived)
├── address_line, city, district, postal_code
├── lat, lng (PostGIS geography point)
├── total_units, total_area_sqm
├── year_built, floors_count
├── amenities (JSON: مسبح، صالة، مواقف، حديقة...)
├── onboarding_date, operation_start_date
├── risk_level (enum: low, medium, high, critical)
├── metadata (JSON — بيانات مرنة حسب نوع العقار)
├── manager_id → users
└── timestamps, soft_deletes

[الوحدات]
units
├── id (UUID)
├── property_id → properties
├── unit_number, floor
├── type (enum: apartment, office, shop, studio, villa, warehouse, parking, other)
├── area_sqm, rooms, bathrooms
├── status (enum: vacant, occupied, under_maintenance, reserved, not_available)
├── base_rent (decimal)
├── features (JSON: مكيفات، مطبخ، شرفة...)
├── meter_numbers (JSON: كهرباء، مياه، غاز)
├── last_inspection_date
└── timestamps

[العقود]
leases
├── id (UUID)
├── unit_id → units
├── tenant_id → tenants_residents (المستأجر)
├── lease_number (unique, auto-generated)
├── ejar_contract_id (nullable — رقم عقد إيجار)
├── type (enum: new, renewal, sublease)
├── status (enum: draft, active, expired, terminated, cancelled)
├── start_date, end_date
├── rent_amount, rent_frequency (enum: monthly, quarterly, semi_annual, annual)
├── deposit_amount
├── payment_method (enum: cash, bank_transfer, sadad, mada)
├── terms (JSON — شروط خاصة)
├── auto_renew (boolean)
├── renewal_increase_pct (nullable)
├── signed_at, signed_by_tenant, signed_by_manager
├── document_path (رابط ملف العقد)
└── timestamps

[المستأجرون — tenants_residents لتجنب التعارض مع SaaS tenants]
tenants_residents
├── id (UUID)
├── name, national_id_type, national_id
├── phone, email, phone_2
├── company_name, commercial_reg (nullable — للمستأجر التجاري)
├── nationality
├── emergency_contact_name, emergency_contact_phone
├── credit_score (nullable)
├── status (enum: prospect, active, former, blacklisted)
├── onboarding_completed (boolean)
├── rating (1-5)
├── notes
└── timestamps

[الفواتير]
invoices
├── id (UUID)
├── lease_id → leases
├── tenant_id → tenants_residents
├── invoice_number (unique)
├── type (enum: rent, deposit, service, penalty, maintenance, other)
├── amount, vat_amount, total_amount
├── currency (default: SAR)
├── due_date, issued_date
├── status (enum: draft, issued, partially_paid, paid, overdue, cancelled, written_off)
├── sadad_number (nullable)
├── notes
└── timestamps

[المدفوعات]
payments
├── id (UUID)
├── invoice_id → invoices
├── amount
├── payment_date
├── method (enum: cash, bank_transfer, sadad, mada, cheque)
├── reference_number
├── receipt_path
├── collected_by → users
├── status (enum: pending, confirmed, bounced, refunded)
└── timestamps

[أوامر العمل — الصيانة]
work_orders
├── id (UUID)
├── property_id → properties
├── unit_id → units (nullable — قد يكون للأجزاء المشتركة)
├── reported_by_type (enum: tenant, staff, inspection)
├── reported_by_id
├── category (enum: plumbing, electrical, hvac, structural,
│            painting, appliance, pest_control, other)
├── priority (enum: emergency, high, medium, low)
├── title, description
├── status (enum: open, assigned, in_progress, on_hold,
│          completed, verified, closed, cancelled)
├── assigned_to_type (enum: staff, contractor)
├── assigned_to_id
├── estimated_cost, actual_cost
├── estimated_hours, actual_hours
├── scheduled_date, completed_date
├── sla_deadline
├── before_photos (JSON array)
├── after_photos (JSON array)
├── tenant_rating, tenant_feedback
└── timestamps

[المقاولين/الموردين]
contractors
├── id (UUID)
├── name, company_name, commercial_reg
├── phone, email
├── specialization (JSON array: plumbing, electrical, hvac...)
├── rating (1-5), completed_orders_count
├── contract_type (enum: per_job, retainer, framework)
├── contract_start, contract_end
├── hourly_rate, status
└── timestamps

[الصيانة الوقائية]
preventive_maintenance_plans
├── id (UUID)
├── property_id → properties
├── asset_type (enum: elevator, generator, hvac, fire_system,
│              water_tank, pool, electrical_panel)
├── asset_identifier (رقم/اسم الأصل)
├── frequency (enum: daily, weekly, monthly, quarterly, semi_annual, annual)
├── checklist (JSON — قائمة فحص تفصيلية)
├── contractor_id → contractors (nullable)
├── next_scheduled_date
├── estimated_cost_per_service
├── is_active
└── timestamps

preventive_maintenance_logs
├── id (UUID)
├── plan_id → preventive_maintenance_plans
├── work_order_id → work_orders (يتم إنشاء WO تلقائياً)
├── executed_date
├── executed_by_type, executed_by_id
├── checklist_results (JSON)
├── findings, recommendations
├── photos (JSON)
├── cost
└── timestamps

[المعاينات والفحوصات]
inspections
├── id (UUID)
├── property_id → properties
├── unit_id → units (nullable)
├── type (enum: onboarding, periodic, move_in, move_out, safety, complaint)
├── inspector_id → users
├── scheduled_date, completed_date
├── checklist (JSON)
├── results (JSON)
├── overall_rating (1-5)
├── photos (JSON)
├── findings, recommendations
├── status (enum: scheduled, in_progress, completed, cancelled)
└── timestamps

[المستندات]
documents
├── id (UUID)
├── documentable_type, documentable_id (polymorphic)
├── category (enum: deed, license, contract, identity, inspection,
│            insurance, financial, legal, photo, other)
├── title, description
├── file_path, file_size, mime_type
├── uploaded_by → users
├── expiry_date (nullable — للوثائق ذات صلاحية)
├── is_verified
├── metadata (JSON)
└── timestamps

[المراسلات والتواصل]
communications
├── id (UUID)
├── communicatable_type, communicatable_id (polymorphic)
├── channel (enum: sms, email, whatsapp, push, in_app, call)
├── direction (enum: outbound, inbound)
├── subject, body
├── sent_by → users (nullable)
├── sent_to_type, sent_to_id
├── status (enum: queued, sent, delivered, read, failed)
├── template_id (nullable)
└── timestamps

[سجل المخاطر]
risks
├── id (UUID)
├── riskable_type, riskable_id (polymorphic)
├── category (enum: operational, financial, legal, safety,
│            tenant, vendor, market)
├── title, description
├── likelihood (enum: rare, unlikely, possible, likely, certain)
├── impact (enum: negligible, minor, moderate, major, catastrophic)
├── risk_score (calculated)
├── mitigation_plan
├── status (enum: identified, mitigating, accepted, resolved)
├── owner_id → users
├── review_date
└── timestamps

[جمعيات الملاك]
hoa_associations
├── id (UUID)
├── property_id → properties
├── name
├── annual_budget
├── reserve_fund_balance
├── bylaws_document_id → documents
└── timestamps

hoa_members
├── id, hoa_id → hoa_associations
├── owner_id → owners
├── unit_ids (JSON)
├── ownership_share_pct
├── voting_weight
├── fee_amount
└── timestamps

hoa_meetings
├── id, hoa_id → hoa_associations
├── type (enum: annual, extraordinary)
├── scheduled_date, location
├── agenda (JSON), minutes (text)
├── decisions (JSON)
├── attendees (JSON)
├── status (enum: scheduled, in_progress, completed, cancelled)
└── timestamps

[المعاملات المالية — دفتر الأستاذ]
ledger_entries
├── id (UUID)
├── property_id → properties
├── account_type (enum: revenue, expense, asset, liability)
├── category (enum: rent, maintenance, management_fee, insurance,
│            utility, tax, capital_expense, other)
├── amount (decimal, +/-)
├── description
├── reference_type, reference_id (polymorphic: invoice, work_order, etc.)
├── transaction_date
├── posted_by → users
└── timestamps

[التنبيهات والتذكيرات]
alerts
├── id (UUID)
├── alertable_type, alertable_id (polymorphic)
├── type (enum: lease_expiry, payment_due, maintenance_due,
│        insurance_expiry, license_expiry, inspection_due,
│        sla_breach, budget_exceeded, custom)
├── severity (enum: info, warning, critical)
├── title, message
├── trigger_date
├── is_read, read_at
├── assigned_to → users (nullable)
└── timestamps
```

### 4.2 العلاقات الرئيسية (Key Relationships)

```
Owner ──(1:N)──► Property ──(1:N)──► Unit ──(1:N)──► Lease
                    │                   │               │
                    │                   │               └──(1:N)──► Invoice ──(1:N)──► Payment
                    │                   │
                    │                   └──(N:1)──► Tenant_Resident
                    │
                    ├──(1:N)──► Work_Order ──(N:1)──► Contractor
                    │               │
                    │               └── links to ──► PM_Plan (via auto-generated WO)
                    │
                    ├──(1:N)──► Inspection
                    ├──(1:N)──► Risk
                    ├──(1:N)──► Ledger_Entry
                    ├──(1:1)──► HOA_Association
                    └──(polymorphic)──► Document, Communication, Alert
```

### 4.3 Indexes الأساسية

```sql
-- Performance-critical indexes
CREATE INDEX idx_units_property_status ON units(property_id, status);
CREATE INDEX idx_leases_unit_status ON leases(unit_id, status);
CREATE INDEX idx_leases_dates ON leases(start_date, end_date);
CREATE INDEX idx_invoices_status_due ON invoices(status, due_date);
CREATE INDEX idx_invoices_tenant ON invoices(tenant_id, status);
CREATE INDEX idx_work_orders_property_status ON work_orders(property_id, status);
CREATE INDEX idx_work_orders_priority ON work_orders(priority, status);
CREATE INDEX idx_payments_date ON payments(payment_date);
CREATE INDEX idx_ledger_property_date ON ledger_entries(property_id, transaction_date);
CREATE INDEX idx_alerts_type_read ON alerts(type, is_read, trigger_date);

-- PostGIS spatial index
CREATE INDEX idx_properties_location ON properties USING GIST(
    ST_SetSRID(ST_MakePoint(lng, lat), 4326)
);

-- Full-text search (Arabic support)
CREATE INDEX idx_properties_search ON properties USING GIN(
    to_tsvector('arabic', name || ' ' || address_line || ' ' || city)
);
```

---

## 5. استراتيجية الذكاء الاصطناعي والأتمتة

### 5.1 محرك الأتمتة (Workflow Engine)

```
Laravel Implementation:
├── State Machines (spatie/laravel-model-states)
│   ├── LeaseState: draft → active → expiring → expired/renewed/terminated
│   ├── InvoiceState: draft → issued → overdue → paid/cancelled/written_off
│   ├── WorkOrderState: open → assigned → in_progress → completed → verified → closed
│   └── TenantState: prospect → active → former → blacklisted
│
├── Scheduled Jobs (Laravel Scheduler)
│   ├── GenerateMonthlyInvoices (1st of month)
│   ├── SendPaymentReminders (3 days before, on due, 3/7/14/30 after)
│   ├── CheckLeaseExpirations (daily — 90/60/30 day alerts)
│   ├── TriggerPreventiveMaintenance (daily — check PM plans)
│   ├── CalculateOccupancyMetrics (daily)
│   ├── GenerateOwnerStatements (monthly)
│   ├── CheckInsuranceExpiry (weekly)
│   └── RunCollectionEscalation (daily — auto-escalate overdue)
│
├── Event-Driven Architecture (Laravel Events + Queues)
│   ├── LeaseCreated → GenerateInvoiceSchedule, NotifyOwner, UpdateOccupancy
│   ├── PaymentReceived → UpdateInvoice, UpdateLedger, NotifyTenant, CheckFullPayment
│   ├── WorkOrderCreated → NotifyAssignee, SetSLATimer, UpdateDashboard
│   ├── InvoiceOverdue → SendReminder, CreateAlert, CheckEscalation
│   └── InspectionCompleted → GenerateReport, CreateWorkOrders, UpdateRiskScore
│
└── Queue Workers (Laravel Horizon)
    ├── high: payments, emergency_work_orders
    ├── default: notifications, reports
    └── low: analytics, AI_processing
```

### 5.2 دمج الذكاء الاصطناعي (AI Integration)

```php
// Module: AIEngine — بنية المحرك الذكي

app/Modules/AIEngine/
├── Services/
│   ├── AIReportGenerator.php        // توليد التقارير والملخصات التنفيذية
│   ├── RiskAnalyzer.php             // تحليل المخاطر والتنبؤ
│   ├── PricingOptimizer.php         // تسعير ذكي بناءً على السوق
│   ├── TenantScorer.php             // تقييم المستأجرين المحتملين
│   ├── MaintenancePredictor.php     // صيانة تنبؤية
│   ├── CollectionOptimizer.php      // تحسين استراتيجيات التحصيل
│   ├── ChatAssistant.php            // المساعد الذكي (Chatbot)
│   └── ContentGenerator.php         // توليد محتوى الإعلانات والوصف
│
├── Prompts/                         // قوالب Prompts منظمة
│   ├── report_summary.blade.php
│   ├── risk_analysis.blade.php
│   ├── pricing_recommendation.blade.php
│   └── tenant_evaluation.blade.php
│
├── Contracts/
│   └── AIProviderInterface.php      // Interface للتبديل بين OpenAI/Claude/Local
│
└── Providers/
    ├── OpenAIProvider.php
    ├── ClaudeProvider.php
    └── LocalLLMProvider.php         // للشركات التي تريد self-hosted
```

#### حالات الاستخدام الرئيسية:

**1. الملخص التنفيذي الذكي (AI Executive Summary)**
```
Input: بيانات الشهر (إشغال، تحصيل، صيانة، مالية) → JSON
AI Processing: تحليل الأنماط + مقارنة بالشهر السابق + تحديد المخاطر
Output: تقرير نصي بالعربية مع توصيات + تنبيهات
Trigger: آخر يوم من كل شهر (Scheduled Job)
```

**2. التسعير الذكي (Smart Pricing)**
```
Input: بيانات الوحدة + إيجارات مشابهة + نسبة إشغال + موسمية
AI Processing: نموذج تسعير يحلل العوامل ويقترح سعر أمثل
Output: نطاق سعري موصى به + مبررات
Trigger: عند تسجيل شاغر جديد أو طلب تقييم
```

**3. تقييم المستأجرين (Tenant Scoring)**
```
Input: بيانات المستأجر + سجل سابق (إن وجد) + نوع النشاط
AI Processing: نموذج تسجيل نقاط: مخاطر مالية، استقرار، ملاءمة
Output: درجة 1-100 + توصية (قبول/رفض/شروط إضافية)
Trigger: عند إضافة عميل محتمل جديد
```

**4. الصيانة التنبؤية (Predictive Maintenance)**
```
Input: سجل الأعطال + عمر الأصول + بيانات IoT (مستقبلاً)
AI Processing: أنماط الأعطال المتكررة + توقع العمر المتبقي
Output: توصيات صيانة استباقية + أولوية
Trigger: تحليل أسبوعي (Scheduled)
```

**5. المساعد الذكي (AI Chatbot)**
```
القنوات: بوابة المستأجر + بوابة المالك + WhatsApp
القدرات:
├── استقبال بلاغات صيانة وتصنيفها تلقائياً
├── الرد على استفسارات المستأجرين (أوقات سداد، حالة طلب...)
├── إرشاد المالك لتقارير محددة
├── تسجيل شكوى وتوجيهها للقسم المختص
└── FAQ ذكي يتعلم من التذاكر السابقة

Implementation: Laravel + WebSocket (Reverb) + AI Provider
```

**6. أتمتة التقارير (Auto Reports)**
```
الآلية:
1. Laravel Scheduler يجمع البيانات من الـ Modules
2. يبني JSON مُهيكل لكل قسم (مالي، تشغيلي، إشغال...)
3. يرسل للـ AI Provider لتوليد narrative بالعربية
4. يولد PDF (باستخدام DomPDF/Browsershot)
5. يرسل للمالك عبر Email/WhatsApp + يحفظ في النظام
```

---

## 6. التكاملات المستقبلية (Integration Roadmap)

| النظام | النوع | الاستخدام | الأولوية |
|--------|------|----------|---------|
| إيجار (Ejar) | REST API | تسجيل العقود إلكترونياً | P0 |
| سداد (SADAD) | Payment Gateway | تحصيل إلكتروني | P0 |
| ملاك (Mullak) | API | بيانات العقارات والملاك | P1 |
| نفاذ (Nafath) | Authentication | التحقق من الهوية | P1 |
| Unifonic/Twilio | SMS/WhatsApp API | إشعارات ورسائل | P0 |
| Moyasar/HyperPay | Payment Gateway | دفع إلكتروني متنوع | P1 |
| Google Maps | Maps API | خرائط وGeocoding | P1 |
| IoT Sensors | MQTT/HTTP | حساسات مياه/حرارة/حركة | P2 |
| Power BI / Metabase | Analytics | تقارير متقدمة | P2 |
| زاتكا (ZATCA) | E-Invoicing | الفوترة الإلكترونية | P0 |

---

## 7. ملخص الجدول الزمني

| المرحلة | الأسابيع | المجلدات | الحالة |
|---------|---------|---------|--------|
| Phase 0: البنية التحتية | 1–4 | الأسس + البنية + الأنظمة | 🔲 |
| Phase 1: تهيئة العقار | 5–8 | تهيئة_العقار | 🔲 |
| Phase 2: التأجير | 9–14 | التأجير_وإشغال_الوحدات | 🔲 |
| Phase 3: المستأجرين | 15–18 | إدارة_المستأجرين | 🔲 |
| Phase 4: التحصيل | 19–22 | التحصيل_وإدارة_الذمم | 🔲 |
| Phase 5: المالية | 23–26 | الإدارة_المالية | 🔲 |
| Phase 6: الصيانة | 27–30 | الصيانة_والتشغيل | 🔲 |
| Phase 7: المرافق | 31–33 | إدارة_المرافق | 🔲 |
| Phase 8: الحوكمة | 34–37 | الحوكمة + جمعيات_الملاك | 🔲 |
| Phase 9: التقارير | 38–41 | التقارير + تحسين_الأداء | 🔲 |
| Phase 10: التسويق + التجربة | 42–45 | التسويق + التجربة + المخاطر | 🔲 |
| Phase 11: النمو | 46–48 | النمو + الاستراتيجية | 🔲 |

**إجمالي:** ~48 أسبوع (12 شهر) لفريق من 4–6 مطورين
**MVP (الحد الأدنى القابل للإطلاق):** Phases 0–4 = ~22 أسبوع (5.5 شهر)

---

## 8. التوصيات الفنية

1. **ابدأ بـ MVP:** Phases 0-4 تعطيك نظام يعمل ويُؤجّر ويُحصّل — هذا كافي للإطلاق الأول.
2. **استخدم Stancl/Tenancy v3:** أنضج حزمة Multi-Tenancy لـ Laravel مع دعم Database-per-Tenant.
3. **استثمر في الـ Seeder:** بيانات تجريبية واقعية لكل Module تسرّع التطوير والاختبار.
4. **API-First:** كل Module يكشف API قبل بناء الواجهة — هذا يسهّل بناء تطبيق جوال لاحقاً.
5. **Feature Flags:** استخدم Laravel Pennant لتشغيل/إيقاف الميزات حسب خطة الاشتراك.
6. **التوثيق:** كل Module له README + API Docs (Scribe/Swagger) من اليوم الأول.
