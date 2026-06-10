<template>
  <div>
    <!-- Notifications Section -->
    <div class="mb-8">
      <h3 class="text-lg font-semibold text-slate-800 mb-4 flex items-center">
        <svg class="w-5 h-5 mr-2 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
        Maintenance Alerts
      </h3>
      <div class="space-y-3">
        <div v-if="loading" class="animate-pulse flex space-x-4 p-4 rounded-xl bg-white border border-slate-100">
          <div class="rounded-full bg-slate-200 h-10 w-10"></div>
          <div class="flex-1 space-y-3 py-1">
            <div class="h-2 bg-slate-200 rounded w-3/4"></div>
            <div class="h-2 bg-slate-200 rounded"></div>
          </div>
        </div>
        <div v-else-if="notifications.length === 0" class="bg-white rounded-xl p-5 text-center border border-slate-100 shadow-sm flex items-center gap-4">
          <div class="w-10 h-10 rounded-full bg-green-50 text-green-500 flex items-center justify-center flex-shrink-0">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
          </div>
          <div class="text-left">
            <h4 class="text-slate-700 font-medium text-sm">Semua Aman!</h4>
            <p class="text-xs text-slate-400 mt-0.5">Tidak ada jadwal maintenance yang tertunggak.</p>
          </div>
        </div>
        <div v-else v-for="notif in notifications" :key="notif.id" :class="getNotifClasses(notif.next_due_date)" class="flex items-center p-4 rounded-xl border transition-all hover:shadow-md cursor-pointer gap-4" 
        @click="$router.push(`/machine/${notif.machine_id}`)"
        >
          <div :class="getNotifIconClasses(notif.next_due_date)" class="flex-shrink-0 w-10 h-10 rounded-full flex items-center justify-center">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
          </div>
          <div class="flex-1 min-w-0">
            <div class="flex justify-between items-center gap-2">
              <h4 class="text-sm font-bold text-slate-800 truncate">{{ notif.machine.name }}</h4>
              <span :class="getNotifBadgeClasses(notif.next_due_date)" class="text-xs font-semibold bg-white px-2 py-0.5 rounded-full border border-current flex-shrink-0">{{ getNotifTimeText(notif.next_due_date) }}</span>
            </div>
            <p class="text-xs text-slate-500 mt-0.5">{{ notif.schedule_type }} · Jadwal: {{ formatDate(notif.next_due_date) }}</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Machines Grid -->
    <div>
      <div class="flex flex-wrap items-center justify-between gap-4 mb-5">
        <h3 class="text-lg font-semibold text-slate-800 flex items-center">
          <svg class="w-5 h-5 mr-2 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
          Machine Status
        </h3>
        <!-- Filter & Sort -->
        <div class="flex flex-wrap gap-2 items-center">
          <div class="relative">
            <svg class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            <input v-model="machineSearch" type="text" placeholder="Cari mesin..." class="pl-9 pr-4 py-2 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm text-slate-700 w-44">
          </div>
          <select v-model="machineSort" class="rounded-xl border border-slate-200 px-3 py-2 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 cursor-pointer">
            <option value="name">Nama A-Z</option>
            <option value="condition_asc">Kondisi Terendah</option>
            <option value="condition_desc">Kondisi Tertinggi</option>
          </select>
          <!-- FILTER STATUS MESIN -->
          <!-- <select v-model="machineFilter" class="rounded-xl border border-slate-200 px-3 py-2 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 cursor-pointer">
            <option value="">Semua Status</option>
            <option value="active">Active</option>
            <option value="maintenance">Maintenance</option>
            <option value="inactive">Inactive</option>
          </select> -->
        </div>
      </div>

      <div v-if="loading" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
        <div v-for="i in 3" :key="i" class="bg-white rounded-2xl p-6 border border-slate-100 shadow-sm animate-pulse">
          <div class="h-4 bg-slate-200 rounded w-1/2 mb-6"></div>
          <div class="flex justify-center mb-6"><div class="w-32 h-32 rounded-full border-8 border-slate-100"></div></div>
        </div>
      </div>

      <div v-else-if="filteredMachines.length === 0" class="text-center py-12 text-slate-400 bg-white rounded-2xl border border-slate-100">
        <p>Tidak ada mesin ditemukan</p>
      </div>

      <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
        <div v-for="machine in filteredMachines" :key="machine.id" @click="$$(`/machine/${machine.id}`)" class="bg-white rounded-2xl p-6 border border-slate-100 shadow-sm hover:shadow-lg transition-all relative overflow-hidden group cursor-pointer transform hover:-translate-y-1 duration-300">
          <div :class="['absolute top-0 right-0 w-24 h-24 rounded-bl-full -z-10 opacity-50 group-hover:scale-110 transition-transform bg-gradient-to-br', getColorTheme(machine.condition_pct).gradClass]"></div>

          <div class="flex justify-between items-start mb-4">
            <div class="flex-1 min-w-0 pr-2">
              <h4 class="font-semibold text-slate-800 leading-tight">{{ machine.name }}</h4>
              <!-- <span :class="statusBadge(machine.status)" class="inline-flex items-center mt-1 px-2.5 py-0.5 rounded-full text-xs font-medium">{{ machine.status.toUpperCase() }}</span> -->
            </div>
            <div :class="getColorTheme(machine.condition_pct).bgClass" class="p-2 rounded-lg flex-shrink-0">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            </div>
          </div>

          <div class="flex justify-center my-5 relative">
            <svg class="w-28 h-28" viewBox="0 0 100 100" style="transform: rotate(-90deg);">
              <circle class="text-slate-100 stroke-current" stroke-width="8" cx="50" cy="50" r="45" fill="transparent"/>
              <circle :class="getColorTheme(machine.condition_pct).textClass" class="stroke-current transition-all duration-1000 ease-out" stroke-width="8" stroke-linecap="round" cx="50" cy="50" r="45" fill="transparent" :stroke-dasharray="282.74" :stroke-dashoffset="282.74 - (machine.condition_pct / 100) * 282.74"/>
            </svg>
            <div class="absolute inset-0 flex flex-col items-center justify-center">
              <span :class="getColorTheme(machine.condition_pct).textClass" class="text-2xl font-bold">{{ machine.condition_pct }}%</span>
              <span class="text-[10px] text-slate-400 uppercase tracking-wide font-medium mt-0.5">Health</span>
            </div>
          </div>

          <div class="text-xs text-slate-500 flex items-center justify-center gap-1">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/></svg>
            {{ machine.location ?? '-' }}
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import axios from 'axios';


const machines = ref([]);
const notifications = ref([]);
const loading = ref(true);
const machineSearch = ref('');
const machineSort = ref('name');
const machineFilter = ref('');

const loadData = async () => {
  try {
    const [machRes, notifRes] = await Promise.all([
      axios.get('/api/machines'),
      axios.get('/api/schedules/notifications'),
    ]);
    machines.value = machRes.data;
    notifications.value = notifRes.data;
  } catch (e) {
    console.error(e);
  } finally {
    loading.value = false;
  }
};

onMounted(() => { loadData(); window.addEventListener('refresh-data', loadData); });
onUnmounted(() => window.removeEventListener('refresh-data', loadData));

const filteredMachines = computed(() => {
  let list = machines.value;
  if (machineSearch.value) {
    const q = machineSearch.value.toLowerCase();
    list = list.filter(m => m.name.toLowerCase().includes(q) || (m.location ?? '').toLowerCase().includes(q));
  }
  if (machineFilter.value) list = list.filter(m => m.status === machineFilter.value);
  if (machineSort.value === 'name') list = [...list].sort((a, b) => a.name.localeCompare(b.name));
  if (machineSort.value === 'condition_asc') list = [...list].sort((a, b) => a.condition_pct - b.condition_pct);
  if (machineSort.value === 'condition_desc') list = [...list].sort((a, b) => b.condition_pct - a.condition_pct);
  return list;
});

// const openReport = (machineId) => window.dispatchEvent(new CustomEvent('open-report-modal', { detail: { machineId } }));

// untuk fungsi onclick menuju report mesin tersebut, error Uncaught ReferenceError: router is not defined

const openReport = (machineId) => {
  console.log(machineId)
  $router.push(`/machine/${machineId}`);
}

const isOverdue = (dateStr) => {
  const d = new Date(dateStr); d.setHours(0,0,0,0);
  const t = new Date(); t.setHours(0,0,0,0);
  return d < t;
};

const getNotifClasses = (d) => isOverdue(d) ? 'bg-red-50 border-red-200' : 'bg-amber-50 border-amber-200';
const getNotifIconClasses = (d) => isOverdue(d) ? 'bg-red-100 text-red-600' : 'bg-amber-100 text-amber-600';
const getNotifBadgeClasses = (d) => isOverdue(d) ? 'text-red-600' : 'text-amber-600';
const getNotifTimeText = (dateStr) => {
  if (!isOverdue(dateStr)) return 'Hari ini';
  const days = Math.ceil(Math.abs(new Date() - new Date(dateStr)) / (1000*60*60*24));
  return `Telat ${days} hari`;
};
const formatDate = (d) => new Date(d).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
const getColorTheme = (pct) => {
  if (pct < 50) return { textClass: 'text-red-500', bgClass: 'bg-red-50 text-red-700', gradClass: 'from-red-50 to-white' };
  if (pct < 80) return { textClass: 'text-amber-500', bgClass: 'bg-amber-50 text-amber-700', gradClass: 'from-amber-50 to-white' };
  return { textClass: 'text-green-500', bgClass: 'bg-green-50 text-green-700', gradClass: 'from-green-50 to-white' };
};
const statusBadge = (s) => ({
  active: 'bg-indigo-50 text-indigo-700',
  maintenance: 'bg-amber-50 text-amber-700',
  inactive: 'bg-slate-100 text-slate-600',
}[s] ?? 'bg-slate-100 text-slate-600');
</script>
