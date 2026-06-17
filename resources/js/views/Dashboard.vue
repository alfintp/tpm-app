<template>
  <div class="space-y-6">
    <!-- Welcome Header -->
    <div class="bg-gradient-to-tr from-brand-brown to-brand-gradation rounded-2xl p-6 text-white shadow-lg">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold mb-1">Selamat Datang, {{ user?.full_name || 'User' }}! 👋</h1>
          <p class="text-indigo-100 text-sm">{{ getGreeting() }}. Ini adalah ringkasan status maintenance hari ini.</p>
        </div>
        <div class="text-right">
          <p class="text-sm text-indigo-100">{{ formatDate(new Date()) }}</p>
          <p class="text-xs text-indigo-200 mt-1">{{ user?.role ? user.role.charAt(0).toUpperCase() + user.role.slice(1) : '' }}</p>
        </div>
      </div>
    </div>

    <!-- Quick Stats -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
      <div class="bg-white rounded-2xl p-5 border border-slate-100 shadow-sm hover:shadow-md transition-shadow">
        <div class="flex items-center justify-between mb-2">
          <div class="w-10 h-10 bg-blue-50 rounded-xl flex items-center justify-center">
            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
          </div>
          <span class="text-xs text-slate-400 font-medium">Total</span>
        </div>
        <p class="text-2xl font-bold text-slate-800">{{ machines.length }}</p>
        <p class="text-xs text-slate-500 mt-1">Mesin Terdaftar</p>
      </div>

      <div class="bg-white rounded-2xl p-5 border border-slate-100 shadow-sm hover:shadow-md transition-shadow">
        <div class="flex items-center justify-between mb-2">
          <div class="w-10 h-10 bg-amber-50 rounded-xl flex items-center justify-center">
            <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
          </div>
          <span class="text-xs text-slate-400 font-medium">Aktif</span>
        </div>
        <p class="text-2xl font-bold text-slate-800">{{ activeMachines }}</p>
        <p class="text-xs text-slate-500 mt-1">Mesin Aktif</p>
      </div>

      <div class="bg-white rounded-2xl p-5 border border-slate-100 shadow-sm hover:shadow-md transition-shadow">
        <div class="flex items-center justify-between mb-2">
          <div class="w-10 h-10 bg-green-50 rounded-xl flex items-center justify-center">
            <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
          </div>
          <span class="text-xs text-slate-400 font-medium">Sehat</span>
        </div>
        <p class="text-2xl font-bold text-slate-800">{{ healthyMachines }}</p>
        <p class="text-xs text-slate-500 mt-1">Kondisi >80%</p>
      </div>

      <div class="bg-white rounded-2xl p-5 border border-slate-100 shadow-sm hover:shadow-md transition-shadow">
        <div class="flex items-center justify-between mb-2">
          <div class="w-10 h-10 bg-red-50 rounded-xl flex items-center justify-center">
            <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
          </div>
          <span class="text-xs text-slate-400 font-medium">Perhatian</span>
        </div>
        <p class="text-2xl font-bold text-slate-800">{{ criticalMachines }}</p>
        <p class="text-xs text-slate-500 mt-1">Kondisi <50%</p>
      </div>
    </div>

    <!-- Maintenance Alerts (Enhanced) -->
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
      <div class="p-6 border-b border-slate-100">
        <div class="flex items-center justify-between">
          <h3 class="text-lg font-semibold text-slate-800 flex items-center">
            <svg class="w-5 h-5 mr-2 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
            Maintenance Alerts
          </h3>
          <button 
            @click="router.visit('/machines')" 
            class="text-sm text-indigo-600 hover:text-indigo-800 font-medium flex items-center gap-1"
          >
            Lihat Semua
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
          </button>
        </div>
      </div>
      
      <div class="p-6">
        <div v-if="loading" class="space-y-3">
          <div v-for="i in 3" :key="i" class="animate-pulse flex space-x-4 p-4 rounded-xl bg-slate-50">
            <div class="rounded-full bg-slate-200 h-10 w-10"></div>
            <div class="flex-1 space-y-3 py-1">
              <div class="h-2 bg-slate-200 rounded w-3/4"></div>
              <div class="h-2 bg-slate-200 rounded"></div>
            </div>
          </div>
        </div>

        <div v-else-if="maintenanceAlerts.length === 0" class="text-center py-8">
          <div class="w-16 h-16 bg-green-50 rounded-full flex items-center justify-center mx-auto mb-4">
            <svg class="w-8 h-8 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
          </div>
          <h4 class="text-lg font-semibold text-slate-700 mb-1">Semua Aman! 🎉</h4>
          <p class="text-sm text-slate-500">Tidak ada jadwal maintenance yang perlu perhatian hari ini.</p>
        </div>

        <div v-else class="space-y-3">
          <div v-for="alert in maintenanceAlerts" :key="alert.id" 
               :class="getAlertClasses(alert)" 
               class="group flex items-center p-4 rounded-xl border transition-all hover:shadow-md cursor-pointer gap-4"
               @click="router.visit(`/machine/${alert.machine_id}`)">
            
            <div :class="getAlertIconClasses(alert)" class="flex-shrink-0 w-12 h-12 rounded-xl flex items-center justify-center">
              <svg v-if="alert.isFullyChecked" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
              <svg v-else class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            </div>

            <div class="flex-1 min-w-0">
              <div class="flex justify-between items-start gap-2 mb-2">
                <div>
                  <h4 class="text-base font-bold text-slate-800 truncate">{{ alert.machine?.name }}</h4>
                  <p class="text-xs text-slate-500 mt-0.5">
                    <span class="capitalize">{{ alert.schedule_type }}</span> · {{ formatDate(alert.next_due_date) }}
                  </p>
                </div>
                <span :class="getAlertBadgeClasses(alert)" class="text-xs font-semibold bg-white px-3 py-1 rounded-full border border-current flex-shrink-0">
                  {{ alert.isFullyChecked ? 'Selesai' : getAlertTimeText(alert.next_due_date) }}
                </span>
              </div>

              <!-- Progress Bar (hanya untuk hari ini atau terlambat) -->
              <div v-if="!alert.isFullyChecked && alert.totalComponents > 0 && (alert.daysUntil <= 0)" class="mb-2">
                <div class="flex justify-between text-xs text-slate-600 mb-1">
                  <span>Progress Komponen</span>
                  <span class="font-medium">{{ alert.checkedCount }}/{{ alert.totalComponents }}</span>
                </div>
                <div class="w-full bg-slate-100 rounded-full h-2">
                  <div 
                    :class="alert.isPartiallyChecked ? 'bg-amber-500' : 'bg-red-500'" 
                    class="h-2 rounded-full transition-all duration-300"
                    :style="{ width: `${(alert.checkedCount / alert.totalComponents) * 100}%` }"
                  ></div>
                </div>
              </div>

              <!-- Status Messages (hanya untuk hari ini atau terlambat) -->
              <div v-if="alert.daysUntil <= 0" class="flex items-center gap-2 text-xs">
                <span v-if="alert.isFullyChecked" class="text-green-600 font-semibold flex items-center gap-1">
                  <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                  Semua komponen sudah dicek
                </span>
                <span v-else-if="alert.isPartiallyChecked" class="text-amber-600 font-semibold flex items-center gap-1">
                  <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                  {{ alert.uncheckedCount }} komponen tersisa
                </span>
                <span v-else class="text-red-500 font-semibold flex items-center gap-1">
                  <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                  Belum ada yang dicek
                </span>
              </div>
            </div>

            <div class="flex-shrink-0 opacity-0 group-hover:opacity-100 transition-opacity">
              <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Machines Grid with Enhanced Cards -->
    <div>
      <div class="flex flex-wrap items-center justify-between gap-4 mb-5">
        <h3 class="text-lg font-semibold text-slate-800 flex items-center">
          <svg class="w-5 h-5 mr-2 text-brand-brown" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
          Status Mesin
        </h3>
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
        </div>
      </div>

      <div v-if="loading" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
        <div v-for="i in 4" :key="i" class="bg-white rounded-2xl p-6 border border-slate-100 shadow-sm animate-pulse">
          <div class="h-4 bg-slate-200 rounded w-1/2 mb-6"></div>
          <div class="flex justify-center mb-6"><div class="w-32 h-32 rounded-full border-8 border-slate-100"></div></div>
        </div>
      </div>

      <div v-else-if="filteredMachines.length === 0" class="text-center py-12 text-slate-400 bg-white rounded-2xl border border-slate-100">
        <svg class="w-12 h-12 mx-auto mb-3 text-slate-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <p class="font-medium text-slate-500">Tidak ada mesin ditemukan</p>
      </div>

      <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
        <div v-for="machine in filteredMachines" :key="machine.id" 
             @click="router.visit(`/machine/${machine.id}`)" 
             class="bg-white rounded-2xl p-6 border border-slate-100 shadow-sm hover:shadow-lg transition-all relative overflow-hidden group cursor-pointer transform hover:-translate-y-1 duration-300">
          
          <!-- Gradient Background -->
          <div :class="['absolute top-0 right-0 w-24 h-24 rounded-bl-full -z-10 opacity-50 group-hover:scale-110 transition-transform bg-gradient-to-br', getColorTheme(machine.condition_pct).gradClass]"></div>

          <!-- Machine Header -->
          <div class="flex justify-between items-start mb-4">
            <div class="flex-1 min-w-0 pr-2">
              <h4 class="font-semibold text-slate-800 leading-tight truncate">{{ machine.name }}</h4>
              <p class="text-xs text-slate-400 mt-1 truncate">{{ machine.location ?? '-' }}</p>
            </div>
            <div :class="getColorTheme(machine.condition_pct).bgClass" class="p-2 rounded-lg flex-shrink-0">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            </div>
          </div>

          <!-- Condition Circle -->
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

          <!-- Machine Info -->
          <div class="space-y-2">
            <div class="flex items-center justify-between text-xs">
              <span class="text-slate-500">Status:</span>
              <span :class="statusBadge(machine.status)" class="px-2 py-1 rounded-full text-xs font-semibold">
                {{ machine.status }}
              </span>
            </div>
            
            <!-- Next Schedule Info -->
            <div v-if="getMachineSchedule(machine)" class="flex items-center justify-between text-xs">
              <span class="text-slate-500">Jadwal:</span>
              <span :class="urgencyClass(getMachineSchedule(machine).next_due_date).badge" class="px-2 py-1 rounded-full text-xs font-bold">
                {{ urgencyClass(getMachineSchedule(machine).next_due_date).label }}
              </span>
            </div>

            <!-- Location with City -->
            <div class="flex items-center text-xs text-slate-500">
              <svg class="w-3.5 h-3.5 mr-1 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/></svg>
              <span class="truncate">{{ machine.location ?? '-' }}</span>
              <span v-if="machine.kota" class="ml-1 px-1.5 py-0.5 bg-indigo-50 text-indigo-600 rounded text-[10px] font-bold uppercase">
                {{ machine.kota === 'sby' ? 'Sby' : 'Psn' }}
              </span>
            </div>
          </div>

          <!-- Hover Action -->
          <div class="absolute top-4 right-4 opacity-0 group-hover:opacity-100 transition-opacity">
            <div class="bg-white rounded-lg shadow-md p-1.5 border border-slate-100">
              <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { router } from '@inertiajs/vue3';
import axios from 'axios';
import { useAuth } from '@/composables/useAuth';

const props = defineProps({
  machines: {
    type: Array,
    default: null
  },
  schedules: {
    type: Array,
    default: null
  },
  notifications: {
    type: Array,
    default: null
  }
});

const { user } = useAuth();

const machines = ref(props.machines || []);
const notifications = ref(props.notifications || []);
const loading = ref(!props.machines);
const machineSearch = ref('');
const machineSort = ref('name');

// Load data from API
const loadData = async () => {
  try {
    const [machRes, notifRes] = await Promise.all([
      axios.get('/api/machines'),
      axios.get('/api/schedules/notifications'),
    ]);
    machines.value = machRes.data;
    notifications.value = notifRes.data;
  } catch (e) {
    console.error('Failed to load dashboard data:', e);
  } finally {
    loading.value = false;
  }
};

// Enhanced maintenance alerts algorithm (from Machines.vue)
const maintenanceAlerts = computed(() => {
  const today = new Date();
  today.setHours(0, 0, 0, 0);

  const isSameDay = (date1, date2) => {
    const d1 = new Date(date1);
    const d2 = new Date(date2);
    return d1.getFullYear() === d2.getFullYear() &&
           d1.getMonth() === d2.getMonth() &&
           d1.getDate() === d2.getDate();
  };

  return notifications.value.map(notif => {
    const dueDate = new Date(notif.next_due_date);
    dueDate.setHours(0, 0, 0, 0);

    // Show alert from H-1 (1 day before due date)
    const daysUntil = Math.ceil((dueDate - today) / (1000 * 60 * 60 * 24));
    if (daysUntil < -1) return null; // Hide if more than 1 day overdue

    const machine = machines.value.find(m => m.id === notif.machine_id);
    if (!machine) return null;

    // Calculate component check status today
    const todayRecords = (machine.records || []).filter(r => r.status === 'completed' && isSameDay(r.maintenance_date, today));
    const checkedComponentIds = new Set();
    todayRecords.forEach(r => {
      (r.actions || []).forEach(a => {
        if (a.machine_component_id) {
          checkedComponentIds.add(a.machine_component_id);
        }
      });
    });

    const totalComponents = machine.components ? machine.components.length : 0;
    let checkedCount = 0;
    if (machine.components) {
      machine.components.forEach(c => {
        if (checkedComponentIds.has(c.id)) {
          checkedCount++;
        }
      });
    }

    const uncheckedCount = totalComponents - checkedCount;
    const isFullyChecked = totalComponents > 0 && checkedCount === totalComponents;
    const isPartiallyChecked = checkedCount > 0 && checkedCount < totalComponents;

    // Check if maintenance has been done recently (within the last interval, but not today)
    const lastRecord = machine.records
      .filter(r => r.status === 'completed')
      .sort((a, b) => new Date(b.maintenance_date) - new Date(a.maintenance_date))[0];

    let isDoneRecently = false;
    if (lastRecord) {
      const lastMaintenanceDate = new Date(lastRecord.maintenance_date);
      lastMaintenanceDate.setHours(0, 0, 0, 0);

      const previousDueDate = new Date(dueDate);
      previousDueDate.setDate(previousDueDate.getDate() - (notif.interval_days || 7));

      isDoneRecently = lastMaintenanceDate >= previousDueDate;
    }

    // Filter out if done recently but not touched today (to avoid active spam alert)
    if (isDoneRecently && !isFullyChecked && !isPartiallyChecked) {
      return null;
    }

    return {
      ...notif,
      machine,
      totalComponents,
      checkedCount,
      uncheckedCount,
      isFullyChecked,
      isPartiallyChecked,
      daysUntil
    };
  }).filter(item => item !== null);
});

// Computed properties for stats
const activeMachines = computed(() => machines.value.filter(m => m.status === 'active').length);
const healthyMachines = computed(() => machines.value.filter(m => m.condition_pct > 80).length);
const criticalMachines = computed(() => machines.value.filter(m => m.condition_pct < 50).length);

// Filter and sort machines
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
  if (machineSort.value === 'name') list = [...list].sort((a, b) => a.name.localeCompare(b.name));
  if (machineSort.value === 'condition_asc') list = [...list].sort((a, b) => a.condition_pct - b.condition_pct);
  if (machineSort.value === 'condition_desc') list = [...list].sort((a, b) => b.condition_pct - a.condition_pct);
  return list;
});

// Helper functions
const getGreeting = () => {
  const hour = new Date().getHours();
  if (hour < 12) return 'Selamat Pagi';
  if (hour < 15) return 'Selamat Siang';
  if (hour < 18) return 'Selamat Sore';
  return 'Selamat Malam';
};

const formatDate = (d) => new Date(d).toLocaleDateString('id-ID', { 
  day: 'numeric', 
  month: 'short', 
  year: 'numeric' 
});

const getColorTheme = (pct) => {
  if (pct < 50) return { 
    textClass: 'text-red-500', 
    bgClass: 'bg-red-50 text-red-700', 
    gradClass: 'from-red-50 to-white' 
  };
  if (pct < 80) return { 
    textClass: 'text-amber-500', 
    bgClass: 'bg-amber-50 text-amber-700', 
    gradClass: 'from-amber-50 to-white' 
  };
  return { 
    textClass: 'text-green-500', 
    bgClass: 'bg-green-50 text-green-700', 
    gradClass: 'from-green-50 to-white' 
  };
};

const statusBadge = (s) => ({
  active: 'bg-green-50 text-green-700',
  maintenance: 'bg-amber-50 text-amber-700',
  inactive: 'bg-slate-100 text-slate-600',
}[s] ?? 'bg-slate-100 text-slate-600');

// Schedule helpers
const getMachineSchedule = (machine) => {
  if (!machine.schedules || machine.schedules.length === 0) return null;
  return machine.schedules
    .filter(s => s.is_active !== false)
    .sort((a, b) => new Date(a.next_due_date) - new Date(b.next_due_date))[0];
};

const getDaysUntil = (dateStr) => {
  const d = new Date(dateStr);
  d.setHours(0, 0, 0, 0);
  const t = new Date();
  t.setHours(0, 0, 0, 0);
  return Math.ceil((d - t) / (1000 * 60 * 60 * 24));
};

const urgencyClass = (dateStr) => {
  const days = getDaysUntil(dateStr);
  if (days < 0) return { badge: 'bg-red-100 text-red-700', label: `Telat ${Math.abs(days)}` };
  if (days === 0) return { badge: 'bg-amber-100 text-amber-700', label: 'Hari Ini' };
  if (days <= 3) return { badge: 'bg-amber-50 text-amber-600', label: `${days} Hari` };
  if (days <= 7) return { badge: 'bg-blue-50 text-blue-600', label: `${days} Hari` };
  return { badge: 'bg-slate-50 text-slate-600', label: `${days} Hari` };
};

// Alert helper functions
const getAlertClasses = (alert) => {
  if (alert.isFullyChecked) return 'bg-green-50 border-green-200';
  if (alert.isPartiallyChecked) return 'bg-amber-50 border-amber-200';
  return alert.daysUntil < 0 ? 'bg-red-50 border-red-200' : 'bg-amber-50 border-amber-200';
};

const getAlertIconClasses = (alert) => {
  if (alert.isFullyChecked) return 'bg-green-100 text-green-600';
  if (alert.isPartiallyChecked) return 'bg-amber-100 text-amber-600';
  return alert.daysUntil < 0 ? 'bg-red-100 text-red-600' : 'bg-amber-100 text-amber-600';
};

const getAlertBadgeClasses = (alert) => {
  if (alert.isFullyChecked) return 'text-green-600';
  if (alert.isPartiallyChecked) return 'text-amber-600';
  return alert.daysUntil < 0 ? 'text-red-600' : 'text-amber-600';
};

const getAlertTimeText = (dateStr) => {
  const days = getDaysUntil(dateStr);
  if (days < 0) return `Telat ${Math.abs(days)} hari`;
  if (days === 0) return 'Hari Ini';
  if (days === 1) return 'Besok';
  return `${days} Hari Lagi`;
};

// Lifecycle
onMounted(() => { 
  if (!props.machines) {
    loadData(); 
  }
  window.addEventListener('refresh-data', loadData); 
});

onUnmounted(() => { 
  window.removeEventListener('refresh-data', loadData); 
});
</script>
