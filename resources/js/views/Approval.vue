<template>
  <div class="space-y-6">
    <!-- Header -->
    <PageHeader
    title="Approval Laporan"
      :subtitle="isApproverUser ? 'Review dan approve laporan maintenance dari teknisi' : 'Status laporan maintenance yang sudah kamu kirimkan'"
    />

    <!-- Stats (Approver only) -->
    <div v-if="isApproverUser" class="grid grid-cols-2 md:grid-cols-4 gap-4">
      <StatCard :value="pendingCount"  label="Menunggu Approval" color="amber" />
      <StatCard :value="approvedCount" label="Disetujui"         color="green" />
      <StatCard :value="rejectedCount" label="Ditolak"           color="red" />
      <StatCard :value="items.length"  label="Total Report"      color="slate" />
    </div>

    <!-- Search & Filter -->
    <div class="flex flex-col md:flex-row gap-4 items-center justify-between">
      <SearchInput
        v-model="searchQuery"
        placeholder="Cari mesin, teknisi..."
      />
      <div class="flex items-center gap-3">
        <FilterTabs v-model="activeFilter" :tabs="filterTabsWithCount" />
        <button
          v-if="isAdmin"
          @click="openFlowConfig"
          class="px-3 py-2 bg-white border border-slate-200 hover:border-indigo-400 text-slate-600 hover:text-indigo-600 rounded-xl text-xs font-bold transition-all cursor-pointer flex items-center gap-1.5"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37 1 .608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
          Alur Approval
        </button>
      </div>
    </div>

    <!-- Main Table -->
    <DataTable
      :columns="approvalColumns"
      :rows="paginatedItems"
      :loading="loading"
      loading-text="Memuat data approval..."
      loading-subtext="Mengambil laporan dari server"
      empty-title="Tidak ada laporan ditemukan"
      empty-subtext="Coba sesuaikan filter atau pencarian."
      min-width="min-w-[800px]"
      :paginate="false"
      actions-align="right"
      actions-width="w-[12%]"
    >
      <!-- Kolom: Mesin & Waktu -->
      <template #cell-machine_name="{ row }">
        <p class="font-semibold text-slate-800 text-sm cursor-pointer hover:text-indigo-600 transition-colors" @click="goToMachine(row.machine_id)">{{ row.machine_name }}</p>
        <p class="text-xs text-slate-400 mt-0.5">{{ formatDateTime(row.maintenance_date) }}</p>
        <p v-if="row.duration_minutes" class="text-xs text-brand-gradation font-semibold mt-0.5">
          Durasi: {{ formatDuration(row.duration_minutes) }}
          <span v-if="row.start_time || row.end_time" class="text-slate-400 font-normal">({{ formatTime(row.start_time) }} - {{ formatTime(row.end_time) }})</span>
        </p>
        <p v-if="row.notes" class="text-xs text-slate-500 mt-1 italic line-clamp-1">{{ row.notes }}</p>
      </template>

      <!-- Kolom: Teknisi -->
      <template #cell-technician_name="{ row }">
        <div class="flex items-center gap-2">
          <div class="w-7 h-7 rounded-full bg-linear-to-tr from-brand-brown to-brand-gradation flex items-center justify-center text-white text-[10px] font-bold shrink-0">
            {{ initials(row.technician_name) }}
          </div>
          <span class="text-sm text-slate-700">{{ row.technician_name ?? '-' }}</span>
        </div>
      </template>

      <!-- Kolom: Detail Tindakan -->
      <template #cell-actions_summary="{ row }">
        <div class="space-y-1.5">
          <div class="flex items-center gap-1.5 flex-wrap">
            <span class="text-xs font-semibold px-2 py-0.5 bg-slate-100 text-slate-700 rounded-md">{{ row.actions.length }} Tindakan</span>
            <span v-if="getReplaceCount(row.actions) > 0" class="text-xs font-semibold px-2 py-0.5 bg-red-50 text-red-700 border border-red-100 rounded-md">
              {{ getReplaceCount(row.actions) }} Ganti
            </span>
            <span v-if="getInspectCount(row.actions) > 0" class="text-xs font-semibold px-2 py-0.5 bg-blue-50 text-blue-700 border border-blue-100 rounded-md">
              {{ getInspectCount(row.actions) }} Inspeksi
            </span>
          </div>
          <button
            @click="openDetails(row)"
            class="text-indigo-600 hover:text-indigo-800 text-xs font-bold flex items-center gap-1 transition-colors cursor-pointer pt-0.5"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
            Lihat Laporan ({{ row.actions.length }})
          </button>
        </div>
      </template>

      <!-- Kolom: Status -->
      <template #cell-approval_status="{ row }">
        <div class="space-y-2">
          <span :class="statusBadgeClass(row.approval_status)" class="inline-flex items-center gap-1.5 text-xs font-semibold px-3 py-1 rounded-full">
            <span class="w-1.5 h-1.5 rounded-full" :class="statusDotClass(row.approval_status)"></span>
            {{ statusLabel(row.approval_status) }}
          </span>
          <ApprovalProgress
            :flow-steps="row.flow_steps || []"
            :current-step="row.current_step || 1"
            :completed-steps="row.completed_steps || 0"
            :total-steps="row.total_steps || 0"
            :status="row.approval_status"
            :pending-role="row.pending_role"
          />
          <p v-if="row.approval_status === 'approved' && row.approved_by" class="text-[10px] text-slate-400">oleh {{ row.approved_by }} • {{ formatDateTime(row.decided_at) }}</p>
          <p v-if="row.approval_status === 'rejected' && row.approved_by" class="text-[10px] text-red-400">oleh {{ row.approved_by }} • {{ formatDateTime(row.decided_at) }}</p>
          <div v-if="row.approval_notes && row.approval_status === 'rejected'" class="mt-1 bg-red-50 border border-red-100 rounded-lg px-2 py-1.5">
            <p class="text-[10px] font-semibold text-red-600">Alasan:</p>
            <p class="text-[10px] text-red-700 italic line-clamp-3">{{ row.approval_notes }}</p>
          </div>
          <p v-else-if="row.approval_notes" class="text-[10px] text-amber-600 italic line-clamp-2">{{ row.approval_notes }}</p>
        </div>
      </template>

      <!-- Slot Aksi -->
      <template #actions="{ row }">
        <div v-if="canDecide(row)" class="flex items-center justify-end gap-1.5">
          <Button
            size="sm"
            @click="openDecide(row, 'approved')"
            class="rounded-lg bg-green-500 hover:bg-green-600 text-white text-xs font-semibold h-auto py-1.5 px-3"
          >Setujui</Button>
          <Button
            size="sm"
            @click="openDecide(row, 'rejected')"
            class="rounded-lg bg-red-500 hover:bg-red-600 text-white text-xs font-semibold h-auto py-1.5 px-3"
          >Tolak</Button>
        </div>
        <Button
          v-else-if="row.approval_status === 'rejected' && row.technician_id === currentUser?.id"
          size="sm"
          @click="goToMachine(row.machine_id)"
          class="rounded-lg bg-amber-500 hover:bg-amber-600 text-white text-xs font-semibold h-auto py-1.5 px-3 flex items-center gap-1.5"
        >
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
          Cek Ulang
        </Button>
      </template>
    </DataTable>

    <!-- Pagination -->
    <TablePagination
      v-if="!loading"
      v-model="currentPage"
      :total="filteredItems.length"
      :per-page="perPage"
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

  <!-- Approval Flow Config Modal -->
  <ApprovalFlowConfigModal
    :show="flowConfigModal.show"
    :steps="flowConfigModal.steps"
    :roles="roles"
    :loading="flowConfigModal.loading"
    @close="flowConfigModal.show = false"
    @save="saveFlowConfig"
    @manage-roles="openRoleConfig"
  />

  <!-- Role Config Modal -->
  <RoleConfigModal
    :show="roleConfigModal.show"
    :roles="roles"
    :loading="roleConfigModal.loading"
    @close="roleConfigModal.show = false"
    @save="saveRole"
    @delete="deleteRole"
  />
</template>

<script setup>
import { ref, computed, onMounted, watch, watchEffect } from 'vue';
import axios from 'axios';
import { router } from '@inertiajs/vue3';
import { useAuth } from '../composables/useAuth.js';
import PageHeader from '../components/PageHeader.vue';
import SearchInput from '../components/SearchInput.vue';
import FilterTabs from '../components/FilterTabs.vue';
import DataTable from '../components/DataTable.vue';
import TablePagination from '../components/TablePagination.vue';
import StatCard from '../components/StatCard.vue';
import Button from '../../views/components/ui/button/Button.vue';
import ApprovalDecideModal from '../components/ApprovalDecideModal.vue';
import ApprovalDetailModal from '../components/ApprovalDetailModal.vue';
import ApprovalProgress from '../components/ApprovalProgress.vue';
import ApprovalFlowConfigModal from '../components/ApprovalFlowConfigModal.vue';
import RoleConfigModal from '../components/RoleConfigModal.vue';
import { showAlert } from '../composables/useAlert.js';

const loading = ref(true);
const deciding = ref(false);
const items = ref([]);
const activeFilter = ref('all');
const searchQuery = ref('');
const currentPage = ref(1);
const perPage = 10;

const approvalColumns = [
  { key: 'machine_name',     label: 'Mesin & Waktu',    width: 'w-[22%]', cellClass: 'align-top' },
  { key: 'technician_name',  label: 'Teknisi',           width: 'w-[18%]', cellClass: 'align-top' },
  { key: 'actions_summary',  label: 'Detail Tindakan',   width: 'w-[30%]', cellClass: 'align-top' },
  { key: 'approval_status',  label: 'Status',            width: 'w-[20%]', cellClass: 'align-top' },
];

const { isAdmin, user: currentUser, authReady } = useAuth();

const decideModal = ref({ show: false, item: null, decision: 'approved', notes: '' });
const detailModal = ref({ show: false, item: null });
const flowConfigModal = ref({ show: false, steps: [], loading: false });
const roleConfigModal = ref({ show: false, loading: false });
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

const openDecideFromDetail = (decision) => {
  if (!detailModal.value.item) return;
  openDecide(detailModal.value.item, decision);
};

const loadData = async () => {
  loading.value = true;
  try {
    const [approvalsRes, rolesRes] = await Promise.all([
      axios.get('/api/approvals'),
      axios.get('/api/roles'),
    ]);
    items.value = Array.isArray(approvalsRes.data) ? approvalsRes.data : [];
    roles.value = Array.isArray(rolesRes.data) ? rolesRes.data : [];
  } catch (e) {
    console.error(e);
    showAlert('error', 'Gagal Memuat', e.response?.data?.message || 'Terjadi kesalahan saat memuat data.');
    items.value = [];
    roles.value = [];
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
  return list;
});

const paginatedItems = computed(() => {
  const start = (currentPage.value - 1) * perPage;
  return filteredItems.value.slice(start, start + perPage);
});

watch([activeFilter, searchQuery], () => { currentPage.value = 1; });

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

const openFlowConfig = async () => {
  flowConfigModal.value.loading = true;
  flowConfigModal.value.show = true;
  try {
    const res = await axios.get('/api/approval-flow');
    flowConfigModal.value.steps = res.data.steps.map((s, idx) => ({
      ...s,
      step_order: idx + 1,
    }));
  } catch (e) {
    showAlert('error', 'Gagal Memuat', e.response?.data?.message || 'Gagal memuat konfigurasi alur approval.');
    flowConfigModal.value.show = false;
  } finally {
    flowConfigModal.value.loading = false;
  }
};

const saveFlowConfig = async (steps) => {
  flowConfigModal.value.loading = true;
  try {
    await axios.put('/api/approval-flow', { steps: steps.map(s => ({ role: s.role })) });
    flowConfigModal.value.show = false;
    showAlert('success', 'Berhasil', 'Konfigurasi alur approval berhasil disimpan.');
    await loadData();
  } catch (e) {
    showAlert('error', 'Gagal Menyimpan', e.response?.data?.message || e.message);
  } finally {
    flowConfigModal.value.loading = false;
  }
};

const openRoleConfig = () => {
  roleConfigModal.value.show = true;
};

const saveRole = async (role) => {
  roleConfigModal.value.loading = true;
  try {
    if (role.id) {
      await axios.put(`/api/roles/${role.id}`, role);
    } else {
      await axios.post('/api/roles', role);
    }
    await loadData();
    roleConfigModal.value.loading = false;
    showAlert('success', 'Berhasil', `Role ${role.display_name} berhasil disimpan.`);
  } catch (e) {
    roleConfigModal.value.loading = false;
    showAlert('error', 'Gagal Menyimpan', e.response?.data?.message || e.message);
  }
};

const deleteRole = async (role) => {
  roleConfigModal.value.loading = true;
  try {
    await axios.delete(`/api/roles/${role.id}`);
    await loadData();
    roleConfigModal.value.loading = false;
    showAlert('success', 'Berhasil', `Role ${role.display_name} berhasil dihapus.`);
  } catch (e) {
    roleConfigModal.value.loading = false;
    showAlert('error', 'Gagal Menghapus', e.response?.data?.message || e.message);
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
