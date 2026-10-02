<template>
  <div class="min-h-screen bg-gray-50" dir="rtl">
    <header class="bg-white border-b border-gray-200">
      <div class="max-w-6xl mx-auto px-6 h-16 flex items-center justify-between">
        <span class="text-lg font-bold text-primary-600">PM-OS · لوحة الإدارة</span>
        <nav class="flex items-center gap-4 text-sm">
          <Link href="/admin/dashboard" class="font-medium text-gray-900">لوحة التحكم</Link>
          <Link href="/admin/tenants" class="text-gray-600 hover:text-gray-900">الشركات</Link>
        </nav>
      </div>
    </header>

    <main class="max-w-6xl mx-auto px-6 py-10">
      <h1 class="text-2xl font-bold text-gray-900">لوحة التحكم المركزية</h1>
      <p class="mt-1 text-gray-600">نظرة عامة على منصة SaaS وإدارة الشركات المشتركة.</p>

      <div class="mt-8 grid gap-6 sm:grid-cols-3">
        <div class="card">
          <p class="text-sm text-gray-500">إجمالي الشركات</p>
          <p class="mt-2 text-3xl font-bold text-gray-900">{{ stats.tenants }}</p>
        </div>
        <div class="card">
          <p class="text-sm text-gray-500">اشتراكات نشطة</p>
          <p class="mt-2 text-3xl font-bold text-gray-900">{{ stats.active }}</p>
        </div>
        <div class="card">
          <p class="text-sm text-gray-500">خطط متاحة</p>
          <p class="mt-2 text-3xl font-bold text-gray-900">{{ stats.plans }}</p>
        </div>
      </div>
    </main>
  </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';

defineOptions({ layout: null });

defineProps({
  stats: {
    type: Object,
    default: () => ({ tenants: 0, active: 0, plans: 0 }),
  },
});
</script>
