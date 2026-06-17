<template>
  <div class="space-y-6">
    <!-- Welcome banner -->
    <DashboardWelcome :user="user" />

    <!-- Quick stats -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
      <StatCard :value="machines.length"  label="Mesin Terdaftar" color="blue">
        <template #icon>
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
        </template>
      </StatCard>
      <StatCard :value="activeMachines"   label="Mesin Aktif"     color="amber">
        <template #icon>
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </template>
      </StatCard>
      <StatCard :value="healthyMachines"  label="Kondisi >80%"    color="green">
        <template #icon>
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </template>
      </StatCard>
      <StatCard :value="criticalMachines" label="Kondisi <50%"    color="red">
        <template #icon>
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
        </template>
      </StatCard>
    </div>

    <!-- Maintenance alert panel -->
    <DashboardAlertPanel
      :alerts="maintenanceAlerts"
      :loading="loading"
      @view-all="router.visit('/machines')"
      @click-alert="(a) => router.visit(`/machine/${a.machine_id}`)"
    />

    <!-- Machines grid -->
    <div>
      <div class="flex flex-wrap items-center justify-between gap-4 mb-5">
        <h3 class="text-lg font-semibold text-slate-800 flex items-center">
          <svg class="w-5 h-5 mr-2 text-brand-brown" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
          Status Mesin
        </h3>
        <div class="flex flex-wrap gap-2 items-center">
          <SearchInput v-model="machineSearch" placeholder="Cari mesin..." wrapper-class="w-44" />
          <select v-model="machineSort" class="rounded-xl border border-slate-200 px-3 py-2 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 cursor-pointer">
            <option value="name">Nama A-Z</option>
            <option value="condition_asc">Kondisi Terendah</option>
            <option value="condition_desc">Kondisi Tertinggi</option>
          </select>
        </div>
      </div>

      <!-- Skeleton -->
      <div v-if="loading" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
        <div v-for="i in 4" :key="i" class="bg-white rounded-2xl p-6 border border-slate-100 shadow-sm animate-pulse">
          <div class="h-4 bg-slate-200 rounded w-1/2 mb-6"></div>
          <div class="flex justify-center mb-6"><div class="w-32 h-32 rounded-full border-8 border-slate-100"></div></div>
        </div>
      </div>

      <!-- Empty -->
      <div v-else-if="filteredMachines.length === 0" class="text-center py-12 text-slate-400 bg-white rounded-2xl border border-slate-100">
        <svg class="w-12 h-12 mx-auto mb-3 text-slate-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <p class="font-medium text-slate-500">Tidak ada mesin ditemukan</p>
      </div>

      <!-- Cards -->
      <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
        <DashboardMachineCard
          v-for="machine in filteredMachines"
          :key="machine.id"
          :machine="machine"
          @click="(m) => router.visit(`/machine/${m.id}`)"
        />
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import { useAuth } from '../composables/useAuth.js';
import DashboardWelcome from '../components/DashboardWelcome.vue';
import DashboardAlertPanel from '../components/DashboardAlertPanel.vue';
import DashboardMachineCard from '../components/DashboardMachineCard.vue';
import StatCard from '../components/StatCard.vue';
import SearchInput from '../components/SearchInput.vue';

const props = defineProps({
  machines:      { type: Array, default: () => [] },
  notifications: { type: Array, default: () => [] },
});

const { user } = useAuth();

const machines      = ref(props.machines);
const notifications = ref(props.notifications);
const loading       = ref(false);
const machineSearch = ref('');
const machineSort   = ref('name');

const activeMachines   = computed(() => machines.value.filter(m => m.status === 'active').length);
const healthyMachines  = computed(() => machines.value.filter(m => m.condition_pct > 80).length);
const criticalMachines = computed(() => machines.value.filter(m => m.condition_pct < 50).length);

const filteredMachines = computed(() => {
  let list = machines.value;
  if (machineSearch.value) {
    const q = machineSearch.value.toLowerCase();
    list = list.filter(m =>
      m.name.toLowerCase().includes(q) ||
      (m.location ?? '').toLowerCase().includes(q) ||
      (m.kota ?? '').toLowerCase().includes(q)
    );
  }
  if (machineSort.value === 'name')          list = [...list].sort((a, b) => a.name.localeCompare(b.name));
  if (machineSort.value === 'condition_asc') list = [...list].sort((a, b) => a.condition_pct - b.condition_pct);
  if (machineSort.value === 'condition_desc') list = [...list].sort((a, b) => b.condition_pct - a.condition_pct);
  return list;
});

const maintenanceAlerts = computed(() => {
  const today = new Date(); today.setHours(0,0,0,0);
  const isSameDay = (d1, d2) => {
    const a = new Date(d1), b = new Date(d2);
    return a.getFullYear() === b.getFullYear() && a.getMonth() === b.getMonth() && a.getDate() === b.getDate();
  };

  return notifications.value.map(notif => {
    const dueDate = new Date(notif.next_due_date); dueDate.setHours(0,0,0,0);
    const daysUntil = Math.ceil((dueDate - today) / 86400000);
    if (daysUntil < -1) return null;

    const machine = machines.value.find(m => m.id === notif.machine_id);
    if (!machine) return null;

    const todayRecords = (machine.records || []).filter(r => r.status === 'completed' && isSameDay(r.maintenance_date, today));
    const checkedIds = new Set();
    todayRecords.forEach(r => (r.actions || []).forEach(a => a.machine_component_id && checkedIds.add(a.machine_component_id)));

    const totalComponents = machine.components?.length ?? 0;
    const checkedCount    = (machine.components || []).filter(c => checkedIds.has(c.id)).length;
    const uncheckedCount  = totalComponents - checkedCount;
    const isFullyChecked  = totalComponents > 0 && checkedCount === totalComponents;
    const isPartiallyChecked = checkedCount > 0 && checkedCount < totalComponents;

    const lastRecord = [...(machine.records || [])]
      .filter(r => r.status === 'completed')
      .sort((a, b) => new Date(b.maintenance_date) - new Date(a.maintenance_date))[0];

    if (lastRecord) {
      const lastDate = new Date(lastRecord.maintenance_date); lastDate.setHours(0,0,0,0);
      const prevDue  = new Date(dueDate); prevDue.setDate(prevDue.getDate() - (notif.interval_days || 7));
      if (lastDate >= prevDue && !isFullyChecked && !isPartiallyChecked && daysUntil !== 1) return null;
    }

    return { ...notif, machine, totalComponents, checkedCount, uncheckedCount, isFullyChecked, isPartiallyChecked, daysUntil };
  }).filter(Boolean);
});
</script>
