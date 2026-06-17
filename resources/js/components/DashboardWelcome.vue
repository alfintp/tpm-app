<template>
  <div class="bg-gradient-to-tr from-brand-brown to-brand-gradation rounded-2xl p-6 text-white shadow-lg mt-4">
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold mb-1">{{ greeting }}, {{ name }}! 👋</h1>
        <p class="text-indigo-100 text-sm">{{ timeGreeting }}, ini adalah ringkasan status maintenance hari ini.</p>
      </div>
      <div class="text-right">
        <p class="text-sm text-indigo-100">{{ today }}</p>
        <p class="text-xs text-indigo-200 mt-1">{{ roleLabel }}</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  user: { type: Object, default: null },
});

const name = computed(() => props.user?.full_name || 'User');

const roleLabel = computed(() => {
  const role = props.user?.role;
  if (!role) return '';
  return role.charAt(0).toUpperCase() + role.slice(1);
});

const greeting = computed(() => {
  const hour = new Date().getHours();
  if (hour < 12) return 'Selamat Pagi';
  if (hour < 15) return 'Selamat Siang';
  if (hour < 18) return 'Selamat Sore';
  return 'Selamat Malam';
});

const timeGreeting = computed(() => greeting.value);

const today = computed(() =>
  new Date().toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' })
);
</script>
