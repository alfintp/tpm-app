<template>
  <div>
  <div class="p-6">
    <div v-if="records.length === 0" class="text-center py-12 text-slate-400 text-sm">
      Belum ada riwayat pengerjaan.
    </div>

    <div v-else class="relative border-l-2 border-brand-cream ml-3 space-y-6 pb-4">
      <div v-for="record in records" :key="record.id" class="relative pl-6">
        <div class="absolute w-4 h-4 rounded-full bg-brand-brown border-4 border-white left-[-9px] top-1.5 shadow-sm"></div>
        <div class="bg-slate-50 rounded-xl p-4 border border-slate-100 hover:shadow-md transition-shadow">
          <div class="flex justify-between items-start mb-2 gap-2">
            <div>
              <span class="text-sm font-bold text-slate-800">{{ formatDateTime(record.maintenance_date) }}</span>
              <p class="text-xs text-slate-400 mt-0.5">Teknisi: {{ record.technician?.full_name ?? '-' }}</p>
              <p v-if="record.duration_minutes" class="text-xs text-brand-gradation font-semibold mt-0.5">
                <span class="inline-block bg-brand-cream px-2 py-0.5 rounded-lg">Durasi: {{ formatDuration(record.duration_minutes) }}</span>
                <span v-if="record.start_time || record.end_time" class="text-slate-400 font-normal">({{ record.start_time ?? '-' }} - {{ record.end_time ?? '-' }})</span>
              </p>
            </div>
            <div class="flex flex-col items-end gap-1.5 shrink-0">
              <span class="text-xs font-semibold px-2 py-1 rounded-lg border uppercase tracking-wider" :class="getRecordApprovalState(record).class">
                {{ getRecordApprovalState(record).label }}
              </span>
              <div class="flex items-center gap-1 flex-wrap justify-end">
                <span v-if="record.is_unscheduled" class="text-[9px] font-extrabold px-1.5 py-0.5 rounded bg-amber-50 text-amber-700 border border-amber-200 uppercase tracking-wide">Luar Jadwal</span>
                <span v-if="record.is_late" class="text-[9px] font-extrabold px-1.5 py-0.5 rounded bg-rose-50 text-rose-700 border border-rose-100 uppercase tracking-wide">Terlambat</span>
              </div>
            </div>
          </div>

          <div class="mb-3">
            <ApprovalProgress
              v-if="getFlowStepsForRecord(record).length > 0"
              :flow-steps="getFlowStepsForRecord(record)"
              v-bind="getRecordProgress(record)"
            />
            <span v-else class="text-xs font-semibold px-2 py-1 rounded-lg border bg-amber-100 text-amber-800 border-amber-200">
              MENUNGGU APPROVAL
            </span>
          </div>

          <div class="flex items-center justify-between gap-2">
            <p class="text-sm text-slate-500 italic">{{ record.notes || 'Tidak ada catatan.' }}</p>
            <button
              v-if="record.actions?.length > 0"
              @click="openActionModal(record)"
              class="text-indigo-600 hover:text-indigo-800 text-xs font-bold flex items-center gap-1 transition-colors cursor-pointer shrink-0"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
              Lihat Laporan ({{ record.actions.length }})
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Action detail modal -->
  <Teleport to="body">
    <div v-if="actionModal.show" class="fixed inset-0 z-70 flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm" @click="actionModal.show = false"></div>
      <div class="bg-white rounded-3xl shadow-2xl w-full max-w-4xl max-h-[85vh] relative z-10 flex flex-col overflow-hidden">

        <!-- Header -->
        <div class="p-6 border-b border-slate-100 flex items-start justify-between">
          <div>
            <h3 class="text-xl font-bold text-slate-800">Detail Laporan</h3>
            <p class="text-slate-500 text-xs mt-1.5">Tanggal: <span class="font-bold text-slate-700">{{ actionModal.date }}</span></p>
            <p v-if="actionModal.notes" class="text-xs text-slate-500 mt-2 bg-slate-50 px-3 py-1.5 rounded-xl border border-slate-100 italic">Catatan: "{{ actionModal.notes }}"</p>
          </div>
          <button @click="actionModal.show = false" class="p-1.5 rounded-full hover:bg-slate-100 text-slate-400 hover:text-slate-600 transition-colors cursor-pointer shrink-0">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
          </button>
        </div>

        <!-- Filters & Search -->
        <div class="p-6 pb-4 border-b border-slate-50 bg-slate-50/50 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
          <div class="flex items-center gap-2 flex-wrap">
            <button
              v-for="tab in actionTabs" :key="tab.value"
              @click="actionActiveTab = tab.value"
              :class="actionActiveTab === tab.value ? 'bg-indigo-600 text-white shadow-sm' : 'bg-white text-slate-500 border border-slate-200 hover:border-indigo-300'"
              class="px-3 py-1.5 rounded-lg text-xs font-semibold transition-all cursor-pointer"
            >
              {{ tab.label }} ({{ tab.count }})
            </button>
          </div>
          <div class="relative w-full sm:w-64">
            <input
              v-model="actionSearch"
              type="text"
              placeholder="Cari nama komponen..."
              class="w-full rounded-xl border border-slate-200 bg-white pl-9 pr-4 py-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-700 shadow-sm"
            />
            <svg class="w-4 h-4 text-slate-400 absolute left-3 top-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
          </div>
        </div>

        <!-- Table -->
        <div class="flex-1 overflow-y-auto min-h-[200px]">
          <table class="w-full text-left">
            <thead class="bg-slate-50 border-b border-slate-100 sticky top-0 z-10">
              <tr>
                <th class="text-xs font-semibold text-slate-500 uppercase tracking-wider px-6 py-3">Nama Komponen</th>
                <th class="text-xs font-semibold text-slate-500 uppercase tracking-wider px-4 py-3">Tindakan</th>
                <th class="text-xs font-semibold text-slate-500 uppercase tracking-wider px-4 py-3">Kondisi</th>
                <th class="text-xs font-semibold text-slate-500 uppercase tracking-wider px-4 py-3">Indikator / Keterangan</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-for="(action, idx) in filteredActions" :key="idx" class="hover:bg-slate-50/50 transition-colors align-top">
                <td class="px-6 py-3.5">
                  <span class="text-sm font-semibold text-slate-800">{{ action.component?.name ?? '-' }}</span>
                </td>
                <td class="px-4 py-3.5">
                  <span :class="actionTypeClass(action.action_type)" class="text-[10px] font-semibold px-2.5 py-0.5 rounded-full capitalize">{{ action.action_type }}</span>
                </td>
                <td class="px-4 py-3.5">
                  <div class="flex items-center gap-1.5 text-xs text-slate-600">
                    <span>{{ action.condition_before_pct ?? '-' }}%</span>
                    <svg class="w-3.5 h-3.5 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                    <span class="text-indigo-600 font-bold bg-indigo-50 px-1.5 py-0.5 rounded">{{ action.condition_after_pct ?? '-' }}%</span>
                  </div>
                </td>
                <td class="px-4 py-3.5">
                  <!-- Indicators list (if any) -->
                  <div v-if="action.indicator_values && action.indicator_values.length > 0" class="space-y-1.5">
                    <div
                      v-for="(iv, ivIdx) in action.indicator_values"
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
                        @click.stop="toggleTooltip(idx, ivIdx)"
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
                          v-if="activeTooltip === `${idx}-${ivIdx}`"
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
                  <!-- Fallback: description text -->
                  <span v-else class="text-xs text-slate-500 italic">{{ action.description || '-' }}</span>
                  <p v-if="action.indicator_values?.length > 0 && action.description" class="text-xs text-slate-400 italic mt-1.5">{{ action.description }}</p>
                </td>
              </tr>
              <tr v-if="filteredActions.length === 0">
                <td colspan="4" class="text-center py-12 text-slate-400 text-xs">Tidak ada tindakan yang sesuai dengan filter/pencarian.</td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Footer -->
        <div class="p-6 border-t border-slate-100 bg-slate-50 flex items-center justify-between gap-4">
          <span class="text-xs text-slate-500 font-medium">Menampilkan {{ filteredActions.length }} dari {{ actionModal.actions.length }} tindakan</span>
          <button @click="actionModal.show = false" class="px-4 py-2 bg-white hover:bg-slate-100 border border-slate-200 rounded-xl text-slate-600 text-xs font-semibold transition-colors cursor-pointer shadow-sm">Tutup</button>
        </div>

      </div>
    </div>
  </Teleport>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';
import ApprovalProgress from './ApprovalProgress.vue';

const props = defineProps({
  records: { type: Array, default: () => [] },
  formatDateTime: { type: Function, required: true },
});

const flowSteps = ref([]);

const actionModal      = ref({ show: false, date: '', notes: '', actions: [] });
const actionSearch     = ref('');
const actionActiveTab  = ref('all');
const activeTooltip    = ref(null);

const toggleTooltip = (idx, ivIdx) => {
  const key = `${idx}-${ivIdx}`;
  activeTooltip.value = activeTooltip.value === key ? null : key;
};

const openActionModal = (record) => {
  actionSearch.value    = '';
  actionActiveTab.value = 'all';
  activeTooltip.value   = null;
  actionModal.value = {
    show:    true,
    date:    props.formatDateTime(record.maintenance_date),
    notes:   record.notes ?? '',
    actions: record.actions ?? [],
  };
};

const actionTabs = computed(() => {
  const all     = actionModal.value.actions;
  const replace = all.filter(a => a.action_type === 'replace').length;
  const other   = all.filter(a => a.action_type !== 'replace').length;
  return [
    { value: 'all',     label: 'Semua',       count: all.length },
    { value: 'replace', label: 'Penggantian', count: replace },
    { value: 'other',   label: 'Inspeksi',    count: other },
  ];
});

const filteredActions = computed(() => {
  let list = actionModal.value.actions;
  if (actionActiveTab.value === 'replace') list = list.filter(a => a.action_type === 'replace');
  else if (actionActiveTab.value === 'other') list = list.filter(a => a.action_type !== 'replace');
  const q = actionSearch.value.trim().toLowerCase();
  if (!q) return list;
  return list.filter(a =>
    (a.component?.name ?? '').toLowerCase().includes(q) ||
    (a.action_type ?? '').toLowerCase().includes(q) ||
    (a.description ?? '').toLowerCase().includes(q)
  );
});

const loadFlowConfig = async () => {
  try {
    const res = await axios.get('/api/approval-flow');
    flowSteps.value = res.data.steps || [];
  } catch (e) {
    console.error('Failed to load approval flow:', e);
  }
};

onMounted(loadFlowConfig);

const getFlowStepsForRecord = (record) => {
  const reporterRole = record.technician?.role || 'technician';
  let steps = flowSteps.value.filter(s => s.reporter_role === reporterRole);
  if (steps.length === 0 && reporterRole !== 'technician') {
    steps = flowSteps.value.filter(s => s.reporter_role === 'technician');
  }
  return steps.sort((a, b) => a.step_order - b.step_order);
};

const getRecordApprovalState = (record) => {
  const progress = getRecordProgress(record);
  const s = progress.status;
  if (s === 'approved') return { label: 'DISETUJUI', class: 'bg-green-100 text-green-700 border-green-200' };
  if (s === 'rejected') return { label: 'DITOLAK', class: 'bg-red-100 text-red-700 border-red-200' };
  
  const roleLabel = progress.pendingRole ? ` (${progress.pendingRole.toUpperCase()})` : '';
  return { label: `MENUNGGU${roleLabel}`, class: 'bg-amber-100 text-amber-700 border-amber-200' };
};

const getRecordProgress = (record) => {
  const latest = record.latest_approval;
  const steps = getFlowStepsForRecord(record);
  const total = steps.length;
  if (total === 0) {
    return { status: 'pending', currentStep: 1, completedSteps: 0, totalSteps: 0, pendingRole: null };
  }
  if (!latest) {
    return { status: 'pending', currentStep: 1, completedSteps: 0, totalSteps: total, pendingRole: steps[0]?.role };
  }
  if (latest.decision === 'rejected') {
    return { status: 'rejected', currentStep: latest.step_order, completedSteps: latest.step_order - 1, totalSteps: total, pendingRole: null };
  }
  if (latest.decision === 'approved' && latest.step_order >= total) {
    return { status: 'approved', currentStep: total, completedSteps: total, totalSteps: total, pendingRole: null };
  }
  const currentStep = latest.step_order + 1;
  return { status: 'pending', currentStep, completedSteps: latest.step_order, totalSteps: total, pendingRole: steps[currentStep - 1]?.role };
};

const formatDuration = (minutes) => {
  if (minutes === null || minutes === undefined) return '-';
  if (minutes < 60) return `${minutes} menit`;
  const h = Math.floor(minutes / 60);
  const rem = minutes % 60;
  return rem ? `${h} jam ${rem} menit` : `${h} jam`;
};

const actionTypeClass = (type) => {
  const map = {
    replace:   'bg-red-100 text-red-700',
    repair:    'bg-orange-100 text-orange-700',
    inspect:   'bg-blue-100 text-blue-700',
    clean:     'bg-teal-100 text-teal-700',
    lubricate: 'bg-purple-100 text-purple-700',
  };
  return map[type] || 'bg-slate-100 text-slate-700';
};
</script>
