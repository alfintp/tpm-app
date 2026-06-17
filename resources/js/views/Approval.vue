<template>
  <div class="space-y-6">
    <!-- Header -->
    <PageHeader
    title="Approval Laporan"
      :subtitle="isManagerOrAdmin ? 'Review dan approve laporan maintenance dari teknisi' : 'Status laporan maintenance yang sudah kamu kirimkan'"
    />

    <!-- Stats (Manager/Admin only) -->
    <div v-if="isManagerOrAdmin" class="grid grid-cols-2 md:grid-cols-4 gap-4">
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
      <FilterTabs v-model="activeFilter" :tabs="filterTabsWithCount" />
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
        <p class="font-semibold text-slate-800 text-sm">{{ row.machine_name }}</p>
        <p class="text-xs text-slate-400 mt-0.5">{{ formatDateTime(row.maintenance_date) }}</p>
        <p v-if="row.notes" class="text-xs text-slate-500 mt-1 italic line-clamp-1">{{ row.notes }}</p>
      </template>

      <!-- Kolom: Teknisi -->
      <template #cell-technician_name="{ row }">
        <div class="flex items-center gap-2">
          <div class="w-7 h-7 rounded-full bg-gradient-to-tr from-brand-brown to-brand-gradation flex items-center justify-center text-white text-[10px] font-bold flex-shrink-0">
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
        <span :class="statusBadgeClass(row.approval_status)" class="inline-flex items-center gap-1.5 text-xs font-semibold px-3 py-1 rounded-full">
          <span class="w-1.5 h-1.5 rounded-full" :class="statusDotClass(row.approval_status)"></span>
          {{ statusLabel(row.approval_status) }}
        </span>
        <p v-if="row.approved_by"    class="text-[10px] text-slate-400 mt-1">oleh {{ row.approved_by }}</p>
        <p v-if="row.decided_at"     class="text-[10px] text-slate-400">{{ formatDateTime(row.decided_at) }}</p>
        <p v-if="row.approval_notes" class="text-[10px] text-amber-600 mt-1 italic line-clamp-2">{{ row.approval_notes }}</p>
      </template>

      <!-- Slot Aksi (Manager/Admin only) -->
      <template v-if="isManagerOrAdmin" #actions="{ row }">
        <div class="flex items-center justify-end gap-1.5">
          <template v-if="row.approval_status === 'pending'">
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
          </template>
          <template v-else>
            <Button
              variant="outline" size="sm"
              @click="openDecide(row, row.approval_status)"
              class="rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-600 text-xs font-semibold h-auto py-1.5 border-slate-200"
            >Ubah</Button>
          </template>
        </div>
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
    :notes="decideModal.notes"
    :loading="deciding"
    @close="decideModal.show = false"
    @update:notes="decideModal.notes = $event"
    @submit="submitDecision"
  />

  <!-- Detail Actions Modal -->
  <ApprovalDetailModal
    :show="detailModal.show"
    :item="detailModal.item"
    :can-decide="isManagerOrAdmin"
    @close="detailModal.show = false"
    @decide="openDecideFromDetail"
  />
</template>

<script setup>
import { ref, computed, onMounted, watch, watchEffect } from 'vue';
import axios from 'axios';
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

const { isManagerOrAdmin, user: currentUser, authReady } = useAuth();

const decideModal = ref({ show: false, item: null, decision: 'approved', notes: '' });
const detailModal = ref({ show: false, item: null });

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
    const res = await axios.get('/api/approvals');
    items.value = res.data;
  } catch (e) {
    console.error(e);
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

const pendingCount  = computed(() => items.value.filter(i => i.approval_status === 'pending').length);
const approvedCount = computed(() => items.value.filter(i => i.approval_status === 'approved').length);
const rejectedCount = computed(() => items.value.filter(i => i.approval_status === 'rejected').length);

const filterTabsWithCount = computed(() => [
  { value: 'all',      label: 'Semua',    count: items.value.length },
  { value: 'pending',  label: 'Menunggu', count: pendingCount.value },
  { value: 'approved', label: 'Disetujui', count: approvedCount.value },
  { value: 'rejected', label: 'Ditolak',  count: rejectedCount.value },
]);

const filteredItems = computed(() => {
  let list = items.value;
  if (activeFilter.value !== 'all') {
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
  decideModal.value = { show: true, item, decision, notes: item.approval_notes ?? '' };
};

const submitDecision = async () => {
  deciding.value = true;
  try {
    await axios.post(`/api/approvals/${decideModal.value.item.record_id}/decide`, {
      decision: decideModal.value.decision,
      notes: decideModal.value.notes,
    });
    decideModal.value.show = false;
    detailModal.value.show = false;
    await loadData();
  } catch (e) {
    alert('Gagal: ' + (e.response?.data?.message || e.message));
  } finally {
    deciding.value = false;
  }
};

const formatDateTime = (d) => {
  if (!d) return '-';
  return new Date(d).toLocaleString('id-ID', { day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' });
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
