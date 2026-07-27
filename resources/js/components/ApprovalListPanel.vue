<template>
  <div class="space-y-6">
    <!-- Stats (Approver only) -->
    <div v-if="isApproverUser" class="grid grid-cols-2 md:grid-cols-4 gap-4">
      <StatCard :value="approvedCount" label="Disetujui"         color="green" />
      <StatCard :value="rejectedCount" label="Ditolak"           color="red" />
      <StatCard :value="items.length"  label="Total Report"      color="slate" />
    </div>

    <!-- Search & Filter -->
    <div class="flex flex-col md:flex-row gap-4 items-center justify-between">
      <SearchInput
        :model-value="searchQuery"
        @update:model-value="$emit('update:searchQuery', $event)"
        placeholder="Cari mesin, teknisi..."
      />
      <div class="flex flex-wrap items-center justify-end gap-3">
        <FilterTabs :model-value="activeFilter" @update:model-value="$emit('update:activeFilter', $event)" :tabs="filterTabs" />
        <select
          :value="sortOrder"
          @change="$emit('update:sortOrder', $event.target.value)"
          class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-600 focus:outline-none focus:ring-2 focus:ring-brand-brown/50 cursor-pointer"
          aria-label="Urutkan laporan approval"
        >
          <option value="newest">Terbaru</option>
          <option value="oldest">Terlama</option>
        </select>
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
      min-width="min-w-[900px] lg:min-w-[700px]"
      table-class="table-auto lg:table-fixed"
      :paginate="false"
      actions-align="right"
      actions-width="w-[16%]"
    >
      <!-- Kolom: Mesin & Waktu -->
      <template #cell-machine_name="{ row }">
        <div class="flex items-center gap-1.5 flex-wrap">
          <p class="font-semibold text-slate-800 text-sm cursor-pointer hover:text-indigo-600 transition-colors min-w-0 whitespace-normal break-words" @click="goToMachine(row.machine_id)">{{ row.machine_name }}</p>
          <span v-if="row.is_unscheduled" class="text-[9px] font-extrabold px-1.5 py-0.5 rounded bg-amber-100 text-amber-800 border border-amber-200 tracking-wider uppercase shrink-0">Luar Jadwal</span>
          <span v-else-if="row.is_late" class="text-[9px] font-extrabold px-1.5 py-0.5 rounded bg-rose-50 text-rose-700 border border-rose-100 tracking-wider uppercase shrink-0">Terlambat</span>
          <span v-else class="text-[9px] font-extrabold px-1.5 py-0.5 rounded bg-indigo-50 text-indigo-700 border border-indigo-100 tracking-wider uppercase shrink-0">Sesuai Jadwal</span>
        </div>
        <div class="flex items-center gap-1.5 mt-0.5">
          <span v-if="row.machine_kota" class="text-[9px] font-bold px-1.5 py-0.5 rounded bg-brand-cream text-brand-brown border border-brand-cream/50 uppercase tracking-wider">
            {{ row.machine_kota === 'sby' ? 'Surabaya' : row.machine_kota === 'pasuruan' ? 'Pasuruan' : row.machine_kota }}
          </span>
          <span v-if="row.machine_location" class="text-[9px] font-bold px-1.5 py-0.5 rounded bg-slate-100 text-slate-500 border border-slate-200 uppercase tracking-wider">
            {{ row.machine_location }}
          </span>
          <p class="text-xs text-slate-400">{{ formatDateTime(row.created_at) }}</p>
        </div>
        <p v-if="row.duration_minutes" class="text-xs text-brand-gradation font-semibold mt-0.5">
          Durasi: {{ formatDuration(row.duration_minutes) }}
          <span v-if="row.start_time || row.end_time" class="text-slate-400 font-normal">({{ formatTime(row.start_time) }} - {{ formatTime(row.end_time) }})</span>
        </p>
        <p class="text-xs text-slate-500 mt-0.5 truncate">Teknisi: {{ row.technician_name ?? '-' }}</p>
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
            @click="$emit('openDetails', row)"
            class="text-indigo-600 hover:text-indigo-800 text-xs font-bold flex items-center gap-1 transition-colors cursor-pointer pt-0.5"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
            Lihat Laporan ({{ row.actions.length }})
          </button>
          <button
            @click="$emit('openNotes', row)"
            class="text-amber-600 hover:text-amber-800 text-xs font-bold flex items-center gap-1 transition-colors cursor-pointer pt-0.5"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/></svg>
            Lihat Catatan
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
        <div v-if="canDecide(row)" class="flex flex-nowrap items-center justify-end gap-1.5">
          <Button
            size="sm"
            @click="$emit('openDecide', { item: row, decision: 'approved' })"
            class="rounded-lg bg-green-500 hover:bg-green-600 text-white text-xs font-semibold h-auto py-1.5 px-3"
          >Setujui</Button>
          <Button
            size="sm"
            @click="$emit('openDecide', { item: row, decision: 'rejected' })"
            class="rounded-lg bg-red-500 hover:bg-red-600 text-white text-xs font-semibold h-auto py-1.5 px-3"
          >Tolak</Button>
        </div>
        <Button
          v-else-if="row.approval_status === 'rejected' && row.technician_id === currentUser?.id"
          size="sm"
          @click="goToReport(row.machine_id)"
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
      :model-value="currentPage"
      @update:model-value="$emit('update:currentPage', $event)"
      :per-page="perPage"
      @update:per-page="$emit('update:perPage', $event)"
      :total="filteredItems.length"
      :show-per-page-selector="true"
    />
  </div>
</template>

<script setup>
import SearchInput from './SearchInput.vue';
import FilterTabs from './FilterTabs.vue';
import DataTable from './DataTable.vue';
import TablePagination from './TablePagination.vue';
import StatCard from './StatCard.vue';
import Button from '../../views/components/ui/button/Button.vue';
import ApprovalProgress from './ApprovalProgress.vue';

defineProps({
  items: { type: Array, default: () => [] },
  loading: { type: Boolean, default: false },
  isApproverUser: { type: Boolean, default: false },
  currentUser: { type: Object, default: null },
  searchQuery: { type: String, default: '' },
  activeFilter: { type: String, default: 'all' },
  sortOrder: { type: String, default: 'newest' },
  filterTabs: { type: Array, default: () => [] },
  currentPage: { type: Number, default: 1 },
  perPage: { type: Number, default: 10 },
  filteredItems: { type: Array, default: () => [] },
  paginatedItems: { type: Array, default: () => [] },
  canDecide: { type: Function, required: true },
  formatDateTime: { type: Function, required: true },
  formatDuration: { type: Function, required: true },
  formatTime: { type: Function, required: true },
  getReplaceCount: { type: Function, required: true },
  getInspectCount: { type: Function, required: true },
  statusBadgeClass: { type: Function, required: true },
  statusDotClass: { type: Function, required: true },
  statusLabel: { type: Function, required: true },
  initials: { type: Function, required: true },
  goToMachine: { type: Function, required: true },
  goToReport: { type: Function, required: true },
  approvalColumns: { type: Array, default: () => [] },
  pendingCount: { type: Number, default: 0 },
  approvedCount: { type: Number, default: 0 },
  rejectedCount: { type: Number, default: 0 },
});

defineEmits(['update:searchQuery', 'update:activeFilter', 'update:sortOrder', 'update:currentPage', 'update:perPage', 'openDetails', 'openNotes', 'openDecide']);
</script>
