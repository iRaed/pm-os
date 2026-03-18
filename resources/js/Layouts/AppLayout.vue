<template>
  <div class="min-h-screen bg-gray-50" dir="rtl">
    <!-- Sidebar -->
    <aside
      :class="[sidebarOpen ? 'w-64' : 'w-20']"
      class="fixed inset-y-0 right-0 z-30 flex flex-col bg-white border-l border-gray-200 transition-all duration-300"
    >
      <!-- Logo -->
      <div class="flex items-center justify-center h-16 border-b border-gray-200">
        <span v-if="sidebarOpen" class="text-xl font-bold text-primary-600">PM-OS</span>
        <span v-else class="text-lg font-bold text-primary-600">P</span>
      </div>

      <!-- Navigation -->
      <nav class="flex-1 overflow-y-auto p-3 space-y-1">
        <SidebarLink
          v-for="item in navigation"
          :key="item.name"
          :href="item.href"
          :icon="item.icon"
          :label="item.label"
          :collapsed="!sidebarOpen"
          :active="isActive(item.href)"
          :badge="item.badge"
        />
      </nav>

      <!-- Toggle -->
      <button
        @click="sidebarOpen = !sidebarOpen"
        class="flex items-center justify-center h-12 border-t border-gray-200 text-gray-400 hover:text-gray-600"
      >
        <svg class="w-5 h-5 transition-transform" :class="{ 'rotate-180': !sidebarOpen }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
        </svg>
      </button>
    </aside>

    <!-- Main Content -->
    <div :class="[sidebarOpen ? 'mr-64' : 'mr-20']" class="transition-all duration-300">
      <!-- Top Bar -->
      <header class="sticky top-0 z-20 flex items-center justify-between h-16 px-6 bg-white border-b border-gray-200">
        <div class="flex items-center gap-4">
          <h1 class="text-lg font-semibold text-gray-800">
            {{ $t(`nav.${currentPage}`) }}
          </h1>
        </div>

        <div class="flex items-center gap-4">
          <!-- Notifications -->
          <button class="relative p-2 text-gray-400 hover:text-gray-600 rounded-lg hover:bg-gray-100">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
            </svg>
            <span v-if="alertsCount > 0" class="absolute top-1 left-1 w-4 h-4 text-[10px] font-bold text-white bg-red-500 rounded-full flex items-center justify-center">
              {{ alertsCount > 9 ? '9+' : alertsCount }}
            </span>
          </button>

          <!-- User Menu -->
          <div class="flex items-center gap-3 cursor-pointer">
            <div class="w-8 h-8 rounded-full bg-primary-100 text-primary-600 flex items-center justify-center text-sm font-bold">
              {{ userInitials }}
            </div>
            <span class="text-sm font-medium text-gray-700 hidden md:block">{{ userName }}</span>
          </div>
        </div>
      </header>

      <!-- Page Content -->
      <main class="p-6">
        <slot />
      </main>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { usePage, router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import SidebarLink from '@/Components/SidebarLink.vue';

const { t } = useI18n();
const page = usePage();

const sidebarOpen = ref(true);
const alertsCount = ref(0);

const userName = computed(() => page.props.auth?.user?.name ?? 'مستخدم');
const userInitials = computed(() => {
    const name = userName.value;
    return name.substring(0, 2);
});

const currentPage = computed(() => {
    const url = page.url;
    if (url === '/') return 'dashboard';
    return url.split('/')[1] || 'dashboard';
});

const isActive = (href) => {
    if (href === '/') return page.url === '/';
    return page.url.startsWith(href);
};

const navigation = [
    { name: 'dashboard', href: '/', label: 'لوحة التحكم', icon: 'home' },
    { name: 'properties', href: '/properties', label: 'العقارات', icon: 'building' },
    { name: 'units', href: '/units', label: 'الوحدات', icon: 'grid' },
    { name: 'owners', href: '/owners', label: 'الملاك', icon: 'user' },
    { name: 'residents', href: '/residents', label: 'المستأجرون', icon: 'users' },
    { name: 'leases', href: '/leases', label: 'العقود', icon: 'file-text' },
    { name: 'invoices', href: '/invoices', label: 'الفواتير', icon: 'receipt' },
    { name: 'collection', href: '/collection', label: 'التحصيل', icon: 'dollar' },
    { name: 'work-orders', href: '/work-orders', label: 'أوامر العمل', icon: 'wrench' },
    { name: 'pm-plans', href: '/pm-plans', label: 'الصيانة الوقائية', icon: 'calendar' },
    { name: 'contractors', href: '/contractors', label: 'المقاولون', icon: 'truck' },
    { name: 'finance', href: '/finance', label: 'المالية', icon: 'chart' },
    { name: 'reports', href: '/reports', label: 'التقارير', icon: 'bar-chart' },
    { name: 'settings', href: '/settings', label: 'الإعدادات', icon: 'settings' },
];
</script>
