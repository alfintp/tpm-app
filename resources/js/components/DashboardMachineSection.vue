<template>
  <!-- Skeleton -->
  <div v-if="loading" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
    <div v-for="i in 4" :key="i" class="bg-white rounded-2xl p-6 border border-slate-100 shadow-sm animate-pulse">
      <div class="h-4 bg-slate-200 rounded w-1/2 mb-6"></div>
      <div class="flex justify-center mb-6"><div class="w-32 h-32 rounded-full border-8 border-slate-100"></div></div>
    </div>
  </div>

  <!-- Empty -->
  <div v-else-if="machines.length === 0" class="text-center py-12 text-slate-400 bg-white rounded-2xl border border-slate-100">
    <svg class="w-12 h-12 mx-auto mb-3 text-slate-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
    <p class="font-medium text-slate-500">Tidak ada mesin ditemukan</p>
  </div>

  <!-- Cards -->
  <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
    <DashboardMachineCard
      v-for="machine in paginatedMachines"
      :key="machine.id"
      :machine="machine"
      @click="(m) => router.visit(`/machine/${m.id}`)"
    />
  </div>

  <!-- Pagination -->
  <TablePagination
    v-if="!loading && machines.length > 0"
    :model-value="page"
    @update:model-value="$emit('update:page', $event)"
    :total="machines.length"
    :per-page="perPage"
    :show-per-page-selector="true"
    :per-page-options="[4, 8, 12, 16, 20, 24]"
    @update:per-page="$emit('update:perPage', $event)"
  />
</template>

<script setup>
import { computed } from 'vue';
import { router } from '@inertiajs/vue3';
import DashboardMachineCard from './DashboardMachineCard.vue';
import TablePagination from './TablePagination.vue';

const props = defineProps({
  machines: { type: Array, default: () => [] },
  loading: { type: Boolean, default: false },
  page: { type: Number, default: 1 },
  perPage: { type: Number, default: 12 },
});

const emit = defineEmits(['update:page', 'update:perPage']);

const paginatedMachines = computed(() => {
  const start = (props.page - 1) * props.perPage;
  return props.machines.slice(start, start + props.perPage);
});
</script>
