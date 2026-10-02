<template>
  <div class="min-h-screen bg-gray-50" dir="rtl">
    <header class="bg-white border-b border-gray-200">
      <div class="max-w-6xl mx-auto px-6 h-16 flex items-center justify-between">
        <span class="text-lg font-bold text-primary-600">PM-OS · لوحة الإدارة</span>
        <nav class="flex items-center gap-4 text-sm">
          <Link href="/admin/dashboard" class="text-gray-600 hover:text-gray-900">لوحة التحكم</Link>
          <Link href="/admin/tenants" class="font-medium text-gray-900">الشركات</Link>
        </nav>
      </div>
    </header>

    <main class="max-w-6xl mx-auto px-6 py-10">
      <h1 class="text-2xl font-bold text-gray-900">الشركات المشتركة</h1>
      <p class="mt-1 text-gray-600">قائمة الشركات (Tenants) المسجلة على المنصة.</p>

      <div class="mt-8 table-container">
        <table class="w-full">
          <thead>
            <tr class="table-header text-right">
              <th class="table-cell">الشركة</th>
              <th class="table-cell">النطاق</th>
              <th class="table-cell">الخطة</th>
              <th class="table-cell">الحالة</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="t in tenants" :key="t.id" class="table-row">
              <td class="table-cell font-medium text-gray-900">{{ t.name }}</td>
              <td class="table-cell" dir="ltr">{{ t.domain ?? '—' }}</td>
              <td class="table-cell">{{ t.plan ?? '—' }}</td>
              <td class="table-cell">
                <span class="badge" :class="statusBadge(t.status)">{{ statusLabel(t.status) }}</span>
              </td>
            </tr>
            <tr v-if="!tenants.length">
              <td class="table-cell text-center text-gray-400" colspan="4">
                لا توجد شركات مسجلة بعد. أنشئ واحدة:
                <code class="font-mono text-xs">php artisan tenant:create …</code>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </main>
  </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';

defineOptions({ layout: null });

defineProps({
  tenants: { type: Array, default: () => [] },
});

const statusLabels = {
  trial: 'تجريبي',
  active: 'نشط',
  suspended: 'موقوف',
  cancelled: 'ملغى',
};
const statusLabel = (s) => statusLabels[s] ?? s ?? '—';

const statusBadge = (s) =>
  ({
    trial: 'badge-yellow',
    active: 'badge-green',
    suspended: 'badge-red',
    cancelled: 'badge-gray',
  })[s] ?? 'badge-gray';
</script>
