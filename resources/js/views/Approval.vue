<template>
  <div class="w-full max-w-5xl mx-auto space-y-6">
    <!-- Header -->
    <PageHeader
    title="Approval Laporan"
      :subtitle="isApproverUser ? 'Review dan approve laporan maintenance dari teknisi' : 'Status laporan maintenance yang sudah kamu kirimkan'"
    />

    <!-- Admin Tabs -->
    <div v-if="isAdmin" class="border-b border-slate-200">
      <nav class="flex gap-6 -mb-px">
        <button
          @click="currentTab = 'list'"
          :class="currentTab === 'list' ? 'border-indigo-600 text-indigo-600 font-bold' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300'"
          class="pb-3 border-b-2 text-sm font-semibold transition-all cursor-pointer bg-transparent border-0"
        >
          Daftar Approval
        </button>
        <button
          @click="currentTab = 'flow'"
          :class="currentTab === 'flow' ? 'border-indigo-600 text-indigo-600 font-bold' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300'"
          class="pb-3 border-b-2 text-sm font-semibold transition-all cursor-pointer bg-transparent border-0"
        >
          Alur Approval
        </button>
        <button
          @click="currentTab = 'roles'"
          :class="currentTab === 'roles' ? 'border-indigo-600 text-indigo-600 font-bold' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300'"
          class="pb-3 border-b-2 text-sm font-semibold transition-all cursor-pointer bg-transparent border-0"
        >
          Kelola Role
        </button>
      </nav>
    </div>

    <ApprovalFlowPanel
      v-if="currentTab === 'flow'"
      v-model:active-reporter-role="activeReporterRole"
      :flow-edit-mode="flowEditMode"
      :reporter-roles="reporterRoles"
      :approvable-roles="approvableRoles"
      :flow-steps-for-reporter="getFlowStepsForReporter(activeReporterRole)"
      :active-reporter-display-name="activeReporterDisplayName"
      :is-flow-config-valid="isFlowConfigValid"
      :saving-flow-config="savingFlowConfig"
      @edit="flowEditMode = true"
      @cancel="cancelFlowEdit"
      @save="saveFlowConfig"
      @add-step="addStepForReporter(activeReporterRole)"
      @remove-step="(idx) => removeStepForReporter(activeReporterRole, idx)"
      @initialize-default="initializeWithDefaultSteps(activeReporterRole)"
      @update-step-role="({ index, value }) => getFlowStepsForReporter(activeReporterRole)[index].role = value"
    />

    <ApprovalRolesPanel
      v-else-if="currentTab === 'roles'"
      v-model:new-role="newRole"
      :role-edit-mode="roleEditMode"
      :local-roles="localRoles"
      :difficulty-options="difficultyOptions"
      :role-config-loading="roleConfigLoading"
      :is-new-role-valid="isNewRoleValid"
      :is-role-names-valid="isRoleNamesValid"
      :is-core-system-role="isCoreSystemRole"
      :is-system-role="isSystemRole"
      @edit="roleEditMode = true"
      @cancel="cancelRoleEdit"
      @save="saveAllRoles"
      @add-role="addNewRole"
      @delete-role="confirmDeleteRole"
      @toggle-difficulty="({ role, diffValue }) => toggleDifficulty(role, diffValue)"
      @update-role-name="({ role, value }) => role.name = value"
      @update-role-display-name="({ role, value }) => role.display_name = value"
      @toggle-can-approve="({ role, value }) => role.can_approve = value"
      @toggle-can-report="({ role, value }) => role.can_report = value"
    />

    <ApprovalListPanel
      v-else
      v-model:search-query="searchQuery"
      v-model:active-filter="activeFilter"
      v-model:sort-order="sortOrder"
      v-model:current-page="currentPage"
      v-model:per-page="perPage"
      :items="items"
      :loading="loading"
      :is-approver-user="isApproverUser"
      :current-user="currentUser"
      :filter-tabs="filterTabsWithCount"
      :filtered-items="filteredItems"
      :paginated-items="paginatedItems"
      :can-decide="canDecide"
      :format-date-time="formatDateTime"
      :format-duration="formatDuration"
      :format-time="formatTime"
      :get-replace-count="getReplaceCount"
      :get-inspect-count="getInspectCount"
      :status-badge-class="statusBadgeClass"
      :status-dot-class="statusDotClass"
      :status-label="statusLabel"
      :initials="initials"
      :go-to-machine="goToMachine"
      :go-to-report="goToReport"
      :approval-columns="approvalColumns"
      :pending-count="pendingCount"
      :approved-count="approvedCount"
      :rejected-count="rejectedCount"
      @open-details="openDetails"
      @open-notes="openNotes"
      @open-decide="({ item, decision }) => openDecide(item, decision)"
    />
  </div>

  <!-- Decide Modal -->
  <ApprovalDecideModal
    :show="decideModal.show"
    :item="decideModal.item"
    :decision="decideModal.decision"
    :loading="deciding"
    @close="decideModal.show = false"
    @submit="submitDecision"
  />

  <!-- Detail Actions Modal -->
  <ApprovalDetailModal
    :show="detailModal.show"
    :item="detailModal.item"
    :can-decide="detailCanDecide"
    @close="detailModal.show = false"
    @decide="openDecideFromDetail"
  />

  <!-- Approval Notes Modal -->
  <ApprovalNotesModal
    :show="notesModal.show"
    :item="notesModal.item"
    @close="notesModal.show = false"
  />

</template>

<script setup>
import { ref, computed, onMounted, watch, watchEffect } from 'vue';
import axios from 'axios';
import { router } from '@inertiajs/vue3';
import { useAuth } from '../composables/useAuth.js';
import PageHeader from '../components/PageHeader.vue';
import Button from '../../views/components/ui/button/Button.vue';
import ApprovalDecideModal from '../components/ApprovalDecideModal.vue';
import ApprovalDetailModal from '../components/ApprovalDetailModal.vue';
import ApprovalNotesModal from '../components/ApprovalNotesModal.vue';
import ApprovalListPanel from '../components/ApprovalListPanel.vue';
import ApprovalFlowPanel from '../components/ApprovalFlowPanel.vue';
import ApprovalRolesPanel from '../components/ApprovalRolesPanel.vue';
import { showAlert, showConfirm } from '../composables/useAlert.js';

const loading = ref(true);
const deciding = ref(false);
const items = ref([]);
const activeFilter = ref('all');
const searchQuery = ref('');
const sortOrder = ref('newest');
const currentPage = ref(1);
const perPage = ref(5);

const currentTab = ref('list'); // 'list', 'flow', or 'roles'
const activeReporterRole = ref('technician');
const localFlowSteps = ref([]);
const savingFlowConfig = ref(false);
const flowEditMode = ref(false);
const flowStepsBackup = ref([]);

const localRoles = ref([]);
const rolesBackup = ref([]);
const newRole = ref({ name: '', display_name: '', can_approve: true, can_report: false, is_active: true });
const roleConfigLoading = ref(false);
const roleEditMode = ref(false);

const difficultyOptions = [
  { value: 'berat', label: 'Berat' },
  { value: 'sedang', label: 'Sedang' },
  { value: 'ringan', label: 'Ringan' },
  { value: 'none', label: 'Tanpa Kategori' },
];

const approvalColumns = [
  { key: 'machine_name',     label: 'Mesin & Waktu',    width: 'w-[30%]', cellClass: 'align-top break-words' },
  { key: 'actions_summary',  label: 'Tindakan',          width: 'w-[28%]', cellClass: 'align-top break-words' },
  { key: 'approval_status',  label: 'Status',            width: 'w-[26%]', cellClass: 'align-top break-words' },
];

const { isAdmin, user: currentUser, authReady } = useAuth();

const decideModal = ref({ show: false, item: null, decision: 'approved', notes: '' });
const detailModal = ref({ show: false, item: null });
const notesModal = ref({ show: false, item: null });
const roles = ref([]);

const getReplaceCount = (actions) => {
  if (!actions) return 0;
  return actions.filter(a => a.action_type === 'replace').length;
};

const getInspectCount = (actions) => {
  if (!actions) return 0;
  return actions.filter(a => a.action_type !== 'replace').length;
};

const openDetails = (item) => {
  detailModal.value = { show: true, item };
};

const openNotes = (item) => {
  notesModal.value = { show: true, item };
};

const openDecideFromDetail = (decision) => {
  if (!detailModal.value.item) return;
  openDecide(detailModal.value.item, decision);
};

const loadData = async () => {
  loading.value = true;
  try {
    const [approvalsRes, rolesRes, flowConfigRes] = await Promise.all([
      axios.get('/api/approvals'),
      axios.get('/api/roles'),
      axios.get('/api/approval-flow'),
    ]);
    items.value = Array.isArray(approvalsRes.data) ? approvalsRes.data : [];
    roles.value = Array.isArray(rolesRes.data) ? rolesRes.data : [];
    localRoles.value = roles.value.map(r => ({ ...r, required_difficulties: r.required_difficulties || [] }));
    rolesBackup.value = roles.value.map(r => ({ ...r, required_difficulties: [...(r.required_difficulties || [])] }));
    localFlowSteps.value = Array.isArray(flowConfigRes.data?.steps) ? flowConfigRes.data.steps.map(s => ({ ...s })) : [];
    flowStepsBackup.value = localFlowSteps.value.map(s => ({ ...s }));
  } catch (e) {
    console.error(e);
    showAlert('error', 'Gagal Memuat', e.response?.data?.message || 'Terjadi kesalahan saat memuat data.');
    items.value = [];
    roles.value = [];
    localRoles.value = [];
    localFlowSteps.value = [];
  } finally {
    loading.value = false;
  }
};

onMounted(() => {
  if (authReady.value) loadData();
});

watch(authReady, (ready) => {
  if (ready && items.value.length === 0 && !loading.value) loadData();
});

const approvingRoleNames = computed(() => roles.value.filter(r => r.can_approve).map(r => r.name));
const approvableRoles = computed(() => roles.value.filter(r => r.can_approve && r.is_active));
const isApproverUser = computed(() => {
  const role = currentUser.value?.role;
  return role === 'admin' || approvingRoleNames.value.includes(role);
});

const myPendingItems = computed(() => {
  const role = currentUser.value?.role;
  if (role === 'admin') return items.value.filter(i => i.approval_status === 'pending');
  if (!isApproverUser.value) return items.value.filter(i => i.approval_status === 'pending');
  return items.value.filter(i => i.approval_status === 'pending' && i.pending_role === role);
});
const pendingCount  = computed(() => myPendingItems.value.length);
const approvedCount = computed(() => items.value.filter(i => i.approval_status === 'approved').length);
const rejectedCount = computed(() => items.value.filter(i => i.approval_status === 'rejected').length);

const detailCanDecide = computed(() => canDecide(detailModal.value.item));

const filterTabsWithCount = computed(() => [
  { value: 'all',      label: 'Semua',    count: items.value.length },
  { value: 'pending',  label: isApproverUser.value ? 'Menunggu Anda' : 'Menunggu', count: pendingCount.value },
  { value: 'approved', label: 'Disetujui', count: approvedCount.value },
  { value: 'rejected', label: 'Ditolak',  count: rejectedCount.value },
]);

const filteredItems = computed(() => {
  let list = items.value;
  if (activeFilter.value === 'pending') {
    list = myPendingItems.value;
  } else if (activeFilter.value !== 'all') {
    list = list.filter(i => i.approval_status === activeFilter.value);
  }
  if (searchQuery.value.trim()) {
    const q = searchQuery.value.toLowerCase();
    list = list.filter(i =>
      (i.machine_name && i.machine_name.toLowerCase().includes(q)) ||
      (i.technician_name && i.technician_name.toLowerCase().includes(q))
    );
  }

  const newestFirst = (a, b) => new Date(b.created_at ?? 0) - new Date(a.created_at ?? 0);
  if (sortOrder.value === 'oldest') {
    return [...list].sort((a, b) => -newestFirst(a, b));
  }
  if (sortOrder.value === 'pending_first') {
    return [...list].sort((a, b) => {
      const statusDiff = Number(a.approval_status !== 'pending') - Number(b.approval_status !== 'pending');
      return statusDiff || newestFirst(a, b);
    });
  }
  return [...list].sort(newestFirst);
});

const paginatedItems = computed(() => {
  const start = (currentPage.value - 1) * perPage.value;
  return filteredItems.value.slice(start, start + perPage.value);
});

watch([activeFilter, searchQuery, sortOrder, perPage], () => { currentPage.value = 1; });

const openDecide = (item, decision) => {
  decideModal.value = { show: true, item, decision };
};

const submitDecision = async (notes) => {
  deciding.value = true;
  try {
    await axios.post(`/api/approvals/${decideModal.value.item.record_id}/decide`, {
      decision: decideModal.value.decision,
      notes: notes || null,
    });
    decideModal.value.show = false;
    detailModal.value.show = false;
    await loadData();
  } catch (e) {
    showAlert('error', 'Gagal Memproses', e.response?.data?.message || e.message);
  } finally {
    deciding.value = false;
  }
};

const canDecide = (item) => {
  if (!item || !item.approval_status || !item.pending_role) return false;
  const role = currentUser.value?.role;
  if (role === 'admin') return true;
  if (!approvingRoleNames.value.includes(role)) return false;
  return item.approval_status === 'pending' && role === item.pending_role;
};

const reporterRoles = computed(() => {
  const list = roles.value.filter(r => r.is_active && r.can_report);
  if (!list.some(r => r.name === 'technician')) {
    const tech = roles.value.find(r => r.name === 'technician');
    if (tech) list.unshift(tech);
  }
  return list;
});

const getFlowStepsForReporter = (reporterRole) => {
  return localFlowSteps.value.filter(s => s.reporter_role === reporterRole);
};

const addStepForReporter = (reporterRole) => {
  const steps = getFlowStepsForReporter(reporterRole);
  if (steps.length < 10) {
    localFlowSteps.value.push({
      reporter_role: reporterRole,
      role: '',
      step_order: steps.length + 1,
      is_active: true
    });
  }
};

const removeStepForReporter = (reporterRole, indexInGroup) => {
  let count = 0;
  const globalIndex = localFlowSteps.value.findIndex(s => {
    if (s.reporter_role === reporterRole) {
      if (count === indexInGroup) return true;
      count++;
    }
    return false;
  });
  
  if (globalIndex !== -1) {
    localFlowSteps.value.splice(globalIndex, 1);
    let newOrder = 1;
    localFlowSteps.value.forEach(s => {
      if (s.reporter_role === reporterRole) {
        s.step_order = newOrder++;
      }
    });
  }
};

const initializeWithDefaultSteps = (reporterRole) => {
  localFlowSteps.value.push({
    reporter_role: reporterRole,
    role: '',
    step_order: 1,
    is_active: true
  });
};

const activeReporterDisplayName = computed(() => {
  const role = roles.value.find(r => r.name === activeReporterRole.value);
  return role ? (role.display_name || role.name) : activeReporterRole.value;
});

const isFlowConfigValid = computed(() => {
  return localFlowSteps.value.length > 0 && localFlowSteps.value.every(s => s.role);
});

const cancelFlowEdit = () => {
  localFlowSteps.value = flowStepsBackup.value.map(s => ({ ...s }));
  flowEditMode.value = false;
};

const saveFlowConfig = async () => {
  if (!isFlowConfigValid.value) return;
  savingFlowConfig.value = true;
  try {
    await axios.put('/api/approval-flow', {
      steps: localFlowSteps.value.map(s => ({
        reporter_role: s.reporter_role,
        role: s.role
      }))
    });
    showAlert('success', 'Berhasil', 'Alur approval berhasil disimpan.');
    flowEditMode.value = false;
    await loadData();
  } catch (e) {
    showAlert('error', 'Gagal Menyimpan', e.response?.data?.message || e.message);
  } finally {
    savingFlowConfig.value = false;
  }
};

const isNewRoleValid = computed(() => {
  return roles.value.every(r => r.name !== newRole.value.name) &&
    /^[a-z0-9_]+$/.test(newRole.value.name) &&
    newRole.value.display_name.trim().length > 0;
});

const isCoreSystemRole = (role) => {
  return ['admin', 'technician'].includes(role.name);
};

const isSystemRole = (role) => {
  return isCoreSystemRole(role) || role.is_manager;
};

const isRoleNamesValid = computed(() => {
  const names = localRoles.value.map(r => r.name.trim().toLowerCase());
  if (new Set(names).size !== names.length) return false;
  return localRoles.value.every(r => {
    const name = r.name.trim();
    if (!name) return false;
    if (!/^[a-z0-9_]+$/.test(name)) return false;
    const originalName = rolesBackup.value.find(rb => rb.id === r.id)?.name;
    if (isCoreSystemRole(r) && name !== originalName) return false;
    return true;
  });
});

const toggleDifficulty = (role, diffValue) => {
  if (!role.required_difficulties) role.required_difficulties = [];
  const idx = role.required_difficulties.indexOf(diffValue);
  if (idx === -1) {
    role.required_difficulties.push(diffValue);
  } else {
    role.required_difficulties.splice(idx, 1);
  }
};

const addNewRole = async () => {
  if (!isNewRoleValid.value) return;
  roleConfigLoading.value = true;
  try {
    await axios.post('/api/roles', { ...newRole.value });
    newRole.value = { name: '', display_name: '', can_approve: true, can_report: false, is_active: true };
    await loadData();
    showAlert('success', 'Berhasil', 'Role baru berhasil ditambahkan.');
  } catch (e) {
    showAlert('error', 'Gagal Menyimpan', e.response?.data?.message || e.message);
  } finally {
    roleConfigLoading.value = false;
  }
};

const cancelRoleEdit = () => {
  localRoles.value = rolesBackup.value.map(r => ({ ...r, required_difficulties: [...(r.required_difficulties || [])] }));
  roleEditMode.value = false;
};

const saveAllRoles = async () => {
  roleConfigLoading.value = true;
  try {
    await axios.put('/api/roles/bulk', {
      roles: localRoles.value.map(r => ({
        id: r.id,
        name: r.name,
        display_name: r.display_name,
        can_approve: r.can_approve,
        can_report: r.can_report,
        required_difficulties: r.required_difficulties || [],
      }))
    });
    showAlert('success', 'Berhasil', 'Semua role berhasil disimpan.');
    roleEditMode.value = false;
    await loadData();
  } catch (e) {
    showAlert('error', 'Gagal Menyimpan', e.response?.data?.message || e.message);
  } finally {
    roleConfigLoading.value = false;
  }
};

const confirmDeleteRole = async (role) => {
  const confirmed = await showConfirm('Hapus Role?', `Yakin ingin menghapus role ${role.display_name}?`);
  if (!confirmed) return;
  roleConfigLoading.value = true;
  try {
    await axios.delete(`/api/roles/${role.id}`);
    await loadData();
    showAlert('success', 'Berhasil', `Role ${role.display_name} berhasil dihapus.`);
  } catch (e) {
    showAlert('error', 'Gagal Menghapus', e.response?.data?.message || e.message);
  } finally {
    roleConfigLoading.value = false;
  }
};

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
  return new Date(d).toLocaleString('id-ID', { day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' });
};

const goToMachine = (machineId) => {
  if (machineId) router.visit(`/machine/${machineId}`);
};

const goToReport = (machineId) => {
  if (machineId) router.visit(`/report?machine=${machineId}`);
};

const initials = (name) => {
  if (!name) return '?';
  return name.split(' ').slice(0, 2).map(w => w[0]).join('').toUpperCase();
};

const statusLabel = (s) => ({ pending: 'Menunggu', approved: 'Disetujui', rejected: 'Ditolak' }[s] ?? s);

const statusBadgeClass = (s) => ({
  pending:  'bg-amber-100 text-amber-700',
  approved: 'bg-green-100 text-green-700',
  rejected: 'bg-red-100 text-red-700',
}[s] ?? 'bg-slate-100 text-slate-600');

const statusDotClass = (s) => ({
  pending:  'bg-amber-500',
  approved: 'bg-green-500',
  rejected: 'bg-red-500',
}[s] ?? 'bg-slate-400');
</script>
