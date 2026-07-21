<template>
  <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
    <div class="p-5 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div>
        <h3 class="text-base font-bold text-slate-800 flex items-center gap-2">
          <svg class="w-5 h-5 text-brand-brown" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
          Laporan Bulan {{ selectedMonthLabel }}
        </h3>
        <p class="text-xs text-slate-500 mt-0.5">Ringkasan laporan maintenance untuk mesin ini.</p>
      </div>
      <div class="flex items-center gap-2">
        <label class="text-xs font-semibold text-slate-500">Pilih Bulan</label>
        <select
          v-model="selectedMonth"
          class="rounded-xl border border-slate-200 px-3 py-2 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-brand-brown cursor-pointer min-w-[160px]"
        >
          <option v-for="m in monthOptions" :key="m.value" :value="m.value">{{ m.label }}</option>
        </select>
      </div>
    </div>

    <div class="p-5 border-b border-slate-100 bg-slate-50/50">
      <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-8 gap-3">
        <button
          v-for="box in statBoxes"
          :key="box.key"
          @click="openStat(box.key)"
          class="bg-white rounded-xl border p-3 text-center shadow-sm cursor-pointer select-none transition-all hover:shadow-md active:scale-[0.98] focus:outline-none focus:ring-2 focus:ring-offset-1"
          :class="box.ringClass"
        >
          <p class="text-[10px] font-bold uppercase tracking-wider h-8 flex items-center justify-center text-center leading-tight px-1" :class="box.labelClass">{{ box.label }}</p>
          <p class="text-lg font-bold mt-1" :class="box.valueClass">{{ box.value }}</p>
        </button>
      </div>
    </div>

    <ReportStatModal
      :show="statModal.show"
      :title="statModal.title"
      :mode="statModal.mode"
      :records="statModal.records"
      :replacements="statModal.replacements"
      @close="statModal.show = false"
    />

    <div class="p-0">
      <div v-if="filteredRecords.length === 0" class="text-center py-10 text-slate-400 text-sm bg-slate-50/30">
        Tidak ada laporan untuk bulan ini.
      </div>
      <MachineHistoryTab v-else :records="paginatedRecords" :format-date-time="formatDateTime" />
      <TablePagination
        v-if="filteredRecords.length > perPage"
        v-model="currentPage"
        :total="filteredRecords.length"
        :per-page="perPage"
      />
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import MachineHistoryTab from './MachineHistoryTab.vue';
import TablePagination from './TablePagination.vue';
import ReportStatModal from './ReportStatModal.vue';

const props = defineProps({
  records: { type: Array, default: () => [] },
  formatDateTime: { type: Function, required: true },
});

const today = new Date();
const currentMonth = `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, '0')}`;
const selectedMonth = ref(currentMonth);
const currentPage = ref(1);
const perPage = 2;
const statModal = ref({ show: false, title: '', mode: 'records', records: [], replacements: [] });

const monthOptions = computed(() => {
  const options = [];
  const now = new Date();
  for (let i = 0; i < 12; i++) {
    const d = new Date(now.getFullYear(), now.getMonth() - i, 1);
    const value = `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}`;
    const label = d.toLocaleString('id-ID', { month: 'long', year: 'numeric' });
    options.push({ value, label });
  }
  return options;
});

const selectedMonthLabel = computed(() => {
  const option = monthOptions.value.find(m => m.value === selectedMonth.value);
  return option ? option.label : selectedMonth.value;
});

const filteredRecords = computed(() => {
  return props.records
    .filter(r => r.maintenance_date && r.maintenance_date.startsWith(selectedMonth.value))
    .sort((a, b) => new Date(b.maintenance_date) - new Date(a.maintenance_date) || new Date(b.created_at) - new Date(a.created_at));
});

const paginatedRecords = computed(() => {
  const start = (currentPage.value - 1) * perPage;
  return filteredRecords.value.slice(start, start + perPage);
});

watch(selectedMonth, () => {
  currentPage.value = 1;
});

const stats = computed(() => {
  const list = filteredRecords.value;
  const total = list.length;
  const approved = list.filter(r => getStatus(r) === 'approved').length;
  const pending  = list.filter(r => getStatus(r) === 'pending').length;
  const rejected = list.filter(r => getStatus(r) === 'rejected').length;
  const onTime   = list.filter(r => !r.is_late && !r.is_unscheduled).length;
  const late     = list.filter(r => r.is_late && !r.is_unscheduled).length;
  const unscheduled = list.filter(r => r.is_unscheduled).length;
  const replacements = list.reduce((sum, r) => sum + (r.actions ?? []).filter(a => a.action_type === 'replace').length, 0);
  return { total, approved, pending, rejected, onTime, late, unscheduled, replacements };
});

const getStatus = (record) => {
  if (record.latest_approval?.decision === 'rejected') return 'rejected';
  if (record.latest_approval?.decision === 'approved') return 'approved';
  return 'pending';
};

const replacementItems = computed(() => {
  const items = [];
  for (const record of filteredRecords.value) {
    for (const action of record.actions ?? []) {
      if (action.action_type === 'replace') {
        items.push({
          date: record.maintenance_date,
          component: action.component?.name ?? '-',
          technician: record.technician?.full_name ?? '-',
          notes: record.notes ?? '',
        });
      }
    }
  }
  return items.sort((a, b) => new Date(b.date) - new Date(a.date));
});

const statBoxes = computed(() => [
  { key: 'total', label: 'Total', value: stats.value.total, labelClass: 'text-slate-400', valueClass: 'text-slate-800', ringClass: 'border-slate-100 hover:border-slate-200 focus:ring-slate-200' },
  { key: 'approved', label: 'Disetujui', value: stats.value.approved, labelClass: 'text-green-600', valueClass: 'text-green-700', ringClass: 'border-green-100 hover:border-green-200 focus:ring-green-200' },
  { key: 'pending', label: 'Menunggu', value: stats.value.pending, labelClass: 'text-amber-600', valueClass: 'text-amber-700', ringClass: 'border-amber-100 hover:border-amber-200 focus:ring-amber-200' },
  { key: 'rejected', label: 'Ditolak', value: stats.value.rejected, labelClass: 'text-red-600', valueClass: 'text-red-700', ringClass: 'border-red-100 hover:border-red-200 focus:ring-red-200' },
  { key: 'onTime', label: 'Tepat Waktu', value: stats.value.onTime, labelClass: 'text-emerald-600', valueClass: 'text-emerald-700', ringClass: 'border-emerald-100 hover:border-emerald-200 focus:ring-emerald-200' },
  { key: 'late', label: 'Terlambat', value: stats.value.late, labelClass: 'text-rose-600', valueClass: 'text-rose-700', ringClass: 'border-rose-100 hover:border-rose-200 focus:ring-rose-200' },
  { key: 'unscheduled', label: 'Luar Jadwal', value: stats.value.unscheduled, labelClass: 'text-indigo-600', valueClass: 'text-indigo-700', ringClass: 'border-indigo-100 hover:border-indigo-200 focus:ring-indigo-200' },
  { key: 'replacements', label: 'Ganti Komponen', value: stats.value.replacements, labelClass: 'text-orange-600', valueClass: 'text-orange-700', ringClass: 'border-orange-100 hover:border-orange-200 focus:ring-orange-200' },
]);

const recordToModal = (record, statusOverride) => ({
  maintenance_date: record.maintenance_date,
  technician_name: record.technician?.full_name ?? '-',
  notes: record.notes ?? '',
  status: statusOverride ?? getStatus(record),
  is_late: record.is_late,
  is_unscheduled: record.is_unscheduled,
});

const openStat = (key) => {
  const titleBase = selectedMonthLabel.value;
  if (key === 'replacements') {
    statModal.value = {
      show: true,
      title: `Ganti Komponen - ${titleBase}`,
      mode: 'replacements',
      records: [],
      replacements: replacementItems.value,
    };
    return;
  }

  let records = [];
  let title = '';
  if (key === 'total') {
    records = filteredRecords.value.map(r => recordToModal(r));
    title = `Semua Laporan - ${titleBase}`;
  } else if (key === 'approved') {
    records = filteredRecords.value.filter(r => getStatus(r) === 'approved').map(r => recordToModal(r, 'approved'));
    title = `Laporan Disetujui - ${titleBase}`;
  } else if (key === 'pending') {
    records = filteredRecords.value.filter(r => getStatus(r) === 'pending').map(r => recordToModal(r, 'pending'));
    title = `Laporan Menunggu - ${titleBase}`;
  } else if (key === 'rejected') {
    records = filteredRecords.value.filter(r => getStatus(r) === 'rejected').map(r => recordToModal(r, 'rejected'));
    title = `Laporan Ditolak - ${titleBase}`;
  } else if (key === 'onTime') {
    records = filteredRecords.value.filter(r => !r.is_late && !r.is_unscheduled).map(r => recordToModal(r, 'onTime'));
    title = `Laporan Tepat Waktu - ${titleBase}`;
  } else if (key === 'late') {
    records = filteredRecords.value.filter(r => r.is_late && !r.is_unscheduled).map(r => recordToModal(r, 'late'));
    title = `Laporan Terlambat - ${titleBase}`;
  } else if (key === 'unscheduled') {
    records = filteredRecords.value.filter(r => r.is_unscheduled).map(r => recordToModal(r, 'unscheduled'));
    title = `Laporan Luar Jadwal - ${titleBase}`;
  }

  statModal.value = { show: true, title, mode: 'records', records, replacements: [] };
};
</script>
