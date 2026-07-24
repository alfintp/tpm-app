<template>
  <div class="w-full max-w-5xl mx-auto space-y-6">
    <DashboardStats :machines="cityFilteredMachines" />

    <DashboardAlertPanel
      :alerts="maintenanceAlerts"
      :loading="loading"
      @view-all="router.visit('/machines')"
      @click-alert="(a) => router.visit(`/machine/${a.machine_id}`)"
    />

    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
      <div class="p-5 border-b border-slate-100 flex flex-col lg:flex-row lg:items-center justify-between gap-4">
        <div class="flex items-center gap-1 bg-slate-100 rounded-xl p-1">
          <button
            @click="dashboardTab = 'reports'"
            :class="dashboardTab === 'reports' ? 'bg-white text-brand-gradation shadow-sm' : 'text-slate-500 hover:text-slate-700'"
            class="px-4 py-2 rounded-lg text-xs font-bold transition-all cursor-pointer flex items-center gap-1.5"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            Laporan
          </button>
          <button
            @click="dashboardTab = 'machines'"
            :class="dashboardTab === 'machines' ? 'bg-white text-brand-gradation shadow-sm' : 'text-slate-500 hover:text-slate-700'"
            class="px-4 py-2 rounded-lg text-xs font-bold transition-all cursor-pointer flex items-center gap-1.5"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
            Status Mesin
          </button>
        </div>
        <DashboardFilters
          v-if="dashboardTab === 'machines'"
          v-model:search="machineSearch"
          v-model:city="machineKota"
          v-model:location="locationSearch"
          :selected-location="selectedLocation"
          :locations="uniqueLocations"
          v-model:sort="machineSort"
          :show-sort="true"
          :has-both-cities="hasBothCities"
          @select-location="selectLocation"
          @clear-location="clearLocation"
        />
      </div>

      <div class="p-5">
        <DashboardReportPanel
          v-if="dashboardTab === 'reports'"
          :machines="filteredMachines"
          v-model:search="machineSearch"
          v-model:city="machineKota"
          v-model:location="locationSearch"
          :selected-location="selectedLocation"
          :locations="uniqueLocations"
          :has-both-cities="hasBothCities"
          @select-location="selectLocation"
          @clear-location="clearLocation"
        />
        <DashboardMachineSection v-else :machines="filteredMachines" :loading="loading" v-model:page="currentPage" v-model:per-page="perPage" />
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import { useAuth } from '../composables/useAuth.js';
import DashboardAlertPanel from '../components/DashboardAlertPanel.vue';
import DashboardReportPanel from '../components/DashboardReportPanel.vue';
import DashboardStats from '../components/DashboardStats.vue';
import DashboardFilters from '../components/DashboardFilters.vue';
import DashboardMachineSection from '../components/DashboardMachineSection.vue';

const props = defineProps({
  machines:      { type: Array, default: () => [] },
  notifications: { type: Array, default: () => [] },
});

const { user, hasBothCities } = useAuth();

const machines      = ref(props.machines);
const notifications = ref(props.notifications);
const loading       = ref(false);
const dashboardTab  = ref('reports');
const machineSearch = ref('');
const machineSort   = ref('name');
const machineKota   = ref('');
const locationSearch = ref('');
const selectedLocation = ref('');
const currentPage   = ref(1);
const perPage       = ref(12);

const cityFilteredMachines = computed(() => {
  const city = user.value?.city;
  if (city && city !== 'both') {
    return machines.value.filter(m => m.kota === city);
  }
  return machines.value;
});

const locationBaseMachines = computed(() => {
  let list = cityFilteredMachines.value;
  if (machineKota.value) {
    list = list.filter(m => m.kota === machineKota.value);
  }
  return list;
});

const uniqueLocations = computed(() => {
  const locations = new Set();
  locationBaseMachines.value.forEach(m => {
    if (m.location) locations.add(m.location);
  });
  return Array.from(locations).sort();
});

const selectLocation = (loc) => {
  locationSearch.value = loc;
  selectedLocation.value = loc;
};

const clearLocation = () => {
  locationSearch.value = '';
  selectedLocation.value = '';
};

watch(machineKota, () => {
  clearLocation();
});

const filteredMachines = computed(() => {
  let list = cityFilteredMachines.value;
  if (machineKota.value) {
    list = list.filter(m => m.kota === machineKota.value);
  }
  if (selectedLocation.value) {
    list = list.filter(m => m.location === selectedLocation.value);
  }
  if (machineSearch.value) {
    const q = machineSearch.value.toLowerCase();
    list = list.filter(m =>
      m.name.toLowerCase().includes(q) ||
      (m.location ?? '').toLowerCase().includes(q)
    );
  }
  if (machineSort.value === 'name')           list = [...list].sort((a, b) => a.name.localeCompare(b.name));
  if (machineSort.value === 'condition_asc')  list = [...list].sort((a, b) => a.condition_pct - b.condition_pct);
  if (machineSort.value === 'condition_desc') list = [...list].sort((a, b) => b.condition_pct - a.condition_pct);
  return list;
});

watch([machineSearch, machineSort, machineKota, selectedLocation, perPage], () => { currentPage.value = 1; });

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

    const machine = cityFilteredMachines.value.find(m => m.id === notif.machine_id);
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
  }).filter(Boolean).sort((a, b) => {
    const priority = (d) => {
      if (d < 0)   return 0;
      if (d === 0) return 1;
      if (d === 1) return 2;
      return 3;
    };
    const pa = priority(a.daysUntil), pb = priority(b.daysUntil);
    if (pa !== pb) return pa - pb;
    return a.daysUntil - b.daysUntil;
  });
});
</script>
