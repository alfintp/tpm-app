<template>
  <div class="space-y-6">
    <SearchInput
      :model-value="searchQuery"
      @update:model-value="$emit('update:searchQuery', $event)"
      placeholder="Cari nama, email, role..."
      wrapper-class="max-w-sm"
    />

    <DataTable
      :columns="userColumns"
      :rows="paginatedUsers"
      :loading="loading"
      loading-text="Memuat daftar user..."
      loading-subtext="Mengambil data dari server"
      empty-title="Tidak Ada User"
      empty-subtext="Belum ada user terdaftar di dalam sistem."
      min-width="min-w-[900px] lg:min-w-[700px]"
      table-class="table-auto lg:table-fixed"
      :paginate="false"
      actions-align="right"
      actions-width="w-[14%]"
    >
      <!-- Kolom: User (avatar + nama) -->
      <template #cell-full_name="{ row }">
        <div class="flex items-center gap-3">
          <div class="h-10 w-10 rounded-full bg-linear-to-tr from-brand-brown to-brand-gradation text-brand-cream flex items-center justify-center font-bold text-sm shadow-inner uppercase shrink-0">
            {{ getInitials(row.full_name) }}
          </div>
          <div>
            <div class="text-sm font-bold text-brand-brown">
              {{ row.full_name }}
              <span v-if="row.id === currentUser?.id" class="ml-1.5 text-xs bg-slate-200 text-slate-700 font-bold px-2 py-0.5 rounded">Anda</span>
            </div>
            <div class="text-xs text-slate-400 font-medium">Terdaftar {{ formatDate(row.created_at) }}</div>
            <div v-if="isAdmin" class="text-xs text-slate-500 truncate">{{ row.email }}</div>
          </div>
        </div>
      </template>

      <!-- Kolom: Role badge -->
      <template #cell-role="{ row }">
        <span :class="getRoleClass(row.role)" class="inline-flex items-center text-xs font-black px-3 py-1 rounded-full uppercase">
          {{ getRoleName(row.role) }}
        </span>
      </template>

      <!-- Kolom: Ubah Role (Admin only) -->
      <template #cell-role_select="{ row }">
        <select
          :value="row.role"
          @change="handleRoleChange(row, $event.target.value)"
          :disabled="updatingId === row.id"
          class="w-full bg-white border border-slate-300 rounded-lg text-xs font-bold text-slate-700 px-3 py-1.5 focus:outline-none focus:ring-1 focus:ring-brand-brown focus:border-brand-brown disabled:opacity-50 cursor-pointer"
        >
          <option v-for="role in activeRoles" :key="role.name" :value="role.name">
            {{ role.display_name }}
          </option>
        </select>
      </template>

      <!-- Kolom: Kota -->
      <template #cell-city="{ row }">
        <select
          v-if="isAdmin"
          :value="row.city"
          @change="handleCityChange(row, $event.target.value)"
          :disabled="updatingCityId === row.id"
          class="bg-white border border-slate-300 rounded-lg text-xs font-bold text-slate-700 px-3 py-1.5 focus:outline-none focus:ring-1 focus:ring-brand-brown focus:border-brand-brown disabled:opacity-50 cursor-pointer"
        >
          <option value="pasuruan">Pasuruan</option>
          <option value="sby">Surabaya</option>
          <option value="both">Keduanya</option>
        </select>
        <span v-else class="text-xs font-bold text-slate-600">{{ getCityName(row.city) }}</span>
      </template>

      <!-- Kolom: Mesin PIC (Admin & Manager) -->
      <template #cell-machines_pic="{ row }">
        <Button
          variant="outline"
          size="sm"
          @click="$emit('openMachinesModal', row)"
          class="text-emerald-600 border-emerald-200 bg-emerald-50 hover:bg-emerald-100 hover:text-emerald-800 rounded-lg text-xs font-bold gap-1.5 h-auto py-1.5"
        >
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"/></svg>
          Lihat Mesin
        </Button>
      </template>

      <!-- Slot Actions: Edit, Password, Hapus (Admin only) -->
      <template v-if="isAdmin" #actions="{ row }">
        <div class="flex flex-nowrap items-center justify-end gap-1.5">
          <Button
            variant="outline" size="sm"
            @click="$emit('openEditModal', row)"
            title="Ubah Nama & Email"
            class="h-8 w-8 p-0 text-amber-600 border-amber-200 bg-amber-50 hover:bg-amber-100 hover:text-amber-800 rounded-lg"
          >
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
          </Button>
          <Button
            variant="outline" size="sm"
            @click="$emit('openPasswordModal', row)"
            title="Ubah Password"
            class="h-8 w-8 p-0 text-indigo-600 border-indigo-200 bg-indigo-50 hover:bg-indigo-100 hover:text-indigo-800 rounded-lg"
          >
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
          </Button>
          <Button
            variant="outline" size="sm"
            @click="$emit('deleteUser', row)"
            :disabled="row.id === currentUser?.id || deletingId === row.id"
            class="h-8 px-2.5 text-red-500 border-red-200 bg-red-50 hover:bg-red-100 hover:text-red-700 rounded-lg text-xs font-bold disabled:opacity-30"
          >
            <span v-if="deletingId === row.id">...</span>
            <span v-else>Hapus</span>
          </Button>
        </div>
      </template>
    </DataTable>

    <TablePagination
      v-if="!loading"
      :model-value="currentPage"
      @update:model-value="$emit('update:currentPage', $event)"
      :total="filteredUsers.length"
      :per-page="perPage"
      :show-per-page-selector="true"
      :per-page-options="[10, 15, 20, 30, 50]"
      @update:per-page="$emit('update:perPage', $event)"
    />
  </div>
</template>

<script setup>
import SearchInput from './SearchInput.vue';
import DataTable from './DataTable.vue';
import TablePagination from './TablePagination.vue';
import Button from '../../views/components/ui/button/Button.vue';

defineProps({
  searchQuery: { type: String, default: '' },
  currentPage: { type: Number, default: 1 },
  perPage: { type: Number, default: 15 },
  loading: { type: Boolean, default: false },
  paginatedUsers: { type: Array, default: () => [] },
  filteredUsers: { type: Array, default: () => [] },
  userColumns: { type: Array, default: () => [] },
  activeRoles: { type: Array, default: () => [] },
  isAdmin: { type: Boolean, default: false },
  isManager: { type: Boolean, default: false },
  currentUser: { type: Object, default: null },
  updatingId: { type: [Number, String], default: null },
  updatingCityId: { type: [Number, String], default: null },
  deletingId: { type: [Number, String], default: null },
  getInitials: { type: Function, required: true },
  getRoleName: { type: Function, required: true },
  getCityName: { type: Function, required: true },
  getRoleClass: { type: Function, required: true },
  formatDate: { type: Function, required: true },
  handleRoleChange: { type: Function, required: true },
  handleCityChange: { type: Function, required: true },
});

defineEmits(['update:searchQuery', 'update:currentPage', 'update:perPage', 'openEditModal', 'openPasswordModal', 'openMachinesModal', 'deleteUser']);
</script>
