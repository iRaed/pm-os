<template>
  <div class="space-y-6">
    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
      <StatCard
        v-for="stat in stats"
        :key="stat.label"
        :label="stat.label"
        :value="stat.value"
        :icon="stat.icon"
        :color="stat.color"
        :suffix="stat.suffix"
      />
    </div>

    <!-- Charts Row -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <!-- Occupancy by Property -->
      <div class="bg-white rounded-xl border border-gray-200 p-6">
        <h3 class="text-base font-semibold text-gray-800 mb-4">نسبة الإشغال حسب العقار</h3>
        <div v-if="loading" class="h-48 flex items-center justify-center text-gray-400">
          جاري التحميل...
        </div>
        <div v-else class="space-y-3">
          <div v-for="prop in occupancy" :key="prop.id" class="flex items-center gap-3">
            <span class="text-sm text-gray-600 w-32 truncate">{{ prop.name }}</span>
            <div class="flex-1 bg-gray-100 rounded-full h-3 overflow-hidden">
              <div
                class="h-full rounded-full transition-all"
                :class="prop.rate >= 80 ? 'bg-green-500' : prop.rate >= 60 ? 'bg-yellow-500' : 'bg-red-500'"
                :style="{ width: prop.rate + '%' }"
              />
            </div>
            <span class="text-sm font-medium w-12 text-left" dir="ltr">{{ prop.rate }}%</span>
          </div>
        </div>
      </div>

      <!-- Collection Status -->
      <div class="bg-white rounded-xl border border-gray-200 p-6">
        <h3 class="text-base font-semibold text-gray-800 mb-4">حالة التحصيل</h3>
        <div v-if="loading" class="h-48 flex items-center justify-center text-gray-400">
          جاري التحميل...
        </div>
        <div v-else class="space-y-4">
          <div class="flex justify-between items-center p-3 bg-green-50 rounded-lg">
            <span class="text-sm text-green-700">محصّل هذا الشهر</span>
            <span class="text-lg font-bold text-green-700">{{ formatMoney(collection.collected_this_month) }}</span>
          </div>
          <div class="flex justify-between items-center p-3 bg-red-50 rounded-lg">
            <span class="text-sm text-red-700">متأخرات</span>
            <span class="text-lg font-bold text-red-700">{{ formatMoney(collection.overdue_amount) }}</span>
          </div>
          <div class="flex justify-between items-center p-3 bg-blue-50 rounded-lg">
            <span class="text-sm text-blue-700">إجمالي المستحقات</span>
            <span class="text-lg font-bold text-blue-700">{{ formatMoney(collection.total_receivable) }}</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Bottom Row -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <!-- Expiring Leases -->
      <div class="bg-white rounded-xl border border-gray-200 p-6">
        <h3 class="text-base font-semibold text-gray-800 mb-4">عقود قاربت على الانتهاء</h3>
        <div v-if="expiringLeases.length === 0" class="text-gray-400 text-sm py-8 text-center">
          لا توجد عقود قاربت على الانتهاء
        </div>
        <div v-else class="divide-y divide-gray-100">
          <div v-for="lease in expiringLeases" :key="lease.id" class="flex items-center justify-between py-3">
            <div>
              <p class="text-sm font-medium text-gray-800">{{ lease.resident }}</p>
              <p class="text-xs text-gray-500">{{ lease.property }} — وحدة {{ lease.unit }}</p>
            </div>
            <div class="text-left">
              <span
                :class="lease.days_remaining <= 7 ? 'text-red-600 bg-red-50' : lease.days_remaining <= 30 ? 'text-yellow-600 bg-yellow-50' : 'text-blue-600 bg-blue-50'"
                class="text-xs font-medium px-2 py-1 rounded-full"
              >
                {{ lease.days_remaining }} يوم
              </span>
            </div>
          </div>
        </div>
      </div>

      <!-- Open Work Orders -->
      <div class="bg-white rounded-xl border border-gray-200 p-6">
        <h3 class="text-base font-semibold text-gray-800 mb-4">أوامر العمل المفتوحة</h3>
        <div class="grid grid-cols-2 gap-3">
          <div class="text-center p-4 bg-red-50 rounded-lg">
            <p class="text-2xl font-bold text-red-600">{{ maintenance.emergency }}</p>
            <p class="text-xs text-red-600 mt-1">طارئ</p>
          </div>
          <div class="text-center p-4 bg-yellow-50 rounded-lg">
            <p class="text-2xl font-bold text-yellow-600">{{ maintenance.sla_breached }}</p>
            <p class="text-xs text-yellow-600 mt-1">تجاوز SLA</p>
          </div>
          <div class="text-center p-4 bg-blue-50 rounded-lg">
            <p class="text-2xl font-bold text-blue-600">{{ maintenance.open_work_orders }}</p>
            <p class="text-xs text-blue-600 mt-1">مفتوحة</p>
          </div>
          <div class="text-center p-4 bg-green-50 rounded-lg">
            <p class="text-2xl font-bold text-green-600">{{ maintenance.completed_this_month }}</p>
            <p class="text-xs text-green-600 mt-1">مكتملة هذا الشهر</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import axios from 'axios';
import StatCard from '@/Components/StatCard.vue';

const loading = ref(true);
const dashboardData = ref({});

const overview = computed(() => dashboardData.value.overview || {});
const occupancy = computed(() => dashboardData.value.occupancy?.by_property || []);
const collection = computed(() => dashboardData.value.collection || {});
const maintenance = computed(() => dashboardData.value.maintenance || {});
const expiringLeases = computed(() => dashboardData.value.leases?.upcoming_expirations || []);

const stats = computed(() => [
    {
        label: 'إجمالي العقارات',
        value: overview.value.total_properties || 0,
        icon: 'building',
        color: 'blue',
    },
    {
        label: 'نسبة الإشغال',
        value: occupancy.value.length > 0
            ? Math.round(occupancy.value.reduce((s, p) => s + p.rate, 0) / occupancy.value.length)
            : 0,
        icon: 'chart',
        color: 'green',
        suffix: '%',
    },
    {
        label: 'أوامر عمل مفتوحة',
        value: maintenance.value.open_work_orders || 0,
        icon: 'wrench',
        color: 'yellow',
    },
    {
        label: 'فواتير متأخرة',
        value: collection.value.overdue_count || 0,
        icon: 'alert',
        color: 'red',
    },
]);

const formatMoney = (amount) => {
    if (!amount) return '0 ر.س';
    return new Intl.NumberFormat('ar-SA', {
        style: 'currency',
        currency: 'SAR',
        maximumFractionDigits: 0,
    }).format(amount);
};

onMounted(async () => {
    try {
        const { data } = await axios.get('/api/v1/dashboard');
        dashboardData.value = data.data;
    } catch (err) {
        console.error('Failed to load dashboard:', err);
    } finally {
        loading.value = false;
    }
});
</script>
