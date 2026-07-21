<template>
  <div class="flex flex-wrap gap-2 items-center">
    <SearchInput :model-value="search" @update:model-value="$emit('update:search', $event)" placeholder="Cari mesin..." wrapper-class="w-44" />
    <select
      v-if="hasBothCities"
      :value="city"
      @change="$emit('update:city', $event.target.value)"
      class="rounded-xl border border-slate-200 px-3 py-2 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 cursor-pointer"
    >
      <option value="">Semua Kota</option>
      <option value="pasuruan">Pasuruan</option>
      <option value="sby">Surabaya</option>
    </select>
    <div class="relative">
      <input
        :value="location"
        @input="$emit('update:location', $event.target.value); showLocationDropdown = true"
        @focus="showLocationDropdown = true"
        @blur="handleBlur"
        type="text"
        placeholder="Lokasi / Area"
        class="rounded-xl border border-slate-200 px-3 py-2 pr-8 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 w-40"
      />
      <button
        v-if="location || selectedLocation"
        @click="$emit('clear-location')"
        type="button"
        class="absolute right-2 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 cursor-pointer"
        title="Hapus filter lokasi"
      >
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
      </button>
      <div
        v-if="showLocationDropdown && filteredLocations.length > 0"
        class="absolute top-full left-0 right-0 mt-1 bg-white border border-slate-200 rounded-xl shadow-lg z-50 max-h-48 overflow-y-auto"
      >
        <div
          v-for="loc in filteredLocations"
          :key="loc"
          @mousedown="$emit('select-location', loc); showLocationDropdown = false"
          class="px-3 py-2 text-sm text-slate-700 hover:bg-slate-50 cursor-pointer"
        >
          {{ loc }}
        </div>
      </div>
    </div>
    <select
      v-if="showSort"
      :value="sort"
      @change="$emit('update:sort', $event.target.value)"
      class="rounded-xl border border-slate-200 px-3 py-2 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 cursor-pointer"
    >
      <option value="name">Nama A-Z</option>
      <option value="condition_asc">Kondisi Terendah</option>
      <option value="condition_desc">Kondisi Tertinggi</option>
    </select>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import SearchInput from './SearchInput.vue';

const props = defineProps({
  search: { type: String, default: '' },
  city: { type: String, default: '' },
  location: { type: String, default: '' },
  selectedLocation: { type: String, default: '' },
  locations: { type: Array, default: () => [] },
  sort: { type: String, default: 'name' },
  showSort: { type: Boolean, default: false },
  hasBothCities: { type: Boolean, default: false },
});

const emit = defineEmits(['update:search', 'update:city', 'update:location', 'update:sort', 'select-location', 'clear-location']);

const showLocationDropdown = ref(false);

const filteredLocations = computed(() => {
  if (!props.location) return props.locations;
  const q = props.location.toLowerCase();
  return props.locations.filter(loc => loc.toLowerCase().includes(q));
});

const handleBlur = () => {
  setTimeout(() => { showLocationDropdown.value = false; }, 200);
};
</script>
