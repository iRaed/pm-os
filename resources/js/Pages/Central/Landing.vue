<template>
  <div class="min-h-screen bg-gray-50" dir="rtl">
    <!-- Nav -->
    <header class="sticky top-0 z-20 bg-white/90 backdrop-blur border-b border-gray-200">
      <div class="max-w-6xl mx-auto px-6 h-16 flex items-center justify-between">
        <span class="text-xl font-bold text-primary-600">PM-OS</span>
        <nav class="flex items-center gap-3">
          <Link href="/pricing" class="text-sm text-gray-600 hover:text-gray-900">الأسعار</Link>
          <Link href="/login" class="btn-primary">تسجيل الدخول</Link>
        </nav>
      </div>
    </header>

    <!-- Hero -->
    <section class="max-w-6xl mx-auto px-6 pt-20 pb-16 text-center">
      <h1 class="text-4xl sm:text-5xl font-bold text-gray-900 leading-tight">
        نظام تشغيل متكامل
        <span class="text-primary-600">لإدارة الأملاك</span>
      </h1>
      <p class="mt-5 max-w-2xl mx-auto text-lg text-gray-600">
        منصة سحابية عربية لإدارة العقارات والوحدات والعقود والتحصيل والصيانة —
        كل شركة بقاعدة بيانات مستقلة ومعزولة بالكامل.
      </p>
      <div class="mt-8 flex items-center justify-center gap-3">
        <Link href="/pricing" class="btn-primary">ابدأ الآن</Link>
        <Link href="/login" class="btn-secondary">دخول العملاء</Link>
      </div>
    </section>

    <!-- Features -->
    <section class="max-w-6xl mx-auto px-6 pb-24">
      <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
        <div v-for="f in features" :key="f.title" class="card">
          <div class="w-11 h-11 rounded-lg bg-primary-50 text-primary-600 flex items-center justify-center text-xl">
            {{ f.icon }}
          </div>
          <h3 class="mt-4 text-lg font-semibold text-gray-900">{{ f.title }}</h3>
          <p class="mt-2 text-sm text-gray-600 leading-6">{{ f.desc }}</p>
        </div>
      </div>
    </section>

    <footer class="border-t border-gray-200 bg-white">
      <div class="max-w-6xl mx-auto px-6 py-6 text-center text-xs text-gray-400">
        PM-OS — منصة إدارة الأملاك والعقارات © {{ new Date().getFullYear() }}
      </div>
    </footer>
  </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';

defineOptions({ layout: null });

const features = [
  { icon: '🏢', title: 'إدارة العقارات والوحدات', desc: 'سجل كامل للعقارات والوحدات مع الحالة التشغيلية والإشغال والتسعير.' },
  { icon: '📄', title: 'العقود والإيجارات', desc: 'إنشاء العقود وتجديدها ومتابعة انتهائها مع ربط المستأجرين والوحدات.' },
  { icon: '💰', title: 'التحصيل والفواتير', desc: 'فواتير آلية، متابعة المتأخرات، وتصعيد التحصيل مع دعم سداد ومدى.' },
  { icon: '🔧', title: 'الصيانة الوقائية', desc: 'أوامر عمل، خطط صيانة دورية، ومتابعة المقاولين واتفاقيات الخدمة.' },
  { icon: '📊', title: 'التقارير والمالية', desc: 'كشوف حساب الملاك والتقارير التشغيلية والمالية بلمسة واحدة.' },
  { icon: '🔒', title: 'عزل كامل للبيانات', desc: 'قاعدة بيانات مستقلة لكل شركة (Database-per-Tenant) لأعلى درجات الخصوصية.' },
];
</script>
