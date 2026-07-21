<template>
  <div v-if="total > 0" class="flex flex-col gap-3 px-6 py-4 border-t border-slate-100 sm:flex-row sm:items-center sm:justify-between">
    <div class="flex items-center gap-3">
      <p class="text-xs text-slate-400">
        Menampilkan {{ (modelValue - 1) * perPage + 1 }}–{{ Math.min(modelValue * perPage, total) }} dari {{ total }} data
      </p>
      <label v-if="showPerPageSelector" class="flex items-center gap-2 text-xs font-semibold text-slate-500">
        Baris
        <input
          :value="perPage"
          :list="perPageListId"
          @change="updatePerPage($event.target.value)"
          @keyup.enter="updatePerPage($event.target.value)"
          type="number"
          min="1"
          inputmode="numeric"
          class="w-16 rounded-lg border border-slate-200 bg-white px-2 py-1 text-xs font-semibold text-slate-600 focus:outline-none focus:ring-2 focus:ring-brand-brown/50"
          aria-label="Jumlah baris per halaman"
        >
        <datalist :id="perPageListId">
          <option v-for="option in perPageOptions" :key="option" :value="option" />
        </datalist>
      </label>
    </div>
    <div v-if="totalPages > 1" class="flex items-center gap-1">
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
              ? 'bg-linear-to-tr from-brand-brown to-brand-gradation text-brand-cream border-transparent shadow-sm'
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
  showPerPageSelector: { type: Boolean, default: false },
  perPageOptions: { type: Array, default: () => [5, 10, 20, 50] },
});
const emit = defineEmits(['update:modelValue', 'update:perPage']);

const perPageListId = 'table-pagination-per-page-options';

const updatePerPage = (value) => {
  const pageSize = Number.parseInt(value, 10);
  if (Number.isFinite(pageSize) && pageSize > 0) {
    emit('update:perPage', pageSize);
  }
};

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
