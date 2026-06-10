<template>
  <div class="space-y-6">
    <!-- Header + Create Button -->
    <div class="flex justify-between items-start flex-wrap gap-4">
      <div>
        <h2 class="text-2xl font-bold text-slate-800">Daftar Mesin</h2>
        <p class="text-sm text-slate-500 mt-1">Kelola semua mesin dalam sistem</p>
      </div>
      <button @click="openCreate" class="flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white px-5 py-2.5 rounded-xl font-medium transition-all shadow-md cursor-pointer">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        Tambah Mesin
      </button>
    </div>

    <!-- Filter & Sort Bar -->
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-4 flex flex-wrap gap-3 items-center">
      <div class="flex-1 relative min-w-[200px]">
        <svg class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
        <input v-model="search" type="text" placeholder="Cari nama mesin atau lokasi..." class="w-full pl-9 pr-4 py-2 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm text-slate-700">
      </div>
      <select v-model="sortBy" class="rounded-xl border border-slate-200 px-3 py-2 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 cursor-pointer">
        <option value="name">Nama A-Z</option>
        <option value="name_desc">Nama Z-A</option>
        <option value="condition_asc">Kondisi Terendah</option>
        <option value="condition_desc">Kondisi Tertinggi</option>
      </select>
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
      <table v-else class="w-full min-w-[600px]">
        <thead>
          <tr class="bg-slate-50 border-b border-slate-100">
            <th class="text-left text-xs font-semibold text-slate-500 uppercase tracking-wider px-6 py-4">Nama Mesin</th>
            <th class="text-left text-xs font-semibold text-slate-500 uppercase tracking-wider px-6 py-4">Lokasi</th>
            <!-- <th class="text-left text-xs font-semibold text-slate-500 uppercase tracking-wider px-6 py-4">Status</th> -->
            <th class="text-center text-xs font-semibold text-slate-500 uppercase tracking-wider px-6 py-4">Kondisi</th>
            <th class="text-center text-xs font-semibold text-slate-500 uppercase tracking-wider px-6 py-4">Komponen</th>
            <th class="text-right text-xs font-semibold text-slate-500 uppercase tracking-wider px-6 py-4">Aksi</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-50">
          <tr v-for="machine in filtered" :key="machine.id" class="hover:bg-slate-50/50 transition-colors group">
            <td class="px-6 py-4">
              <button @click="$router.push(`/machine/${machine.id}`)" class="font-semibold text-slate-800 hover:text-indigo-600 transition-colors cursor-pointer text-left">{{ machine.name }}</button>
              <p class="text-xs text-slate-400 mt-0.5 line-clamp-1">{{ machine.description }}</p>
            </td>
            <td class="px-6 py-4 text-sm text-slate-600">{{ machine.location ?? '-' }}</td>
            <!-- <td class="px-6 py-4">
              <span :class="statusClass(machine.status)" class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold">
                <span class="w-1.5 h-1.5 rounded-full mr-1.5" :class="statusDotClass(machine.status)"></span>
                {{ machine.status }}
              </span>
            </td> -->
            <td class="px-6 py-4 text-center">
              <span :class="condClass(machine.condition_pct)" class="font-bold text-base">{{ machine.condition_pct }}%</span>
            </td>
            <td class="px-6 py-4 text-center text-sm text-slate-600">{{ machine.components?.length ?? 0 }}</td>
            <td class="px-6 py-4 text-right">
              <div class="flex items-center justify-end gap-2 opacity-100 ">
                <button @click="$router.push(`/machine/${machine.id}`)" class="p-2 text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 rounded-lg cursor-pointer transition-colors" title="Detail / Edit">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                </button>
                <button @click="$router.push(`/machine/${machine.id}`)" class="p-2 text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 rounded-lg cursor-pointer transition-colors" title="Buat Laporan">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                </button>
                <!-- REVISI hanya admin yg bisa hapus -->
                <button @click="deleteMachine(machine)" class="p-2 text-slate-400 hover:text-red-500 hover:bg-red-50 rounded-lg cursor-pointer transition-colors" title="Hapus">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                </button>
              </div>
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
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import axios from 'axios';
import MachineCreateModal from '../components/MachineCreateModal.vue';
import { showConfirm, showAlert } from '../composables/useAlert.js';

const machines = ref([]);
const loading = ref(true);
const search = ref('');
const filterStatus = ref('');
const sortBy = ref('name');
const showCreate = ref(false);

const loadData = async () => {
  try {
    const res = await axios.get('/api/machines');
    machines.value = res.data;
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

const filtered = computed(() => {
  let list = machines.value;
  if (search.value) {
    const q = search.value.toLowerCase();
    list = list.filter(m => m.name.toLowerCase().includes(q) || (m.location ?? '').toLowerCase().includes(q));
  }
  if (filterStatus.value) list = list.filter(m => m.status === filterStatus.value);
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

const deleteMachine = async (machine) => {
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
</script>
