<template>
  <div class="space-y-6">
    <div>
      <h2 class="text-2xl font-bold text-slate-800">Jadwal Maintenance</h2>
      <p class="text-sm text-slate-500 mt-1">Semua jadwal maintenance mesin, diurutkan berdasarkan prioritas</p>
    </div>

    <!-- Filter Bar -->
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-4 flex flex-wrap gap-3 items-center">
      <div class="flex-1 relative min-w-[200px]">
        <svg class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
        <input v-model="search" type="text" placeholder="Cari nama mesin..." class="w-full pl-9 pr-4 py-2 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm text-slate-700">
      </div>
      <select v-model="filterType" class="rounded-xl border border-slate-200 px-3 py-2 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 cursor-pointer">
        <option value="">Semua Tipe</option>
        <option value="preventive">Preventive</option>
        <option value="predictive">Predictive</option>
        <option value="breakdown">Breakdown</option>
      </select>
      <select v-model="filterUrgency" class="rounded-xl border border-slate-200 px-3 py-2 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 cursor-pointer">
        <option value="">Semua Jadwal</option>
        <option value="overdue">Overdue / Terlewat</option>
        <option value="today">Hari Ini</option>
        <option value="week">7 Hari ke Depan</option>
        <option value="upcoming">Akan Datang</option>
      </select>
    </div>

    <!-- Stats Summary -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
      <div v-for="stat in stats" :key="stat.label" :class="stat.bg" class="rounded-2xl p-4 border">
        <p :class="stat.textColor" class="text-2xl font-bold">{{ stat.value }}</p>
        <p class="text-xs text-slate-500 mt-1 font-medium">{{ stat.label }}</p>
      </div>
    </div>

    <!-- Schedule List -->
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
      <div v-if="loading" class="p-8 text-center text-slate-400">
        <div class="animate-spin w-8 h-8 border-4 border-indigo-200 border-t-indigo-600 rounded-full mx-auto mb-3"></div>
        Memuat data...
      </div>
      <div v-else-if="filteredSchedules.length === 0" class="p-12 text-center text-slate-400">
        <svg class="w-12 h-12 mx-auto mb-3 text-slate-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
        <p class="font-medium text-slate-500">Tidak ada jadwal ditemukan</p>
      </div>
      <table v-else class="w-full min-w-[600px]">
        <thead>
          <tr class="bg-slate-50 border-b border-slate-100">
            <th class="text-left text-xs font-semibold text-slate-500 uppercase tracking-wider px-6 py-4">Mesin</th>
            <th class="text-left text-xs font-semibold text-slate-500 uppercase tracking-wider px-6 py-4">Tipe</th>
            <th class="text-left text-xs font-semibold text-slate-500 uppercase tracking-wider px-6 py-4">Jadwal Berikutnya</th>
            <th class="text-left text-xs font-semibold text-slate-500 uppercase tracking-wider px-6 py-4">Interval</th>
            <th class="text-left text-xs font-semibold text-slate-500 uppercase tracking-wider px-6 py-4">Status</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-50">
          <tr v-for="sched in filteredSchedules" :key="sched.id" class="hover:bg-slate-50/50 transition-colors">
            <td class="px-6 py-4">
              <button @click="$router.push(`/machine/${sched.machine_id}`)" class="font-semibold text-slate-800 hover:text-indigo-600 cursor-pointer text-left">{{ sched.machine?.name }}</button>
              <p class="text-xs text-slate-400 mt-0.5">{{ sched.machine?.location }}</p>
            </td>
            <td class="px-6 py-4">
              <span :class="typeClass(sched.schedule_type)" class="px-2.5 py-1 rounded-full text-xs font-semibold">{{ sched.schedule_type }}</span>
            </td>
            <td class="px-6 py-4 text-sm font-medium text-slate-700">
              {{ formatDate(sched.next_due_date) }}
            </td>
            <td class="px-6 py-4 text-sm text-slate-600">{{ sched.interval_days }} hari</td>
            <td class="px-6 py-4">
              <div class="flex items-center gap-2">
                <span :class="urgencyClass(sched.next_due_date).badge" class="px-2.5 py-1 rounded-full text-xs font-bold">
                  {{ urgencyClass(sched.next_due_date).label }}
                </span>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
      <div v-if="!loading && filteredSchedules.length > 0" class="px-6 py-3 bg-slate-50 border-t border-slate-100 text-xs text-slate-400">
        Menampilkan {{ filteredSchedules.length }} dari {{ schedules.length }} jadwal
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import axios from 'axios';

const schedules = ref([]);
const loading = ref(true);
const search = ref('');
const filterType = ref('');
const filterUrgency = ref('');

const loadData = async () => {
  try {
    const res = await axios.get('/api/schedules');
    // Sort: overdue first, then today, then upcoming (closest first)
    schedules.value = res.data.sort((a, b) => new Date(a.next_due_date) - new Date(b.next_due_date));
  } catch (e) {
    console.error(e);
  } finally {
    loading.value = false;
  }
};

onMounted(() => { loadData(); window.addEventListener('refresh-data', loadData); });
onUnmounted(() => window.removeEventListener('refresh-data', loadData));

const getDaysUntil = (dateStr) => {
  const d = new Date(dateStr);
  d.setHours(0,0,0,0);
  const t = new Date(); t.setHours(0,0,0,0);
  return Math.ceil((d - t) / (1000*60*60*24));
};

const filteredSchedules = computed(() => {
  let list = schedules.value;
  if (search.value) {
    const q = search.value.toLowerCase();
    list = list.filter(s => (s.machine?.name ?? '').toLowerCase().includes(q));
  }
  if (filterType.value) list = list.filter(s => s.schedule_type === filterType.value);
  if (filterUrgency.value === 'overdue') list = list.filter(s => getDaysUntil(s.next_due_date) < 0);
  if (filterUrgency.value === 'today') list = list.filter(s => getDaysUntil(s.next_due_date) === 0);
  if (filterUrgency.value === 'week') list = list.filter(s => getDaysUntil(s.next_due_date) >= 0 && getDaysUntil(s.next_due_date) <= 7);
  if (filterUrgency.value === 'upcoming') list = list.filter(s => getDaysUntil(s.next_due_date) > 7);
  return list;
});

const stats = computed(() => {
  const s = schedules.value;
  return [
    { label: 'Overdue', value: s.filter(x => getDaysUntil(x.next_due_date) < 0).length, bg: 'bg-red-50 border-red-100', textColor: 'text-red-600' },
    { label: 'Hari Ini', value: s.filter(x => getDaysUntil(x.next_due_date) === 0).length, bg: 'bg-amber-50 border-amber-100', textColor: 'text-amber-600' },
    { label: 'Minggu Ini', value: s.filter(x => getDaysUntil(x.next_due_date) > 0 && getDaysUntil(x.next_due_date) <= 7).length, bg: 'bg-blue-50 border-blue-100', textColor: 'text-blue-600' },
    { label: 'Mendatang', value: s.filter(x => getDaysUntil(x.next_due_date) > 7).length, bg: 'bg-slate-50 border-slate-100', textColor: 'text-slate-600' },
  ];
});

const formatDate = (d) => new Date(d).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' });

const typeClass = (t) => ({
  preventive: 'bg-indigo-50 text-indigo-700',
  predictive: 'bg-purple-50 text-purple-700',
  breakdown: 'bg-red-50 text-red-700',
}[t] ?? 'bg-slate-100 text-slate-600');

const urgencyClass = (dateStr) => {
  const days = getDaysUntil(dateStr);
  if (days < 0) return { badge: 'bg-red-100 text-red-700', label: `Telat ${Math.abs(days)} hari` };
  if (days === 0) return { badge: 'bg-amber-100 text-amber-700', label: 'Hari ini' };
  if (days <= 7) return { badge: 'bg-blue-100 text-blue-700', label: `${days} hari lagi` };
  return { badge: 'bg-slate-100 text-slate-600', label: `${days} hari lagi` };
};
</script>
