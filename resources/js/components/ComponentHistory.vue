<template>
  <div class="fixed inset-0 z-50 flex items-center justify-center">
    <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm" @click="$emit('close')"></div>
    <div class="bg-white rounded-3xl shadow-2xl w-full max-w-2xl relative z-10 max-h-[90vh] flex flex-col">
      <div class="px-8 py-6 border-b border-slate-100 flex items-start justify-between">
        <div>
          <h3 class="text-xl font-semibold text-slate-800">Riwayat Komponen</h3>
          <p class="text-sm text-slate-500 mt-1">{{ component?.name }} · <span class="text-indigo-600 font-medium">{{ component?.category }}</span></p>
        </div>
        <button @click="$emit('close')" class="text-slate-400 hover:text-slate-600 p-2 rounded-full hover:bg-slate-100 cursor-pointer mt-1">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
      </div>

      <!-- Filters -->
      <div class="px-8 py-4 border-b border-slate-100 bg-slate-50 flex flex-wrap gap-3 items-center">
        <span class="text-sm font-medium text-slate-600">Filter:</span>
        <select v-model="filterMonth" @change="loadHistory" class="rounded-lg border border-slate-300 px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-700 cursor-pointer">
          <option value="">Semua Bulan</option>
          <option v-for="m in months" :key="m.value" :value="m.value">{{ m.label }}</option>
        </select>
        <select v-model="filterYear" @change="loadHistory" class="rounded-lg border border-slate-300 px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-700 cursor-pointer">
          <option v-for="y in years" :key="y" :value="y">{{ y }}</option>
        </select>
        <button v-if="filterMonth || filterYear !== currentYear" @click="resetFilter" class="text-xs text-indigo-600 hover:text-indigo-800 cursor-pointer underline">Reset</button>
      </div>

      <!-- History -->
      <div class="p-8 overflow-y-auto flex-1">
        <div v-if="loading" class="animate-pulse space-y-4">
          <div class="h-20 bg-slate-100 rounded-xl"></div>
          <div class="h-20 bg-slate-100 rounded-xl"></div>
        </div>

        <div v-else-if="history.length === 0" class="text-center py-12 text-slate-400">
          <svg class="w-12 h-12 mx-auto mb-3 text-slate-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
          <p class="text-sm">Tidak ada riwayat pengerjaan pada periode ini.</p>
        </div>

        <div v-else class="relative border-l-2 border-indigo-100 ml-3 space-y-6">
          <div v-for="item in history" :key="item.id" class="relative pl-6">
            <div class="absolute w-4 h-4 rounded-full bg-indigo-500 border-4 border-white left-[-9px] top-1.5 shadow-sm"></div>
            <div class="bg-slate-50 rounded-xl p-4 border border-slate-100 hover:shadow-md transition-shadow">
              <div class="flex justify-between items-start mb-2 gap-2">
                <div>
                  <span class="text-sm font-bold text-slate-800">{{ formatDateTime(item.record?.maintenance_date) }}</span>
                  <p class="text-xs text-slate-500 mt-0.5">Teknisi: {{ item.record?.technician?.full_name ?? '-' }}</p>
                  <p v-if="item.record?.duration_minutes" class="text-xs text-brand-gradation font-semibold mt-0.5">
                    <span class="inline-block bg-brand-cream px-2 py-0.5 rounded-lg">Durasi: {{ formatDuration(item.record.duration_minutes) }}</span>
                  </p>
                </div>
                <div class="flex flex-col items-end gap-1.5 shrink-0">
                  <span class="text-xs font-bold px-3 py-1 rounded-full uppercase" :class="getActionTypeClass(item.action_type)">{{ item.action_type }}</span>
                  <div class="flex items-center gap-1">
                    <span v-if="item.record?.is_unscheduled" class="text-[9px] font-extrabold px-1.5 py-0.5 rounded bg-amber-50 text-amber-700 border border-amber-200 uppercase tracking-wide">Luar Jadwal</span>
                    <span v-if="item.record?.is_late" class="text-[9px] font-extrabold px-1.5 py-0.5 rounded bg-rose-50 text-rose-700 border border-rose-200 uppercase tracking-wide">Terlambat</span>
                  </div>
                  <span class="text-[10px] font-semibold px-2 py-0.5 rounded border" :class="approvalStateClass(item.record?.approval_state)">
                    {{ approvalStateLabel(item.record?.approval_state) }}
                  </span>
                  <span v-if="item.record?.approval_state?.status === 'pending' && item.record.approval_state.pending_role" class="text-[9px] text-amber-600 font-medium">
                    Menunggu: {{ item.record.approval_state.pending_role }}
                  </span>
                  <span v-if="item.record?.approval_state?.status === 'rejected' && item.record.approval_state.notes" class="text-[9px] text-rose-600 italic max-w-[150px] text-right line-clamp-2">
                    {{ item.record.approval_state.notes }}
                  </span>
                </div>
              </div>

              <!-- Condition change -->
              <div class="flex items-center gap-3 my-3 text-sm bg-white p-2 rounded-lg border border-slate-100">
                <div class="text-center flex-1">
                  <p class="text-xs text-slate-400 mb-1">Sebelum</p>
                  <span :class="getCondPctClass(item.condition_before_pct)" class="text-lg font-bold">{{ item.condition_before_pct ?? '-' }}%</span>
                </div>
                <svg class="w-5 h-5 text-slate-300 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                <div class="text-center flex-1">
                  <p class="text-xs text-slate-400 mb-1">Sesudah</p>
                  <span :class="getCondPctClass(item.condition_after_pct)" class="text-lg font-bold">{{ item.condition_after_pct ?? '-' }}%</span>
                </div>
              </div>

              <!-- Indicator values (if any) -->
              <div v-if="item.indicator_values && item.indicator_values.length > 0" class="mt-3 space-y-1.5">
                <p class="text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Parameter Indikator:</p>
                <div
                  v-for="(iv, ivIdx) in item.indicator_values"
                  :key="ivIdx"
                  class="flex items-center gap-2"
                >
                  <span
                    :class="iv.value
                      ? 'bg-emerald-50 text-emerald-700 border-emerald-200'
                      : 'bg-red-50 text-red-700 border-red-200'"
                    class="text-[10px] font-bold px-2 py-0.5 rounded-md border leading-none shrink-0"
                  >
                    {{ iv.value ? 'OK' : 'Tidak OK' }}
                  </span>
                  <span class="text-xs text-slate-700 font-medium">{{ iv.indicator?.name ?? '-' }}</span>
                  <span
                    v-if="iv.indicator?.description"
                    class="relative group cursor-pointer shrink-0"
                    @click.stop="toggleTooltip(item.id, ivIdx)"
                  >
                    <svg class="w-3.5 h-3.5 text-slate-400 hover:text-indigo-500 transition-colors" fill="currentColor" viewBox="0 0 20 20">
                      <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                    </svg>
                    <div class="pointer-events-none absolute z-30 hidden group-hover:block bottom-full left-1/2 -translate-x-1/2 mb-1.5 w-56 bg-slate-800 text-white text-[11px] leading-relaxed rounded-xl px-3 py-2 shadow-xl">
                      <p class="font-bold text-slate-200 mb-0.5">Keterangan Indikator</p>
                      <p>{{ iv.indicator.description }}</p>
                      <div class="absolute top-full left-1/2 -translate-x-1/2 border-4 border-transparent border-t-slate-800"></div>
                    </div>
                    <div
                      v-if="activeTooltip === `${item.id}-${ivIdx}`"
                      class="absolute z-30 bottom-full left-1/2 -translate-x-1/2 mb-1.5 w-56 bg-slate-800 text-white text-[11px] leading-relaxed rounded-xl px-3 py-2 shadow-xl"
                      @click.stop
                    >
                      <p class="font-bold text-slate-200 mb-0.5">Keterangan Indikator</p>
                      <p>{{ iv.indicator.description }}</p>
                      <div class="absolute top-full left-1/2 -translate-x-1/2 border-4 border-transparent border-t-slate-800"></div>
                    </div>
                  </span>
                </div>
              </div>
              <p v-if="item.description" class="text-sm text-slate-600 mt-2 italic">"{{ item.description }}"</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import axios from 'axios';

const props = defineProps({
  component: { type: Object, required: true },
});
defineEmits(['close']);

const history = ref([]);
const loading = ref(true);
const currentYear = new Date().getFullYear();
const filterMonth = ref('');
const filterYear = ref(currentYear);
const activeTooltip = ref(null);

const toggleTooltip = (itemId, ivIdx) => {
  const key = `${itemId}-${ivIdx}`;
  activeTooltip.value = activeTooltip.value === key ? null : key;
};

const months = [
  { value: 1, label: 'Januari' }, { value: 2, label: 'Februari' }, { value: 3, label: 'Maret' },
  { value: 4, label: 'April' }, { value: 5, label: 'Mei' }, { value: 6, label: 'Juni' },
  { value: 7, label: 'Juli' }, { value: 8, label: 'Agustus' }, { value: 9, label: 'September' },
  { value: 10, label: 'Oktober' }, { value: 11, label: 'November' }, { value: 12, label: 'Desember' },
];
const years = computed(() => {
  const y = [];
  for (let i = currentYear; i >= currentYear - 3; i--) y.push(i);
  return y;
});

const loadHistory = async () => {
  loading.value = true;
  try {
    const params = {};
    if (filterMonth.value) params.month = filterMonth.value;
    if (filterYear.value) params.year = filterYear.value;
    const res = await axios.get(`/api/components/${props.component.id}/history`, { params });
    history.value = res.data.history;
  } catch (e) {
    console.error(e);
  } finally {
    loading.value = false;
  }
};

const resetFilter = () => {
  filterMonth.value = '';
  filterYear.value = currentYear;
  loadHistory();
};

onMounted(loadHistory);

const formatDuration = (minutes) => {
  if (minutes === null || minutes === undefined) return '-';
  if (minutes < 60) return `${minutes} menit`;
  const h = Math.floor(minutes / 60);
  const rem = minutes % 60;
  return rem ? `${h} jam ${rem} menit` : `${h} jam`;
};

const formatDateTime = (d) => {
  if (!d) return '-';
  return new Date(d).toLocaleString('id-ID', { day: 'numeric', month: 'long', year: 'numeric', hour: '2-digit', minute: '2-digit' });
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

const getCondPctClass = (pct) => {
  if (!pct && pct !== 0) return 'text-slate-400';
  if (pct < 50) return 'text-red-500';
  if (pct < 80) return 'text-amber-500';
  return 'text-green-500';
};

const approvalStateClass = (state) => {
  const s = state?.status;
  if (s === 'approved') return 'bg-emerald-100 text-emerald-800 border-emerald-200';
  if (s === 'rejected') return 'bg-rose-100 text-rose-800 border-rose-200';
  return 'bg-amber-100 text-amber-800 border-amber-200';
};

const approvalStateLabel = (state) => {
  const s = state?.status;
  if (s === 'approved') return 'DISETUJUI';
  if (s === 'rejected') return 'DITOLAK';
  return 'MENUNGGU';
};
</script>
