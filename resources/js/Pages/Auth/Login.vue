<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4" dir="rtl">
    <div class="max-w-md w-full space-y-8">
      <!-- Header -->
      <div class="text-center">
        <h1 class="text-3xl font-bold text-primary-600">PM-OS</h1>
        <p class="mt-2 text-sm text-gray-600">منصة إدارة الأملاك والعقارات</p>
      </div>

      <!-- Form -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-8">
        <div class="space-y-5">
          <div>
            <label class="input-label">البريد الإلكتروني</label>
            <input
              v-model="form.email"
              type="email"
              class="input"
              dir="ltr"
              placeholder="email@company.sa"
              @keyup.enter="submit"
            />
            <p v-if="errors.email" class="mt-1 text-sm text-red-600">{{ errors.email }}</p>
          </div>

          <div>
            <label class="input-label">كلمة المرور</label>
            <input
              v-model="form.password"
              type="password"
              class="input"
              dir="ltr"
              placeholder="••••••••"
              @keyup.enter="submit"
            />
            <p v-if="errors.password" class="mt-1 text-sm text-red-600">{{ errors.password }}</p>
          </div>

          <div class="flex items-center justify-between">
            <label class="flex items-center gap-2 cursor-pointer">
              <input v-model="form.remember" type="checkbox" class="rounded border-gray-300 text-primary-600 focus:ring-primary-500" />
              <span class="text-sm text-gray-600">تذكرني</span>
            </label>
          </div>

          <button
            @click="submit"
            :disabled="loading"
            class="btn-primary w-full justify-center"
          >
            <span v-if="loading">جاري الدخول...</span>
            <span v-else>تسجيل الدخول</span>
          </button>
        </div>
      </div>

      <p class="text-center text-xs text-gray-400">
        PM-OS v1.0 — منصة إدارة الأملاك
      </p>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue';
import { router } from '@inertiajs/vue3';

defineOptions({ layout: null }); // No app layout for login

const form = reactive({
    email: '',
    password: '',
    remember: false,
});

const errors = ref({});
const loading = ref(false);

const submit = () => {
    loading.value = true;
    errors.value = {};

    router.post('/login', form, {
        onError: (e) => {
            errors.value = e;
            loading.value = false;
        },
        onFinish: () => {
            loading.value = false;
        },
    });
};
</script>
