<template>
  <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm" @click="$emit('close')"></div>
    <div class="bg-white rounded-3xl shadow-2xl w-full max-w-4xl max-h-[85vh] relative z-10 flex flex-col overflow-hidden">

      <!-- Modal Header -->
      <div class="p-6 border-b border-slate-100 flex items-start justify-between">
        <div>
          <h3 class="text-xl font-bold text-slate-800">Detail Laporan</h3>
          <p class="text-slate-500 text-xs mt-1.5 flex flex-wrap items-center gap-x-2 gap-y-1.5 leading-relaxed">
            <span>Mesin: <span class="font-bold text-slate-700">{{ item?.machine_name }}</span></span> &bull;
            <span>Teknisi: <span class="font-bold text-slate-700">{{ item?.technician_name }}</span></span> &bull;
            <span>Tanggal: <span class="font-bold text-slate-700">{{ formatDateTime(item?.maintenance_date) }}</span></span>
            <template v-if="item?.start_time || item?.end_time">
              &bull;
              <span class="inline-flex items-center gap-1 text-[11px] font-semibold text-slate-600 bg-slate-50 border border-slate-200 px-2.5 py-0.5 rounded-lg">
                <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Jam: {{ formatTime(item.start_time) }} - {{ formatTime(item.end_time) }}
              </span>
            </template>
            <span v-if="item?.duration_minutes" class="text-brand-gradation font-bold bg-brand-cream border border-brand-cream/40 px-2 py-0.5 rounded-lg text-[11px] inline-flex items-center gap-1 shadow-sm">
              Durasi: {{ formatDuration(item.duration_minutes) }}
            </span>
          </p>
          <p v-if="item?.notes" class="text-xs text-slate-500 mt-2.5 bg-slate-50 px-3 py-1.5 rounded-xl border border-slate-100 italic">
            Catatan Teknisi: "{{ item.notes }}"
          </p>
        </div>
        <button
          @click="$emit('close')"
          class="p-1.5 rounded-full hover:bg-slate-100 text-slate-400 hover:text-slate-600 transition-colors cursor-pointer flex-shrink-0"
        >
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
          </svg>
        </button>
      </div>

      <!-- Filters & Search -->
      <div class="p-6 pb-4 border-b border-slate-50 bg-slate-50/50 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="flex items-center gap-2 flex-wrap">
          <button
            v-for="tab in actionTabs"
            :key="tab.value"
            @click="activeTab = tab.value"
            :class="activeTab === tab.value
              ? 'bg-indigo-600 text-white shadow-sm'
              : 'bg-white text-slate-500 border border-slate-200 hover:border-indigo-300'"
            class="px-3 py-1.5 rounded-lg text-xs font-semibold transition-all cursor-pointer"
          >
            {{ tab.label }} ({{ tab.count }})
          </button>
        </div>
        <div class="relative w-full sm:w-64">
          <input
            v-model="searchQuery"
            type="text"
            placeholder="Cari nama komponen..."
            class="w-full rounded-xl border border-slate-200 bg-white pl-9 pr-4 py-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-700 shadow-sm"
          />
          <svg class="w-4 h-4 text-slate-400 absolute left-3 top-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
          </svg>
        </div>
      </div>

      <!-- Scrollable Table -->
      <div class="flex-1 overflow-y-auto min-h-[300px]">
        <table class="w-full text-left">
          <thead class="bg-slate-50 border-b border-slate-100 sticky top-0 z-10">
            <tr>
              <th class="text-xs font-semibold text-slate-500 uppercase tracking-wider px-6 py-3">Nama Komponen</th>
              <th class="text-xs font-semibold text-slate-500 uppercase tracking-wider px-6 py-3">Tindakan</th>
              <th class="text-xs font-semibold text-slate-500 uppercase tracking-wider px-6 py-3">Kondisi</th>
              <th class="text-xs font-semibold text-slate-500 uppercase tracking-wider px-6 py-3">Keterangan / Catatan</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr
              v-for="(action, idx) in filteredActions"
              :key="idx"
              class="hover:bg-slate-50/50 transition-colors"
            >
              <td class="px-6 py-3.5">
                <span class="text-sm font-semibold text-slate-800">{{ action.component_name }}</span>
              </td>
              <td class="px-6 py-3.5">
                <span :class="actionBadgeClass(action.action_type)" class="text-[10px] font-semibold px-2.5 py-0.5 rounded-full capitalize">
                  {{ action.action_type }}
                </span>
              </td>
              <td class="px-6 py-3.5">
                <div class="flex items-center gap-1.5 text-xs text-slate-600">
                  <span>{{ action.condition_before }}%</span>
                  <svg class="w-3.5 h-3.5 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                  </svg>
                  <span class="text-indigo-600 font-bold bg-indigo-50 px-1.5 py-0.5 rounded">{{ action.condition_after }}%</span>
                </div>
              </td>
              <td class="px-6 py-3.5">
                <span class="text-xs text-slate-600 italic">{{ action.description || '-' }}</span>
              </td>
            </tr>
            <tr v-if="filteredActions.length === 0">
              <td colspan="4" class="text-center py-12 text-slate-400 text-xs">
                Tidak ada tindakan komponen yang sesuai dengan filter/pencarian.
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Footer / Quick Action -->
      <div class="p-6 border-t border-slate-100 bg-slate-50 flex items-center justify-between gap-4">
        <span class="text-xs text-slate-500 font-medium">
          Menampilkan {{ filteredActions.length }} dari {{ item?.actions?.length ?? 0 }} tindakan
        </span>
        <div class="flex items-center gap-2">
          <button
            @click="$emit('close')"
            class="px-4 py-2 bg-white hover:bg-slate-100 border border-slate-200 rounded-xl text-slate-600 text-xs font-semibold transition-colors cursor-pointer shadow-sm"
          >Tutup</button>
          <template v-if="canDecide && item?.approval_status === 'pending'">
            <button
              @click="$emit('decide', 'approved')"
              class="px-4 py-2 rounded-xl bg-green-500 hover:bg-green-600 text-white text-xs font-bold transition-colors cursor-pointer shadow-sm"
            >Setujui Report</button>
            <button
              @click="$emit('decide', 'rejected')"
              class="px-4 py-2 rounded-xl bg-red-500 hover:bg-red-600 text-white text-xs font-bold transition-colors cursor-pointer shadow-sm"
            >Tolak Report</button>
          </template>
        </div>
      </div>

    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';

const props = defineProps({
  show:      { type: Boolean, required: true },
  item:      { type: Object,  default: null },
  canDecide: { type: Boolean, default: false },
});

defineEmits(['close', 'decide']);

const searchQuery = ref('');
const activeTab   = ref('all');

const replaceCount = computed(() => (props.item?.actions ?? []).filter(a => a.action_type === 'replace').length);
const inspectCount = computed(() => (props.item?.actions ?? []).filter(a => a.action_type !== 'replace').length);

const actionTabs = computed(() => [
  { value: 'all',     label: 'Semua',       count: props.item?.actions?.length ?? 0 },
  { value: 'replace', label: 'Penggantian', count: replaceCount.value },
  { value: 'inspect', label: 'Inspeksi',    count: inspectCount.value },
]);

const filteredActions = computed(() => {
  if (!props.item) return [];
  let list = props.item.actions ?? [];

  if (activeTab.value === 'replace') {
    list = list.filter(a => a.action_type === 'replace');
  } else if (activeTab.value === 'inspect') {
    list = list.filter(a => a.action_type !== 'replace');
  }

  if (searchQuery.value.trim()) {
    const q = searchQuery.value.toLowerCase();
    list = list.filter(a =>
      (a.component_name && a.component_name.toLowerCase().includes(q)) ||
      (a.description     && a.description.toLowerCase().includes(q))
    );
  }

  return list;
});

const formatDuration = (minutes) => {
  if (minutes === null || minutes === undefined) return '-';
  if (minutes < 60) return `${minutes} menit`;
  const h = Math.floor(minutes / 60);
  const rem = minutes % 60;
  return rem ? `${h} jam ${rem} menit` : `${h} jam`;
};

const formatTime = (timeStr) => {
  if (!timeStr) return '-';
  const parts = timeStr.split(':');
  if (parts.length >= 2) {
    return `${parts[0].padStart(2, '0')}:${parts[1].padStart(2, '0')}`;
  }
  return timeStr;
};

const formatDateTime = (d) => {
  if (!d) return '-';
  return new Date(d).toLocaleString('id-ID', {
    day: 'numeric', month: 'short', year: 'numeric',
    hour: '2-digit', minute: '2-digit',
  });
};

const actionBadgeClass = (t) => ({
  replace:   'bg-red-100 text-red-700',
  repair:    'bg-orange-100 text-orange-700',
  inspect:   'bg-blue-100 text-blue-700',
  clean:     'bg-teal-100 text-teal-700',
  lubricate: 'bg-purple-100 text-purple-700',
}[t] ?? 'bg-slate-100 text-slate-600');
</script>
