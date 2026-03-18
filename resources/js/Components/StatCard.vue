<template>
  <div class="bg-white rounded-xl border border-gray-200 p-5">
    <div class="flex items-center justify-between">
      <div>
        <p class="text-sm text-gray-500">{{ label }}</p>
        <p class="mt-1 text-2xl font-bold" :class="textColor">
          {{ value }}{{ suffix }}
        </p>
      </div>
      <div
        class="flex items-center justify-center w-12 h-12 rounded-xl"
        :class="bgColor"
      >
        <span class="text-lg" :class="iconColor">
          <slot name="icon">📊</slot>
        </span>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    label: String,
    value: [Number, String],
    icon: String,
    color: { type: String, default: 'blue' },
    suffix: { type: String, default: '' },
});

const colorMap = {
    blue: { text: 'text-blue-700', bg: 'bg-blue-50', icon: 'text-blue-500' },
    green: { text: 'text-green-700', bg: 'bg-green-50', icon: 'text-green-500' },
    yellow: { text: 'text-yellow-700', bg: 'bg-yellow-50', icon: 'text-yellow-500' },
    red: { text: 'text-red-700', bg: 'bg-red-50', icon: 'text-red-500' },
};

const colors = computed(() => colorMap[props.color] || colorMap.blue);
const textColor = computed(() => colors.value.text);
const bgColor = computed(() => colors.value.bg);
const iconColor = computed(() => colors.value.icon);
</script>
