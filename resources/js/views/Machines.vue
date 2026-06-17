<template>
  <div class="space-y-6">
    <!-- Header -->
    <PageHeader title="Daftar Mesin" subtitle="Kelola semua mesin dalam sistem">
      <template #actions>
        <Button
          v-if="isAdmin"
          @click="triggerMachineImport"
          class="bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl font-semibold text-sm gap-2 hover:cursor-pointer"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
          Import Mesin
        </Button>
        <Button
          v-if="isAdmin"
          @click="triggerComponentImport"
          class="bg-teal-600 hover:bg-teal-700 text-white rounded-xl font-semibold text-sm gap-2 hover:cursor-pointer"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
          Import Komponen
        </Button>
        <Button
          @click="showCalendarModal = true"
          class="bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl font-semibold text-sm gap-2 hover:cursor-pointer"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
          Kalender
        </Button>
        <Button
          v-if="isManagerOrAdmin"
          @click="openCreate"
          class="bg-gradient-to-tr from-brand-brown to-brand-gradation text-white hover:opacity-90 rounded-xl font-semibold text-sm gap-2 shadow-md hover:cursor-pointer"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
          Tambah Mesin
        </Button>
      </template>
    </PageHeader>

    <!-- Stats Summary -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
      <StatCard v-for="stat in stats" :key="stat.label" :value="stat.value" :label="stat.label" :color="stat.color" />
    </div>

    <!-- Maintenance Alerts -->
    <MaintenanceAlerts
      v-if="!loading"
      :alerts="maintenanceAlerts"
      @click-alert="navigateToMachine($event.machine_id)"
    />

    <!-- Search & Filter Bar -->
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-4 flex flex-wrap gap-3 items-center">
      <SearchInput
        v-model="search"
        placeholder="Cari nama mesin atau lokasi..."
        class="flex-1 min-w-[200px]"
      />
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

    <!-- Machines Table -->
    <DataTable
      :columns="machineColumns"
      :rows="filtered"
      :loading="loading"
      loading-text="Memuat data mesin..."
      loading-subtext="Mengambil data dari server"
      empty-title="Tidak ada mesin ditemukan"
      empty-subtext="Coba ubah kata kunci pencarian atau filter"
      min-width="min-w-[700px]"
      :paginate="false"
      actions-label=""
      actions-width="w-16"
      row-clickable
      @row-click="navigateToMachine($event.id)"
    >
      <!-- Kolom: Nama Mesin -->
      <template #cell-name="{ row }">
        <p class="font-bold text-slate-800">{{ row.name }}</p>
        <p class="text-xs text-slate-400 mt-0.5 line-clamp-1">{{ row.description }}</p>
      </template>

      <!-- Kolom: Lokasi -->
      <template #cell-location="{ row }">
        <div class="font-medium text-slate-700 text-sm">{{ row.location ?? '-' }}</div>
        <span v-if="row.kota" class="text-[11px] font-bold text-indigo-500 uppercase tracking-wide">
          {{ row.kota === 'sby' ? 'Surabaya' : 'Pasuruan' }}
        </span>
      </template>

      <!-- Kolom: Jadwal -->
      <template #cell-schedule="{ row }">
        <div v-if="getMachineSchedule(row)" class="flex items-center gap-2 flex-wrap">
          <span class="text-sm font-medium text-slate-700">{{ formatDate(getMachineSchedule(row).next_due_date) }}</span>
          <span :class="urgencyClass(getMachineSchedule(row).next_due_date).badge" class="px-2 py-0.5 rounded-full text-xs font-bold">
            {{ urgencyClass(getMachineSchedule(row).next_due_date).label }}
          </span>
        </div>
        <span v-else class="text-sm text-slate-400">-</span>
      </template>

      <!-- Kolom: Status -->
      <template #cell-status="{ row }">
        <span :class="statusClass(row.status)" class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold">
          <span class="w-1.5 h-1.5 rounded-full mr-1.5" :class="statusDotClass(row.status)"></span>
          {{ row.status }}
        </span>
      </template>

      <!-- Kolom: Kondisi -->
      <template #cell-condition_pct="{ row }">
        <span :class="condClass(row.condition_pct)" class="font-bold text-base">{{ row.condition_pct }}%</span>
      </template>

      <!-- Kolom: Komponen -->
      <template #cell-components_count="{ row }">
        <span class="text-sm text-slate-600">{{ row.components?.length ?? 0 }}</span>
      </template>

      <!-- Slot Actions: Hapus -->
      <template #actions="{ row }">
        <button
          v-if="isManagerOrAdmin"
          @click.stop="deleteMachine(row, $event)"
          class="p-2 text-slate-400 hover:text-red-500 hover:bg-red-50 rounded-lg cursor-pointer transition-colors"
          title="Hapus"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
        </button>
      </template>
    </DataTable>

    <!-- Table Footer -->
    <div v-if="!loading && filtered.length > 0" class="text-xs text-slate-400 px-1">
      Menampilkan {{ filtered.length }} dari {{ allowedMachines.length }} mesin
    </div>

    <!-- Create Machine Modal -->
    <MachineCreateModal v-if="showCreate" @close="showCreate = false" @saved="onMachineSaved" />

    <!-- Import Modal -->
    <MachineImportModal
      :show="showImportModal"
      :type="importType"
      :loading="importing"
      @close="showImportModal = false"
      @download-template="importType === 'machine' ? downloadMachineTemplate() : downloadComponentTemplateGlobal()"
      @import="handleImportFile"
    />

    <!-- Calendar Modal -->
    <MachineCalendarModal
      :show="showCalendarModal"
      :machines="allowedMachines"
      @close="showCalendarModal = false"
      @go-to-machine="goToMachine"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { router } from '@inertiajs/vue3';
import axios from 'axios';
import MachineCreateModal from '../components/MachineCreateModal.vue';
import { showConfirm, showAlert } from '../composables/useAlert.js';
import { useAuth } from '../composables/useAuth.js';
import PageHeader from '../components/PageHeader.vue';
import SearchInput from '../components/SearchInput.vue';
import DataTable from '../components/DataTable.vue';
import StatCard from '../components/StatCard.vue';
import MaintenanceAlerts from '../components/MaintenanceAlerts.vue';
import MachineImportModal from '../components/MachineImportModal.vue';
import MachineCalendarModal from '../components/MachineCalendarModal.vue';
import Button from '../../views/components/ui/button/Button.vue';

const props = defineProps({
  initialMachines: {
    type: Array,
    default: null
  },
  machines: {
    type: Array,
    default: null
  },
  initialSchedules: {
    type: Array,
    default: null
  },
  schedules: {
    type: Array,
    default: null
  },
  initialNotifications: {
    type: Array,
    default: null
  },
  notifications: {
    type: Array,
    default: null
  }
});

const { isManagerOrAdmin, isAdmin, user } = useAuth();
const machines = ref(props.machines || props.initialMachines || []);
const schedules = ref(props.schedules || props.initialSchedules || []);
const notifications = ref(props.notifications || props.initialNotifications || []);
const loading = ref(!props.machines && !props.initialMachines);
const search = ref('');
const filterSchedule = ref('');
const sortBy = ref('name');
const showCreate = ref(false);

const allowedMachines = computed(() => {
  let list = machines.value;
  if (user.value?.role === 'technician' && user.value?.city && user.value.city !== 'both') {
    list = list.filter(m => m.kota === user.value.city);
  }
  return list;
});

const machineColumns = [
  { key: 'name',             label: 'Nama Mesin', width: 'w-[25%]' },
  { key: 'location',         label: 'Lokasi',     width: 'w-[15%]' },
  { key: 'schedule',         label: 'Jadwal',     width: 'w-[20%]' },
  { key: 'status',           label: 'Status',     width: 'w-[12%]' },
  { key: 'condition_pct',    label: 'Kondisi',    width: 'w-[10%]', headerClass: 'text-center' },
  { key: 'components_count', label: 'Komponen',   width: 'w-[10%]', headerClass: 'text-center', cellClass: 'text-center' },
];

const showImportModal = ref(false);
const importing = ref(false);
const showCalendarModal = ref(false);

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

const navigateToMachine = (machineId) => {
  router.visit(`/machine/${machineId}`);
};

onMounted(() => {
  if (!props.machines && !props.initialMachines) {
    loadData();
  }
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
  const active = machine.schedules
    .filter(s => s.is_active !== false)
    .sort((a, b) => new Date(a.next_due_date) - new Date(b.next_due_date));
  return active.length > 0 ? active[0] : null;
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
  const m = allowedMachines.value;
  const machineSchedules = m.map(machine => getMachineSchedule(machine)).filter(s => s !== null);
  return [
    { label: 'Telat',      value: machineSchedules.filter(s => getDaysUntil(s.next_due_date) < 0).length,                                    color: 'red' },
    { label: 'Hari Ini',   value: machineSchedules.filter(s => getDaysUntil(s.next_due_date) === 0).length,                                   color: 'amber' },
    { label: 'Minggu Ini', value: machineSchedules.filter(s => getDaysUntil(s.next_due_date) > 0 && getDaysUntil(s.next_due_date) <= 7).length, color: 'blue' },
    { label: 'Mendatang',  value: machineSchedules.filter(s => getDaysUntil(s.next_due_date) > 7).length,                                    color: 'slate' },
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

    const machine = allowedMachines.value.find(m => m.id === notif.machine_id);
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

    // Filter out if done recently but not touched today — except always show H-1 (tomorrow) alerts
    if (isDoneRecently && !isFullyChecked && !isPartiallyChecked && daysUntil !== 1) {
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
  let list = allowedMachines.value;
  
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

const goToMachine = (machineId) => { navigateToMachine(machineId); };

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

const importType = ref('machine'); // 'machine' or 'component'

const triggerMachineImport = () => {
  importType.value = 'machine';
  showImportModal.value = true;
};

const triggerComponentImport = () => {
  importType.value = 'component';
  showImportModal.value = true;
};

const handleImportFile = (file) => {
  if (!file) return;
  if (importType.value === 'machine') {
    importMachines(file);
  } else {
    importComponentsGlobal(file);
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
      ['Kode Mesin', 'Kategori', 'Nama Komponen', 'Spesifikasi', 'Jumlah (Qty)', 'Satuan', 'Kondisi Awal (%)']
    ];
    const rows = [
      ['LL-BLR-01', 'Suku Cadang Utama', 'Piston Cylinder Boiler', 'Stainless Steel 316 100mm', 2, 'Pcs', 100],
      ['LL-PKG-01', 'Sensor & Kontrol', 'Thermostat Digital TC-40', 'Range -50C to 200C', 1, 'Unit', 90]
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
      { wch: 20 }  // Kondisi Awal (%)
    ];
    
    XLSX.utils.book_append_sheet(wb, ws, 'Template Import Komponen');
    XLSX.writeFile(wb, 'Format_Import_Komponen_Massal.xlsx');
  } catch (err) {
    console.error('Template download failed:', err);
    showAlert('error', 'Gagal!', 'Gagal mendownload template Excel.');
  }
};

const importMachines = async (file) => {
  if (!file) return;
  importing.value = true;
  try {
    const XLSX = await loadSheetJS();
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

const importComponentsGlobal = async (file) => {
  if (!file) return;
  importing.value = true;
  try {
    const XLSX = await loadSheetJS();
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

const getMachinesForDate = (date) => {
  const machinesForDate = [];
  
  const targetDate = new Date(date);
  targetDate.setHours(0, 0, 0, 0);
  const targetTime = targetDate.getTime();
  
  const today = new Date();
  today.setHours(0, 0, 0, 0);
  const todayTime = today.getTime();
  
  allowedMachines.value.forEach(machine => {
    // 1. Check if there is a completed/submitted maintenance record on this date
    const hasRecord = (machine.records ?? []).some(record => {
      const recDate = new Date(record.maintenance_date);
      recDate.setHours(0, 0, 0, 0);
      return recDate.getTime() === targetTime;
    });
    
    if (hasRecord) {
      machinesForDate.push({
        id: machine.id,
        name: machine.name,
        status: 'completed',
        label: 'Selesai'
      });
      return; // Skip further checks for this machine on this date
    }
    
    // 2. Check schedules and project future dates based on interval_days
    (machine.schedules ?? []).forEach(sched => {
      if (sched.is_active === false) return;
      
      const baseDueDate = new Date(sched.next_due_date);
      baseDueDate.setHours(0, 0, 0, 0);
      
      // Generate a sequence of actual scheduled dates (up to 60 days ahead)
      const projectedTimes = [];
      let currentProjDate = new Date(baseDueDate);
      
      for (let k = 0; k < 20; k++) {
        const projTime = currentProjDate.getTime();
        projectedTimes.push({
          time: projTime,
          isOriginal: k === 0
        });
        
        // Advance by interval days
        const nextDate = new Date(currentProjDate);
        nextDate.setDate(currentProjDate.getDate() + sched.interval_days);
        
        // Rule: If nextDate falls on a Sunday (getDay() === 0), shift to Monday (add 1 day)
        if (nextDate.getDay() === 0) {
          nextDate.setDate(nextDate.getDate() + 1);
        }
        
        currentProjDate = nextDate;
        
        // Stop generating if we exceed 60 days to prevent infinite loops
        const diffDays = Math.round((currentProjDate - baseDueDate) / (1000 * 60 * 60 * 24));
        if (diffDays > 60) break;
      }
      
      // Check if targetTime matches any of the projected times
      const match = projectedTimes.find(p => p.time === targetTime);
      if (match) {
        if (match.isOriginal) {
          const isPast = targetTime < todayTime;
          machinesForDate.push({
            id: machine.id,
            name: machine.name,
            status: isPast ? 'overdue' : 'pending',
            label: isPast ? 'Terlambat' : 'Jadwal'
          });
        } else {
          machinesForDate.push({
            id: machine.id,
            name: machine.name,
            status: 'pending',
            label: 'Proyeksi'
          });
        }
      }
    });
  });
  
  // Sort by name
  return machinesForDate.sort((a, b) => a.name.localeCompare(b.name));
};

</script>
