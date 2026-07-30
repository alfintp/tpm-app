<template>
  <div class="w-full max-w-5xl mx-auto space-y-6">
    <div v-if="loading" class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-8">
      <div v-for="i in 4" :key="i" class="h-24 bg-white rounded-2xl border border-slate-100 shadow-sm animate-pulse"></div>
    </div>
    <DashboardStats v-else :machines="cityFilteredMachines" />

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
            v-if="isApprover"
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
        <div v-if="loading && dashboardTab === 'reports'" class="space-y-4 animate-pulse">
          <div class="h-16 bg-slate-50 rounded-2xl w-full"></div>
          <div class="grid grid-cols-4 gap-4">
            <div v-for="i in 4" :key="i" class="h-20 bg-slate-50 rounded-xl"></div>
          </div>
          <div class="h-64 bg-slate-50 rounded-2xl w-full"></div>
        </div>
        <DashboardReportPanel
          v-else-if="dashboardTab === 'reports'"
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
import { ref, computed, watch, onMounted } from 'vue';
import { router } from '@inertiajs/vue3';
import axios from 'axios';
import { useAuth } from '../composables/useAuth.js';
import DashboardAlertPanel from '../components/DashboardAlertPanel.vue';
import DashboardReportPanel from '../components/DashboardReportPanel.vue';
import DashboardStats from '../components/DashboardStats.vue';
import DashboardFilters from '../components/DashboardFilters.vue';
import DashboardMachineSection from '../components/DashboardMachineSection.vue';
import { getCurrentPeriod } from '../composables/useSchedulePeriods.js';

const props = defineProps({
  machines:      { type: Array, default: null },
  notifications: { type: Array, default: null },
  initialMachines: { type: Array, default: null },
  initialNotifications: { type: Array, default: null },
});

const { user, isApprover, hasBothCities } = useAuth();

const machines      = ref(props.machines || props.initialMachines || []);
const notifications = ref(props.notifications || props.initialNotifications || []);
const loading       = ref(!props.machines && !props.initialMachines);
const dashboardTab  = ref(isApprover.value ? 'reports' : 'machines');
const machineSearch = ref('');
const machineSort   = ref('name');
const machineKota   = ref('');
const locationSearch = ref('');
const selectedLocation = ref('');
const currentPage   = ref(1);
const perPage       = ref(12);
const alertDaysBefore = ref(7);
const daysBeforeSetting = ref(2);

const loadData = async () => {
  try {
    const [machinesRes, notifRes, settingsRes] = await Promise.all([
      axios.get('/api/machines'),
      axios.get('/api/schedules/notifications'),
      axios.get('/api/settings/maintenance-window')
    ]);
    machines.value = machinesRes.data;
    notifications.value = notifRes.data;
    alertDaysBefore.value = settingsRes.data.alert_days_before ?? 7;
    daysBeforeSetting.value = settingsRes.data.days_before ?? 2;
  } catch (e) {
    console.error('Failed to load dashboard data:', e);
  } finally {
    loading.value = false;
  }
};

onMounted(() => {
  if (!props.machines && !props.initialMachines) {
    loadData();
  }
});

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
    const machine = cityFilteredMachines.value.find(m => m.id === notif.machine_id);
    if (!machine) return null;

    // Use the canonical current-period due date (same source as MachineDetail page)
    // instead of the raw schedule's next_due_date.
    const period = getCurrentPeriod(machine.schedules ?? []);
    const dueDate = period ? period.due : (() => { const d = new Date(notif.next_due_date); d.setHours(0,0,0,0); return d; })();
    const daysUntil = period ? period.diffDays : Math.ceil((dueDate - today) / 86400000);

    // Alert window: H-[alertDaysBefore] up to H (today). Overdue and far-future schedules are hidden here.
    if (daysUntil < 0 || daysUntil > alertDaysBefore.value) return null;

    const todayRecords = (machine.records || []).filter(r => r.status === 'completed' && isSameDay(r.maintenance_date, today));
    const checkedIds = new Set();
    todayRecords.forEach(r => (r.actions || []).forEach(a => a.machine_component_id && checkedIds.add(a.machine_component_id)));

    const totalComponents = machine.components?.length ?? 0;
    const checkedCount    = (machine.components || []).filter(c => checkedIds.has(c.id)).length;
    const uncheckedCount  = totalComponents - checkedCount;
    const isFullyChecked  = totalComponents > 0 && checkedCount === totalComponents;
    const isPartiallyChecked = checkedCount > 0 && checkedCount < totalComponents;

    return { ...notif, next_due_date: dueDate, machine, totalComponents, checkedCount, uncheckedCount, isFullyChecked, isPartiallyChecked, daysUntil, daysBeforeSetting: daysBeforeSetting.value };
  }).filter(Boolean)
    .sort((a, b) => a.daysUntil - b.daysUntil)
    .filter((item, index, self) => self.findIndex(i => i.machine_id === item.machine_id) === index);
});
</script>
