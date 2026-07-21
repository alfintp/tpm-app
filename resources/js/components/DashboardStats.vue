<template>
  <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-8">
    <StatCard :value="activeMachines" label="Mesin Aktif" color="amber">
      <template #icon>
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
      </template>
    </StatCard>
    <StatCard :value="healthyMachines" label="Kondisi >80%" color="green">
      <template #icon>
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
      </template>
    </StatCard>
    <StatCard :value="criticalMachines" label="Kondisi <50%" color="red">
      <template #icon>
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
      </template>
    </StatCard>
    <StatCard :value="props.machines.length" label="Mesin Terdaftar" color="blue">
      <template #icon>
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
      </template>
    </StatCard>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import StatCard from './StatCard.vue';

const props = defineProps({
  machines: { type: Array, default: () => [] },
});

const activeMachines = computed(() => props.machines.filter(m => m.status === 'active').length);
const healthyMachines = computed(() => props.machines.filter(m => m.condition_pct > 80).length);
const criticalMachines = computed(() => props.machines.filter(m => m.condition_pct < 50).length);
</script>
