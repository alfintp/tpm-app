<template>
  <div class="space-y-6">
    <!-- Header -->
    <PageHeader
    title="Log Aktifitas Sistem"
      subtitle="Memantau seluruh jejak riwayat aktivitas dan tindakan pengguna di dalam sistem"
      :badge="!loading ? `${filteredLogs.length} Log Ditemukan` : ''"
    />

    <!-- Search & Filter Controls -->
    <div class="flex flex-col md:flex-row gap-4 items-center justify-between">
      <SearchInput
        v-model="search"
        placeholder="Cari aktivitas, nama pengguna, detail, atau IP..."
      />
      <FilterTabs v-model="activeTypeFilter" :tabs="logTypes" />
    </div>

    <!-- Logs Table -->
    <DataTable
      :columns="columns"
      :rows="paginatedLogs"
      :loading="loading"
      loading-text="Memuat log aktivitas..."
      loading-subtext="Mengambil data aman dari server"
      empty-title="Tidak ada log ditemukan"
      empty-subtext="Coba sesuaikan kata kunci pencarian atau ganti filter kategori."
      min-width="min-w-[950px]"
      :paginate="false"
    >
      <!-- Kolom: Waktu -->
      <template #cell-created_at="{ row }">
        <p class="font-semibold text-slate-800 text-sm">{{ formatDateTime(row.created_at).date }}</p>
        <p class="text-xs text-slate-400 font-medium mt-0.5">{{ formatDateTime(row.created_at).time }} WIB</p>
      </template>

      <!-- Kolom: Pengguna -->
      <template #cell-user_fullname="{ row }">
        <div class="flex items-center">
          <div class="h-8 w-8 rounded-full bg-gradient-to-tr from-brand-brown to-brand-gradation text-brand-cream flex items-center justify-center font-bold text-xs shadow-inner uppercase flex-shrink-0">
            {{ getInitials(row.user_fullname) }}
          </div>
          <div class="ml-3">
            <p class="text-sm font-bold text-slate-800">{{ row.user_fullname }}</p>
            <p class="text-[10px] text-slate-400 font-semibold uppercase mt-0.5">Role: {{ row.user?.role ?? 'Guest/System' }}</p>
          </div>
        </div>
      </template>

      <!-- Kolom: Aktivitas -->
      <template #cell-activity="{ row }">
        <span :class="getActivityTheme(row.activity)" class="inline-flex items-center px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-wider whitespace-nowrap">
          {{ row.activity }}
        </span>
      </template>

      <!-- Kolom: Detail Tindakan -->
      <template #cell-details="{ row }">
        <p class="font-medium text-slate-700 leading-relaxed break-words line-clamp-3 text-sm" :title="row.details ?? '-'">{{ row.details ?? '-' }}</p>
      </template>

      <!-- Kolom: IP Address -->
      <template #cell-ip_address="{ row }">
        <span class="text-xs font-mono text-slate-400">{{ row.ip_address ?? '127.0.0.1' }}</span>
      </template>
    </DataTable>

    <!-- Pagination -->
    <TablePagination
      v-if="!loading"
      v-model="currentPage"
      :total="filteredLogs.length"
      :per-page="perPage"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import axios from 'axios';
import { useAuth } from '../composables/useAuth.js';
import PageHeader from '../components/PageHeader.vue';
import SearchInput from '../components/SearchInput.vue';
import FilterTabs from '../components/FilterTabs.vue';
import DataTable from '../components/DataTable.vue';
import TablePagination from '../components/TablePagination.vue';

const props = defineProps({
  initialLogs: {
    type: Array,
    default: null
  }
});

const { authReady } = useAuth();
const loading = ref(props.initialLogs === null);
const logs = ref(props.initialLogs || []);
const search = ref('');
const activeTypeFilter = ref('all');
const currentPage = ref(1);
const perPage = 10;

const columns = [
  { key: 'created_at',   label: 'Waktu',           width: 'w-[15%]', cellClass: 'whitespace-nowrap' },
  { key: 'user_fullname', label: 'Pengguna',         width: 'w-[18%]' },
  { key: 'activity',    label: 'Aktivitas',         width: 'w-[20%]', cellClass: 'whitespace-nowrap' },
  { key: 'details',     label: 'Detail Tindakan',   width: 'w-[27%]', cellClass: 'max-w-0 whitespace-normal' },
  { key: 'ip_address',  label: 'IP Address',        width: 'w-[15%]', cellClass: 'whitespace-nowrap' },
];

const logTypes = [
  { value: 'all', label: 'Semua Kategori' },
  { value: 'user', label: 'User & Auth' },
  { value: 'machine', label: 'Mesin' },
  { value: 'component', label: 'Komponen' },
  { value: 'report', label: 'Laporan & Approval' }
];

const loadLogs = async () => {
  if (props.initialLogs !== null) return;
  loading.value = true;
  try {
    const res = await axios.get('/api/admin/logs');
    logs.value = res.data;
  } catch (e) {
    console.error('Gagal mengambil logs:', e);
  } finally {
    loading.value = false;
  }
};

onMounted(() => {
  if (authReady.value) loadLogs();
});

watch(authReady, (ready) => {
  if (ready && logs.value.length === 0 && !loading.value) loadLogs();
});

const getInitials = (fullName) => {
  if (!fullName) return 'SYS';
  const parts = fullName.split(' ').filter(n => n.length > 0);
  if (parts.length >= 2) {
    return (parts[0][0] + parts[1][0]).toUpperCase();
  }
  return fullName.slice(0, 2).toUpperCase();
};

const formatDateTime = (dateStr) => {
  if (!dateStr) return { date: '-', time: '-' };
  const d = new Date(dateStr);
  
  // Format Date (e.g., "11 Jun 2026")
  const dateOptions = { day: 'numeric', month: 'short', year: 'numeric' };
  const formattedDate = d.toLocaleDateString('id-ID', dateOptions);

  // Format Time (e.g., "15:04")
  const timeOptions = { hour: '2-digit', minute: '2-digit', hour12: false };
  const formattedTime = d.toLocaleTimeString('id-ID', timeOptions);

  return { date: formattedDate, time: formattedTime };
};

const getActivityTheme = (activity) => {
  const text = activity.toLowerCase();
  
  if (text.includes('pendaftaran') || text.includes('tambah')) {
    return 'bg-green-50 text-green-700 border border-green-200/50';
  }
  if (text.includes('hapus') || text.includes('delete') || text.includes('rejected')) {
    return 'bg-red-50 text-red-700 border border-red-200/50';
  }
  if (text.includes('edit') || text.includes('ubah') || text.includes('update')) {
    return 'bg-blue-50 text-blue-700 border border-blue-200/50';
  }
  if (text.includes('login') || text.includes('logout')) {
    return 'bg-slate-50 text-slate-700 border border-slate-200/50';
  }
  if (text.includes('kirim') || text.includes('decision') || text.includes('approved')) {
    return 'bg-indigo-50 text-indigo-700 border border-indigo-200/50';
  }
  
  return 'bg-slate-50 text-slate-600 border border-slate-100';
};

watch([search, activeTypeFilter], () => { currentPage.value = 1; });

const filteredLogs = computed(() => {
  let list = logs.value;

  // Apply search query
  if (search.value) {
    const q = search.value.toLowerCase();
    list = list.filter(l => 
      l.activity.toLowerCase().includes(q) ||
      (l.details && l.details.toLowerCase().includes(q)) ||
      (l.user_fullname && l.user_fullname.toLowerCase().includes(q)) ||
      (l.ip_address && l.ip_address.toLowerCase().includes(q)) ||
      (l.user?.email && l.user.email.toLowerCase().includes(q))
    );
  }

  // Apply category filter
  if (activeTypeFilter.value !== 'all') {
    const filter = activeTypeFilter.value;
    list = list.filter(l => {
      const act = l.activity.toLowerCase();
      if (filter === 'user') {
        return act.includes('user') || act.includes('role') || act.includes('pendaftaran') || act.includes('login') || act.includes('logout');
      }
      if (filter === 'machine') {
        return act.includes('mesin') || act.includes('machine');
      }
      if (filter === 'component') {
        return act.includes('komponen') || act.includes('component');
      }
      if (filter === 'report') {
        return act.includes('laporan') || act.includes('kirim') || act.includes('decision') || act.includes('approved') || act.includes('rejected');
      }
      return true;
    });
  }

  return list;
});

const paginatedLogs = computed(() => {
  const start = (currentPage.value - 1) * perPage;
  return filteredLogs.value.slice(start, start + perPage);
});
</script>
