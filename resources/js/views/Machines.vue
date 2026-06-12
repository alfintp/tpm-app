<template>
  <div class="space-y-6">
    <!-- Header + Create Button -->
    <div class="flex justify-between items-start flex-wrap gap-4">
      <div>
        <h2 class="text-2xl font-bold text-slate-800">Daftar Mesin</h2>
        <p class="text-sm text-slate-500 mt-1">Kelola semua mesin dalam sistem</p>
      </div>
      <div class="flex items-center gap-3">
        <!-- Buttons Import (Admin only) -->
        <button 
          v-if="isAdmin" 
          @click="triggerMachineImport" 
          class="flex items-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2.5 rounded-xl font-semibold text-sm transition-all shadow-sm cursor-pointer"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
          Import Mesin
        </button>
        <button 
          v-if="isAdmin" 
          @click="triggerComponentImport" 
          class="flex items-center gap-2 bg-teal-600 hover:bg-teal-700 text-white px-4 py-2.5 rounded-xl font-semibold text-sm transition-all shadow-sm cursor-pointer"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
          Import Komponen
        </button>
        <button v-if="isManagerOrAdmin" @click="openCreate" class="flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white px-5 py-2.5 rounded-xl font-medium transition-all shadow-md cursor-pointer">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
          Tambah Mesin
        </button>
      </div>
    </div>

    <!-- Filter & Sort Bar -->
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-4 flex flex-wrap gap-3 items-center">
      <div class="flex-1 relative min-w-[200px]">
        <svg class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
        <input v-model="search" type="text" placeholder="Cari nama mesin atau lokasi..." class="w-full pl-9 pr-4 py-2 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm text-slate-700">
      </div>
      <select v-model="filterSchedule" class="rounded-xl border border-slate-200 px-3 py-2 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 cursor-pointer">
        <option value="">Semua Jadwal</option>
        <option value="overdue">Telat / Overdue</option>
        <option value="today">Hari Ini</option>
        <option value="week">Minggu Ini</option>
        <option value="upcoming">Mendatang</option>
      </select>
      <select v-model="sortBy" class="rounded-xl border border-slate-200 px-3 py-2 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 cursor-pointer">
        <option value="name">Nama A-Z</option>
        <option value="name_desc">Nama Z-A</option>
        <option value="condition_asc">Kondisi Terendah</option>
        <option value="condition_desc">Kondisi Tertinggi</option>
      </select>
    </div>

    <!-- Stats Summary -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
      <div v-for="stat in stats" :key="stat.label" :class="stat.bg" class="rounded-2xl p-4 border">
        <p :class="stat.textColor" class="text-2xl font-bold">{{ stat.value }}</p>
        <p class="text-xs text-slate-500 mt-1 font-medium">{{ stat.label }}</p>
      </div>
    </div>

    <!-- Maintenance Alerts -->
    <div v-if="!loading && maintenanceAlerts.length > 0">
      <h3 class="text-lg font-semibold text-slate-800 mb-4 flex items-center">
        <svg class="w-5 h-5 mr-2 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
        Maintenance Alerts
      </h3>
      <div class="space-y-3">
        <div v-for="alert in maintenanceAlerts" :key="alert.id" :class="getAlertClasses(alert)" class="flex items-center p-4 rounded-xl border transition-all hover:shadow-md cursor-pointer gap-4"
          @click="$router.push(`/machine/${alert.machine_id}`)"
        >
          <div :class="getAlertIconClasses(alert)" class="flex-shrink-0 w-10 h-10 rounded-full flex items-center justify-center">
            <svg v-if="alert.isFullyChecked" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
          </div>
          <div class="flex-1 min-w-0">
            <div class="flex justify-between items-center gap-2">
              <h4 class="text-sm font-bold text-slate-800 truncate">{{ alert.machine?.name }}</h4>
              <span :class="getAlertBadgeClasses(alert)" class="text-xs font-semibold bg-white px-2 py-0.5 rounded-full border border-current flex-shrink-0">
                {{ alert.isFullyChecked ? 'Sudah Dicek' : getAlertTimeText(alert.next_due_date) }}
              </span>
            </div>
            <p class="text-xs text-slate-500 mt-0.5">
              <span class="capitalize">{{ alert.schedule_type }}</span> · Jadwal: {{ formatDate(alert.next_due_date) }}
              <span v-if="alert.isFullyChecked" class="text-green-600 font-semibold"> · ✅ {{ alert.totalComponents }} komponen sudah dicek</span>
              <span v-else-if="alert.isPartiallyChecked" class="text-amber-600 font-semibold"> · ⚠️ Baru {{ alert.checkedCount }} dari {{ alert.totalComponents }} komponen dicek (sisa {{ alert.uncheckedCount }} lagi)</span>
              <span v-else class="text-red-500 font-semibold"> · {{ alert.totalComponents }} komponen perlu dicek</span>
            </p>
          </div>
        </div>
      </div>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
      <div v-if="loading" class="p-8 text-center text-slate-400">
        <div class="animate-spin w-8 h-8 border-4 border-indigo-200 border-t-indigo-600 rounded-full mx-auto mb-3"></div>
        Memuat data...
      </div>
      <div v-else-if="filtered.length === 0" class="p-12 text-center text-slate-400">
        <svg class="w-12 h-12 mx-auto mb-3 text-slate-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <p class="font-medium text-slate-500">Tidak ada mesin ditemukan</p>
        <p class="text-sm mt-1">Coba ubah kata kunci pencarian atau filter</p>
      </div>
      <table v-else class="w-full min-w-[700px]">
        <thead>
          <tr class="bg-slate-50 border-b border-slate-100">
            <th class="text-left text-xs font-semibold text-slate-500 uppercase tracking-wider px-6 py-4">Nama Mesin</th>
            <th class="text-left text-xs font-semibold text-slate-500 uppercase tracking-wider px-6 py-4">Lokasi</th>
            <th class="text-left text-xs font-semibold text-slate-500 uppercase tracking-wider px-6 py-4">Jadwal</th>
            <th class="text-left text-xs font-semibold text-slate-500 uppercase tracking-wider px-6 py-4">Status</th>
            <th class="text-center text-xs font-semibold text-slate-500 uppercase tracking-wider px-6 py-4">Kondisi</th>
            <th class="text-center text-xs font-semibold text-slate-500 uppercase tracking-wider px-6 py-4">Komponen</th>
            <th class="text-right text-xs font-semibold text-slate-500 uppercase tracking-wider px-6 py-4 w-16"></th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-50">
          <tr v-for="machine in filtered" :key="machine.id" @click="$router.push(`/machine/${machine.id}`)" class="hover:bg-indigo-50/50 transition-colors cursor-pointer group relative">
            <td class="px-6 py-4">
              <div class="flex items-center gap-2">
                <p class="font-bold text-slate-800">{{ machine.name }}</p>
                <!-- <span v-if="machine.kode" class="px-2 py-0.5 bg-slate-100 text-slate-600 rounded-lg text-[10px] font-mono font-semibold">{{ machine.kode }}</span> -->
              </div>
              <p class="text-xs text-slate-400 mt-0.5 line-clamp-1">{{ machine.description }}</p>
            </td>
            <td class="px-6 py-4 text-sm text-slate-600">
              <div class="font-medium text-slate-700">{{ machine.location ?? '-' }}</div>
              <span v-if="machine.kota" class="text-[11px] font-bold text-indigo-500 uppercase tracking-wide">
                {{ machine.kota === 'sby' ? 'Surabaya' : 'Pasuruan' }}
              </span>
            </td>
            <td class="px-6 py-4">
              <div v-if="getMachineSchedule(machine)" class="flex items-center gap-2">
                <span class="text-sm font-medium text-slate-700">{{ formatDate(getMachineSchedule(machine).next_due_date) }}</span>
                <span :class="urgencyClass(getMachineSchedule(machine).next_due_date).badge" class="px-2 py-0.5 rounded-full text-xs font-bold">
                  {{ urgencyClass(getMachineSchedule(machine).next_due_date).label }}
                </span>
              </div>
              <span v-else class="text-sm text-slate-400">-</span>
            </td>
            <td class="px-6 py-4">
              <span :class="statusClass(machine.status)" class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold">
                <span class="w-1.5 h-1.5 rounded-full mr-1.5" :class="statusDotClass(machine.status)"></span>
                {{ machine.status }}
              </span>
            </td>
            <td class="px-6 py-4 text-center">
              <span :class="condClass(machine.condition_pct)" class="font-bold text-base">{{ machine.condition_pct }}%</span>
            </td>
            <td class="px-6 py-4 text-center text-sm text-slate-600">{{ machine.components?.length ?? 0 }}</td>
            <td class="px-6 py-4 text-right">
              <button v-if="isManagerOrAdmin" @click="deleteMachine(machine, $event)" class="p-2 text-slate-400 hover:text-red-500 hover:bg-red-50 rounded-lg cursor-pointer transition-colors opacity-0 group-hover:opacity-100" title="Hapus">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
              </button>
            </td>
          </tr>
        </tbody>
      </table>

      <!-- Table footer -->
      <div v-if="!loading && filtered.length > 0" class="px-6 py-3 bg-slate-50 border-t border-slate-100 text-xs text-slate-400">
        Menampilkan {{ filtered.length }} dari {{ machines.length }} mesin
      </div>
    </div>

    <!-- Create Machine Modal -->
    <MachineCreateModal v-if="showCreate" @close="showCreate = false" @saved="onMachineSaved" />

    <!-- Import Excel Modal -->
    <div v-if="showImportModal" class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
      <div class="bg-white rounded-3xl max-w-lg w-full p-6 shadow-xl border border-slate-100 space-y-6">
        <div class="flex justify-between items-start">
          <div>
            <h3 class="text-xl font-bold text-slate-800 flex items-center gap-2">
              <svg v-if="importType === 'machine'" class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
              <svg v-else class="w-6 h-6 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
              {{ importType === 'machine' ? 'Import Data Mesin' : 'Import Komponen Mesin' }}
            </h3>
            <p class="text-xs text-slate-500 mt-1">
              {{ importType === 'machine' ? 'Unggah file Excel untuk mengimpor data mesin secara massal' : 'Unggah file Excel untuk mengimpor komponen massal berdasarkan Kode Mesin' }}
            </p>
          </div>
          <button @click="showImportModal = false" class="p-1.5 hover:bg-slate-100 rounded-xl transition-all cursor-pointer text-slate-400">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
          </button>
        </div>

        <div class="space-y-4">
          <!-- Step 1: Download Template -->
          <div class="bg-slate-50 border border-slate-100 p-4 rounded-2xl flex items-center justify-between gap-4">
            <div>
              <p class="text-xs font-bold text-slate-700">1. Unduh Format Template</p>
              <p class="text-[11px] text-slate-400 mt-0.5">Gunakan template resmi agar susunan kolom sesuai dengan sistem</p>
            </div>
            <button 
              @click="importType === 'machine' ? downloadMachineTemplate() : downloadComponentTemplateGlobal()" 
              class="flex items-center gap-1.5 bg-white border border-emerald-200 hover:border-emerald-300 hover:bg-emerald-50 text-emerald-700 text-xs font-bold px-3 py-2 rounded-xl transition-all cursor-pointer shadow-sm"
            >
              <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
              Format Excel
            </button>
          </div>

          <!-- Step 2: Choose File -->
          <div class="space-y-2">
            <p class="text-xs font-bold text-slate-700">2. Pilih File Excel (.xlsx, .xls, .csv)</p>
            <div 
              class="border-2 border-dashed border-slate-200 hover:border-indigo-400 bg-white hover:bg-indigo-50/20 p-6 rounded-2xl text-center transition-all relative"
            >
              <input 
                type="file" 
                ref="fileInput" 
                @change="handleFileChange" 
                accept=".xlsx, .xls, .csv" 
                class="absolute inset-0 opacity-0 w-full h-full cursor-pointer"
              />
              <svg class="w-8 h-8 text-slate-400 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
              <p class="text-xs font-semibold text-slate-700">
                {{ selectedFile ? selectedFile.name : 'Klik untuk cari file atau seret file ke sini' }}
              </p>
              <p v-if="selectedFile" class="text-[10px] text-slate-400 mt-1">
                Ukuran: {{ (selectedFile.size / 1024).toFixed(1) }} KB
              </p>
            </div>
          </div>
        </div>

        <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
          <button 
            @click="showImportModal = false" 
            class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-xl text-xs font-bold cursor-pointer transition-all"
          >
            Batal
          </button>
          <button 
            @click="importType === 'machine' ? importMachines() : importComponentsGlobal()" 
            :disabled="!selectedFile || importing" 
            :class="importType === 'machine' ? 'bg-emerald-600 hover:bg-emerald-700' : 'bg-teal-600 hover:bg-teal-700'"
            class="px-4 py-2 disabled:bg-slate-200 disabled:text-slate-400 text-white rounded-xl text-xs font-bold flex items-center gap-1.5 cursor-pointer disabled:cursor-not-allowed transition-all"
          >
            <span v-if="importing" class="animate-spin w-3 h-3 border-2 border-white/20 border-t-white rounded-full"></span>
            {{ importing ? 'Mengimpor...' : 'Mulai Import' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';
import MachineCreateModal from '../components/MachineCreateModal.vue';
import { showConfirm, showAlert } from '../composables/useAlert.js';
import { useAuth } from '../composables/useAuth.js';

const router = useRouter();
const { isManagerOrAdmin, isAdmin } = useAuth();
const machines = ref([]);
const schedules = ref([]);
const notifications = ref([]);
const loading = ref(true);
const search = ref('');
const filterSchedule = ref('');
const sortBy = ref('name');
const showCreate = ref(false);

const showImportModal = ref(false);
const importing = ref(false);
const selectedFile = ref(null);
const fileInput = ref(null);

const loadData = async () => {
  try {
    const [machinesRes, schedulesRes, notifRes] = await Promise.all([
      axios.get('/api/machines'),
      axios.get('/api/schedules'),
      axios.get('/api/schedules/notifications')
    ]);
    machines.value = machinesRes.data;
    schedules.value = schedulesRes.data;
    notifications.value = notifRes.data;
  } catch (e) {
    console.error(e);
  } finally {
    loading.value = false;
  }
};

onMounted(() => {
  loadData();
  window.addEventListener('refresh-data', loadData);
});
onUnmounted(() => window.removeEventListener('refresh-data', loadData));

const getDaysUntil = (dateStr) => {
  if (!dateStr) return null;
  const d = new Date(dateStr);
  d.setHours(0,0,0,0);
  const t = new Date(); t.setHours(0,0,0,0);
  return Math.ceil((d - t) / (1000*60*60*24));
};

const getMachineSchedule = (machine) => {
  if (!machine.schedules || machine.schedules.length === 0) return null;
  // Get the earliest upcoming schedule
  const upcoming = machine.schedules
    .filter(s => new Date(s.next_due_date) >= new Date(new Date().setHours(0,0,0,0)))
    .sort((a, b) => new Date(a.next_due_date) - new Date(b.next_due_date));
  return upcoming.length > 0 ? upcoming[0] : null;
};

const urgencyClass = (dateStr) => {
  const days = getDaysUntil(dateStr);
  if (days === null) return { badge: 'bg-slate-100 text-slate-600', label: '-' };
  if (days < 0) return { badge: 'bg-red-100 text-red-700', label: `Telat ${Math.abs(days)} hari` };
  if (days === 0) return { badge: 'bg-amber-100 text-amber-700', label: 'Hari ini' };
  if (days <= 7) return { badge: 'bg-blue-100 text-blue-700', label: `${days} hari lagi` };
  return { badge: 'bg-slate-100 text-slate-600', label: `${days} hari lagi` };
};

const formatDate = (d) => {
  if (!d) return '-';
  return new Date(d).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
};

const stats = computed(() => {
  const m = machines.value;
  const machineSchedules = m.map(machine => getMachineSchedule(machine)).filter(s => s !== null);
  return [
    { label: 'Telat', value: machineSchedules.filter(s => getDaysUntil(s.next_due_date) < 0).length, bg: 'bg-red-50 border-red-100', textColor: 'text-red-600' },
    { label: 'Hari Ini', value: machineSchedules.filter(s => getDaysUntil(s.next_due_date) === 0).length, bg: 'bg-amber-50 border-amber-100', textColor: 'text-amber-600' },
    { label: 'Minggu Ini', value: machineSchedules.filter(s => getDaysUntil(s.next_due_date) > 0 && getDaysUntil(s.next_due_date) <= 7).length, bg: 'bg-blue-50 border-blue-100', textColor: 'text-blue-600' },
    { label: 'Mendatang', value: machineSchedules.filter(s => getDaysUntil(s.next_due_date) > 7).length, bg: 'bg-slate-50 border-slate-100', textColor: 'text-slate-600' },
  ];
});

// Maintenance alerts - show from H-1 (1 day before) and hide if maintenance already done
const maintenanceAlerts = computed(() => {
  const today = new Date();
  today.setHours(0, 0, 0, 0);

  const isSameDay = (d1, d2) => {
    const date1 = new Date(d1);
    const date2 = new Date(d2);
    return date1.getFullYear() === date2.getFullYear() &&
           date1.getMonth() === date2.getMonth() &&
           date1.getDate() === date2.getDate();
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
      totalComponents,
      checkedCount,
      uncheckedCount,
      isFullyChecked,
      isPartiallyChecked,
      daysUntil
    };
  }).filter(item => item !== null);
});

const filtered = computed(() => {
  let list = machines.value;
  
  // Apply search filter
  if (search.value) {
    const q = search.value.toLowerCase();
    list = list.filter(m => m.name.toLowerCase().includes(q) || (m.location ?? '').toLowerCase().includes(q));
  }
  
  // Apply schedule filter
  if (filterSchedule.value) {
    list = list.filter(m => {
      const schedule = getMachineSchedule(m);
      if (!schedule) return false;
      const days = getDaysUntil(schedule.next_due_date);
      if (filterSchedule.value === 'overdue') return days < 0;
      if (filterSchedule.value === 'today') return days === 0;
      if (filterSchedule.value === 'week') return days > 0 && days <= 7;
      if (filterSchedule.value === 'upcoming') return days > 7;
      return true;
    });
  }
  
  // Apply sort
  if (sortBy.value === 'name') list = [...list].sort((a, b) => a.name.localeCompare(b.name));
  if (sortBy.value === 'name_desc') list = [...list].sort((a, b) => b.name.localeCompare(a.name));
  if (sortBy.value === 'condition_asc') list = [...list].sort((a, b) => a.condition_pct - b.condition_pct);
  if (sortBy.value === 'condition_desc') list = [...list].sort((a, b) => b.condition_pct - a.condition_pct);
  
  return list;
});

const openCreate = () => { showCreate.value = true; };
const onMachineSaved = async () => {
  showCreate.value = false;
  await loadData();
  showAlert('success', 'Berhasil!', 'Mesin baru berhasil ditambahkan.');
};

const deleteMachine = async (machine, event) => {
  event.stopPropagation();
  if (!isManagerOrAdmin.value) return;
  const ok = await showConfirm('Hapus Mesin', `Apakah Anda yakin ingin menghapus "${machine.name}"? Semua data terkait (komponen, jadwal, riwayat) akan ikut terhapus.`);
  if (!ok) return;
  try {
    await axios.delete(`/api/machines/${machine.id}`);
    await loadData();
    showAlert('success', 'Dihapus!', `Mesin "${machine.name}" berhasil dihapus.`);
  } catch (e) {
    showAlert('error', 'Gagal!', 'Gagal menghapus mesin: ' + (e.response?.data?.message || e.message));
  }
};

const statusClass = (s) => ({
  active: 'bg-green-50 text-green-700',
  maintenance: 'bg-amber-50 text-amber-700',
  inactive: 'bg-slate-100 text-slate-600',
}[s] ?? 'bg-slate-100 text-slate-600');

const statusDotClass = (s) => ({
  active: 'bg-green-500',
  maintenance: 'bg-amber-500',
  inactive: 'bg-slate-400',
}[s] ?? 'bg-slate-400');

const condClass = (pct) => pct < 50 ? 'text-red-500' : pct < 80 ? 'text-amber-500' : 'text-green-500';

// Alert helper functions
const isAlertOverdue = (dateStr) => {
  const d = new Date(dateStr);
  d.setHours(0, 0, 0, 0);
  const t = new Date();
  t.setHours(0, 0, 0, 0);
  return d < t;
};

const getAlertClasses = (alert) => {
  if (alert.isFullyChecked) return 'bg-green-50 border-green-200';
  if (alert.isPartiallyChecked) return 'bg-amber-50 border-amber-200';
  return isAlertOverdue(alert.next_due_date) ? 'bg-red-50 border-red-200' : 'bg-amber-50 border-amber-200';
};

const getAlertIconClasses = (alert) => {
  if (alert.isFullyChecked) return 'bg-green-100 text-green-600';
  if (alert.isPartiallyChecked) return 'bg-amber-100 text-amber-600';
  return isAlertOverdue(alert.next_due_date) ? 'bg-red-100 text-red-600' : 'bg-amber-100 text-amber-600';
};

const getAlertBadgeClasses = (alert) => {
  if (alert.isFullyChecked) return 'text-green-600';
  if (alert.isPartiallyChecked) return 'text-amber-600';
  return isAlertOverdue(alert.next_due_date) ? 'text-red-600' : 'text-amber-600';
};

const getAlertTimeText = (dateStr) => {
  const d = new Date(dateStr);
  d.setHours(0, 0, 0, 0);
  const t = new Date();
  t.setHours(0, 0, 0, 0);
  const days = Math.ceil((d - t) / (1000 * 60 * 60 * 24));

  if (days < 0) return `Telat ${Math.abs(days)} hari`;
  if (days === 0) return 'Hari ini';
  if (days === 1) return 'Besok';
  return `${days} hari lagi`;
};

const importType = ref('machine'); // 'machine' or 'component'

const triggerMachineImport = () => {
  importType.value = 'machine';
  selectedFile.value = null;
  showImportModal.value = true;
};

const triggerComponentImport = () => {
  importType.value = 'component';
  selectedFile.value = null;
  showImportModal.value = true;
};

const handleFileChange = (e) => {
  const file = e.target.files[0];
  if (file) {
    selectedFile.value = file;
  }
};

const loadSheetJS = () => {
  return new Promise((resolve) => {
    if (window.XLSX) {
      resolve(window.XLSX);
      return;
    }
    const script = document.createElement('script');
    script.src = 'https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js';
    script.onload = () => resolve(window.XLSX);
    document.head.appendChild(script);
  });
};

const downloadMachineTemplate = async () => {
  try {
    const XLSX = await loadSheetJS();
    const headers = [
      ['Kode Mesin', 'Nama Mesin', 'Deskripsi', 'Kondisi (%)', 'Lokasi', 'Kota (psn/sby)', 'Status', 'Email PIC', 'Interval Perawatan (Hari)', 'Tanggal Mulai Perawatan']
    ];
    const rows = [
      ['LL-BLR-01', 'Boiler Utama', 'Mesin pemanas uap utama pabrik', 95, 'Gedung A-1', 'psn', 'active', 'pic@ladanglima.com', 30, '2026-06-12'],
      ['LL-PKG-01', 'Mesin Packaging 1', 'Mesin pengemas tepung singkong otomatis', 80, 'Gedung B-2', 'sby', 'active', 'pic@ladanglima.com', 15, '2026-06-15']
    ];
    
    const wb = XLSX.utils.book_new();
    const ws = XLSX.utils.aoa_to_sheet([...headers, ...rows]);
    
    ws['!cols'] = [
      { wch: 15 }, // Kode Mesin
      { wch: 20 }, // Nama Mesin
      { wch: 35 }, // Deskripsi
      { wch: 15 }, // Kondisi (%)
      { wch: 15 }, // Lokasi
      { wch: 20 }, // Kota
      { wch: 15 }, // Status
      { wch: 25 }, // Email PIC
      { wch: 25 }, // Interval Perawatan (Hari)
      { wch: 25 }  // Tanggal Mulai Perawatan
    ];
    
    XLSX.utils.book_append_sheet(wb, ws, 'Template Import Mesin');
    XLSX.writeFile(wb, 'Format_Import_Mesin.xlsx');
  } catch (err) {
    console.error('Template download failed:', err);
    showAlert('error', 'Gagal!', 'Gagal mendownload template Excel.');
  }
};

const downloadComponentTemplateGlobal = async () => {
  try {
    const XLSX = await loadSheetJS();
    const headers = [
      ['Kode Mesin', 'Kategori', 'Nama Komponen', 'Spesifikasi', 'Jumlah (Qty)', 'Satuan', 'Kondisi Awal (%)', 'Jadwal Perawatan']
    ];
    const rows = [
      ['LL-BLR-01', 'Suku Cadang Utama', 'Piston Cylinder Boiler', 'Stainless Steel 316 100mm', 2, 'Pcs', 100, 'Bulanan'],
      ['LL-PKG-01', 'Sensor & Kontrol', 'Thermostat Digital TC-40', 'Range -50C to 200C', 1, 'Unit', 90, 'Harian']
    ];
    
    const wb = XLSX.utils.book_new();
    const ws = XLSX.utils.aoa_to_sheet([...headers, ...rows]);
    
    ws['!cols'] = [
      { wch: 15 }, // Kode Mesin
      { wch: 20 }, // Kategori
      { wch: 25 }, // Nama Komponen
      { wch: 30 }, // Spesifikasi
      { wch: 15 }, // Jumlah (Qty)
      { wch: 15 }, // Satuan
      { wch: 20 }, // Kondisi Awal (%)
      { wch: 20 }  // Jadwal Perawatan
    ];
    
    XLSX.utils.book_append_sheet(wb, ws, 'Template Import Komponen');
    XLSX.writeFile(wb, 'Format_Import_Komponen_Massal.xlsx');
  } catch (err) {
    console.error('Template download failed:', err);
    showAlert('error', 'Gagal!', 'Gagal mendownload template Excel.');
  }
};

const importMachines = async () => {
  if (!selectedFile.value) return;
  importing.value = true;
  try {
    const XLSX = await loadSheetJS();
    const file = selectedFile.value;
    const reader = new FileReader();
    
    reader.onload = async (e) => {
      try {
        const data = new Uint8Array(e.target.result);
        const workbook = XLSX.read(data, { type: 'array' });
        
        const firstSheetName = workbook.SheetNames[0];
        const worksheet = workbook.Sheets[firstSheetName];
        
        const rows = XLSX.utils.sheet_to_json(worksheet, { header: 1 });
        if (rows.length < 2) {
          showAlert('error', 'Gagal!', 'File Excel kosong atau tidak memiliki baris data.');
          importing.value = false;
          return;
        }
        
        const mappedMachines = [];
        for (let i = 1; i < rows.length; i++) {
          const row = rows[i];
          if (row.length === 0 || !row[0]) continue;
          
          mappedMachines.push({
            kode: row[0]?.toString()?.trim() || '',
            name: row[1]?.toString()?.trim() || '',
            description: row[2]?.toString()?.trim() || null,
            condition_pct: parseFloat(row[3]) || 100,
            location: row[4]?.toString()?.trim() || null,
            kota: (() => {
              const rk = row[5]?.toString()?.trim()?.toLowerCase() || '';
              return (rk === 'sby' || rk === 'surabaya') ? 'sby' : 'pasuruan';
            })(),
            status: row[6]?.toString()?.trim()?.toLowerCase() || 'active',
            pic_email: row[7]?.toString()?.trim() || null,
            maintenance_duration: parseInt(row[8]) || null,
            maintenance_start_date: row[9] ? formatDateISO(row[9]) : null
          });
        }
        
        if (mappedMachines.length === 0) {
          showAlert('error', 'Gagal!', 'Tidak menemukan baris data mesin yang valid.');
          importing.value = false;
          return;
        }
        
        const res = await axios.post('/api/machines/import', { machines: mappedMachines });
        showAlert('success', 'Berhasil!', res.data.message || `Berhasil mengimpor ${mappedMachines.length} mesin.`);
        showImportModal.value = false;
        await loadData();
      } catch (err) {
        console.error('File parsing/import failed:', err);
        showAlert('error', 'Gagal!', 'Gagal memproses file: ' + (err.response?.data?.message || err.message));
      } finally {
        importing.value = false;
      }
    };
    
    reader.readAsArrayBuffer(file);
  } catch (err) {
    console.error('Import failed:', err);
    showAlert('error', 'Gagal!', 'Terjadi kesalahan sistem.');
    importing.value = false;
  }
};

const importComponentsGlobal = async () => {
  if (!selectedFile.value) return;
  importing.value = true;
  try {
    const XLSX = await loadSheetJS();
    const file = selectedFile.value;
    const reader = new FileReader();
    
    reader.onload = async (e) => {
      try {
        const data = new Uint8Array(e.target.result);
        const workbook = XLSX.read(data, { type: 'array' });
        
        const firstSheetName = workbook.SheetNames[0];
        const worksheet = workbook.Sheets[firstSheetName];
        
        const rows = XLSX.utils.sheet_to_json(worksheet, { header: 1 });
        if (rows.length < 2) {
          showAlert('error', 'Gagal!', 'File Excel kosong atau tidak memiliki baris data.');
          importing.value = false;
          return;
        }
        
        const mappedComponents = [];
        for (let i = 1; i < rows.length; i++) {
          const row = rows[i];
          if (row.length === 0 || !row[0]) continue;
          
          mappedComponents.push({
            machine_code: row[0]?.toString()?.trim() || '',
            category: row[1]?.toString()?.trim() || '',
            name: row[2]?.toString()?.trim() || '',
            specification: row[3]?.toString()?.trim() || null,
            qty: parseInt(row[4]) || 1,
            unit: row[5]?.toString()?.trim() || 'Pcs',
            last_condition_pct: parseFloat(row[6]) || 100,
            maintenance_schedule: row[7]?.toString()?.trim() || null
          });
        }
        
        if (mappedComponents.length === 0) {
          showAlert('error', 'Gagal!', 'Tidak menemukan baris data komponen yang valid.');
          importing.value = false;
          return;
        }
        
        const res = await axios.post('/api/components/import-global', { components: mappedComponents });
        showAlert('success', 'Berhasil!', res.data.message || `Berhasil mengimpor ${mappedComponents.length} komponen.`);
        showImportModal.value = false;
        await loadData();
      } catch (err) {
        console.error('File parsing/import failed:', err);
        showAlert('error', 'Gagal!', 'Gagal memproses file: ' + (err.response?.data?.message || err.message));
      } finally {
        importing.value = false;
      }
    };
    
    reader.readAsArrayBuffer(file);
  } catch (err) {
    console.error('Import failed:', err);
    showAlert('error', 'Gagal!', 'Terjadi kesalahan sistem.');
    importing.value = false;
  }
};

const formatDateISO = (val) => {
  if (!val) return null;
  if (typeof val === 'number') {
    const d = new Date(Math.round((val - 25569) * 86400 * 1000));
    return d.toISOString().split('T')[0];
  }
  const dateObj = new Date(val);
  if (!isNaN(dateObj.getTime())) {
    return dateObj.toISOString().split('T')[0];
  }
  return val.toString();
};
</script>
