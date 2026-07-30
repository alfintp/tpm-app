<template>
  <div class="space-y-4 pb-24">

    <!-- ══════════════════════════════════════════════════════════════
         PAGE TITLE
    ═══════════════════════════════════════════════════════════════════ -->
    <div class="w-full max-w-5xl mx-auto">
      <PageHeader title="Laporan TPM" subtitle="Pilih mesin dan jadwal untuk memulai laporan pemeriksaan">
        <template #actions>
          <button
            v-if="machine"
            @click="goToMachineDetail"
            class="flex items-center gap-1.5 bg-white border border-slate-200 hover:border-brand-brown hover:text-brand-brown text-slate-600 text-xs font-bold px-3 py-2 rounded-xl transition-all cursor-pointer shadow-sm"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            Detail Mesin
          </button>
        </template>
      </PageHeader>
    </div>

    <ReportMachineSelector
      v-model:search="machineSearch"
      :machines="machinesList"
      :loading="machinesLoading"
      :selected="selectedMachineId"
      :machine="machine"
      @select="pickMachine"
      @clear="clearMachine"
    />

    <!-- Loading mesin data -->
    <div v-if="loading" class="w-full max-w-5xl mx-auto bg-white rounded-2xl border border-slate-200 shadow-sm px-5 py-12 flex flex-col items-center gap-3 text-slate-400">
      <svg class="animate-spin w-8 h-8 text-brand-brown" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
      <p class="text-sm font-semibold text-brand-brown/70">Memuat data mesin...</p>
    </div>

    <!-- Error -->
    <div v-else-if="loadError" class="w-full max-w-5xl mx-auto bg-white rounded-2xl border border-red-100 shadow-sm px-5 py-8 flex flex-col items-center gap-3 text-slate-500">
      <svg class="w-10 h-10 text-red-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
      <p class="text-sm font-bold text-slate-700">{{ loadError }}</p>
      <button @click="loadMachineData" class="px-4 py-2 bg-brand-brown text-white text-xs font-bold rounded-xl cursor-pointer">Coba Lagi</button>
    </div>

    <template v-else-if="machine">

      <ReportSchedulePanel
        :schedule-periods="schedulePeriods"
        :selected-period="selectedPeriod"
        :is-unscheduled="isUnscheduled"
        :machine="machine"
        @select-period="selectPeriod"
        @select-unscheduled="selectUnscheduled"
        @request-unlock="showUnlockModal = true"
      />

      <ReportProgressPanel
        :progress-summaries="progressSummaries"
        :difficulty-filter="difficultyFilter"
        :show-only-pending="showOnlyPending"
        :pending-summary="pendingSummary"
        :is-unscheduled="isUnscheduled"
        @update:difficulty-filter="v => difficultyFilter = v"
        @toggle:show-only-pending="showOnlyPending = !showOnlyPending"
      />

      <div v-if="selectedPeriod?.isLocked && !isUnscheduled && !isAdmin" class="w-full max-w-5xl mx-auto p-4 bg-amber-50 rounded-2xl border border-amber-200 flex items-center gap-3">
        <svg class="w-5 h-5 text-amber-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
        <p class="text-xs font-bold text-amber-700">Jadwal ini terkunci karena sudah melewati batas jadwal maintenance.</p>
      </div>

      <ReportComponentTable
        v-model:search="componentSearch"
        :components="filteredComponentsList"
        :difficulty-filter="difficultyFilter"
        :presets="presets"
        :set-indicator="setIndicator"
        :apply-preset="applyPreset"
        :is-comp-done="isCompDone"
        :cond-class="condClass"
        :get-difficulty-badge-class="getDifficultyBadgeClass"
      />

    </template>

    <button
      v-if="machine && (!selectedPeriod?.isLocked || isUnscheduled || isAdmin)"
      @click="triggerSaveReport"
      :disabled="submitting || !hasCheckedComponents"
      class="fixed bottom-4 right-4 z-40 flex items-center gap-2 px-4 py-3 sm:px-5 sm:py-2.5 bg-emerald-600 hover:bg-emerald-500 disabled:bg-slate-100 disabled:text-slate-400 disabled:cursor-not-allowed text-white text-sm font-bold rounded-2xl shadow-lg transition-all cursor-pointer"
    >
      <svg v-if="submitting" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
      <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
      Simpan Laporan
    </button>

    <ReportSaveModal
      :show="showSaveModal"
      :machine="machine"
      :schedule-label="reportScheduleLabel"
      :schedule-status="reportScheduleStatus"
      :schedule-class="reportScheduleClass"
      :light-reported-count="lightReportedCount"
      :light-component-count="lightComponentCount"
      :heavy-reported-count="heavyReportedCount"
      :heavy-component-count="heavyComponentCount"
      v-model:start-time="startTime"
      v-model:end-time="endTime"
      :duration="calculatedDuration"
      v-model:notes="generalNotes"
      :submitting="submitting"
      @submit="submitReport"
      @close="showSaveModal = false"
    />

    <ReportAccessDeniedModal
      :show="showAccessDeniedModal"
      @close="showAccessDeniedModal = false"
      @back="router.visit('/')"
    />

    <MachineUnlockRequestModal
      :show="showUnlockModal"
      :machine="machine"
      :requested-period="selectedPeriod ? `${selectedPeriod.label} (${selectedPeriod.dateStr})` : null"
      @close="showUnlockModal = false"
      @submitted="m => { machine = m; showUnlockModal = false; }"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, nextTick } from 'vue';
import axios from 'axios';
import { router } from '@inertiajs/vue3';
import { showAlert, showConfirm } from '../composables/useAlert.js';
import { useAuth } from '../composables/useAuth.js';
import PageHeader from '../components/PageHeader.vue';
import ReportMachineSelector from '../components/ReportMachineSelector.vue';
import ReportSchedulePanel from '../components/ReportSchedulePanel.vue';
import ReportProgressPanel from '../components/ReportProgressPanel.vue';
import ReportComponentTable from '../components/ReportComponentTable.vue';
import ReportSaveModal from '../components/ReportSaveModal.vue';
import ReportAccessDeniedModal from '../components/ReportAccessDeniedModal.vue';
import MachineUnlockRequestModal from '../components/MachineUnlockRequestModal.vue';
import { getMonthlyPeriods } from '../composables/useSchedulePeriods.js';

const { user, isAdmin, isTechnician } = useAuth();

// ── Machines list / combobox ───────────────────────────────────────────────
const machinesList = ref([]);
const machinesLoading = ref(true);
const selectedMachineId = ref('');
const machineSearch = ref('');

const pickMachine = async (m) => {
  if (m.id !== selectedMachineId.value && !(await confirmAbandonReport())) return;
  selectedMachineId.value = m.id;
  machineSearch.value = m.name;
  loadMachineData(m.id);
};

const goToMachineDetail = () => {
  if (machine.value) router.visit(`/machine/${machine.value.id}`);
};

const clearMachine = () => {
  machineSearch.value = '';
};

const loadMachinesList = async () => {
  machinesLoading.value = true;
  try {
    const res = await axios.get('/api/machines');
    machinesList.value = res.data?.data ?? res.data ?? [];
  } catch (err) {
    console.error('Failed to load machines list:', err);
  } finally {
    machinesLoading.value = false;
  }
};

// ── Selected machine data ──────────────────────────────────────────────────
const machine = ref(null);
const loading = ref(false);
const submitting = ref(false);
const loadError = ref(null);
const componentsList = ref([]);

const machineId = computed(() => machine.value?.id ?? null);

// ── Role access check ──────────────────────────────────────────────────────
const roles = ref([]);
const rolesLoaded = ref(false);
const showAccessDeniedModal = ref(false);

const fetchRoles = async () => {
  try {
    const res = await axios.get('/api/roles');
    roles.value = res.data || [];
  } catch (e) {
    console.error('Failed to fetch roles:', e);
  } finally {
    rolesLoaded.value = true;
  }
};

const canCreateReport = computed(() => {
  if (isAdmin.value) return true;
  const role = roles.value.find(r => r.name === user.value?.role);
  return !!role?.can_report;
});

// ── Schedule selection ─────────────────────────────────────────────────────
const selectedPeriod = ref(null);
const isUnscheduled = ref(false);

// Admin-configured maintenance report window (H-x to H+y)
const maintenanceWindow = ref({ days_before: 2, days_after: 0 });
const fetchMaintenanceWindow = async () => {
  try {
    const res = await axios.get('/api/settings/maintenance-window');
    maintenanceWindow.value = {
      days_before: res.data.days_before ?? 2,
      days_after: res.data.days_after ?? 0,
    };
  } catch (e) {
    console.error('Failed to fetch maintenance window setting:', e);
  }
};

// ── Time inputs (no date — auto today) ────────────────────────────────────
const maintenanceDate = ref(new Date().toISOString().split('T')[0]);
const startTime = ref('');
const endTime = ref('');
const generalNotes = ref('');
const showSaveModal = ref(false);
const showUnlockModal = ref(false);
const skipLeaveGuard = ref(false);
let unregisterBeforeListener = null;

const presets = [
  { val: 100, label: 'OK' },
  { val: 80, label: 'Minor' },
  { val: 50, label: 'Medium' },
  { val: 0, label: 'Broken' }
];

// ── Duration (computed, read-only) ─────────────────────────────────────────
const hasUnsubmittedProgress = computed(() =>
  componentsList.value.some(comp => !comp.isLocked && comp.inProgress) ||
  Boolean(startTime.value || endTime.value || generalNotes.value || showSaveModal.value)
);

const confirmAbandonReport = async () => {
  if (!hasUnsubmittedProgress.value) return true;
  return showConfirm(
    'Laporan Belum Dikirim',
    'Progress laporan belum dikirim. Jika ditinggalkan, laporan harus diisi ulang dari awal.',
    { confirmText: 'Tinggalkan', cancelText: 'Lanjutkan Mengisi' }
  );
};

const handleBeforeUnload = (event) => {
  if (!hasUnsubmittedProgress.value || submitting.value) return;
  event.preventDefault();
  event.returnValue = '';
};

const calculatedDuration = computed(() => {
  if (!startTime.value || !endTime.value) return 0;
  const [sh, sm] = startTime.value.split(':').map(Number);
  const [eh, em] = endTime.value.split(':').map(Number);
  let diff = (eh * 60 + em) - (sh * 60 + sm);
  if (diff < 0) diff += 1440;
  return diff;
});

// ── Helpers ────────────────────────────────────────────────────────────────
const formatTime = (date) =>
  `${String(date.getHours()).padStart(2,'0')}:${String(date.getMinutes()).padStart(2,'0')}`;

const formatDateStr = (date) =>
  date.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });

const dateKey = (date) => {
  const year = date.getFullYear();
  const month = String(date.getMonth() + 1).padStart(2, '0');
  const day = String(date.getDate()).padStart(2, '0');
  return `${year}-${month}-${day}`;
};

const condClass = (pct) => {
  if (pct < 50) return 'text-red-500';
  if (pct < 80) return 'text-amber-500';
  return 'text-emerald-500';
};

const getDifficultyBadgeClass = (difficulty) => {
  switch (difficulty) {
    case 'ringan': return 'bg-emerald-100 text-emerald-700';
    case 'sedang': return 'bg-amber-100 text-amber-700';
    case 'berat': return 'bg-red-100 text-red-700';
    default: return 'bg-slate-100 text-slate-600';
  }
};

// ── Schedule Periods ───────────────────────────────────────────────────────
// Canonical due dates come from getMonthlyPeriods (backend-generated occurrences);
// this only layers progress/lock/label display fields on top.
const schedulePeriods = computed(() => {
  const today = new Date(); today.setHours(0,0,0,0);
  const rawPeriods = getMonthlyPeriods(machine.value?.schedules ?? [], today);
  if (!rawPeriods.length) return [];

  const sched = (machine.value?.schedules ?? []).find(s => s.id === rawPeriods[0].scheduleId);
  const intervalDays = Number(sched?.interval_days ?? 28);

  return rawPeriods.map((rawPeriod, idx) => {
    const due = rawPeriod.due;
    const diffDays = Math.ceil((due - today) / 86400000);
    let statusLabel, statusClass;
    if (diffDays > 1) { statusLabel = `${diffDays} hari lagi`; statusClass = 'bg-blue-50 text-blue-600'; }
    else if (diffDays === 1) { statusLabel = 'Besok'; statusClass = 'bg-amber-50 text-amber-600'; }
    else if (diffDays === 0) { statusLabel = 'Hari ini'; statusClass = 'bg-emerald-50 text-emerald-600'; }
    else { statusLabel = `Terlambat ${Math.abs(diffDays)} hari`; statusClass = 'bg-red-50 text-red-600'; }
    const periodDate = dateKey(due);
    const completedComponentIds = new Set(
      (machine.value?.records ?? [])
        .filter(record =>
          record.status === 'completed' &&
          !record.is_unscheduled &&
          record.latest_approval?.decision !== 'rejected' &&
          String(record.schedule_id) === String(rawPeriod.scheduleId) &&
          String(record.scheduled_period_date).slice(0, 10) === periodDate
        )
        .flatMap(record => (record.actions ?? []).map(action => String(action.machine_component_id)))
    );
    const componentCount = machine.value?.components?.length ?? 0;
    const progressCount = completedComponentIds.size;
    const progressPct = componentCount > 0 ? Math.round((progressCount / componentCount) * 100) : 0;

    // Period Locking Logic:
    // A period is LOCKED if:
    // 1. Not an admin
    // 2. AND NOT within the admin-configured window (H-x to H+y)
    // 3. AND progress is 0% OR 100% (not currently being worked on)
    // 4. AND Machine unlock_status is not 'approved'
    let isLocked = false;
    if (!isAdmin.value) {
      const isWindowOpen = diffDays >= -maintenanceWindow.value.days_after && diffDays <= maintenanceWindow.value.days_before;
      const isPartiallyDone = progressPct > 0 && progressPct < 100;
      const isManuallyUnlocked = machine.value?.unlock_status === 'approved' && 
                                machine.value?.unlock_expires_at && 
                                new Date(machine.value.unlock_expires_at) > new Date();
      
      if (!isWindowOpen && !isPartiallyDone && !isManuallyUnlocked) {
        isLocked = true;
      }
    }

    const label = intervalDays <= 14 ? `Week ${idx + 1}` : intervalDays <= 21 ? `Periode ${idx + 1}` : 'Bulan Ini';
    return {
      label,
      dateStr: formatDateStr(due),
      dueDate: due,
      scheduleId: rawPeriod.scheduleId,
      statusLabel,
      statusClass,
      diffDays,
      componentCount,
      progressCount,
      progressPct,
      isLocked
    };
  });
});

const discardReportDraft = () => {
  startTime.value = '';
  endTime.value = '';
  generalNotes.value = '';
  showSaveModal.value = false;
};

const selectPeriod = async (period) => {
  const isCurrentPeriod = !isUnscheduled.value && selectedPeriod.value?.scheduleId === period.scheduleId && dateKey(selectedPeriod.value.dueDate) === dateKey(period.dueDate);
  if (isCurrentPeriod) return;
  if (!(await confirmAbandonReport())) return;
  discardReportDraft();
  selectedPeriod.value = period;
  isUnscheduled.value = false;
  componentSearch.value = '';
  showOnlyPending.value = false;
  // Maintenance date always reflects the actual date the report is being made (today),
  // not the schedule's due date — otherwise lateness can never be detected on the backend.
  maintenanceDate.value = dateKey(new Date());
  applyPeriodProgress(period);
};

const selectUnscheduled = async () => {
  if (isUnscheduled.value) return;
  if (!(await confirmAbandonReport())) return;
  discardReportDraft();
  isUnscheduled.value = true;
  selectedPeriod.value = null;
  componentSearch.value = '';
  showOnlyPending.value = false;
  maintenanceDate.value = dateKey(new Date());
  resetComponentProgress();
};

// ── Components Init ────────────────────────────────────────────────────────
const initializeComponents = () => {
  if (!machine.value) return;
  componentsList.value = (machine.value.components ?? []).map(comp => {
    const indicators = comp.indicators ?? [];
    const indicatorValues = {};
    indicators.forEach(ind => { indicatorValues[ind.id] = null; });
    return {
      id: comp.id,
      name: comp.name,
      category: comp.category,
      difficulty: comp.difficulty ?? null,
      specification: comp.specification,
      last_condition_pct: comp.last_condition_pct ?? 100,
      indicators,
      indicatorValues,
      inProgress: false,
      conditionPct: comp.last_condition_pct ?? 100,
      description: '',
      showNote: false,
      hasError: false,
      isLocked: false,
      lockStatus: '',
      is_component_replacement: false
    };
  });

  startTime.value = '';
  endTime.value = '';

  componentSearch.value = '';
  showOnlyPending.value = false;
  if (schedulePeriods.value.length > 0) {
    const urgent = schedulePeriods.value.find(p => p.diffDays <= 0) ?? schedulePeriods.value[0];
    selectPeriod(urgent);
  } else {
    selectUnscheduled();
  }
};

const resetComponentProgress = () => {
  componentsList.value.forEach(comp => {
    comp.indicatorValues = Object.fromEntries(comp.indicators.map(ind => [ind.id, null]));
    comp.inProgress = false;
    comp.conditionPct = comp.last_condition_pct;
    comp.description = '';
    comp.showNote = false;
    comp.hasError = false;
    comp.isLocked = false;
    comp.lockStatus = '';
    comp.is_component_replacement = false;
  });
};

const applyPeriodProgress = (period) => {
  resetComponentProgress();
  if (!machine.value || !period) return;

  const periodDate = dateKey(period.dueDate);
  const records = (machine.value.records ?? []).filter(record =>
    record.status === 'completed' &&
    !record.is_unscheduled &&
    record.latest_approval?.decision !== 'rejected' &&
    String(record.schedule_id) === String(period.scheduleId) &&
    String(record.scheduled_period_date).slice(0, 10) === periodDate
  );

  records.forEach(record => {
    (record.actions ?? []).forEach(action => {
      const component = componentsList.value.find(comp => String(comp.id) === String(action.machine_component_id));
      if (!component) return;

      component.inProgress = true;
      component.isLocked = true;
      component.lockStatus = record.latest_approval?.decision === 'approved' ? 'Disetujui' : 'Menunggu approval';
      component.conditionPct = action.condition_after_pct ?? component.conditionPct;
      component.description = action.description ?? component.description;
      component.is_component_replacement = action.action_type === 'replace';
      (action.indicator_values ?? []).forEach(value => {
        component.indicatorValues[value.component_indicator_id] = Boolean(value.value);
      });
    });
  });

  // If the whole period is locked (outside H-2 to H-0 and no progress), lock all remaining components
  if (period.isLocked && !isAdmin.value) {
    componentsList.value.forEach(comp => {
      if (!comp.isLocked) {
        comp.isLocked = true;
        comp.lockStatus = 'Jadwal Terkunci';
      }
    });
  }
};

// ── Machine load ───────────────────────────────────────────────────────────
const loadMachineData = async (id) => {
  if (!id) return;
  loading.value = true;
  loadError.value = null;
  machine.value = null;
  componentsList.value = [];
  try {
    const res = await axios.get(`/api/machines/${id}`);
    machine.value = res.data;
    initializeComponents();
  } catch (err) {
    loadError.value = err.response?.data?.message || err.message || 'Gagal memuat data mesin.';
    showAlert('error', 'Gagal!', loadError.value);
  } finally {
    loading.value = false;
  }
};

// ── Indicator / manual actions ─────────────────────────────────────────────
const setIndicator = (comp, indId, value) => {
  comp.indicatorValues[indId] = value;
  comp.inProgress = true;
  comp.hasError = false;
  const passes = comp.indicators.filter(i => comp.indicatorValues[i.id] === true).length;
  comp.conditionPct = Math.round((passes / comp.indicators.length) * 100);
};

const applyPreset = (comp, value) => {
  comp.conditionPct = value;
  comp.inProgress = true;
};

// ── isCompDone: komponen dianggap selesai jika semua indikator terisi (atau manual diisi) ──
const isCompDone = (comp) => {
  if (comp.indicators.length === 0) return comp.inProgress;
  return comp.indicators.every(ind => comp.indicatorValues[ind.id] !== null);
};

// ── Difficulty filter ──────────────────────────────────────────────────────
const difficultyFilter = ref(isTechnician.value ? 'berat' : 'ringan');
const showOnlyPending = ref(false);
const componentSearch = ref('');

const filteredComponentsList = computed(() => {
  let components = componentsList.value;
  if (difficultyFilter.value === 'ringan') components = components.filter(c => c.difficulty === 'ringan');
  if (difficultyFilter.value === 'berat') components = components.filter(c => c.difficulty !== 'ringan');
  const q = componentSearch.value.trim().toLowerCase();
  if (q) {
    components = components.filter(c =>
      (c.name ?? '').toLowerCase().includes(q) ||
      (c.category ?? '').toLowerCase().includes(q) ||
      (c.difficulty ?? '').toLowerCase().includes(q) ||
      (c.specification ?? '').toLowerCase().includes(q)
    );
  }
  return showOnlyPending.value && !isUnscheduled.value ? components.filter(c => !c.isLocked) : components;
});

const progressSummaries = computed(() => [
  { key: 'semua', label: 'Semua', items: componentsList.value },
  { key: 'ringan', label: 'Ringan', items: componentsList.value.filter(c => c.difficulty === 'ringan') },
  { key: 'berat', label: 'Berat', items: componentsList.value.filter(c => c.difficulty !== 'ringan') },
].map(summary => ({
  key: summary.key,
  label: summary.label,
  total: summary.items.length,
  done: summary.items.filter(isCompDone).length,
  pending: summary.items.filter(c => !isCompDone(c) && !c.isLocked).length,
  waiting: summary.items.filter(c => c.isLocked && c.lockStatus === 'Menunggu approval').length,
  approved: summary.items.filter(c => c.isLocked && c.lockStatus === 'Disetujui').length,
})));

const pendingSummary = computed(() => {
  const items = componentsList.value.filter(c => !c.isLocked);
  return {
    total: items.length,
    pending: items.length,
    locked: 0,
  };
});

const filteredCheckedCount = computed(() => filteredComponentsList.value.filter(c => isCompDone(c)).length);
const filteredPendingComponents = computed(() => filteredComponentsList.value.filter(c => !isCompDone(c)));

// ── Global stats ───────────────────────────────────────────────────────────
const doneCount = computed(() => componentsList.value.filter(c => isCompDone(c)).length);
const hasCheckedComponents = computed(() => componentsList.value.some(c => isCompDone(c) && !c.isLocked));
const lightComponentCount = computed(() => componentsList.value.filter(c => c.difficulty === 'ringan').length);
const heavyComponentCount = computed(() => componentsList.value.filter(c => c.difficulty !== 'ringan').length);
const lightReportedCount = computed(() => componentsList.value.filter(c => c.difficulty === 'ringan' && isCompDone(c)).length);
const heavyReportedCount = computed(() => componentsList.value.filter(c => c.difficulty !== 'ringan' && isCompDone(c)).length);
const reportScheduleLabel = computed(() => {
  if (isUnscheduled.value) return 'Di Luar Jadwal';
  if (!selectedPeriod.value) return '-';
  return `${selectedPeriod.value.label} · ${selectedPeriod.value.dateStr}`;
});
const reportScheduleStatus = computed(() => {
  if (isUnscheduled.value) return 'Di luar jadwal';
  if (!selectedPeriod.value) return '-';
  const daysPastGrace = -selectedPeriod.value.diffDays - maintenanceWindow.value.days_after;
  return daysPastGrace > 0
    ? `Terlambat ${daysPastGrace} hari`
    : 'Tepat waktu';
});
const reportScheduleClass = computed(() => {
  if (isUnscheduled.value) return 'text-indigo-600';
  if (!selectedPeriod.value) return '';
  const daysPastGrace = -selectedPeriod.value.diffDays - maintenanceWindow.value.days_after;
  return daysPastGrace > 0 ? 'text-red-600' : 'text-emerald-600';
});

const totalCompliance = computed(() => {
  if (!componentsList.value.length) return 100;
  const total = componentsList.value.reduce((sum, comp) => {
    return sum + (isCompDone(comp) ? comp.conditionPct : comp.last_condition_pct);
  }, 0);
  return Math.round(total / componentsList.value.length);
});

const complianceColorClass = computed(() => {
  const v = totalCompliance.value;
  if (v < 50) return 'text-red-500';
  if (v < 80) return 'text-amber-500';
  return 'text-emerald-500';
});

const complianceBarClass = computed(() => {
  const v = totalCompliance.value;
  if (v < 50) return 'bg-red-500';
  if (v < 80) return 'bg-amber-500';
  return 'bg-emerald-500';
});

const scrollToComponent = (compId) => {
  nextTick(() => {
    const el = document.getElementById(`comp-${compId}`);
    if (el) el.scrollIntoView({ behavior: 'smooth', block: 'center' });
  });
};

// ── Save / Submit ──────────────────────────────────────────────────────────
const triggerSaveReport = () => {
  if (!hasCheckedComponents.value) {
    showAlert('warning', 'Perhatian', 'Isi minimal satu komponen sebelum menyimpan.');
    return;
  }

  // Validate: only components that have been partially touched must be fully complete
  let hasValidationError = false;
  let firstErrorId = null;
  for (const comp of componentsList.value) {
    if (comp.isLocked || comp.indicators.length === 0) continue;
    const anyFilled = comp.indicators.some(ind => comp.indicatorValues[ind.id] !== null);
    if (!anyFilled) {
      comp.hasError = false;
      continue;
    }
    const allFilled = comp.indicators.every(ind => comp.indicatorValues[ind.id] !== null);
    comp.hasError = !allFilled;
    if (!allFilled) {
      hasValidationError = true;
      if (!firstErrorId) firstErrorId = comp.id;
    }
  }

  if (hasValidationError) {
    if (firstErrorId) scrollToComponent(firstErrorId);
    showAlert('warning', 'Ada Indikator Belum Diisi', 'Komponen yang sudah mulai diisi harus dilengkapi semua indikatornya.');
    return;
  }

  showSaveModal.value = true;
};

const submitReport = async () => {
  const doneRows = componentsList.value.filter(c => isCompDone(c) && !c.isLocked);
  if (!doneRows.length) return;
  if (!startTime.value || !endTime.value) {
    showAlert('warning', 'Jam Belum Lengkap', 'Isi jam mulai dan jam selesai sebelum mengirim laporan.');
    return;
  }
  const isValidTime = (time) => /^([01]\d|2[0-3]):[0-5]\d$/.test(time);
  if (!isValidTime(startTime.value) || !isValidTime(endTime.value)) {
    showAlert('warning', 'Format Jam Tidak Valid', 'Gunakan format 24 jam HH:MM, misalnya 08:30 atau 17:45.');
    return;
  }
  submitting.value = true;
  try {
    const actions = doneRows.map(row => {
      const action = {
        machine_component_id: row.id,
        action_type: row.is_component_replacement ? 'replace' : 'inspect',
        condition_before_pct: row.last_condition_pct,
        condition_after_pct: row.conditionPct,
        description: row.description || null,
      };
      if (row.indicators.length > 0) {
        action.indicator_values = row.indicators.map(ind => ({
          component_indicator_id: ind.id,
          value: row.indicatorValues[ind.id] === true
        }));
      }
      return action;
    });

    const scheduleId = selectedPeriod.value?.scheduleId ?? null;
    const payload = {
      machine_id: machineId.value,
      schedule_id: scheduleId,
      scheduled_period_date: selectedPeriod.value ? dateKey(selectedPeriod.value.dueDate) : null,
      is_unscheduled: isUnscheduled.value || !scheduleId,
      maintenance_date: maintenanceDate.value,
      start_time: startTime.value || null,
      end_time: endTime.value || null,
      duration_minutes: calculatedDuration.value,
      status: 'completed',
      notes: generalNotes.value || null,
      actions
    };

    await axios.post('/api/records', payload);
    const msg = user.value?.role === 'technician'
      ? 'Laporan berhasil dibuat. Menunggu approval manager/admin.'
      : `Laporan berhasil disimpan untuk ${doneRows.length} komponen.`;
    await showAlert('success', 'Laporan Terkirim!', msg);
    showSaveModal.value = false;
    skipLeaveGuard.value = true;
    window.location.href = '/approvals';
  } catch (err) {
    showAlert('error', 'Gagal!', err.response?.data?.message || err.message);
  } finally {
    submitting.value = false;
  }
};

// ── Mount ──────────────────────────────────────────────────────────────────
onMounted(async () => {
  window.addEventListener('beforeunload', handleBeforeUnload);
  unregisterBeforeListener = router.on('before', (event) => {
    if (skipLeaveGuard.value || !hasUnsubmittedProgress.value) return;
    event.preventDefault();
    confirmAbandonReport().then((confirmed) => {
      if (!confirmed) return;
      skipLeaveGuard.value = true;
      router.visit(event.detail.visit.url);
    });
  });

  await loadMachinesList();
  await fetchRoles();
  await fetchMaintenanceWindow();

  if (!canCreateReport.value) {
    showAccessDeniedModal.value = true;
    return;
  }

  const queryId = new URLSearchParams(window.location.search).get('machine');
  if (queryId) {
    // Pre-fill combobox text from machines list or just ID
    selectedMachineId.value = String(queryId);
    await loadMachineData(queryId);
    // Set combobox display name after list loaded
    const found = machinesList.value.find(m => String(m.id) === String(queryId));
    if (found) machineSearch.value = found.name;
  }
});

onUnmounted(() => {
  window.removeEventListener('beforeunload', handleBeforeUnload);
  unregisterBeforeListener?.();
});
</script>
