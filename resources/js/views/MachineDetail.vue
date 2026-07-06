<template>
  <div v-if="loading" class="flex flex-col items-center justify-center py-24 gap-4 text-slate-400">
    <Spinner class="size-10 text-brand-brown" />
    <p class="font-semibold text-slate-500">Memuat data mesin...</p>
  </div>

  <div v-else-if="machine" class="space-y-6">
    <!-- Header -->
    <PageHeader :title="machine.name" :subtitle="'Maintenance Report' + (machine.location ? ' · ' + machine.location : '')">
      <template #title-extra>
        <span v-if="machine.kode" class="px-2.5 py-0.5 bg-slate-100 text-slate-600 rounded-lg text-xs font-mono font-bold border border-slate-200">{{ machine.kode }}</span>
        <span v-if="machine.kota" class="px-2.5 py-0.5 bg-brand-cream text-brand-gradation rounded-lg text-xs font-extrabold uppercase tracking-wide border border-brand-cream/50">{{ machine.kota === 'sby' ? 'Surabaya' : 'Pasuruan' }}</span>
      </template>
      <template #actions>
        <Button @click="handleNavigateBack" class="bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl font-medium shadow-sm gap-2 hover:cursor-pointer">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
          Kembali
        </Button>
        <Button
          v-if="isManagerOrAdmin"
          @click="openEditMachine"
          class="bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl font-medium shadow-sm gap-2 hover:cursor-pointer"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
          Edit Mesin
        </Button>
      </template>
    </PageHeader>

    <!-- Maintenance Schedule Banner -->
    <div v-if="nextSchedule" class="rounded-2xl border px-5 py-4 flex flex-wrap items-center justify-between gap-3"
      :class="maintenanceBannerClass">
      <div class="flex items-center gap-3">
        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"
          :class="maintenanceBannerIconClass"
        ><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
        <div>
          <p class="text-xs font-bold uppercase tracking-wide" :class="maintenanceBannerTextClass">{{ maintenanceBannerTitle }}</p>
          <p class="text-sm font-semibold text-slate-700 mt-0.5">
            {{ formatDate(nextSchedule.next_due_date) }}
            <span class="ml-2 font-bold" :class="maintenanceDaysClass">{{ maintenanceDaysLabel }}</span>
          </p>
        </div>
      </div>
      <span class="text-xs font-bold px-3 py-1.5 rounded-full" :class="maintenanceBadgeClass">{{ maintenanceBadgeLabel }}</span>
    </div>

    <!-- Machine Condition Card -->
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6">
      <div class="flex flex-wrap items-center gap-6">
        <div class="flex items-center gap-4">
          <div class="relative">
            <svg class="w-24 h-24" viewBox="0 0 100 100" style="transform: rotate(-90deg)">
              <circle class="text-slate-100 stroke-current" stroke-width="10" cx="50" cy="50" r="42" fill="transparent"/>
              <circle :class="getColorTheme(machine.condition_pct).textClass" class="stroke-current transition-all duration-1000 ease-out" stroke-width="10" stroke-linecap="round" cx="50" cy="50" r="42" fill="transparent"
                :stroke-dasharray="264" :stroke-dashoffset="264 - (machine.condition_pct / 100) * 264"/>
            </svg>
            <div class="absolute inset-0 flex flex-col items-center justify-center">
              <span :class="getColorTheme(machine.condition_pct).textClass" class="text-xl font-bold">{{ machine.condition_pct }}%</span>
            </div>
          </div>
          <div>
            <p class="text-sm text-slate-500 font-medium">Kondisi Mesin (Avg Komponen)</p>
            <h3 :class="getColorTheme(machine.condition_pct).textClass" class="text-2xl font-bold">{{ getConditionLabel(machine.condition_pct) }}</h3>
            <p class="text-xs text-slate-400 mt-1">Dihitung dari {{ machine.components?.length ?? 0 }} komponen</p>
            <p class="text-xs text-slate-500 mt-1.5 flex items-center gap-1.5">
              <svg class="w-3.5 h-3.5 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
              <span v-if="machine.pic_mesin" class="font-semibold text-slate-700">{{ machine.pic_mesin.full_name }}</span>
              <span v-else class="text-slate-400 italic">Belum ada PIC</span>
            </p>
          </div>
        </div>
      </div>
    </div>

    <!-- Tabs -->
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
      <div class="flex overflow-x-auto border-b border-slate-100">
        <button
          v-if="canReport"
          @click="switchTab('report')"
          :class="activeTab === 'report' ? 'border-brand-gradation text-brand-gradation bg-brand-cream/50' : 'border-transparent text-slate-500 hover:text-slate-700 hover:bg-slate-50'"
          class="shrink-0 sm:flex-1 px-3 py-2 sm:px-6 sm:py-4 text-xs sm:text-sm font-semibold border-b-2 transition-colors cursor-pointer flex items-center justify-center gap-1.5 sm:gap-2 whitespace-nowrap"
        >
          <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
          Laporan
          <span v-if="uncheckedTodayCount > 0" class="hidden sm:inline-block text-[10px] bg-amber-100 text-amber-700 px-1.5 py-0.5 rounded-full font-bold">{{ uncheckedTodayCount }} belum lapor</span>
        </button>
        <button
          @click="switchTab('history')"
          :class="activeTab === 'history' ? 'border-brand-gradation text-brand-gradation bg-brand-cream/50' : 'border-transparent text-slate-500 hover:text-slate-700 hover:bg-slate-50'"
          class="shrink-0 sm:flex-1 px-3 py-2 sm:px-6 sm:py-4 text-xs sm:text-sm font-semibold border-b-2 transition-colors cursor-pointer flex items-center justify-center gap-1.5 sm:gap-2 whitespace-nowrap"
        >
          <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
          Riwayat Maintenance
        </button>
        <button
          @click="switchTab('component')"
          :class="activeTab === 'component' ? 'border-brand-gradation text-brand-gradation bg-brand-cream/50' : 'border-transparent text-slate-500 hover:text-slate-700 hover:bg-slate-50'"
          class="shrink-0 sm:flex-1 px-3 py-2 sm:px-6 sm:py-4 text-xs sm:text-sm font-semibold border-b-2 transition-colors cursor-pointer flex items-center justify-center gap-1.5 sm:gap-2 whitespace-nowrap"
        >
          <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
          Daftar Komponen
        </button>
      </div>

      <!-- Tab 1: Report -->
      <MachineReportTab
        v-if="canReport"
        v-show="activeTab === 'report'"
        :rows="filteredComponentRows"
        :has-components="!!machine.components?.length"
        :is-report-blocked="isReportBlocked"
        :force-report="forceReport"
        :force-report-loading="forceReportLoading"
        :is-manager-or-admin="isManagerOrAdmin"
        :active-filter="componentFilter"
        :filter-options="filterOptions"
        :next-schedule-date-formatted="formatDate(nextSchedule?.next_due_date)"
        :maintenance-days-label="maintenanceDaysLabel"
        :get-last-replacement="getLastReplacementDate"
        :get-prev-check="getPreviousCheck"
        :format-date="formatDate"
        :format-date-time="formatDateTime"
        :color-theme="getColorTheme"
        :condition-label="getConditionLabel"
        :row-class="rowRowClass"
        :is-input-disabled="isInputDisabled"
        :can-check="canCheck"
        :is-condition-valid="isConditionValid"
        :start-time="reportStartTime"
        :end-time="reportEndTime"
        :duration-label="reportDurationLabel"
        @force-report="handleForceReport"
        @update:filter="componentFilter = $event"
        @condition-change="onConditionChange"
        @toggle-check="toggleCheck"
        @wizard-check="toggleWizardCheck"
        @wizard-preset="setWizardPreset($event.row, $event.val)"
        @start-edit="startEdit"
        @cancel-edit="cancelEdit"
        @update:start-time="reportStartTime = $event"
        @update:end-time="reportEndTime = $event"
      />

      <!-- Tab 2: Maintenance History -->
      <MachineHistoryTab
        v-show="activeTab === 'history'"
        :records="sortedRecords"
        :format-date-time="formatDateTime"
      />
      <!-- Tab 3: Component -->
      <MachineComponentTab
        v-show="activeTab === 'component'"
        :components="machine.components ?? []"
        :is-admin="isAdmin"
        :is-manager-or-admin="isManagerOrAdmin"
        :color-theme="getColorTheme"
        :format-date="formatDate"
        :get-last-replacement="getLastReplacementDate"
        :get-last-maintenance="getLastMaintenanceDate"
        @add="openAddComponent"
        @import="triggerComponentImport"
        @edit="openEditComponent"
        @delete="deleteComponent"
        @view-history="openComponentHistory"
      />

      <!-- Modals -->
      <ComponentForm
        v-if="showComponentForm"
        :machineId="machineId"
        :component="editingComponent"
        @close="showComponentForm = false"
        @saved="onComponentSaved"
      />
      <ComponentHistory
        v-if="showComponentHistory"
        :component="historyComponent"
        @close="showComponentHistory = false"
      />
      <MachineEditModal
        v-if="showMachineEdit"
        :machine="machine"
        @close="showMachineEdit = false"
        @saved="onMachineSaved"
      />

      <!-- Import Components Excel Modal -->
      <MachineImportModal
        :show="showComponentImportModal"
        type="component"
        :loading="importingComponents"
        @close="showComponentImportModal = false"
        @download-template="downloadComponentTemplate"
        @import="importComponents"
      />
    </div>
    </div>

    <!-- Floating Save Button (FAB) -->
    <button
      v-if="canReport && activeTab === 'report' && pendingCount > 0 && !(isReportBlocked && !forceReport)"
      @click="openWorkTimeConfirm"
      :disabled="submitting"
      class="fixed bottom-6 right-6 z-50 bg-indigo-600 hover:bg-indigo-700 text-white rounded-full shadow-lg transition-all cursor-pointer flex items-center gap-2 px-5 py-3 disabled:opacity-50 disabled:cursor-not-allowed hover:scale-105 active:scale-95"
    >
      <svg v-if="submitting" class="animate-spin h-5 w-5" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
      <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
      <span class="font-semibold">Simpan ({{ pendingCount }})</span>
    </button>

    <!-- Work Time Confirmation Modal -->
    <WorkTimeConfirmModal
      :show="showWorkTimeConfirm"
      :start-time="reportStartTime"
      :end-time="reportEndTime"
      :duration-label="reportDurationLabel"
      @close="showWorkTimeConfirm = false"
      @confirm="onWorkTimeConfirmed"
      @update:start-time="reportStartTime = $event"
      @update:end-time="reportEndTime = $event"
    />

    <!-- Keyboard Shortcut Hint -->
    <div v-if="canReport && activeTab === 'report' && pendingCount > 0" class="fixed bottom-6 right-24 z-40 bg-slate-800 text-white text-xs px-2 py-1 rounded opacity-60">
      Ctrl+S
    </div>

</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
import axios from 'axios';
import { showAlert, showUnsavedConfirm, showConfirm } from '../composables/useAlert.js';
import ComponentForm from '../components/ComponentForm.vue';
import ComponentHistory from '../components/ComponentHistory.vue';
import MachineEditModal from '../components/MachineEditModal.vue';
import WorkTimeConfirmModal from '../components/WorkTimeConfirmModal.vue';
import { useAuth } from '../composables/useAuth.js';
import { router } from '@inertiajs/vue3';
import PageHeader from '../components/PageHeader.vue';
import MachineImportModal from '../components/MachineImportModal.vue';
import MachineReportTab from '../components/MachineReportTab.vue';
import MachineHistoryTab from '../components/MachineHistoryTab.vue';
import MachineComponentTab from '../components/MachineComponentTab.vue';
import Spinner from '../../views/components/ui/spinner/Spinner.vue';
import Button from '../../views/components/ui/button/Button.vue';

const props = defineProps({
  initialMachine: {
    type: Object,
    default: null
  },
  machine: {
    type: Object,
    default: null
  }
});

const { isManagerOrAdmin, isAdmin, user: authUser } = useAuth();
const machine = ref(props.machine || props.initialMachine);
const loading = ref(!machine.value);
const roles = ref([]);
const rolesLoaded = ref(false);

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

const canReport = computed(() => {
  if (isAdmin.value) return true;
  const role = roles.value.find(r => r.name === authUser.value?.role);
  return !!role?.can_report;
});

const currentUserRequiredDifficulties = computed(() => {
  const role = roles.value.find(r => r.name === authUser.value?.role);
  if (!role || !role.required_difficulties || role.required_difficulties.length === 0) {
    return null;
  }
  return role.required_difficulties;
});

const machineId = computed(() => {
  if (machine.value) return machine.value.id;
  
  if (typeof window !== 'undefined') {
    const parts = window.location.pathname.split('/');
    return parts[parts.length - 1];
  }
  return null;
});

const activeTab = ref('component');

watch([rolesLoaded, canReport], ([loaded, report]) => {
  if (!loaded) return;
  if (report && machine.value?.components?.length) {
    activeTab.value = 'report';
  }
}, { immediate: true });
const componentFilter = ref('unchecked_today');
const submitting = ref(false);
const componentRows = ref([]);
const skipLeaveGuard = ref(false);
const forceReport = ref(false);
const forceReportLoading = ref(false);
const reportStartTime = ref('');
const reportEndTime = ref('');

const reportDurationMinutes = computed(() => {
  if (!reportStartTime.value || !reportEndTime.value) return null;
  const [startH, startM] = reportStartTime.value.split(':').map(Number);
  const [endH, endM] = reportEndTime.value.split(':').map(Number);
  const start = startH * 60 + startM;
  const end = endH * 60 + endM;
  let diff = end - start;
  if (diff < 0) diff += 24 * 60;
  return diff;
});

const reportDurationLabel = computed(() => {
  const m = reportDurationMinutes.value;
  if (m === null || m < 0) return '';
  if (m < 60) return `${m} menit`;
  const h = Math.floor(m / 60);
  const rem = m % 60;
  return rem ? `${h} jam ${rem} menit` : `${h} jam`;
});

const showComponentImportModal = ref(false);
const importingComponents = ref(false);

// Modal states for component CRUD
const showComponentForm = ref(false);
const editingComponent = ref(null);
const showComponentHistory = ref(false);
const historyComponent = ref(null);

// Modal states for machine edit
const showMachineEdit = ref(false);

// Work time confirmation modal
const showWorkTimeConfirm = ref(false);

// Filter for component tab
const componentCategoryFilter = ref('all');

// Search filters
const reportSearch = ref('');
const componentSearch = ref('');

const isSameDay = (d1, d2) => {
  const a = new Date(d1);
  const b = new Date(d2);
  return a.getFullYear() === b.getFullYear() && a.getMonth() === b.getMonth() && a.getDate() === b.getDate();
};

// Start of the current maintenance period based on schedule interval.
// If no schedule, defaults to today (same behavior as before).
const currentPeriodStart = computed(() => {
  const today = new Date();
  today.setHours(0, 0, 0, 0);
  const schedules = machine.value?.schedules ?? [];
  const active = schedules
    .filter(s => s.is_active !== false && s.interval_days && s.next_due_date)
    .sort((a, b) => new Date(a.next_due_date) - new Date(b.next_due_date));
  if (active.length === 0) return today;
  const sched = active[0];
  const nextDue = new Date(sched.next_due_date);
  nextDue.setHours(0, 0, 0, 0);
  const start = new Date(nextDue);
  start.setDate(start.getDate() - sched.interval_days);
  return start;
});

const initialLoadDone = ref(false);

const checkCityGuard = () => {
  if (authUser.value?.city && authUser.value.city !== 'both') {
    if (machine.value?.kota && machine.value.kota !== authUser.value.city) {
      showAlert('error', 'Akses Ditolak!', 'Anda tidak memiliki hak akses untuk melihat mesin ini.');
      router.visit('/machines');
      return false;
    }
  }
  return true;
};

const loadData = async () => {
  if ((props.machine || props.initialMachine) && !initialLoadDone.value) {
    initialLoadDone.value = true;
    const ok = checkCityGuard();
    if (ok) {
      initComponentRows();
    }
    return;
  }
  try {
    const res = await axios.get(`/api/machines/${machineId.value}`);
    machine.value = res.data;
    const ok = checkCityGuard();
    if (ok) {
      initComponentRows();
    }
  } catch (e) {
    console.error(e);
  } finally {
    loading.value = false;
  }
};

// Keyboard shortcut handler
const handleKeydown = (e) => {
  if ((e.ctrlKey || e.metaKey) && e.key === 's') {
    e.preventDefault();
    if (canReport.value && activeTab.value === 'report' && pendingCount.value > 0 && !submitting.value) {
      openWorkTimeConfirm();
    }
  }
};

let unregisterBeforeListener = null;

onMounted(() => {
  loadData();
  fetchRoles();
  window.addEventListener('keydown', handleKeydown);

  unregisterBeforeListener = router.on('before', (event) => {
    if (skipLeaveGuard.value) return;
    if (!hasUnsavedChanges.value) return;
    
    event.preventDefault();
    
    confirmUnsaved().then(async (choice) => {
      if (choice === 'save') {
        const saved = await submitReport(false);
        if (saved) {
          skipLeaveGuard.value = true;
          router.visit(event.detail.visit.url);
        }
      } else if (choice === 'discard') {
        skipLeaveGuard.value = true;
        router.visit(event.detail.visit.url);
      }
    });
  });
});

onUnmounted(() => {
  window.removeEventListener('keydown', handleKeydown);
  if (unregisterBeforeListener) {
    unregisterBeforeListener();
  }
});

const todayChecks = computed(() => {
  const map = {};
  const periodStart = currentPeriodStart.value;
  if (!machine.value?.records) return map;

  for (const record of machine.value.records) {
    const recDate = new Date(record.maintenance_date);
    recDate.setHours(0, 0, 0, 0);
    // Only include records within the current maintenance period
    if (recDate < periodStart) continue;
    const approvalStatus = record.latest_approval?.decision ?? 'pending';
    // Rejected records are treated as if they never happened — allow re-submission
    if (approvalStatus === 'rejected') continue;

    for (const action of record.actions ?? []) {
      const id = action.machine_component_id;
      if (!id) continue;
      if (!map[id] || recDate > new Date(map[id].date)) {
        map[id] = {
          condition: action.condition_after_pct,
          date: record.maintenance_date,
          conditionBefore: action.condition_before_pct,
          description: action.description ?? '',
          isReplacement: action.action_type === 'replace',
          approvalStatus, // 'pending' | 'approved'
          startTime: record.start_time,
          endTime: record.end_time,
        };
      }
    }
  }
  return map;
});

// Map of rejected records for the current period (used to show rejection info)
const rejectedChecks = computed(() => {
  const map = {};
  const periodStart = currentPeriodStart.value;
  if (!machine.value?.records) return map;

  for (const record of machine.value.records) {
    const recDate = new Date(record.maintenance_date);
    recDate.setHours(0, 0, 0, 0);
    if (recDate < periodStart) continue;
    const approvalStatus = record.latest_approval?.decision ?? 'pending';
    if (approvalStatus !== 'rejected') continue;

    for (const action of record.actions ?? []) {
      const id = action.machine_component_id;
      if (!id) continue;
      if (!map[id] || recDate > new Date(map[id].date)) {
        map[id] = {
          date: record.maintenance_date,
          notes: record.latest_approval?.notes ?? '',
          startTime: record.start_time,
          endTime: record.end_time,
        };
      }
    }
  }
  return map;
});


const getComponentActions = (componentId) => {
  const actions = [];
  for (const record of machine.value?.records ?? []) {
    // Only include actions from approved records for display purposes
    const approvalStatus = record.latest_approval?.decision ?? 'pending';
    if (approvalStatus !== 'approved') continue;
    for (const action of record.actions ?? []) {
      if (action.machine_component_id === componentId) {
        actions.push({ ...action, recordDate: record.maintenance_date });
      }
    }
  }
  return actions.sort((a, b) => new Date(b.recordDate) - new Date(a.recordDate));
};

const getPreviousCheck = (componentId) => {
  const all = getComponentActions(componentId);
  const today = new Date();
  const nonToday = all.filter(a => !isSameDay(a.recordDate, today));

  if (nonToday.length > 0) {
    const prev = nonToday[0];
    return { condition: prev.condition_after_pct, date: prev.recordDate, description: prev.description };
  }

  const todayAction = all.find(a => isSameDay(a.recordDate, today));
  if (todayAction?.condition_before_pct != null) {
    return { condition: todayAction.condition_before_pct, date: null, description: todayAction.description };
  }

  const comp = machine.value?.components?.find(c => c.id === componentId);
  if (comp?.last_condition_pct != null) {
    return { condition: comp.last_condition_pct, date: null, description: null };
  }

  return null;
};

const initComponentRows = () => {
  componentRows.value = (machine.value?.components ?? []).map(comp => {
    const todayCheck = todayChecks.value[comp.id];
    const rejectedCheck = rejectedChecks.value[comp.id];
    return {
      id: comp.id,
      category: comp.category,
      name: comp.name,
      specification: comp.specification,
      qty: comp.qty,
      unit: comp.unit,
      difficulty: comp.difficulty || null,
      lastConditionPct: comp.last_condition_pct,
      checkedToday: !!todayCheck,
      todayCondition: todayCheck?.condition ?? null,
      todayCheckedAt: todayCheck?.date ?? null,
      todayApprovalStatus: todayCheck?.approvalStatus ?? null,
      startTime: todayCheck?.startTime ?? rejectedCheck?.startTime ?? null,
      endTime: todayCheck?.endTime ?? rejectedCheck?.endTime ?? null,
      conditionPct: todayCheck?.condition ?? comp.last_condition_pct ?? null,
      originalCondition: todayCheck?.condition ?? comp.last_condition_pct ?? null,
      editing: false,
      checked: false,
      checkedAt: null,
      showForm: false,
      description: todayCheck?.description ?? '',
      is_component_replacement: todayCheck?.isReplacement ?? false,
      rejectedToday: !!rejectedCheck,
      rejectedNotes: rejectedCheck?.notes ?? null,
    };
  });

  // Prefill times only when editing an existing (saved) record
  if (!reportStartTime.value) {
    const savedStartRow = componentRows.value.find(r => r.startTime);
    const savedEndRow = componentRows.value.find(r => r.endTime);
    if (savedStartRow) {
      reportStartTime.value = formatTimeForInput(savedStartRow.startTime);
      if (savedEndRow) reportEndTime.value = formatTimeForInput(savedEndRow.endTime);
    }
  }
};

const replacementDates = computed(() => {
  const map = {};
  if (!machine.value?.records) return map;

  for (const record of machine.value.records) {
    // Only count replacements from approved records
    const approvalStatus = record.latest_approval?.decision ?? 'pending';
    if (approvalStatus !== 'approved') continue;
    for (const action of record.actions ?? []) {
      if (action.action_type !== 'replace' || !action.machine_component_id) continue;
      const date = record.maintenance_date;
      if (!map[action.machine_component_id] || new Date(date) > new Date(map[action.machine_component_id])) {
        map[action.machine_component_id] = date;
      }
    }
  }
  return map;
});

const getLastReplacementDate = (componentId) => {
  const fromHistory = replacementDates.value[componentId];
  if (fromHistory) return fromHistory;

  const comp = machine.value?.components?.find(c => c.id === componentId);
  return comp?.last_replaced_at ?? null;
};

const getLastMaintenanceDate = (componentId) => {
  let latest = null;
  for (const record of machine.value?.records ?? []) {
    const approvalStatus = record.latest_approval?.decision ?? 'pending';
    if (approvalStatus !== 'approved') continue;
    for (const action of record.actions ?? []) {
      if (action.machine_component_id !== componentId) continue;
      if (!latest || new Date(record.maintenance_date) > new Date(latest)) {
        latest = record.maintenance_date;
      }
    }
  }
  return latest;
};

// --- Maintenance Schedule Logic ---
const nextSchedule = computed(() => {
  const schedules = machine.value?.schedules ?? [];
  if (schedules.length === 0) return null;
  
  // Find the soonest active schedule
  const active = schedules
    .filter(s => s.is_active !== false)
    .sort((a, b) => new Date(a.next_due_date) - new Date(b.next_due_date));
  
  return active[0] ?? null;
});

const maintenanceDaysFromNow = computed(() => {
  if (!nextSchedule.value) return null;
  const due = new Date(nextSchedule.value.next_due_date);
  due.setHours(0, 0, 0, 0);
  const today = new Date();
  today.setHours(0, 0, 0, 0);
  return Math.round((due - today) / (1000 * 60 * 60 * 24));
});

const maintenanceDaysLabel = computed(() => {
  const d = maintenanceDaysFromNow.value;
  if (d === null) return '';
  if (d === 0) return '(Hari ini)';
  if (d > 0) return `(${d} hari lagi)`;
  return `(${Math.abs(d)} hari yang lalu)`;
});

// Block report only when maintenance is in the FUTURE (d > 0)
const isReportBlocked = computed(() => {
  const d = maintenanceDaysFromNow.value;
  return d !== null && d > 0;
});

const maintenanceBannerClass = computed(() => {
  const d = maintenanceDaysFromNow.value;
  if (d === null)  return 'bg-slate-50 border-slate-200';
  if (d < 0)       return 'bg-red-50 border-red-300';     // terlambat
  if (d === 0)     return 'bg-amber-50 border-amber-300'; // hari ini
  return 'bg-green-50 border-green-200';                   // terjadwal mendatang
});

const maintenanceBannerIconClass = computed(() => {
  const d = maintenanceDaysFromNow.value;
  if (d === null)  return 'text-slate-400';
  if (d < 0)       return 'text-red-500';
  if (d === 0)     return 'text-amber-500';
  return 'text-green-500';
});

const maintenanceBannerTextClass = computed(() => {
  const d = maintenanceDaysFromNow.value;
  if (d === null)  return 'text-slate-500';
  if (d < 0)       return 'text-red-700';
  if (d === 0)     return 'text-amber-700';
  return 'text-green-700';
});

const maintenanceBannerTitle = computed(() => {
  const d = maintenanceDaysFromNow.value;
  if (d === null)  return 'Jadwal Maintenance';
  if (d < 0)       return 'Jadwal Maintenance';           // terlambat — tanpa "Berikutnya"
  if (d === 0)     return 'Jadwal Maintenance';           // hari ini — tanpa "Berikutnya"
  return 'Jadwal Maintenance Berikutnya';                  // future — sudah dicek, tampilkan berikutnya
});

const maintenanceDaysClass = computed(() => {
  const d = maintenanceDaysFromNow.value;
  if (d === null)  return 'text-slate-400';
  if (d < 0)       return 'text-red-600';
  if (d === 0)     return 'text-amber-600';
  return 'text-green-600';
});

const maintenanceBadgeClass = computed(() => {
  const d = maintenanceDaysFromNow.value;
  if (d === null)  return 'bg-slate-100 text-slate-500';
  if (d < 0)       return 'bg-red-100 text-red-700';
  if (d === 0)     return 'bg-amber-100 text-amber-700';
  return 'bg-green-100 text-green-700';
});

const maintenanceBadgeLabel = computed(() => {
  const d = maintenanceDaysFromNow.value;
  if (d === null)  return 'Tidak Ada Jadwal';
  if (d < 0)       return `Terlambat ${Math.abs(d)} hari`;
  if (d === 0)     return 'Hari Ini';
  if (d <= 7)      return 'Segera';
  return 'Terjadwal';
});

const sortedRecords = computed(() => {
  if (!machine.value?.records) return [];
  return [...machine.value.records].sort((a, b) => {
    const diff = new Date(b.maintenance_date) - new Date(a.maintenance_date);
    if (diff !== 0) return diff;
    return new Date(b.created_at) - new Date(a.created_at);
  });
});

const isMandatory = (r) => {
  const reqDiffs = currentUserRequiredDifficulties.value;
  if (!reqDiffs) {
    return r.difficulty === 'ringan' || r.difficulty === 'sedang' || r.difficulty === 'berat';
  }
  const diff = r.difficulty || 'none';
  return reqDiffs.includes(diff);
};
const uncheckedTodayCount = computed(() => componentRows.value.filter(r => isMandatory(r) && !r.checkedToday).length);
const checkedTodayCount = computed(() => componentRows.value.filter(r => r.checkedToday).length);
const optionalCount = computed(() => componentRows.value.filter(r => !isMandatory(r)).length);
const pendingCount = computed(() => componentRows.value.filter(r => r.checked).length);
const hasUnsavedChanges = computed(() => pendingCount.value > 0);

const filterOptions = computed(() => [
  { value: 'unchecked_today', label: 'Belum Dilaporkan (Wajib)', count: uncheckedTodayCount.value },
  { value: 'checked_today', label: 'Sudah Dilaporkan Periode Ini', count: checkedTodayCount.value },
  { value: 'optional', label: 'Opsional', count: optionalCount.value },
  { value: 'all', label: 'Semua', count: componentRows.value.length },
]);

const filteredComponentRows = computed(() => {
  let filtered = componentRows.value;

  // Apply filter by status
  if (componentFilter.value === 'unchecked_today') {
    filtered = filtered.filter(r => isMandatory(r) && !r.checkedToday);
  } else if (componentFilter.value === 'checked_today') {
    filtered = filtered.filter(r => r.checkedToday);
  } else if (componentFilter.value === 'optional') {
    filtered = filtered.filter(r => !isMandatory(r));
  }

  // Apply search filter
  if (reportSearch.value) {
    const q = reportSearch.value.toLowerCase();
    filtered = filtered.filter(r => r.name.toLowerCase().includes(q));
  }

  return filtered;
});

const reportViewMode = ref('table');
const wizardIndex = ref(0);

const currentWizardRow = computed(() => {
  if (filteredComponentRows.value.length === 0) return null;
  const idx = Math.min(Math.max(0, wizardIndex.value), filteredComponentRows.value.length - 1);
  return filteredComponentRows.value[idx];
});

const prevWizard = () => {
  if (wizardIndex.value > 0) wizardIndex.value--;
};

const nextWizard = () => {
  if (wizardIndex.value < filteredComponentRows.value.length - 1) wizardIndex.value++;
};

const setWizardPreset = (row, val) => {
  row.conditionPct = val;
  onConditionChange(row);
};

const toggleWizardCheck = (row) => {
  toggleCheck(row);
  if (row.checked && wizardIndex.value < filteredComponentRows.value.length - 1) {
    setTimeout(() => {
      if (currentWizardRow.value?.id === row.id && row.checked) {
        wizardIndex.value++;
      }
    }, 450);
  }
};

watch(() => filteredComponentRows.value.length, (newLen) => {
  if (wizardIndex.value >= newLen && newLen > 0) {
    wizardIndex.value = newLen - 1;
  }
});

const isConditionValid = (pct) => pct !== null && pct !== '' && !isNaN(pct) && pct >= 0 && pct <= 100;

const isInputDisabled = (row) => (row.checkedToday && !row.editing) || (row.checked && !row.editing);

const canCheck = (row) => isConditionValid(row.conditionPct) && (!row.checkedToday || row.editing);

const rowRowClass = (row) => {
  if (row.checked) return 'bg-green-50/40';
  if (row.checkedToday && !row.editing) {
    if (row.todayApprovalStatus === 'pending') return 'bg-amber-50/40';
    return 'bg-blue-50/30'; // approved
  }
  return 'hover:bg-slate-50/50';
};

const onConditionChange = (row) => {
  if (row.checked) {
    row.checked = false;
    row.checkedAt = null;
  }
};

const formatTimeForInput = (timeStr) => {
  if (!timeStr) return '';
  const parts = timeStr.split(':');
  if (parts.length >= 2) {
    return `${parts[0].padStart(2, '0')}:${parts[1].padStart(2, '0')}`;
  }
  return timeStr;
};

const startEdit = (row) => {
  // Save original values so we can restore on cancel
  row._origConditionPct = row.conditionPct;
  row._origDescription = row.description;
  row._origIsReplacement = row.is_component_replacement;
  row._origStartTime = row.startTime;
  row._origEndTime = row.endTime;

  reportStartTime.value = formatTimeForInput(row.startTime);
  reportEndTime.value = formatTimeForInput(row.endTime);

  row.editing = true;
  row.checked = false;
  row.checkedAt = null;
  if (row.description || row.is_component_replacement) {
    row.showForm = true;
  }
};

const cancelEdit = (row) => {
  // Restore original values
  row.conditionPct = row._origConditionPct;
  row.description = row._origDescription;
  row.is_component_replacement = row._origIsReplacement;

  reportStartTime.value = formatTimeForInput(row._origStartTime);
  reportEndTime.value = formatTimeForInput(row._origEndTime);

  row.editing = false;
  row.checked = false;
  row.checkedAt = null;
  row.showForm = false;
};

const toggleCheck = (row) => {
  if (!canCheck(row) && !row.checked) return;

  if (row.checked) {
    row.checked = false;
    row.checkedAt = null;
  } else {
    row.checked = true;
    row.checkedAt = new Date().toISOString();
  }
};

const confirmUnsaved = async () => {
  return showUnsavedConfirm(
    'Report Belum Disimpan',
    `${pendingCount.value} komponen sudah dikonfirmasi tetapi belum disimpan. Simpan perubahan sebelum melanjutkan?`
  );
};

const handleUnsavedAction = async () => {
  if (!hasUnsavedChanges.value) return true;

  const choice = await confirmUnsaved();
  if (choice === 'cancel') return false;
  if (choice === 'save') {
    return await submitReport(false);
  }
  // discard
  initComponentRows();
  return true;
};

const switchTab = async (tab) => {
  if (tab === activeTab.value) return;
  if (tab === 'report' && !canReport.value) return;
  const ok = await handleUnsavedAction();
  if (!ok) return;
  activeTab.value = tab;
  if (tab !== 'report') forceReport.value = false;
};

const handleNavigateBack = async () => {
  const ok = await handleUnsavedAction();
  if (!ok) return;
  router.visit(`/machines`);
};

const handleForceReport = async () => {
  forceReportLoading.value = true;
  try {
    // Reset all component rows to unchecked for this session
    componentRows.value = componentRows.value.map(row => ({
      ...row,
      checkedToday: false,
      rejectedToday: false,
      rejectedNotes: null,
      status: null,
      notes: null,
      conditionPct: row.originalCondition !== null ? row.originalCondition : row.conditionPct
    }));
    
    // Enable force reporting
    forceReport.value = true;
    
    showAlert('success', 'Berhasil!', 'Status komponen telah diubah. Anda sekarang dapat melakukan maintenance di luar jadwal.');
  } catch (error) {
    console.error('Error handling force report:', error);
    showAlert('error', 'Gagal!', 'Terjadi kesalahan saat mengubah status komponen.');
  } finally {
    forceReportLoading.value = false;
  }
};

const ensureWorkTime = () => {
  // No auto-fill — user must fill manually. Only used as a no-op guard.
};

const openWorkTimeConfirm = () => {
  if (activeTab.value !== 'report' || pendingCount.value === 0 || (isReportBlocked.value && !forceReport.value)) return;
  ensureWorkTime();
  showWorkTimeConfirm.value = true;
};

const onWorkTimeConfirmed = () => {
  if (!reportStartTime.value || !reportEndTime.value) {
    showAlert('warning', 'Waktu Belum Lengkap', 'Jam mulai dan jam selesai wajib diisi.');
    return;
  }
  if (reportStartTime.value === reportEndTime.value) {
    showAlert('warning', 'Waktu Tidak Valid', 'Jam mulai dan jam selesai tidak boleh sama. Minimal selisih 1 menit.');
    return;
  }
  showWorkTimeConfirm.value = false;
  submitReport(false);
};

const submitReport = async (redirect = true) => {
  ensureWorkTime();
  const checkedRows = componentRows.value.filter(r => r.checked);
  if (checkedRows.length === 0) {
    showAlert('warning', 'Perhatian', 'Konfirmasi minimal satu komponen terlebih dahulu.');
    return false;
  }

  submitting.value = true;
  try {
    const actions = checkedRows.map(row => {
      const prev = getPreviousCheck(row.id);
      const isReplacement = !!row.is_component_replacement;
      const type = isReplacement ? 'replace' : 'inspect';
      return {
        machine_component_id: row.id,
        action_type: type,
        condition_before_pct: prev?.condition ?? row.lastConditionPct,
        condition_after_pct: row.conditionPct,
        description: row.description || null,
      };
    });

    await axios.post('/api/records', {
      machine_id: machineId.value,
      schedule_id: forceReport.value ? null : (nextSchedule.value?.id || null),
      is_unscheduled: forceReport.value,
      maintenance_date: new Date().toISOString(),
      start_time: reportStartTime.value || null,
      end_time: reportEndTime.value || null,
      duration_minutes: reportDurationMinutes.value,
      status: 'completed',
      notes: `Maintenance report - ${checkedRows.length} komponen diperiksa`,
      actions,
    });

    const istech = authUser.value?.role === 'technician';
    const successMsg = istech
      ? `Laporan berhasil dibuat untuk ${checkedRows.length} komponen. Menunggu approval dari manager/admin.`
      : `Laporan berhasil disimpan untuk ${checkedRows.length} komponen.`;

    if (redirect) {
      skipLeaveGuard.value = true;
      showAlert('success', 'Laporan Berhasil Dikirim!', successMsg);
      router.visit(`/machine/${machineId.value}`, { preserveState: false });
    } else {
      await loadData();
      reportStartTime.value = '';
      reportEndTime.value = '';
      forceReport.value = false;
      await showAlert('success', 'Laporan Berhasil Dikirim!', successMsg);
      activeTab.value = 'history';
      window.dispatchEvent(new CustomEvent('refresh-data'));
    }
    return true;
  } catch (e) {
    showAlert('error', 'Gagal!', 'Gagal menyimpan report: ' + (e.response?.data?.error || e.message));
    return false;
  } finally {
    submitting.value = false;
  }
};

const formatDateTime = (d) => {
  if (!d) return '-';
  return new Date(d).toLocaleString('id-ID', { day: 'numeric', month: 'long', year: 'numeric', hour: '2-digit', minute: '2-digit' });
};

const formatDate = (d) => {
  if (!d) return '-';
  return new Date(d).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
};

const getColorTheme = (pct) => {
  if (!pct && pct !== 0) return { textClass: 'text-slate-400' };
  if (pct < 50) return { textClass: 'text-red-500' };
  if (pct < 80) return { textClass: 'text-amber-500' };
  return { textClass: 'text-green-500' };
};

const getConditionLabel = (pct) => {
  if (pct < 50) return 'Perlu Perhatian';
  if (pct < 80) return 'Kondisi Sedang';
  return 'Kondisi Baik';
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

// Component CRUD functions
const openAddComponent = () => {
  if (!isManagerOrAdmin.value) return;
  editingComponent.value = null;
  showComponentForm.value = true;
};

const openEditComponent = (comp) => {
  if (!isManagerOrAdmin.value) return;
  editingComponent.value = comp;
  showComponentForm.value = true;
};

const openComponentHistory = (comp) => {
  historyComponent.value = comp;
  showComponentHistory.value = true;
};

const onComponentSaved = async () => {
  showComponentForm.value = false;
  await loadData();
  showAlert('success', 'Berhasil!', 'Komponen berhasil disimpan.');
};

const deleteComponent = async (comp) => {
  if (!isManagerOrAdmin.value) return;
  const ok = await showConfirm('Hapus Komponen Mesin', `Apakah Anda yakin ingin menghapus "${comp.name}"? Semua data terkait (komponen, jadwal, riwayat) akan ikut terhapus.`);
  if (!ok) return;
  try {
    await axios.delete(`/api/components/${comp.id}`);
    await loadData();
    showAlert('success', 'Dihapus!', `Komponen "${comp.name}" berhasil dihapus.`);
  } catch (e) {
    showAlert('error', 'Gagal!', 'Gagal menghapus komponen: ' + (e.response?.data?.message || e.message));
  }
};

// Filter logic for component tab
const componentCategoryFilterOptions = computed(() => {
  const categories = new Set(machine.value?.components?.map(c => c.category) || []);
  const options = [{ value: 'all', label: 'Semua', count: machine.value?.components?.length || 0 }];
  
  categories.forEach(cat => {
    const count = machine.value?.components?.filter(c => c.category === cat).length || 0;
    options.push({ value: cat, label: cat, count });
  });
  
  return options;
});

const filteredComponents = computed(() => {
  let filtered = machine.value?.components || [];

  // Apply category filter
  if (componentCategoryFilter.value !== 'all') {
    filtered = filtered.filter(c => c.category === componentCategoryFilter.value);
  }

  // Apply search filter
  if (componentSearch.value) {
    const q = componentSearch.value.toLowerCase();
    filtered = filtered.filter(c => c.name.toLowerCase().includes(q));
  }

  return filtered;
});

// Machine edit functions
const openEditMachine = () => {
  if (!isManagerOrAdmin.value) return;
  showMachineEdit.value = true;
};

const onMachineSaved = async () => {
  showMachineEdit.value = false;
  await loadData();
  showAlert('success', 'Berhasil!', 'Data mesin berhasil diperbarui.');
};

const triggerComponentImport = () => {
  showComponentImportModal.value = true;
};

const loadSheetJS = () => {
  return new Promise((resolve) => {
    if (window.XLSX) {
      resolve(window.XLSX);
      return;
    }
    const script = document.createElement('script');
    script.src = 'https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js';
    script.onload = () => resolve(window.XLSX);
    document.head.appendChild(script);
  });
};

const downloadComponentTemplate = async () => {
  try {
    const XLSX = await loadSheetJS();
    const headers = [
      ['Kategori', 'Nama Komponen', 'Spesifikasi', 'Jumlah (Qty)', 'Satuan', 'Kondisi Awal (%)', 'Kesulitan (ringan/sedang/berat)']
    ];
    const rows = [
      ['Suku Cadang Utama', 'Piston Cylinder Boiler', 'Stainless Steel 316 100mm', 2, 'Pcs', 100, 'sedang'],
      ['Sensor & Kontrol', 'Thermostat Digital TC-40', 'Range -50C to 200C', 1, 'Unit', 90, 'ringan']
    ];

    const wb = XLSX.utils.book_new();
    const ws = XLSX.utils.aoa_to_sheet([...headers, ...rows]);

    ws['!cols'] = [
      { wch: 20 }, // Kategori
      { wch: 25 }, // Nama Komponen
      { wch: 30 }, // Spesifikasi
      { wch: 15 }, // Jumlah (Qty)
      { wch: 15 }, // Satuan
      { wch: 20 }, // Kondisi Awal (%)
      { wch: 25 }, // Kesulitan
    ];

    XLSX.utils.book_append_sheet(wb, ws, 'Template Import Komponen');
    XLSX.writeFile(wb, 'Format_Import_Komponen.xlsx');
  } catch (err) {
    console.error('Template download failed:', err);
    showAlert('error', 'Gagal!', 'Gagal mendownload template Excel.');
  }
};

const importComponents = async (file) => {
  if (!file) return;
  importingComponents.value = true;
  try {
    const XLSX = await loadSheetJS();
    const reader = new FileReader();
    
    reader.onload = async (e) => {
      try {
        const data = new Uint8Array(e.target.result);
        const workbook = XLSX.read(data, { type: 'array' });
        
        const firstSheetName = workbook.SheetNames[0];
        const worksheet = workbook.Sheets[firstSheetName];
        
        const rows = XLSX.utils.sheet_to_json(worksheet, { header: 1 });
        if (rows.length < 2) {
          showAlert('error', 'Gagal!', 'File Excel kosong atau tidak memiliki baris data.');
          importingComponents.value = false;
          return;
        }
        
        const mappedComponents = [];
        for (let i = 1; i < rows.length; i++) {
          const row = rows[i];
          if (row.length === 0 || !row[0]) continue;

          mappedComponents.push({
            category: row[0]?.toString()?.trim() || '',
            name: row[1]?.toString()?.trim() || '',
            specification: row[2]?.toString()?.trim() || null,
            qty: parseInt(row[3]) || 1,
            unit: row[4]?.toString()?.trim() || 'Pcs',
            last_condition_pct: parseFloat(row[5]) || 100,
            difficulty: row[6]?.toString()?.trim() || null,
          });
        }
        
        if (mappedComponents.length === 0) {
          showAlert('error', 'Gagal!', 'Tidak menemukan baris data komponen yang valid.');
          importingComponents.value = false;
          return;
        }
        
        const res = await axios.post(`/api/machines/${machineId.value}/components/import`, { components: mappedComponents });
        showAlert('success', 'Berhasil!', res.data.message || `Berhasil mengimpor ${mappedComponents.length} komponen.`);
        showComponentImportModal.value = false;
        await loadData();
      } catch (err) {
        console.error('File parsing/import failed:', err);
        showAlert('error', 'Gagal!', 'Gagal memproses file: ' + (err.response?.data?.message || err.message));
      } finally {
        importingComponents.value = false;
      }
    };
    
    reader.readAsArrayBuffer(file);
  } catch (err) {
    console.error('Import failed:', err);
    showAlert('error', 'Gagal!', 'Terjadi kesalahan sistem.');
    importingComponents.value = false;
  }
};
</script>
