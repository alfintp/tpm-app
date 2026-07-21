<template>
  <div class="p-6">
    <!-- Filter Bar -->
    <div class="flex flex-wrap items-center gap-3 mb-4 pb-4 border-b border-slate-100">
      <SearchInput v-model="search" placeholder="Cari nama komponen..." class="flex-1 min-w-[200px]" />
      <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Filter:</span>
      <button
        v-for="opt in categoryOptions"
        :key="opt.value"
        @click="categoryFilter = opt.value"
        :class="categoryFilter === opt.value
          ? 'bg-brand-brown text-white border-brand-brown'
          : 'bg-white text-slate-600 border-slate-200 hover:border-brand-brown/50'"
        class="px-3 py-1.5 rounded-lg text-xs font-semibold border transition-colors cursor-pointer"
      >
        {{ opt.label }}
        <span class="ml-1 opacity-80">({{ opt.count }})</span>
      </button>
      <select
        v-model="difficultyFilter"
        class="rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-600 bg-white hover:border-brand-brown/50 focus:outline-none focus:ring-2 focus:ring-brand-brown/50 cursor-pointer"
      >
        <option value="all">Semua Kesulitan</option>
        <option value="ringan">Ringan</option>
        <option value="sedang">Sedang</option>
        <option value="berat">Berat</option>
        <option value="none">Tanpa Kesulitan</option>
      </select>
      <select
        v-model="conditionSort"
        class="rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-600 bg-white hover:border-brand-brown/50 focus:outline-none focus:ring-2 focus:ring-brand-brown/50 cursor-pointer"
      >
        <option value="default">Urutan Kondisi</option>
        <option value="lowest">Kondisi Terendah</option>
        <option value="highest">Kondisi Tertinggi</option>
      </select>
      <div class="ml-auto text-xs text-slate-400">
        Menampilkan {{ filteredComponents.length }} dari {{ components.length }} komponen
      </div>
    </div>

    <!-- Header with buttons -->
    <div class="flex flex-col items-start gap-3 mb-6 pb-2 border-b border-slate-100 lg:flex-row lg:items-center lg:justify-between">
      <h3 class="text-lg font-semibold text-slate-800 flex items-center gap-2">
        <svg class="w-5 h-5 text-brand-gradation" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
        Daftar Komponen
      </h3>
      <div class="flex flex-wrap items-center gap-2">
        <button
          v-if="isAdmin"
          @click="$emit('import')"
          class="flex items-center gap-1.5 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold px-4 py-2 rounded-xl transition-colors shadow-sm cursor-pointer"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
          Import Komponen
        </button>
        <button
          v-if="isAdmin"
          @click="$emit('import-indicators')"
          class="flex items-center gap-1.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold px-4 py-2 rounded-xl transition-colors shadow-sm cursor-pointer"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6 4h6"/>
          </svg>
          Import Indikator
        </button>
        <button
          v-if="isManagerOrAdmin"
          @click="$emit('add')"
          class="flex items-center gap-1.5 bg-brand-brown hover:bg-brand-gradation text-white text-sm font-medium px-4 py-2 rounded-xl transition-colors shadow-sm cursor-pointer"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
          Tambah Komponen
        </button>
      </div>
    </div>

    <!-- Empty states -->
    <div v-if="components.length === 0" class="text-center py-12 text-slate-400 text-sm">
      Belum ada komponen pada mesin ini.
    </div>
    <div v-else-if="filteredComponents.length === 0" class="text-center py-10 text-slate-400 text-sm">
      Tidak ada komponen untuk filter ini.
    </div>

    <!-- Component Cards -->
    <div v-else class="grid grid-cols-1 lg:grid-cols-2 gap-4">
      <div
        v-for="comp in paginatedComponents"
        :key="comp.id"
        class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5 hover:shadow-md hover:border-brand-cream transition-all group"
      >
        <div class="flex justify-between items-start">
          <div class="flex-1 cursor-pointer" @click="$emit('view-history', comp)">
            <div class="flex gap-2 mb-1.5 flex-wrap">
              <span class="text-xs font-semibold text-brand-gradation bg-brand-cream px-2 py-0.5 rounded-full">{{ comp.category }}</span>
              <span
                v-if="comp.difficulty"
                class="text-xs font-semibold px-2 py-0.5 rounded-full"
                :class="difficultyClass(comp.difficulty)"
              >{{ difficultyLabel(comp.difficulty) }}</span>
              <span
                class="text-xs font-semibold px-2 py-0.5 rounded-full"
                :class="comp.indicators?.length ? 'bg-indigo-100 text-indigo-700' : 'bg-slate-100 text-slate-500'"
              >
                {{ comp.indicators?.length ?? 0 }} indikator
              </span>
            </div>
            <h4 class="font-bold text-slate-800 hover:text-brand-gradation transition-colors">{{ comp.name }}</h4>
            <p class="text-xs text-slate-500 mt-0.5 line-clamp-1">{{ comp.specification }}</p>
          </div>

          <!-- Condition circle -->
          <div class="flex items-center gap-3 ml-3">
            <div class="relative shrink-0">
              <svg class="w-14 h-14" viewBox="0 0 50 50" style="transform: rotate(-90deg)">
                <circle class="text-slate-100 stroke-current" stroke-width="5" cx="25" cy="25" r="20" fill="transparent"/>
                <circle :class="colorTheme(comp.last_condition_pct).textClass" class="stroke-current" stroke-width="5" stroke-linecap="round" cx="25" cy="25" r="20" fill="transparent"
                  :stroke-dasharray="125.7" :stroke-dashoffset="125.7 - (comp.last_condition_pct / 100) * 125.7"/>
              </svg>
              <div class="absolute inset-0 flex items-center justify-center">
                <span :class="colorTheme(comp.last_condition_pct).textClass" class="text-[10px] font-bold">{{ comp.last_condition_pct }}%</span>
              </div>
            </div>

            <!-- Edit/Delete -->
            <div v-if="isManagerOrAdmin" class="flex flex-col gap-1">
              <button @click.stop="$emit('edit', comp)" class="p-1.5 text-slate-400 hover:text-brand-gradation hover:bg-brand-cream rounded-lg cursor-pointer transition-colors" title="Edit">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
              </button>
              <button @click.stop="$emit('delete', comp)" class="p-1.5 text-slate-400 hover:text-red-500 hover:bg-red-50 rounded-lg cursor-pointer transition-colors" title="Hapus">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
              </button>
            </div>
          </div>
        </div>

        <div class="flex justify-between text-xs text-slate-400 border-t border-slate-100 pt-2 mt-3">
          <span>Qty: {{ comp.qty }} {{ comp.unit }}</span>
          <span class="font-medium text-slate-500">Penggantian Terakhir: <span class="text-brand-gradation font-semibold">{{ formatDate(getLastReplacement(comp.id)) }}</span></span>
        </div>
        <div class="flex justify-between items-center mt-1.5">
          <span class="text-xs text-slate-400">Maintenance Terakhir:
            <span class="font-semibold" :class="getLastMaintenance(comp.id) ? 'text-slate-600' : 'text-slate-300'">
              {{ getLastMaintenance(comp.id) ? formatDate(getLastMaintenance(comp.id)) : 'Belum ada' }}
            </span>
          </span>
          <p class="text-xs text-brand-gradation cursor-pointer hover:underline" @click="$emit('view-history', comp)">Lihat riwayat →</p>
        </div>
      </div>
    </div>

    <TablePagination
      v-if="filteredComponents.length > 0"
      v-model="currentPage"
      :total="filteredComponents.length"
      v-model:per-page="perPage"
      :show-per-page-selector="true"
      :per-page-options="[4, 6, 8, 10, 12, 16]"
    />
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import SearchInput from './SearchInput.vue';
import TablePagination from './TablePagination.vue';

const props = defineProps({
  components: { type: Array, default: () => [] },
  isAdmin: { type: Boolean, default: false },
  isManagerOrAdmin: { type: Boolean, default: false },
  colorTheme: { type: Function, required: true },
  formatDate: { type: Function, required: true },
  getLastReplacement: { type: Function, required: true },
  getLastMaintenance: { type: Function, required: true },
});

defineEmits(['add', 'import', 'import-indicators', 'edit', 'delete', 'view-history']);

const search = ref('');
const categoryFilter = ref('all');
const difficultyFilter = ref('all');
const conditionSort = ref('default');
const currentPage = ref(1);
const perPage = ref(6);

const difficultyClass = (diff) => {
  const map = {
    ringan: 'bg-green-100 text-green-700',
    sedang: 'bg-amber-100 text-amber-700',
    berat: 'bg-red-100 text-red-700',
  };
  return map[diff] || 'bg-slate-100 text-slate-600';
};

const difficultyLabel = (diff) => {
  const map = {
    ringan: 'Ringan',
    sedang: 'Sedang',
    berat: 'Berat',
  };
  return map[diff] || diff;
};

const categoryOptions = computed(() => {
  const cats = new Set(props.components.map(c => c.category));
  const opts = [{ value: 'all', label: 'Semua', count: props.components.length }];
  cats.forEach(cat => {
    opts.push({ value: cat, label: cat, count: props.components.filter(c => c.category === cat).length });
  });
  return opts;
});

const filteredComponents = computed(() => {
  let list = props.components;
  if (categoryFilter.value !== 'all') list = list.filter(c => c.category === categoryFilter.value);
  if (difficultyFilter.value !== 'all') {
    if (difficultyFilter.value === 'none') {
      list = list.filter(c => !c.difficulty);
    } else {
      list = list.filter(c => c.difficulty === difficultyFilter.value);
    }
  }
  if (search.value) {
    const q = search.value.toLowerCase();
    list = list.filter(c => c.name.toLowerCase().includes(q));
  }
  if (conditionSort.value === 'lowest') {
    list = [...list].sort((a, b) => {
      if (a.last_condition_pct == null) return 1;
      if (b.last_condition_pct == null) return -1;
      return a.last_condition_pct - b.last_condition_pct;
    });
  } else if (conditionSort.value === 'highest') {
    list = [...list].sort((a, b) => {
      if (a.last_condition_pct == null) return 1;
      if (b.last_condition_pct == null) return -1;
      return b.last_condition_pct - a.last_condition_pct;
    });
  }
  return list;
});

const paginatedComponents = computed(() => {
  const start = (currentPage.value - 1) * perPage.value;
  return filteredComponents.value.slice(start, start + perPage.value);
});

watch([search, categoryFilter, difficultyFilter, conditionSort, perPage], () => {
  currentPage.value = 1;
});
</script>
