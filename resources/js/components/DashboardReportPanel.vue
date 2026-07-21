<template>
  <div class="space-y-5">
    <!-- Month selector -->
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div>
        <h3 class="text-base font-bold text-slate-800 flex items-center gap-2">
          <svg class="w-5 h-5 text-brand-brown" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
          Laporan Maintenance Bulan {{ selectedMonthLabel }}
        </h3>
        <p class="text-xs text-slate-500 mt-0.5">Ringkasan laporan dari seluruh mesin untuk bulan terpilih.</p>
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

    <!-- Top stats -->
    <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-8 gap-3">
      <button
        v-for="box in statBoxes"
        :key="box.key"
        @click="openStat(box.key)"
        class="bg-white rounded-xl border p-4 text-center shadow-sm cursor-pointer select-none transition-all hover:shadow-md active:scale-[0.98] focus:outline-none focus:ring-2 focus:ring-offset-1"
        :class="box.ringClass"
      >
        <p class="text-[10px] font-bold uppercase tracking-wider h-8 flex items-center justify-center text-center leading-tight px-1" :class="box.labelClass">{{ box.label }}</p>
        <p class="text-xl font-bold mt-1" :class="box.valueClass">{{ box.value }}</p>
      </button>
    </div>

    <ReportStatModal
      :show="statModal.show"
      :title="statModal.title"
      :mode="statModal.mode"
      :records="statModal.records"
      :replacements="statModal.replacements"
      show-machine
      @close="statModal.show = false"
    />

    <!-- Charts + extra info -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
      <!-- Approval status donut -->
      <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5">
        <h4 class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-4">Status Approval</h4>
        <div class="flex items-center gap-5">
          <div class="relative w-28 h-28 shrink-0">
            <svg viewBox="0 0 100 100" class="w-full h-full" style="transform: rotate(-90deg)">
              <circle cx="50" cy="50" r="42" fill="transparent" stroke="#f1f5f9" stroke-width="10"/>
              <circle v-for="(arc, i) in statusArcs" :key="i"
                cx="50" cy="50" r="42" fill="transparent"
                :stroke="arc.color" stroke-width="10"
                stroke-dasharray="264"
                :stroke-dashoffset="arc.offset"
                stroke-linecap="butt"
              />
            </svg>
            <div class="absolute inset-0 flex items-center justify-center">
              <span class="text-sm font-bold text-slate-700">{{ stats.total }}</span>
            </div>
          </div>
          <div class="flex-1 space-y-2">
            <div class="flex items-center justify-between text-xs">
              <span class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-green-500"></span>Disetujui</span>
              <span class="font-bold text-slate-700">{{ stats.approved }}</span>
            </div>
            <div class="flex items-center justify-between text-xs">
              <span class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-amber-500"></span>Menunggu</span>
              <span class="font-bold text-slate-700">{{ stats.pending }}</span>
            </div>
            <div class="flex items-center justify-between text-xs">
              <span class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-red-500"></span>Ditolak</span>
              <span class="font-bold text-slate-700">{{ stats.rejected }}</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Timeliness bar -->
      <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5">
        <h4 class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-4">Ketepatan Waktu</h4>
        <div class="space-y-4">
          <div>
            <div class="flex items-center justify-between text-xs mb-1.5">
              <span class="font-medium text-slate-600">Tepat Waktu</span>
              <span class="font-bold text-emerald-700">{{ stats.onTime }}</span>
            </div>
            <div class="h-2.5 bg-slate-100 rounded-full overflow-hidden">
              <div class="h-full bg-emerald-500 rounded-full transition-all" :style="{ width: timelinessPct.onTime + '%' }"></div>
            </div>
          </div>
          <div>
            <div class="flex items-center justify-between text-xs mb-1.5">
              <span class="font-medium text-slate-600">Terlambat</span>
              <span class="font-bold text-rose-700">{{ stats.late }}</span>
            </div>
            <div class="h-2.5 bg-slate-100 rounded-full overflow-hidden">
              <div class="h-full bg-rose-500 rounded-full transition-all" :style="{ width: timelinessPct.late + '%' }"></div>
            </div>
          </div>
          <div>
            <div class="flex items-center justify-between text-xs mb-1.5">
              <span class="font-medium text-slate-600">Luar Jadwal</span>
              <span class="font-bold text-indigo-700">{{ stats.unscheduled }}</span>
            </div>
            <div class="h-2.5 bg-slate-100 rounded-full overflow-hidden">
              <div class="h-full bg-indigo-500 rounded-full transition-all" :style="{ width: timelinessPct.unscheduled + '%' }"></div>
            </div>
          </div>
        </div>
      </div>

      <!-- Component checks -->
      <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5">
        <h4 class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-4">Pengecekan Komponen</h4>
        <div class="flex items-center gap-5">
          <div class="relative w-28 h-28 shrink-0">
            <svg viewBox="0 0 100 100" class="w-full h-full" style="transform: rotate(-90deg)">
              <circle cx="50" cy="50" r="42" fill="transparent" stroke="#f1f5f9" stroke-width="10"/>
              <circle cx="50" cy="50" r="42" fill="transparent" stroke="#10b981" stroke-width="10"
                stroke-dasharray="264" :stroke-dashoffset="264 - componentCheckPct * 264" stroke-linecap="round"/>
            </svg>
            <div class="absolute inset-0 flex flex-col items-center justify-center">
              <span class="text-lg font-bold text-emerald-600">{{ componentCheckPct }}%</span>
              <span class="text-[9px] text-slate-400">komponen</span>
            </div>
          </div>
          <div class="flex-1 space-y-2">
            <div class="flex items-center justify-between text-xs">
              <span class="text-slate-600">Mesin dicek penuh</span>
              <span class="font-bold text-emerald-700">{{ fullyCheckedMachineCount }}</span>
            </div>
            <div class="flex items-center justify-between text-xs">
              <span class="text-slate-600">Dicek sebagian</span>
              <span class="font-bold text-amber-700">{{ partialCheckedMachineCount }}</span>
            </div>
            <div class="flex items-center justify-between text-xs">
              <span class="text-slate-600">Tidak dicek</span>
              <span class="font-bold text-slate-500">{{ uncheckedMachineCount }}</span>
            </div>
            <div class="flex items-center justify-between text-xs">
              <span class="text-slate-600">Ganti komponen</span>
              <span class="font-bold text-orange-700">{{ stats.replacements }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Machine table -->
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
      <div class="p-5 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <h4 class="text-sm font-bold text-slate-800 flex items-center gap-2">
          <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6 4h6"/></svg>
          Detail per Mesin
        </h4>
        <div class="flex flex-wrap gap-2">
          <button
            v-for="filter in issueFilters"
            :key="filter.key"
            @click="toggleIssueFilter(filter.key)"
            class="text-[10px] font-bold px-2.5 py-1 rounded-lg border transition-colors cursor-pointer flex items-center gap-1"
            :class="activeIssueFilters.includes(filter.key) ? filter.activeClass : 'bg-white text-slate-500 border-slate-200 hover:border-slate-300'"
          >
            {{ filter.label }}
          </button>
        </div>
      </div>

      <div v-if="machineRows.length === 0" class="text-center py-10 text-slate-400 text-sm">
        Tidak ada laporan untuk bulan ini.
      </div>
      <div v-else class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
          <thead class="bg-slate-50 text-xs font-bold text-slate-500 uppercase tracking-wider">
            <tr>
              <th class="px-5 py-3">Mesin</th>
              <th class="px-5 py-3 text-center">Laporan</th>
              <th class="px-5 py-3 text-center">Approval</th>
              <th class="px-5 py-3 text-center">Komponen Dicek</th>
              <th class="px-5 py-3 text-center">Kondisi</th>
              <th class="px-5 py-3">Masalah</th>
              <th class="px-5 py-3 text-right"></th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 text-xs">
            <tr v-for="row in paginatedMachineRows" :key="row.machine.id" class="hover:bg-slate-50/60 transition-colors">
              <td class="px-5 py-3">
                <div class="font-bold text-slate-800">{{ row.machine.name }}</div>
                <div class="text-slate-400 text-[10px]">{{ row.machine.location ?? '-' }}</div>
              </td>
              <td class="px-5 py-3 text-center">
                <span class="font-bold text-slate-700">{{ row.total }}</span>
              </td>
              <td class="px-5 py-3 text-center">
                <div class="flex items-center justify-center gap-1.5">
                  <span v-if="row.approved" class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-green-50 text-green-700 border border-green-100">{{ row.approved }}</span>
                  <span v-if="row.pending" class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-amber-50 text-amber-700 border border-amber-100">{{ row.pending }}</span>
                  <span v-if="row.rejected" class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-red-50 text-red-700 border border-red-100">{{ row.rejected }}</span>
                </div>
              </td>
              <td class="px-5 py-3 text-center">
                <div class="flex items-center justify-center gap-2">
                  <div class="w-16 h-2 bg-slate-100 rounded-full overflow-hidden">
                    <div class="h-full rounded-full" :class="row.checkBarClass" :style="{ width: row.checkPct + '%' }"></div>
                  </div>
                  <span class="font-bold text-slate-700 w-12 text-right">{{ row.checked }}/{{ row.totalComponents }}</span>
                </div>
              </td>
              <td class="px-5 py-3 text-center">
                <span class="font-bold" :class="getColorTheme(row.machine.condition_pct).textClass">{{ row.machine.condition_pct ?? 0 }}%</span>
              </td>
              <td class="px-5 py-3">
                <div class="flex flex-wrap gap-1">
                  <span v-for="(issue, idx) in row.issues" :key="idx" :class="issue.class">{{ issue.label }}</span>
                  <span v-if="row.issues.length === 0" class="text-slate-400 italic">-</span>
                </div>
              </td>
              <td class="px-5 py-3 text-right">
                <button
                  @click="router.visit(`/machine/${row.machine.id}`)"
                  class="text-indigo-600 hover:text-indigo-800 font-bold flex items-center gap-1 ml-auto cursor-pointer"
                >
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                  Detail
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <TablePagination
        :model-value="currentPage"
        @update:model-value="currentPage = $event"
        :total="machineRows.length"
        :per-page="perPage"
        :show-per-page-selector="true"
        :per-page-options="[5, 10, 15, 20, 30, 50]"
        @update:per-page="perPage = $event"
      />
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import ReportStatModal from './ReportStatModal.vue';
import TablePagination from './TablePagination.vue';

const props = defineProps({
  machines: { type: Array, default: () => [] },
});

const today = new Date();
const currentMonth = `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, '0')}`;
const selectedMonth = ref(currentMonth);
const activeIssueFilters = ref([]);
const currentPage = ref(1);
const perPage = ref(10);
const statModal = ref({ show: false, title: '', mode: 'records', records: [], replacements: [] });

const issueFilters = [
  { key: 'late', label: 'Terlambat', activeClass: 'bg-rose-50 text-rose-700 border-rose-200' },
  { key: 'unscheduled', label: 'Luar Jadwal', activeClass: 'bg-indigo-50 text-indigo-700 border-indigo-200' },
  { key: 'rejected', label: 'Ditolak', activeClass: 'bg-red-50 text-red-700 border-red-200' },
  { key: 'replacement', label: 'Ganti Komponen', activeClass: 'bg-orange-50 text-orange-700 border-orange-200' },
  { key: 'incomplete', label: 'Cek Tidak Lengkap', activeClass: 'bg-amber-50 text-amber-700 border-amber-200' },
];

const toggleIssueFilter = (key) => {
  const set = new Set(activeIssueFilters.value);
  if (set.has(key)) set.delete(key);
  else set.add(key);
  activeIssueFilters.value = Array.from(set);
};

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

const getStatus = (record) => {
  if (record.latest_approval?.decision === 'rejected') return 'rejected';
  if (record.latest_approval?.decision === 'approved') return 'approved';
  return 'pending';
};

const recordsInMonth = computed(() => {
  const list = [];
  for (const machine of props.machines) {
    for (const record of machine.records ?? []) {
      if (record.maintenance_date && record.maintenance_date.startsWith(selectedMonth.value)) {
        list.push({ machine, record });
      }
    }
  }
  return list.sort((a, b) => new Date(b.record.maintenance_date) - new Date(a.record.maintenance_date));
});

const stats = computed(() => {
  let total = 0, approved = 0, pending = 0, rejected = 0, onTime = 0, late = 0, unscheduled = 0, replacements = 0;
  const machineIds = new Set();
  for (const { machine, record } of recordsInMonth.value) {
    total++;
    machineIds.add(machine.id);
    const status = getStatus(record);
    if (status === 'approved') approved++;
    else if (status === 'rejected') rejected++;
    else pending++;
    if (record.is_unscheduled) unscheduled++;
    else if (record.is_late) late++;
    else onTime++;
    replacements += (record.actions ?? []).filter(a => a.action_type === 'replace').length;
  }
  return {
    total,
    machinesWithReports: machineIds.size,
    approved,
    pending,
    rejected,
    onTime,
    late,
    unscheduled,
    replacements,
  };
});

const statusArcs = computed(() => {
  const { approved, pending, rejected, total } = stats.value;
  if (!total) return [];
  const pct = v => (v / total) * 100;
  let offset = 264;
  const arcs = [];
  [
    { value: approved, color: '#22c55e' },
    { value: pending, color: '#f59e0b' },
    { value: rejected, color: '#ef4444' },
  ].forEach(({ value, color }) => {
    if (!value) return;
    const dash = 264 * (pct(value) / 100);
    arcs.push({ color, offset: offset - dash });
    offset -= dash;
  });
  return arcs;
});

const timelinessPct = computed(() => {
  const { onTime, late, unscheduled, total } = stats.value;
  if (!total) return { onTime: 0, late: 0, unscheduled: 0 };
  return {
    onTime: Math.round((onTime / total) * 100),
    late: Math.round((late / total) * 100),
    unscheduled: Math.round((unscheduled / total) * 100),
  };
});

const componentCheckSummary = computed(() => {
  let totalComponents = 0, checkedComponents = 0;
  for (const machine of props.machines) {
    const compCount = machine.components?.length ?? 0;
    if (!compCount) continue;
    const checkedIds = new Set();
    for (const record of machine.records ?? []) {
      if (!record.maintenance_date || !record.maintenance_date.startsWith(selectedMonth.value)) continue;
      if (record.status !== 'completed') continue;
      if (getStatus(record) === 'rejected') continue;
      for (const action of record.actions ?? []) {
        if (action.machine_component_id) checkedIds.add(action.machine_component_id);
      }
    }
    totalComponents += compCount;
    checkedComponents += Math.min(checkedIds.size, compCount);
  }
  return { totalComponents, checkedComponents, pct: totalComponents ? Math.round((checkedComponents / totalComponents) * 100) : 0 };
});

const componentCheckPct = computed(() => componentCheckSummary.value.pct);

const dashboardReplacementItems = computed(() => {
  const items = [];
  for (const { machine, record } of recordsInMonth.value) {
    for (const action of record.actions ?? []) {
      if (action.action_type === 'replace') {
        items.push({
          machine_name: machine.name ?? '-',
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
  { key: 'machines', label: 'Mesin Laporan', value: stats.value.machinesWithReports, labelClass: 'text-slate-400', valueClass: 'text-slate-800', ringClass: 'border-slate-100 hover:border-slate-200 focus:ring-slate-200' },
  { key: 'total', label: 'Total Laporan', value: stats.value.total, labelClass: 'text-slate-400', valueClass: 'text-slate-800', ringClass: 'border-slate-100 hover:border-slate-200 focus:ring-slate-200' },
  { key: 'approved', label: 'Disetujui', value: stats.value.approved, labelClass: 'text-green-600', valueClass: 'text-green-700', ringClass: 'border-green-100 hover:border-green-200 focus:ring-green-200' },
  { key: 'pending', label: 'Menunggu', value: stats.value.pending, labelClass: 'text-amber-600', valueClass: 'text-amber-700', ringClass: 'border-amber-100 hover:border-amber-200 focus:ring-amber-200' },
  { key: 'rejected', label: 'Ditolak', value: stats.value.rejected, labelClass: 'text-red-600', valueClass: 'text-red-700', ringClass: 'border-red-100 hover:border-red-200 focus:ring-red-200' },
  { key: 'onTime', label: 'Tepat Waktu', value: stats.value.onTime, labelClass: 'text-emerald-600', valueClass: 'text-emerald-700', ringClass: 'border-emerald-100 hover:border-emerald-200 focus:ring-emerald-200' },
  { key: 'late', label: 'Terlambat', value: stats.value.late, labelClass: 'text-rose-600', valueClass: 'text-rose-700', ringClass: 'border-rose-100 hover:border-rose-200 focus:ring-rose-200' },
  { key: 'replacements', label: 'Ganti Komponen', value: stats.value.replacements, labelClass: 'text-orange-600', valueClass: 'text-orange-700', ringClass: 'border-orange-100 hover:border-orange-200 focus:ring-orange-200' },
]);

const dashboardRecordToModal = ({ machine, record }, statusOverride) => ({
  machine_name: machine.name ?? '-',
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
      replacements: dashboardReplacementItems.value,
    };
    return;
  }

  let records = [];
  let title = '';
  if (key === 'machines') {
    records = recordsInMonth.value.map(r => dashboardRecordToModal(r));
    title = `Mesin dengan Laporan - ${titleBase}`;
  } else if (key === 'total') {
    records = recordsInMonth.value.map(r => dashboardRecordToModal(r));
    title = `Semua Laporan - ${titleBase}`;
  } else if (key === 'approved') {
    records = recordsInMonth.value.filter(({ record }) => getStatus(record) === 'approved').map(r => dashboardRecordToModal(r, 'approved'));
    title = `Laporan Disetujui - ${titleBase}`;
  } else if (key === 'pending') {
    records = recordsInMonth.value.filter(({ record }) => getStatus(record) === 'pending').map(r => dashboardRecordToModal(r, 'pending'));
    title = `Laporan Menunggu - ${titleBase}`;
  } else if (key === 'rejected') {
    records = recordsInMonth.value.filter(({ record }) => getStatus(record) === 'rejected').map(r => dashboardRecordToModal(r, 'rejected'));
    title = `Laporan Ditolak - ${titleBase}`;
  } else if (key === 'onTime') {
    records = recordsInMonth.value.filter(({ record }) => !record.is_late && !record.is_unscheduled).map(r => dashboardRecordToModal(r, 'onTime'));
    title = `Laporan Tepat Waktu - ${titleBase}`;
  } else if (key === 'late') {
    records = recordsInMonth.value.filter(({ record }) => record.is_late && !record.is_unscheduled).map(r => dashboardRecordToModal(r, 'late'));
    title = `Laporan Terlambat - ${titleBase}`;
  }

  statModal.value = { show: true, title, mode: 'records', records, replacements: [] };
};

const fullyCheckedMachineCount = computed(() => machineRows.value.filter(r => r.checkState === 'full').length);
const partialCheckedMachineCount = computed(() => machineRows.value.filter(r => r.checkState === 'partial').length);
const uncheckedMachineCount = computed(() => machineRows.value.filter(r => r.checkState === 'none').length);

const getColorTheme = (pct) => {
  if (!pct && pct !== 0) return { textClass: 'text-slate-400' };
  if (pct < 50) return { textClass: 'text-red-500' };
  if (pct < 80) return { textClass: 'text-amber-500' };
  return { textClass: 'text-green-500' };
};

const machineRows = computed(() => {
  const map = {};
  for (const { machine, record } of recordsInMonth.value) {
    if (!map[machine.id]) {
      map[machine.id] = {
        machine,
        total: 0,
        approved: 0,
        pending: 0,
        rejected: 0,
        late: 0,
        unscheduled: 0,
        replacements: 0,
        checkedIds: new Set(),
      };
    }
    const row = map[machine.id];
    row.total++;
    const status = getStatus(record);
    if (status === 'approved') row.approved++;
    else if (status === 'rejected') row.rejected++;
    else row.pending++;
    if (record.is_unscheduled) row.unscheduled++;
    else if (record.is_late) row.late++;
    row.replacements += (record.actions ?? []).filter(a => a.action_type === 'replace').length;

    if (record.status === 'completed' && status !== 'rejected') {
      for (const action of record.actions ?? []) {
        if (action.machine_component_id) row.checkedIds.add(action.machine_component_id);
      }
    }
  }

  const rows = Object.values(map).map(row => {
    const totalComponents = row.machine.components?.length ?? 0;
    const checked = Math.min(row.checkedIds.size, totalComponents);
    const pct = totalComponents ? Math.round((checked / totalComponents) * 100) : 0;
    let checkState = 'none';
    if (totalComponents && checked === totalComponents) checkState = 'full';
    else if (checked > 0) checkState = 'partial';

    const issues = [];
    if (row.late) issues.push({ label: `${row.late} Terlambat`, class: 'text-[10px] font-bold px-1.5 py-0.5 rounded bg-rose-50 text-rose-700 border border-rose-100' });
    if (row.unscheduled) issues.push({ label: `${row.unscheduled} Luar Jadwal`, class: 'text-[10px] font-bold px-1.5 py-0.5 rounded bg-indigo-50 text-indigo-700 border border-indigo-100' });
    if (row.rejected) issues.push({ label: `${row.rejected} Ditolak`, class: 'text-[10px] font-bold px-1.5 py-0.5 rounded bg-red-50 text-red-700 border border-red-100' });
    if (row.replacements) issues.push({ label: `${row.replacements} Ganti`, class: 'text-[10px] font-bold px-1.5 py-0.5 rounded bg-orange-50 text-orange-700 border border-orange-100' });
    if (checkState === 'partial' || checkState === 'none') {
      issues.push({ label: `Cek ${checked}/${totalComponents}`, class: 'text-[10px] font-bold px-1.5 py-0.5 rounded bg-amber-50 text-amber-700 border border-amber-100' });
    }

    return {
      machine: row.machine,
      total: row.total,
      approved: row.approved,
      pending: row.pending,
      rejected: row.rejected,
      totalComponents,
      checked,
      checkPct: pct,
      checkState,
      checkBarClass: checkState === 'full' ? 'bg-emerald-500' : checkState === 'partial' ? 'bg-amber-500' : 'bg-slate-300',
      issues,
      late: row.late,
      unscheduled: row.unscheduled,
      replacements: row.replacements,
    };
  });

  if (activeIssueFilters.value.length > 0) {
    return rows.filter(row => {
      for (const key of activeIssueFilters.value) {
        if (key === 'late' && row.late) return true;
        if (key === 'unscheduled' && row.unscheduled) return true;
        if (key === 'rejected' && row.rejected) return true;
        if (key === 'replacement' && row.replacements) return true;
        if (key === 'incomplete' && row.checkState !== 'full') return true;
      }
      return false;
    });
  }

  return rows.sort((a, b) => {
    const issueWeight = r => r.issues.length;
    if (issueWeight(b) !== issueWeight(a)) return issueWeight(b) - issueWeight(a);
    return a.machine.name.localeCompare(b.machine.name);
  });
});

const paginatedMachineRows = computed(() => {
  const start = (currentPage.value - 1) * perPage.value;
  return machineRows.value.slice(start, start + perPage.value);
});

watch([selectedMonth, activeIssueFilters, perPage], () => {
  currentPage.value = 1;
});
</script>
