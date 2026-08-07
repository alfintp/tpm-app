<template>
  <div class="w-full max-w-5xl mx-auto space-y-6">
    <!-- Header -->
    <PageHeader title="Daftar Komponen" subtitle="Lihat keseluruhan komponen mesin dalam sistem">
    </PageHeader>

    <!-- Stats Summary Cards -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
      <div 
        v-for="stat in difficultyStats" 
        :key="stat.id"
        @click="toggleDifficultyFilter(stat.id)"
        class="bg-white rounded-2xl border border-slate-100 shadow-sm p-4 cursor-pointer transition-all hover:shadow-md active:scale-95 relative overflow-hidden group"
        :class="[
          filterDifficulty === stat.id ? 'ring-2 ring-offset-2 ' + stat.ringColor : 'hover:border-slate-200'
        ]"
      >
        <div class="flex justify-between items-start">
          <div>
            <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">{{ stat.label }}</p>
            <p class="text-2xl font-black text-slate-800">{{ stat.count }}</p>
          </div>
          <div :class="stat.bgClass" class="p-2 rounded-xl text-white">
            <component :is="stat.icon" class="w-5 h-5" />
          </div>
        </div>
        <div 
          class="absolute bottom-0 left-0 h-1 transition-all duration-300"
          :class="[stat.bgClass, filterDifficulty === stat.id ? 'w-full' : 'w-0 group-hover:w-full']"
        ></div>
      </div>
    </div>

    <!-- Search & Filter Bar -->
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-4 flex flex-wrap gap-3 items-center">
      <SearchInput
        v-model="search"
        placeholder="Cari nama komponen, spesifikasi, atau mesin..."
        class="flex-1 min-w-50"
      />
      
      <div class="relative group">
        <select v-model="filterDifficulty" class="rounded-xl border border-slate-200 pl-3 pr-8 py-2 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 cursor-pointer appearance-none bg-white">
          <option value="all">Semua Kesulitan</option>
          <option value="ringan">Ringan</option>
          <option value="sedang">Sedang</option>
          <option value="berat">Berat</option>
          <option value="none">Tanpa Kategori</option>
        </select>
        <button
          v-if="filterDifficulty !== 'all'"
          @click="clearDifficultyFilter"
          type="button"
          class="absolute right-2 top-1/2 -translate-y-1/2 p-1 text-slate-400 hover:text-slate-600 hover:bg-slate-100 rounded-full cursor-pointer transition-colors z-10"
          title="Hapus filter kesulitan"
        >
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
        <div class="absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none text-slate-400" v-if="filterDifficulty === 'all'">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
        </div>
      </div>

      <div class="relative group" v-if="isManagerOrAdmin || hasBothCities">
        <select v-model="filterKota" class="rounded-xl border border-slate-200 pl-3 pr-8 py-2 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 cursor-pointer appearance-none bg-white">
          <option value="">Semua Kota</option>
          <option value="pasuruan">Pasuruan</option>
          <option value="sby">Surabaya</option>
        </select>
        <button
          v-if="filterKota"
          @click="clearKotaFilter"
          type="button"
          class="absolute right-2 top-1/2 -translate-y-1/2 p-1 text-slate-400 hover:text-slate-600 hover:bg-slate-100 rounded-full cursor-pointer transition-colors z-10"
          title="Hapus filter kota"
        >
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
        <div class="absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none text-slate-400" v-if="!filterKota">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
        </div>
      </div>

      <div class="relative group">
        <input
          v-model="locationSearch"
          @focus="showLocationDropdown = true"
          @blur="handleLocationBlur"
          @input="handleLocationInput"
          type="text"
          placeholder="Lokasi / Area"
          class="rounded-xl border border-slate-200 pl-3 pr-8 py-2 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 w-40"
        />
        <button
          v-if="locationSearch"
          @click="clearLocationFilter"
          type="button"
          class="absolute right-2 top-1/2 -translate-y-1/2 p-1 text-slate-400 hover:text-slate-600 hover:bg-slate-100 rounded-full cursor-pointer transition-colors"
          title="Hapus filter lokasi"
        >
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
        <div
          v-if="showLocationDropdown && filteredLocations.length > 0"
          class="absolute top-full left-0 right-0 mt-1 bg-white border border-slate-200 rounded-xl shadow-lg z-50 max-h-48 overflow-y-auto"
        >
          <div
            v-for="loc in filteredLocations"
            :key="loc"
            @mousedown="selectLocation(loc)"
            class="px-3 py-2 text-sm text-slate-700 hover:bg-slate-50 cursor-pointer"
          >
            {{ loc }}
          </div>
        </div>
      </div>

      <div class="relative group">
        <input
          v-model="machineSearch"
          @focus="showMachineDropdown = true"
          @blur="handleMachineBlur"
          @input="handleMachineInput"
          type="text"
          placeholder="Pilih Mesin"
          class="rounded-xl border border-slate-200 pl-3 pr-8 py-2 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 w-48"
        />
        <button
          v-if="machineSearch"
          @click="clearMachineFilter"
          type="button"
          class="absolute right-2 top-1/2 -translate-y-1/2 p-1 text-slate-400 hover:text-slate-600 hover:bg-slate-100 rounded-full cursor-pointer transition-colors"
          title="Hapus filter mesin"
        >
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
        <div
          v-if="showMachineDropdown && filteredMachinesBySearch.length > 0"
          class="absolute top-full left-0 right-0 mt-1 bg-white border border-slate-200 rounded-xl shadow-lg z-50 max-h-48 overflow-y-auto"
        >
          <div
            v-for="m in filteredMachinesBySearch"
            :key="m.id"
            @mousedown="selectMachine(m)"
            class="px-3 py-2 text-sm text-slate-700 hover:bg-slate-50 cursor-pointer"
          >
            {{ m.name }}
          </div>
        </div>
      </div>

      <select v-model="sortBy" class="rounded-xl border border-slate-200 px-3 py-2 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 cursor-pointer">
        <option value="name">Nama A-Z</option>
        <option value="name_desc">Nama Z-A</option>
        <option value="machine">Nama Mesin</option>
        <option value="condition_asc">Kondisi Terendah</option>
        <option value="condition_desc">Kondisi Tertinggi</option>
      </select>
    </div>

    <!-- Components Table -->
    <DataTable
      :columns="columns"
      :rows="paginatedComponents"
      :loading="loading"
      loading-text="Memuat data komponen..."
      loading-subtext="Mengambil data dari server"
      empty-title="Tidak ada komponen ditemukan"
      empty-subtext="Coba ubah kata kunci pencarian atau filter"
      min-width="min-w-[800px]"
      table-class="table-fixed"
      :paginate="true"
      :current-page="currentPage"
      :total-rows="filtered.length"
      :per-page="perPage"
      :show-per-page-selector="true"
      :per-page-options="[10, 20, 30, 50, 100]"
      @update:current-page="currentPage = $event"
      @update:per-page="perPage = $event"
    >
      <!-- Kolom: Nama Komponen -->
      <template #cell-name="{ row }">
        <div class="min-w-0">
          <p class="font-bold text-slate-800 wrap-break-word line-clamp-2" :title="row.name">{{ row.name }}</p>
          <p class="text-[11px] text-slate-400 mt-0.5 line-clamp-1 italic">{{ row.specification || 'Tanpa spesifikasi' }}</p>
        </div>
      </template>

      <!-- Kolom: Mesin -->
      <template #cell-machine_name="{ row }">
        <div
          class="cursor-pointer hover:text-indigo-600 transition-colors"
          @click="goToMachine(row.machine_id)"
        >
          <div class="flex items-center gap-1.5">
            <p class="font-semibold text-slate-700 text-sm line-clamp-1">{{ row.machine?.name }}</p>
            <span
              v-if="row.machine?.kota"
              :class="row.machine.kota === 'sby' ? 'bg-blue-50 text-blue-600 border-blue-200' : 'bg-emerald-50 text-emerald-600 border-emerald-200'"
              class="text-[9px] font-bold uppercase px-1.5 py-0.5 rounded border shrink-0"
            >{{ row.machine.kota === 'sby' ? 'SBY' : 'PSN' }}</span>
          </div>
          <p class="text-[10px] text-slate-400 uppercase tracking-wider">{{ row.machine?.location }}</p>
        </div>
      </template>

      <!-- Kolom: Kategori & Kesulitan -->
      <template #cell-category="{ row }">
        <div class="flex flex-col gap-1">
          <span v-if="row.category" class="text-[10px] font-bold text-slate-500 bg-slate-100 px-1.5 py-0.5 rounded w-fit">
            {{ row.category }}
          </span>
          <span v-if="row.difficulty" :class="getDifficultyBadgeClass(row.difficulty)" class="text-[10px] font-bold px-1.5 py-0.5 rounded w-fit uppercase tracking-tight">
            {{ row.difficulty }}
          </span>
          <span v-else class="text-[10px] font-bold text-slate-400 bg-slate-50 px-1.5 py-0.5 rounded w-fit italic">
            Tanpa Kesulitan
          </span>
        </div>
      </template>

      <!-- Kolom: Kondisi -->
      <template #cell-last_condition_pct="{ row }">
        <div class="flex flex-col items-center">
          <span :class="condClass(row.last_condition_pct)" class="font-bold text-base">{{ row.last_condition_pct }}%</span>
          <p class="text-[9px] text-slate-400 mt-0.5 whitespace-nowrap">Terakhir: {{ formatDate(row.last_replaced_at) }}</p>
        </div>
      </template>

      <!-- Kolom: Info Lain -->
      <template #cell-info="{ row }">
        <div class="space-y-0.5">
          <p class="text-xs text-slate-600 font-medium">Qty: <span class="font-bold text-slate-800">{{ row.qty || '-' }} {{ row.unit }}</span></p>
          <p class="text-[10px] text-slate-400">{{ row.indicators_count ?? row.indicators?.length ?? 0 }} Indikator</p>
        </div>
      </template>

      <!-- Actions -->
      <template #actions="{ row }">
        <div class="flex items-center justify-end gap-1">
          <button
            v-if="isManagerOrAdmin"
            @click="editComponent(row)"
            class="p-2 text-slate-400 hover:text-brand-gradation hover:bg-brand-cream rounded-lg cursor-pointer transition-colors"
            title="Edit Komponen"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
          </button>
          <button
            @click="viewHistory(row)"
            class="p-2 text-indigo-500 hover:bg-indigo-50 rounded-lg cursor-pointer transition-colors"
            title="Riwayat"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
          </button>
        </div>
      </template>
    </DataTable>

    <!-- Component Form Modal -->
    <ComponentForm
      v-if="showComponentForm"
      :machine-id="editingComponent.machine_id"
      :component="editingComponent"
      @close="showComponentForm = false"
      @saved="onComponentSaved"
    />

    <!-- Component History Modal -->
    <ComponentHistory
      v-if="showComponentHistory"
      :component="historyComponent"
      @close="showComponentHistory = false"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import axios from 'axios';
import PageHeader from '../components/PageHeader.vue';
import SearchInput from '../components/SearchInput.vue';
import DataTable from '../components/DataTable.vue';
import ComponentForm from '../components/ComponentForm.vue';
import ComponentHistory from '../components/ComponentHistory.vue';
import { useAuth } from '../composables/useAuth.js';
import { 
  CheckCircle2, 
  AlertTriangle, 
  AlertCircle, 
  HelpCircle 
} from 'lucide-vue-next';

const { user, isAdmin, isManagerOrAdmin, hasBothCities } = useAuth();
const components = ref([]);
const loading = ref(true);
const search = ref('');
const filterDifficulty = ref('all');
const filterKota = ref('');
const locationSearch = ref('');
const selectedLocation = ref('');
const showLocationDropdown = ref(false);
const machineSearch = ref('');
const selectedMachineId = ref('');
const showMachineDropdown = ref(false);
const sortBy = ref('name');
const currentPage = ref(1);
const perPage = ref(20);

const showComponentForm = ref(false);
const editingComponent = ref(null);
const showComponentHistory = ref(false);
const historyComponent = ref(null);

const cityFilteredComponents = computed(() => {
  const city = user.value?.city;
  if (city && city !== 'both') {
    return components.value.filter(c => c.machine?.kota === city);
  }
  return components.value;
});

const uniqueLocations = computed(() => {
  const locations = new Set();
  let list = cityFilteredComponents.value;
  if (filterKota.value) {
    list = list.filter(c => c.machine?.kota === filterKota.value);
  }
  list.forEach(c => {
    if (c.machine?.location) locations.add(c.machine.location);
  });
  return Array.from(locations).sort();
});

const filteredLocations = computed(() => {
  if (!locationSearch.value) return uniqueLocations.value;
  const q = locationSearch.value.toLowerCase();
  return uniqueLocations.value.filter(loc => loc.toLowerCase().includes(q));
});

const uniqueMachines = computed(() => {
  const machinesMap = new Map();
  let list = cityFilteredComponents.value;
  if (filterKota.value) {
    list = list.filter(c => c.machine?.kota === filterKota.value);
  }
  if (selectedLocation.value) {
    list = list.filter(c => c.machine?.location === selectedLocation.value);
  }
  list.forEach(c => {
    if (c.machine && !machinesMap.has(c.machine.id)) {
      machinesMap.set(c.machine.id, { id: c.machine.id, name: c.machine.name });
    }
  });
  return Array.from(machinesMap.values()).sort((a, b) => a.name.localeCompare(b.name));
});

const filteredMachinesBySearch = computed(() => {
  if (!machineSearch.value) return uniqueMachines.value;
  const q = machineSearch.value.toLowerCase();
  return uniqueMachines.value.filter(m => m.name.toLowerCase().includes(q));
});

const handleLocationBlur = () => {
  setTimeout(() => { showLocationDropdown.value = false; }, 200);
};

const handleLocationInput = () => {
  showLocationDropdown.value = true;
  if (!locationSearch.value) {
    selectedLocation.value = '';
  }
};

const selectLocation = (loc) => {
  locationSearch.value = loc;
  selectedLocation.value = loc;
  showLocationDropdown.value = false;
};

const clearLocationFilter = () => {
  locationSearch.value = '';
  selectedLocation.value = '';
  showLocationDropdown.value = false;
};

const handleMachineBlur = () => {
  setTimeout(() => { showMachineDropdown.value = false; }, 200);
};

const handleMachineInput = () => {
  showMachineDropdown.value = true;
  if (!machineSearch.value) {
    selectedMachineId.value = '';
  }
};

const selectMachine = (m) => {
  machineSearch.value = m.name;
  selectedMachineId.value = m.id;
  showMachineDropdown.value = false;
};

const clearMachineFilter = () => {
  machineSearch.value = '';
  selectedMachineId.value = '';
  showMachineDropdown.value = false;
};

const clearDifficultyFilter = () => {
  filterDifficulty.value = 'all';
};

const clearKotaFilter = () => {
  filterKota.value = '';
};

const difficultyStats = computed(() => {
  const counts = {
    ringan: 0,
    sedang: 0,
    berat: 0,
    none: 0
  };

  filteredBase.value.forEach(c => {
    if (!c.difficulty) counts.none++;
    else if (counts.hasOwnProperty(c.difficulty)) counts[c.difficulty]++;
  });

  return [
    { 
      id: 'ringan', 
      label: 'Ringan', 
      count: counts.ringan, 
      icon: CheckCircle2, 
      bgClass: 'bg-emerald-500', 
      ringColor: 'ring-emerald-500' 
    },
    { 
      id: 'sedang', 
      label: 'Sedang', 
      count: counts.sedang, 
      icon: AlertTriangle, 
      bgClass: 'bg-amber-500', 
      ringColor: 'ring-amber-500' 
    },
    { 
      id: 'berat', 
      label: 'Berat', 
      count: counts.berat, 
      icon: AlertCircle, 
      bgClass: 'bg-red-500', 
      ringColor: 'ring-red-500' 
    },
    { 
      id: 'none', 
      label: 'Tanpa Kesulitan', 
      count: counts.none, 
      icon: HelpCircle, 
      bgClass: 'bg-slate-400', 
      ringColor: 'ring-slate-400' 
    },
  ];
});

const toggleDifficultyFilter = (id) => {
  if (filterDifficulty.value === id) {
    filterDifficulty.value = 'all';
  } else {
    filterDifficulty.value = id;
  }
};

const columns = [
  { key: 'name',               label: 'Nama Komponen', width: 'w-[25%]' },
  { key: 'machine_name',       label: 'Mesin',         width: 'w-[20%]' },
  { key: 'category',           label: 'Kategori',      width: 'w-[15%]' },
  { key: 'last_condition_pct', label: 'Kondisi',       width: 'w-[12%]', headerClass: 'text-center', cellClass: 'text-center' },
  { key: 'info',               label: 'Info',          width: 'w-[18%]' },
];

const loadData = async () => {
  loading.value = true;
  try {
    const res = await axios.get('/api/components');
    components.value = res.data;
  } catch (e) {
    console.error('Failed to load components:', e);
  } finally {
    loading.value = false;
  }
};

onMounted(loadData);

const filteredBase = computed(() => {
  let list = cityFilteredComponents.value;

  // Search
  if (search.value) {
    const q = search.value.toLowerCase();
    list = list.filter(c => 
      c.name.toLowerCase().includes(q) || 
      (c.specification && c.specification.toLowerCase().includes(q)) ||
      (c.machine?.name && c.machine.name.toLowerCase().includes(q))
    );
  }

  // Kota
  if (filterKota.value) {
    list = list.filter(c => c.machine?.kota === filterKota.value);
  }

  // Location
  if (selectedLocation.value) {
    list = list.filter(c => c.machine?.location === selectedLocation.value);
  }

  // Machine
  if (selectedMachineId.value) {
    list = list.filter(c => c.machine_id === selectedMachineId.value);
  }

  return list;
});

const filtered = computed(() => {
  let list = filteredBase.value;

  // Difficulty
  if (filterDifficulty.value !== 'all') {
    if (filterDifficulty.value === 'none') {
      list = list.filter(c => !c.difficulty);
    } else {
      list = list.filter(c => c.difficulty === filterDifficulty.value);
    }
  }

  // Sort
  if (sortBy.value === 'name')           list = [...list].sort((a, b) => a.name.localeCompare(b.name));
  if (sortBy.value === 'name_desc')      list = [...list].sort((a, b) => b.name.localeCompare(a.name));
  if (sortBy.value === 'machine')        list = [...list].sort((a, b) => (a.machine?.name || '').localeCompare(b.machine?.name || ''));
  if (sortBy.value === 'condition_asc')  list = [...list].sort((a, b) => a.last_condition_pct - b.last_condition_pct);
  if (sortBy.value === 'condition_desc') list = [...list].sort((a, b) => b.last_condition_pct - a.last_condition_pct);

  return list;
});

const paginatedComponents = computed(() => {
  const start = (currentPage.value - 1) * perPage.value;
  return filtered.value.slice(start, start + perPage.value);
});

watch(filterKota, () => {
  clearLocationFilter();
  clearMachineFilter();
});

watch(selectedLocation, () => {
  clearMachineFilter();
});

watch([search, filterDifficulty, filterKota, selectedLocation, selectedMachineId, machineSearch, sortBy, perPage], () => { currentPage.value = 1; });

const condClass = (pct) => pct < 50 ? 'text-red-500' : pct < 80 ? 'text-amber-500' : 'text-emerald-500';

const getDifficultyBadgeClass = (difficulty) => {
  switch (difficulty) {
    case 'ringan': return 'bg-emerald-100 text-emerald-700';
    case 'sedang': return 'bg-amber-100 text-amber-700';
    case 'berat':  return 'bg-red-100 text-red-700';
    default:       return 'bg-slate-100 text-slate-600';
  }
};

const formatDate = (d) => d ? new Date(d).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }) : '-';

const goToMachine = (id) => {
  if (id) router.visit(`/machine/${id}`);
};

const editComponent = (row) => {
  editingComponent.value = row;
  showComponentForm.value = true;
};

const onComponentSaved = async () => {
  showComponentForm.value = false;
  await loadData();
};

const viewHistory = (row) => {
  if (row) {
    historyComponent.value = row;
    showComponentHistory.value = true;
  }
};
</script>
