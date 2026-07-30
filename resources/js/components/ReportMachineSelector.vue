<template>
  <div class="w-full max-w-5xl mx-auto bg-white rounded-2xl border border-slate-200 shadow-sm">
    <div class="px-5 py-3.5 border-b border-slate-100 flex items-center gap-2">
      <svg class="w-4 h-4 text-brand-brown shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
      <h2 class="text-sm font-bold text-slate-700">Pilih Mesin</h2>
    </div>
    <div class="p-5">
      <div v-if="loading" class="flex items-center gap-2 text-slate-400 text-sm">
        <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
        Memuat daftar mesin...
      </div>
      <div v-else class="space-y-3">
        <div class="relative" v-click-outside="closeCombobox">
          <div class="relative">
            <input
              ref="inputRef"
              :value="search"
              @input="$emit('update:search', $event.target.value); open = true"
              @focus="open = true"
              @keydown.escape="open = false"
              @keydown.enter.prevent="selectFirst"
              placeholder="Ketik nama mesin..."
              class="w-full text-sm border border-slate-200 rounded-xl px-4 py-2.5 focus:outline-none focus:ring-1 focus:ring-brand-brown text-slate-700 bg-white pr-10"
              autocomplete="off"
            />
            <button
              v-if="search || selected"
              @click="clear"
              type="button"
              aria-label="Hapus pilihan mesin"
              class="absolute right-8 top-1/2 -translate-y-1/2 p-1 text-slate-400 hover:text-slate-600 hover:bg-slate-100 rounded-full cursor-pointer"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
            <svg class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
          </div>
          <div
            v-if="open && filteredGroups.length > 0"
            class="absolute z-30 mt-1 w-full bg-white border border-slate-200 rounded-xl shadow-lg overflow-hidden max-h-64 overflow-y-auto"
          >
            <template v-for="group in filteredGroups" :key="group.city">
              <div class="px-3 py-1.5 text-[10px] font-bold text-slate-400 uppercase tracking-wide bg-slate-50 border-b border-slate-100">{{ cityLabel(group.city) }}</div>
              <button
                v-for="m in group.machines" :key="m.id"
                @mousedown.prevent="select(m)"
                :class="[
                  selected === m.id ? 'bg-brand-cream' : 'hover:bg-slate-50',
                  scheduleStatus(m).rowClass
                ]"
                class="w-full text-left px-4 py-2.5 text-sm transition-colors cursor-pointer flex items-center gap-3"
              >
                <span class="flex-1 min-w-0 truncate" :class="selected === m.id ? 'font-bold text-brand-brown' : 'text-slate-700'">{{ m.name }}</span>
                <div class="shrink-0 flex items-center gap-2">
                  <span
                    v-if="scheduleStatus(m).label"
                    :class="scheduleStatus(m).badgeClass"
                    class="shrink-0 text-[10px] font-bold px-2 py-0.5 rounded-full whitespace-nowrap"
                  >{{ scheduleStatus(m).label }}</span>
                  <span class="shrink-0 text-[10px] font-bold px-1.5 py-0.5 rounded bg-slate-100 text-slate-500 uppercase tracking-wider">
                    {{ m.kota === 'sby' ? 'SBY' : m.kota === 'pasuruan' ? 'PSN' : m.kota }}
                  </span>
                </div>
              </button>
            </template>
          </div>
          <div
            v-else-if="open && search.length > 0 && filteredMachines.length === 0"
            class="absolute z-30 mt-1 w-full bg-white border border-slate-200 rounded-xl shadow-lg px-4 py-3 text-sm text-slate-400"
          >Tidak ada mesin ditemukan</div>
        </div>
        <div v-if="machine" class="flex flex-wrap gap-2 items-center">
          <span class="text-xs font-semibold px-2.5 py-1 rounded-lg bg-slate-100 text-slate-600">{{ machine.kode }}</span>
          <span v-if="machine.kota" class="text-xs font-bold px-2.5 py-1 rounded-lg bg-brand-cream text-brand-brown">{{ machine.kota === 'sby' ? 'Surabaya' : 'Pasuruan' }}</span>
          <span v-if="machine.location" class="text-xs text-slate-400">{{ machine.location }}</span>
          <span class="text-xs text-slate-400">·</span>
          <span class="text-xs font-semibold text-slate-500">{{ machine.components?.length || 0 }} komponen</span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { getCurrentPeriod } from '../composables/useSchedulePeriods.js';

const props = defineProps({
  loading: { type: Boolean, default: false },
  machines: { type: Array, default: () => [] },
  search: { type: String, default: '' },
  selected: { type: [String, Number], default: '' },
  machine: { type: Object, default: null },
});

const emit = defineEmits(['update:search', 'select', 'clear']);

const open = ref(false);
const inputRef = ref(null);

const vClickOutside = {
  mounted(el, binding) {
    el._clickOutside = (e) => { if (!el.contains(e.target)) binding.value(); };
    document.addEventListener('mousedown', el._clickOutside);
  },
  unmounted(el) { document.removeEventListener('mousedown', el._clickOutside); }
};

const filteredMachines = computed(() => {
  const q = props.search.trim().toLowerCase();
  if (!q) return props.machines;
  return props.machines.filter(m => m.name.toLowerCase().includes(q));
});

const filteredGroups = computed(() => {
  const groups = {};
  for (const m of filteredMachines.value) {
    const city = m.kota || 'lainnya';
    if (!groups[city]) groups[city] = [];
    groups[city].push(m);
  }
  return Object.entries(groups).map(([city, machines]) => ({ city, machines }));
});

const cityLabel = (city) => {
  const map = { sby: 'Surabaya', pasuruan: 'Pasuruan', lainnya: 'Lainnya' };
  return map[city] ?? city;
};

const scheduleStatus = (m) => {
  const today = new Date(); today.setHours(0,0,0,0);
  const monthStart = new Date(today.getFullYear(), today.getMonth(), 1);
  const monthEnd = new Date(today.getFullYear(), today.getMonth() + 1, 0);

  const records = m.records ?? [];
  const totalComponents = (m.components ?? []).length;
  const completedThisMonth = records.filter(r => {
    if (!r.maintenance_date) return false;
    const d = new Date(r.maintenance_date);
    return d >= monthStart && d <= monthEnd && r.status === 'completed';
  });
  const allCheckedThisMonth = completedThisMonth.some(r => {
    const checked = new Set((r.actions ?? []).map(a => a.machine_component_id).filter(Boolean)).size;
    return totalComponents > 0 && checked >= totalComponents;
  });
  if (allCheckedThisMonth) return { label: 'Sudah dicek', badgeClass: 'bg-emerald-100 text-emerald-700', rowClass: '' };
  const partialThisMonth = completedThisMonth.some(r => {
    const checked = new Set((r.actions ?? []).map(a => a.machine_component_id).filter(Boolean)).size;
    return totalComponents > 0 && checked > 0 && checked < totalComponents;
  });
  if (partialThisMonth) return { label: 'Belum dicek', badgeClass: 'bg-red-100 text-red-700', rowClass: 'bg-red-50/30' };

  const period = getCurrentPeriod(m.schedules ?? []);
  if (!period) return { label: null, badgeClass: '', rowClass: '' };

  const diffDays = period.diffDays;

  if (diffDays < 0) return { label: `Terlambat ${Math.abs(diffDays)}h`, badgeClass: 'bg-red-100 text-red-700', rowClass: 'bg-red-50/30' };
  if (diffDays === 0) return { label: 'Hari ini', badgeClass: 'bg-emerald-100 text-emerald-700', rowClass: 'bg-emerald-50/20' };
  if (diffDays === 1) return { label: 'Besok', badgeClass: 'bg-amber-100 text-amber-700', rowClass: '' };
  if (diffDays <= 7) return { label: `${diffDays} hari lagi`, badgeClass: 'bg-blue-100 text-blue-600', rowClass: '' };
  return { label: `${diffDays}h lagi`, badgeClass: 'bg-slate-100 text-slate-500', rowClass: '' };
};

const closeCombobox = () => { open.value = false; };

const select = (m) => {
  open.value = false;
  emit('select', m);
};

const selectFirst = () => {
  if (filteredMachines.value.length > 0) select(filteredMachines.value[0]);
};

const clear = () => {
  emit('update:search', '');
  emit('clear');
  open.value = true;
  inputRef.value?.focus();
};
</script>
