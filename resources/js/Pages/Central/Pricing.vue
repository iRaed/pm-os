<template>
  <div class="min-h-screen bg-gray-50" dir="rtl">
    <!-- Nav -->
    <header class="sticky top-0 z-20 bg-white/90 backdrop-blur border-b border-gray-200">
      <div class="max-w-6xl mx-auto px-6 h-16 flex items-center justify-between">
        <Link href="/" class="text-xl font-bold text-primary-600">PM-OS</Link>
        <nav class="flex items-center gap-3">
          <Link href="/" class="text-sm text-gray-600 hover:text-gray-900">الرئيسية</Link>
          <Link href="/login" class="btn-primary">تسجيل الدخول</Link>
        </nav>
      </div>
    </header>

    <section class="max-w-6xl mx-auto px-6 pt-16 pb-10 text-center">
      <h1 class="text-3xl sm:text-4xl font-bold text-gray-900">خطط الاشتراك</h1>
      <p class="mt-3 text-gray-600">اختر الخطة المناسبة لحجم محفظتك العقارية. الأسعار بالريال السعودي.</p>
    </section>

    <section class="max-w-6xl mx-auto px-6 pb-24">
      <div v-if="plans.length" class="grid gap-6 lg:grid-cols-3">
        <div
          v-for="(plan, i) in plans"
          :key="plan.id"
          class="card flex flex-col"
          :class="i === 1 ? 'ring-2 ring-primary-500 relative' : ''"
        >
          <span v-if="i === 1" class="badge badge-blue absolute -top-3 right-6">الأكثر شيوعاً</span>

          <h3 class="text-lg font-semibold text-gray-900">{{ plan.name }}</h3>
          <p class="text-xs text-gray-400">{{ plan.name_en }}</p>

          <div class="mt-4">
            <span class="text-3xl font-bold text-gray-900">{{ formatPrice(plan.monthly_price) }}</span>
            <span class="text-sm text-gray-500"> ريال / شهرياً</span>
          </div>
          <p class="mt-1 text-xs text-gray-400">
            أو {{ formatPrice(plan.annual_price) }} ريال سنوياً
          </p>

          <ul class="mt-5 space-y-2 text-sm text-gray-600 flex-1">
            <li class="flex items-center gap-2">
              <span class="text-primary-600">✓</span>
              حتى {{ Number(plan.max_units).toLocaleString('en') }} وحدة
            </li>
            <li class="flex items-center gap-2">
              <span class="text-primary-600">✓</span>
              حتى {{ Number(plan.max_users).toLocaleString('en') }} مستخدم
            </li>
            <li v-for="feat in plan.features" :key="feat" class="flex items-center gap-2">
              <span class="text-primary-600">✓</span>
              {{ featureLabel(feat) }}
            </li>
          </ul>

          <Link href="/login" class="btn-primary w-full justify-center mt-6">اشترك الآن</Link>
        </div>
      </div>

      <div v-else class="card text-center text-gray-500">
        لا توجد خطط متاحة حالياً. شغّل أمر التهيئة:
        <code class="font-mono text-xs text-gray-700">php artisan db:seed --class=PlanSeeder</code>
      </div>
    </section>
  </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';

defineOptions({ layout: null });

defineProps({
  plans: { type: Array, default: () => [] },
});

const featureLabels = {
  property_management: 'إدارة العقارات',
  leasing: 'العقود والإيجارات',
  collection: 'التحصيل',
  maintenance: 'الصيانة',
  finance: 'المالية',
  owner_statements: 'كشوف الملاك',
  basic_reports: 'تقارير أساسية',
  advanced_reports: 'تقارير متقدمة',
  api_access: 'واجهة برمجية (API)',
  ai_engine: 'محرك الذكاء الاصطناعي',
  white_label: 'علامة بيضاء',
  priority_support: 'دعم ذو أولوية',
  custom_integrations: 'تكاملات مخصصة',
};

const featureLabel = (key) => featureLabels[key] ?? key;

const formatPrice = (value) =>
  Number(value).toLocaleString('en', { maximumFractionDigits: 0 });
</script>
