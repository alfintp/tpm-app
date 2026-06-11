<template>
  <div class="space-y-6">
    <!-- Header with count -->
    <div class="flex items-center justify-between flex-wrap gap-4">
      <div>
        <p class="text-slate-500 text-sm mt-0.5">Memantau seluruh jejak riwayat aktivitas dan tindakan pengguna di dalam sistem</p>
      </div>

      <!-- Quick filters / stats -->
      <div v-if="!loading" class="flex items-center gap-4">
        <div class="bg-white border border-slate-100 rounded-2xl px-5 py-3 shadow-sm flex items-center gap-3">
          <div class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center font-bold text-lg">
            {{ filteredLogs.length }}
          </div>
          <div>
            <p class="text-xs text-slate-400 font-medium">Log Ditemukan</p>
            <p class="text-sm font-bold text-slate-800">Total Riwayat</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Search & Filter Controls -->
    <div class="flex flex-col md:flex-row gap-4 items-center justify-between">
      <div class="relative w-full md:max-w-md">
        <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none">
          <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
        </span>
        <input
          v-model="search"
          type="text"
          placeholder="Cari aktivitas, nama pengguna, detail, atau IP..."
          class="w-full rounded-2xl border border-slate-200 bg-white pl-10 pr-4 py-2.5 text-sm text-slate-700 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 shadow-sm transition-all"
        />
      </div>

      <!-- Category Filter -->
      <div class="flex gap-2 w-full md:w-auto overflow-x-auto pb-1 md:pb-0">
        <button
          v-for="type in logTypes"
          :key="type.value"
          @click="activeTypeFilter = type.value"
          :class="activeTypeFilter === type.value
            ? 'bg-brand-brown text-brand-cream border-brand-brown'
            : 'bg-white text-slate-600 border-slate-200 hover:border-brand-brown'"
          class="px-4 py-1.5 rounded-xl text-xs font-bold border transition-all cursor-pointer whitespace-nowrap"
        >
          {{ type.label }}
        </button>
      </div>
    </div>

    <!-- Logs Table Card -->
    <div class="bg-white rounded-3xl border border-slate-100 shadow-sm overflow-hidden">
      <!-- Loading State -->
      <div v-if="loading" class="p-16 text-center text-slate-400">
        <div class="animate-spin w-10 h-10 border-4 border-indigo-200 border-t-indigo-600 rounded-full mx-auto mb-4"></div>
        <p class="font-semibold text-slate-600">Memuat log aktivitas...</p>
        <p class="text-xs text-slate-400 mt-1">Mengambil data aman dari server</p>
      </div>

      <!-- Empty State -->
      <div v-else-if="filteredLogs.length === 0" class="p-16 text-center text-slate-400">
        <svg class="w-16 h-16 mx-auto mb-4 text-slate-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
        <p class="font-bold text-slate-600 text-lg">Tidak ada log ditemukan</p>
        <p class="text-sm text-slate-400 mt-1">Coba sesuaikan kata kunci pencarian atau ganti filter kategori.</p>
      </div>

      <!-- Logs Table -->
      <div v-else class="overflow-x-auto">
        <table class="w-full min-w-[950px] border-collapse">
          <thead class="bg-slate-50/70 border-b border-slate-100">
            <tr>
              <th class="text-left text-xs font-black text-slate-500 uppercase tracking-wider px-6 py-4 w-[18%]">Waktu</th>
              <th class="text-left text-xs font-black text-slate-500 uppercase tracking-wider px-6 py-4 w-[25%]">Pengguna</th>
              <th class="text-left text-xs font-black text-slate-500 uppercase tracking-wider px-6 py-4 w-[18%]">Aktivitas</th>
              <th class="text-left text-xs font-black text-slate-500 uppercase tracking-wider px-6 py-4 w-[27%]">Detail Tindakan</th>
              <th class="text-left text-xs font-black text-slate-500 uppercase tracking-wider px-6 py-4 w-[12%]">IP Address</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-50">
            <tr v-for="log in filteredLogs" :key="log.id" class="hover:bg-slate-50/30 transition-colors">
              <!-- Waktu (Timestamp) -->
              <td class="px-6 py-4 text-sm text-slate-600 whitespace-nowrap">
                <p class="font-semibold text-slate-800">{{ formatDateTime(log.created_at).date }}</p>
                <p class="text-xs text-slate-400 font-medium mt-0.5">{{ formatDateTime(log.created_at).time }} WIB</p>
              </td>

              <!-- Pengguna (User Info) -->
              <td class="px-6 py-4">
                <div class="flex items-center">
                  <div class="h-8 w-8 rounded-full bg-gradient-to-tr from-brand-brown to-brand-gradation text-brand-cream flex items-center justify-center font-bold text-xs shadow-inner uppercase flex-shrink-0">
                    {{ getInitials(log.user_fullname) }}
                  </div>
                  <div class="ml-3">
                    <p class="text-sm font-bold text-slate-800">{{ log.user_fullname }}</p>
                    <p class="text-[10px] text-slate-400 font-semibold uppercase mt-0.5">Role: {{ log.user?.role ?? 'Guest/System' }}</p>
                  </div>
                </div>
              </td>

              <!-- Aktivitas (Badge) -->
              <td class="px-6 py-4 whitespace-nowrap">
                <span :class="getActivityTheme(log.activity)" class="inline-flex items-center px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-wider">
                  {{ log.activity }}
                </span>
              </td>

              <!-- Detail Tindakan -->
              <td class="px-6 py-4 text-sm text-slate-600">
                <p class="font-medium text-slate-700 leading-relaxed break-words max-w-sm">{{ log.details ?? '-' }}</p>
              </td>

              <!-- IP Address -->
              <td class="px-6 py-4 text-xs font-mono text-slate-400 whitespace-nowrap">
                {{ log.ip_address ?? '127.0.0.1' }}
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';

const loading = ref(true);
const logs = ref([]);
const search = ref('');
const activeTypeFilter = ref('all');

const logTypes = [
  { value: 'all', label: 'Semua Kategori' },
  { value: 'user', label: 'User & Auth' },
  { value: 'machine', label: 'Mesin' },
  { value: 'component', label: 'Komponen' },
  { value: 'report', label: 'Laporan & Approval' }
];

const loadLogs = async () => {
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

onMounted(loadLogs);

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
</script>
