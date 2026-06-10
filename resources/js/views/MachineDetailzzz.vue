<template>
  <div v-if="loading" class="animate-pulse">
    <div class="h-8 bg-slate-200 rounded w-1/4 mb-4"></div>
    <div class="h-4 bg-slate-200 rounded w-1/2 mb-8"></div>
    <div class="h-64 bg-slate-200 rounded-xl mb-8"></div>
  </div>
  
  <div v-else-if="machine" class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between flex-wrap gap-4">
      <div class="flex items-center space-x-4">
        <button @click="$router.push('/')" class="p-2 rounded-full hover:bg-slate-200 text-slate-500 transition-colors cursor-pointer">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
        </button>
        <div>
          <h2 class="text-2xl font-bold text-slate-800">{{ machine.name }}</h2>
          <p class="text-slate-500 text-sm mt-0.5">{{ machine.description }} &bull; <span class="font-medium">{{ machine.location }}</span></p>
        </div>
      </div>
      <div class="flex items-center gap-3">
        <!-- STATUS MACHINE -->
        <!-- <span :class="machine.status === 'active' ? 'bg-indigo-100 text-indigo-800' : 'bg-slate-200 text-slate-700'" class="px-3 py-1 rounded-full text-sm font-medium uppercase">{{ machine.status }}</span> -->
        <button @click="$router.push(`/machine/${route.params.id}`)" class="px-4 py-2 bg-indigo-600 text-white rounded-xl font-medium hover:bg-indigo-700 transition-colors shadow-sm cursor-pointer flex items-center gap-2">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
          Maintenance Report
        </button>
      </div>
    </div>

    <!-- Machine Condition Card -->
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6">
      <div class="flex flex-wrap items-center gap-6">
        <div class="flex items-center gap-4">
          <div class="relative">
            <svg class="w-24 h-24" viewBox="0 0 100 100" style="transform: rotate(-90deg)">
              <circle class="text-slate-100 stroke-current" stroke-width="10" cx="50" cy="50" r="42" fill="transparent"/>
              <circle :class="getColorTheme(machine.condition_pct).textClass" class="stroke-current transition-all duration-1000 ease-out" stroke-width="10" stroke-linecap="round" cx="50" cy="50" r="42" fill="transparent"
                :stroke-dasharray="264" :stroke-dashoffset="264 - (machine.condition_pct / 100) * 264"/>
            </svg>
            <div class="absolute inset-0 flex flex-col items-center justify-center">
              <span :class="getColorTheme(machine.condition_pct).textClass" class="text-xl font-bold">{{ machine.condition_pct }}%</span>
            </div>
          </div>
          <div>
            <p class="text-sm text-slate-500 font-medium">Kondisi Mesin (Avg Komponen)</p>
            <h3 :class="getColorTheme(machine.condition_pct).textClass" class="text-2xl font-bold">{{ getConditionLabel(machine.condition_pct) }}</h3>
            <p class="text-xs text-slate-400 mt-1">Dihitung dari {{ machine.components?.length ?? 0 }} komponen</p>
          </div>
        </div>
      </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <!-- Components Panel -->
      <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6">
        <div class="flex justify-between items-center mb-5">
          <h3 class="text-lg font-semibold text-slate-800 flex items-center gap-2">
            <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            Komponen Mesin
          </h3>
          <button @click="openAddComponent" class="flex items-center gap-1.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 text-sm font-medium px-3 py-1.5 rounded-lg transition-colors cursor-pointer">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Tambah
          </button>
        </div>

        <div v-if="machine.components?.length === 0" class="text-center py-8 text-slate-400 text-sm">
          Belum ada komponen. Klik "Tambah" untuk menambahkan.
        </div>

        <div v-else class="space-y-3">
          <div v-for="comp in machine.components" :key="comp.id"
            class="border border-slate-100 rounded-xl p-4 hover:shadow-md hover:border-indigo-100 transition-all group">
            <div class="flex justify-between items-start">
              <div class="flex-1 cursor-pointer" @click="openComponentHistory(comp)">
                <div class="flex gap-2 mb-1.5">
                  <span class="text-xs font-semibold text-indigo-600 bg-indigo-50 px-2 py-0.5 rounded-full">{{ comp.category }}</span>
                  <span v-if="comp.maintenance_schedule" class="text-xs font-semibold text-slate-500 bg-slate-100 px-2 py-0.5 rounded-full">{{ comp.maintenance_schedule }}</span>
                </div>
                <h4 class="font-bold text-slate-800 hover:text-indigo-600 transition-colors">{{ comp.name }}</h4>
                <p class="text-xs text-slate-500 mt-0.5 line-clamp-1">{{ comp.specification }}</p>
              </div>

              <!-- Condition circle -->
              <div class="flex items-center gap-3 ml-3">
                <div class="relative flex-shrink-0">
                  <svg class="w-14 h-14" viewBox="0 0 50 50" style="transform: rotate(-90deg)">
                    <circle class="text-slate-100 stroke-current" stroke-width="5" cx="25" cy="25" r="20" fill="transparent"/>
                    <circle :class="getColorTheme(comp.last_condition_pct).textClass" class="stroke-current" stroke-width="5" stroke-linecap="round" cx="25" cy="25" r="20" fill="transparent"
                      :stroke-dasharray="125.7" :stroke-dashoffset="125.7 - (comp.last_condition_pct / 100) * 125.7"/>
                  </svg>
                  <div class="absolute inset-0 flex items-center justify-center">
                    <span :class="getColorTheme(comp.last_condition_pct).textClass" class="text-[10px] font-bold">{{ comp.last_condition_pct }}%</span>
                  </div>
                </div>

                <!-- Edit/Delete actions -->
                <div class="flex flex-col gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                  <button @click.stop="openEditComponent(comp)" class="p-1.5 text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 rounded-lg cursor-pointer transition-colors" title="Edit">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                  </button>
                  <button @click.stop="deleteComponent(comp)" class="p-1.5 text-slate-400 hover:text-red-500 hover:bg-red-50 rounded-lg cursor-pointer transition-colors" title="Hapus">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                  </button>
                </div>
              </div>
            </div>

            <div class="flex justify-between text-xs text-slate-400 border-t border-slate-100 pt-2 mt-3">
              <span>Qty: {{ comp.qty }} {{ comp.unit }}</span>
              <span>Penggantian: {{ formatDate(comp.last_replaced_at) }}</span>
            </div>
            <p class="text-xs text-indigo-500 mt-2 cursor-pointer hover:underline" @click="openComponentHistory(comp)">Lihat riwayat →</p>
          </div>
        </div>
      </div>

      <!-- Maintenance History Panel -->
      <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6">
        <h3 class="text-lg font-semibold text-slate-800 mb-5 flex items-center gap-2">
          <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
          Riwayat Maintenance
        </h3>

        <div v-if="sortedRecords.length === 0" class="text-center py-12 text-slate-400 text-sm">
          Belum ada riwayat pengerjaan.
        </div>

        <div v-else class="relative border-l-2 border-indigo-100 ml-3 space-y-6 pb-4">
          <div v-for="record in sortedRecords" :key="record.id" class="relative pl-6">
            <div class="absolute w-4 h-4 rounded-full bg-indigo-500 border-4 border-white left-[-9px] top-1.5 shadow-sm"></div>
            <div class="bg-slate-50 rounded-xl p-4 border border-slate-100 hover:shadow-md transition-shadow">
              <div class="flex justify-between items-start mb-2">
                <div>
                  <span class="text-sm font-bold text-slate-800">{{ formatDateTime(record.maintenance_date) }}</span>
                  <p class="text-xs text-slate-400 mt-0.5">Teknisi: {{ record.technician?.full_name ?? '-' }}</p>
                </div>
                <span class="text-xs font-semibold px-2 py-1 rounded-lg" :class="{
                  'bg-green-100 text-green-700': record.status === 'completed',
                  'bg-amber-100 text-amber-700': record.status === 'in_progress',
                  'bg-slate-200 text-slate-700': record.status === 'planned'
                }">{{ record.status.toUpperCase() }}</span>
              </div>

              <p class="text-sm text-slate-600 italic mb-3">{{ record.notes || 'Tidak ada catatan.' }}</p>

              <!-- Actions list -->
              <div v-if="record.actions?.length > 0" class="space-y-2">
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Tindakan:</p>
                <div v-for="action in record.actions" :key="action.id" class="flex items-start gap-2 bg-white border border-slate-100 p-2 rounded-lg">
                  <span :class="getActionTypeClass(action.action_type)" class="text-xs font-semibold px-2 py-0.5 rounded-full flex-shrink-0">{{ action.action_type }}</span>
                  <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-slate-800 truncate">{{ action.component?.name ?? '-' }}</p>
                    <p v-if="action.description" class="text-xs text-slate-500 mt-0.5">{{ action.description }}</p>
                  </div>
                  <div class="text-xs text-slate-400 flex-shrink-0 text-right">
                    <div>{{ action.condition_before_pct ?? '-' }}% → <span class="text-indigo-600 font-medium">{{ action.condition_after_pct ?? '-' }}%</span></div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Modals -->
  <ComponentForm
    v-if="showComponentForm"
    :machineId="route.params.id"
    :component="editingComponent"
    @close="showComponentForm = false"
    @saved="onComponentSaved"
  />
  <ComponentHistory
    v-if="showComponentHistory"
    :component="historyComponent"
    @close="showComponentHistory = false"
  />
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { useRoute } from 'vue-router';
import axios from 'axios';
import ComponentForm from '../components/ComponentForm.vue';
import ComponentHistory from '../components/ComponentHistory.vue';
import { showConfirm, showAlert } from '../composables/useAlert.js';


const route = useRoute();
const machine = ref(null);
const loading = ref(true);

// Modal states
const showComponentForm = ref(false);
const editingComponent = ref(null);
const showComponentHistory = ref(false);
const historyComponent = ref(null);

const loadData = async () => {
  try {
    const res = await axios.get(`/api/machines/${route.params.id}`);
    machine.value = res.data;
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
onUnmounted(() => {
  window.removeEventListener('refresh-data', loadData);
});





const openAddComponent = () => {
  editingComponent.value = null;
  showComponentForm.value = true;
};

const openEditComponent = (comp) => {
  editingComponent.value = comp;
  showComponentForm.value = true;
};

const openComponentHistory = (comp) => {
  historyComponent.value = comp;
  showComponentHistory.value = true;
};

// const onComponentSaved = () => {
//   showComponentForm.value = false;
//   loadData();
// };
const onComponentSaved = async () => {
  showComponentForm.value = false;
  await loadData();
  showAlert('success', 'Berhasil!', 'Komponen baru berhasil ditambahkan.');
};

// const deleteComponent = async (comp) => {
//   const ok = await showConfirm('Hapus Komponen Mesin', `Apakah Anda yakin ingin menghapus "${comp.name}"? Semua data terkait (komponen, jadwal, riwayat) akan ikut terhapus.`);
//   if (!ok) return;
//   // if (!confirm(`Hapus komponen "${comp.name}"? Tindakan ini tidak dapat dibatalkan.`)) return;
//   try {
//     await axios.delete(`/api/components/${comp.id}`);
//     await loadData();
//   } catch (e) {
//     alert('Gagal menghapus komponen: ' + (e.response?.data?.message || e.message));
//   }
// };
const deleteComponent = async (comp) => {
    const ok = await showConfirm('Hapus Komponen Mesin', `Apakah Anda yakin ingin menghapus "${comp.name}"? Semua data terkait (komponen, jadwal, riwayat) akan ikut terhapus.`);
  if (!ok) return;
  // if (!confirm(`Hapus komponen "${comp.name}"? Tindakan ini tidak dapat dibatalkan.`)) return;
  try {
    await axios.delete(`/api/components/${comp.id}`);
    await loadData();
    showAlert('success', 'Dihapus!', `Komponen "${comp.name}" berhasil dihapus.`);
  } catch (e) {
    showAlert('error', 'Gagal!', 'Gagal menghapus komponen: ' + (e.response?.data?.message || e.message));
  }
}

const sortedRecords = computed(() => {
  if (!machine.value?.records) return [];
  return [...machine.value.records].sort((a, b) => new Date(b.maintenance_date) - new Date(a.maintenance_date));
});

const formatDateTime = (d) => {
  if (!d) return '-';
  return new Date(d).toLocaleString('id-ID', { day: 'numeric', month: 'long', year: 'numeric', hour: '2-digit', minute: '2-digit' });
};

const formatDate = (d) => {
  if (!d) return '-';
  return new Date(d).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
};

const getColorTheme = (pct) => {
  if (!pct && pct !== 0) return { textClass: 'text-slate-400' };
  if (pct < 50) return { textClass: 'text-red-500' };
  if (pct < 80) return { textClass: 'text-amber-500' };
  return { textClass: 'text-green-500' };
};

const getConditionLabel = (pct) => {
  if (pct < 50) return 'Perlu Perhatian';
  if (pct < 80) return 'Kondisi Sedang';
  return 'Kondisi Baik';
};

const getActionTypeClass = (type) => {
  const map = {
    replace: 'bg-red-100 text-red-700',
    repair: 'bg-orange-100 text-orange-700',
    inspect: 'bg-blue-100 text-blue-700',
    clean: 'bg-teal-100 text-teal-700',
    lubricate: 'bg-purple-100 text-purple-700',
  };
  return map[type] || 'bg-slate-100 text-slate-700';
};
</script>
