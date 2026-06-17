<template>
  <div v-if="totalPages > 1" class="flex items-center justify-between px-6 py-4 border-t border-slate-100">
    <p class="text-xs text-slate-400">
      Menampilkan {{ (modelValue - 1) * perPage + 1 }}–{{ Math.min(modelValue * perPage, total) }} dari {{ total }} data
    </p>
    <div class="flex items-center gap-1">
      <Button
        variant="outline" size="sm"
        :disabled="modelValue === 1"
        @click="$emit('update:modelValue', 1)"
        class="h-8 w-8 p-0 rounded-lg border-slate-200 text-slate-500 hover:border-brand-brown disabled:opacity-40"
      >«</Button>
      <Button
        variant="outline" size="sm"
        :disabled="modelValue === 1"
        @click="$emit('update:modelValue', modelValue - 1)"
        class="h-8 w-8 p-0 rounded-lg border-slate-200 text-slate-500 hover:border-brand-brown disabled:opacity-40"
      >‹</Button>

      <template v-for="page in visiblePages" :key="page">
        <span v-if="page === '...'" class="px-2 text-slate-400 text-sm">…</span>
        <Button
          v-else
          variant="outline" size="sm"
          @click="$emit('update:modelValue', page)"
          :class="[
            'h-8 w-8 p-0 rounded-lg text-sm font-semibold transition-all',
            modelValue === page
              ? 'bg-gradient-to-tr from-brand-brown to-brand-gradation text-brand-cream border-transparent shadow-sm'
              : 'border-slate-200 text-slate-600 hover:border-brand-brown'
          ]"
        >{{ page }}</Button>
      </template>

      <Button
        variant="outline" size="sm"
        :disabled="modelValue === totalPages"
        @click="$emit('update:modelValue', modelValue + 1)"
        class="h-8 w-8 p-0 rounded-lg border-slate-200 text-slate-500 hover:border-brand-brown disabled:opacity-40"
      >›</Button>
      <Button
        variant="outline" size="sm"
        :disabled="modelValue === totalPages"
        @click="$emit('update:modelValue', totalPages)"
        class="h-8 w-8 p-0 rounded-lg border-slate-200 text-slate-500 hover:border-brand-brown disabled:opacity-40"
      >»</Button>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import Button from '../../views/components/ui/button/Button.vue';

const props = defineProps({
  modelValue: { type: Number, required: true },
  total: { type: Number, required: true },
  perPage: { type: Number, default: 10 },
});
defineEmits(['update:modelValue']);

const totalPages = computed(() => Math.ceil(props.total / props.perPage));

const visiblePages = computed(() => {
  const total = totalPages.value;
  const current = props.modelValue;
  const pages = [];

  if (total <= 7) {
    for (let i = 1; i <= total; i++) pages.push(i);
    return pages;
  }

  pages.push(1);
  if (current > 3) pages.push('...');
  for (let i = Math.max(2, current - 1); i <= Math.min(total - 1, current + 1); i++) {
    pages.push(i);
  }
  if (current < total - 2) pages.push('...');
  pages.push(total);

  return pages;
});
</script>
