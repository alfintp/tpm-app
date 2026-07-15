<template>
  <div class="w-full max-w-5xl mx-auto space-y-6">
    <!-- Header -->
    <PageHeader
    title="Manajemen User"
      subtitle="Ubah role user atau hapus akun pengguna sistem TPM"
      :badge="`Total: ${users.length} Pengguna`"
      
    >
      <template #actions>
        <Button
          v-if="isAdmin"
          @click="openAddModal"
          class="bg-linear-to-tr from-brand-brown to-brand-gradation hover:opacity-90 text-brand-cream rounded-xl font-bold text-sm shadow-md gap-2 hover:cursor-pointer"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
          Tambah User Baru
        </Button>
      </template>
    </PageHeader>

    <!-- Search Bar -->
    <SearchInput
      v-model="searchQuery"
      placeholder="Cari nama, email, role..."
      wrapper-class="max-w-sm"
    />

    <!-- Users Table -->
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
          @click="openMachinesModal(row)"
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
            @click="openEditModal(row)"
            title="Ubah Nama & Email"
            class="h-8 w-8 p-0 text-amber-600 border-amber-200 bg-amber-50 hover:bg-amber-100 hover:text-amber-800 rounded-lg"
          >
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
          </Button>
          <Button
            variant="outline" size="sm"
            @click="openPasswordModal(row)"
            title="Ubah Password"
            class="h-8 w-8 p-0 text-indigo-600 border-indigo-200 bg-indigo-50 hover:bg-indigo-100 hover:text-indigo-800 rounded-lg"
          >
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
          </Button>
          <Button
            variant="outline" size="sm"
            @click="handleDeleteUser(row)"
            :disabled="row.id === currentUser?.id || deletingId === row.id"
            class="h-8 px-2.5 text-red-500 border-red-200 bg-red-50 hover:bg-red-100 hover:text-red-700 rounded-lg text-xs font-bold disabled:opacity-30"
          >
            <span v-if="deletingId === row.id">...</span>
            <span v-else>Hapus</span>
          </Button>
        </div>
      </template>
    </DataTable>

    <!-- Pagination -->
    <TablePagination
      v-if="!loading"
      v-model="currentPage"
      :total="filteredUsers.length"
      :per-page="perPage"
    />


    <!-- Edit Profil Modal (Admin only) -->
    <div v-if="showEditModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm" @click="showEditModal = false"></div>
      <div class="bg-white rounded-3xl shadow-2xl w-full max-w-sm relative z-10 p-8 space-y-6">
        <div class="flex justify-between items-center border-b border-slate-100 pb-4">
          <div>
            <h3 class="text-lg font-black text-brand-brown">Edit Profil User</h3>
            <p class="text-xs text-slate-500 mt-0.5">Ubah Nama atau Email</p>
          </div>
          <button @click="showEditModal = false" class="text-slate-400 hover:text-slate-600 p-1.5 rounded-full hover:bg-slate-100 cursor-pointer">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
          </button>
        </div>
        <form @submit.prevent="handleEditUser" class="space-y-4">
          <div class="space-y-1.5">
            <label class="text-xs font-bold text-slate-700 uppercase tracking-wider">Nama Lengkap *</label>
            <input type="text" v-model="editForm.full_name" required class="w-full rounded-xl border border-slate-300 px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-brand-brown focus:border-brand-brown text-slate-700 font-medium text-sm" placeholder="e.g. John Doe">
          </div>
          <div class="space-y-1.5">
            <label class="text-xs font-bold text-slate-700 uppercase tracking-wider">Email *</label>
            <input type="email" v-model="editForm.email" required class="w-full rounded-xl border border-slate-300 px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-brand-brown focus:border-brand-brown text-slate-700 font-medium text-sm" placeholder="e.g. john@example.com">
          </div>
          <div class="pt-2 flex justify-end gap-3">
            <button type="button" @click="showEditModal = false" class="px-5 py-2.5 rounded-xl font-bold text-sm text-slate-600 hover:bg-slate-100 transition-colors cursor-pointer">Batal</button>
            <button type="submit" :disabled="savingEdit" class="px-5 py-2.5 rounded-xl font-bold text-sm text-brand-cream bg-brand-brown hover:opacity-95 transition-colors shadow-md cursor-pointer disabled:opacity-70 flex items-center gap-2">
              <svg v-if="savingEdit" class="animate-spin h-4 w-4 text-brand-cream" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
              Simpan Profil
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Machines Modal (Admin & Manager) -->
    <div v-if="showMachinesModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm" @click="showMachinesModal = false"></div>
      <div class="bg-white rounded-3xl shadow-2xl w-full max-w-lg relative z-10 p-6 space-y-4 max-h-[80vh] overflow-hidden flex flex-col">
        <div class="flex justify-between items-center border-b border-slate-100 pb-4">
          <div>
            <h3 class="text-lg font-black text-brand-brown">Mesin yang Di-PIC</h3>
            <p class="text-xs text-slate-500 mt-0.5">{{ machinesTarget?.full_name }}</p>
          </div>
          <button @click="showMachinesModal = false" class="text-slate-400 hover:text-slate-600 p-1.5 rounded-full hover:bg-slate-100 cursor-pointer">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
          </button>
        </div>
        
        <!-- Loading State -->
        <div v-if="machinesLoading" class="flex flex-col items-center justify-center py-10 space-y-3">
          <svg class="animate-spin h-8 w-8 text-brand-brown" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.062 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
          </svg>
          <span class="text-sm text-slate-500 font-semibold">Memuat daftar mesin...</span>
        </div>
        
        <!-- Empty State -->
        <div v-else-if="userMachines.length === 0" class="text-center py-10">
          <svg class="mx-auto h-12 w-12 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z" />
          </svg>
          <h3 class="mt-3 text-sm font-bold text-slate-600">Tidak Ada Mesin</h3>
          <p class="mt-1 text-xs text-slate-400">User ini belum ditugaskan sebagai PIC untuk mesin manapun.</p>
        </div>
        
        <!-- Machines List -->
        <div v-else class="overflow-y-auto flex-1 space-y-2 pr-1">
          <div v-for="machine in userMachines" :key="machine.id" 
               class="p-3 bg-slate-50 rounded-xl border border-slate-200 hover:bg-slate-100 transition-colors">
            <div class="flex items-start justify-between gap-3">
              <div class="flex-1 min-w-0">
                <div class="text-sm font-bold text-brand-brown truncate">{{ machine.name }}</div>
                <div class="text-xs text-slate-500 flex items-center gap-2 mt-0.5">
                  <span class="font-mono bg-slate-200 px-1.5 py-0.5 rounded">{{ machine.kode }}</span>
                  <span v-if="machine.location" class="truncate">📍 {{ machine.location }}</span>
                </div>
              </div>
              <span :class="getMachineStatusClass(machine.status)" class="text-[10px] font-black px-2 py-1 rounded-full uppercase whitespace-nowrap">
                {{ getMachineStatusLabel(machine.status) }}
              </span>
            </div>
            <div v-if="machine.condition_pct !== null" class="mt-2 flex items-center gap-2">
              <div class="flex-1 h-1.5 bg-slate-200 rounded-full overflow-hidden">
                <div class="h-full rounded-full" :class="getConditionColor(machine.condition_pct)" :style="{ width: machine.condition_pct + '%' }"></div>
              </div>
              <span class="text-[10px] font-bold text-slate-600">{{ machine.condition_pct }}%</span>
            </div>
          </div>
        </div>
        
        <div class="pt-2 border-t border-slate-100">
          <button @click="showMachinesModal = false" class="w-full px-5 py-2.5 rounded-xl font-bold text-sm text-slate-600 hover:bg-slate-100 transition-colors cursor-pointer">
            Tutup
          </button>
        </div>
      </div>
    </div>

    <!-- Change Password Modal (Admin only) -->
    <div v-if="showPasswordModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm" @click="showPasswordModal = false"></div>
      <div class="bg-white rounded-3xl shadow-2xl w-full max-w-sm relative z-10 p-8 space-y-6">
        <div class="flex justify-between items-center border-b border-slate-100 pb-4">
          <div>
            <h3 class="text-lg font-black text-brand-brown">Ubah Password</h3>
            <p class="text-xs text-slate-500 mt-0.5">{{ passwordTarget?.full_name }}</p>
          </div>
          <button @click="showPasswordModal = false" class="text-slate-400 hover:text-slate-600 p-1.5 rounded-full hover:bg-slate-100 cursor-pointer">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
          </button>
        </div>
        <form @submit.prevent="handleChangePassword" class="space-y-4">
          <div class="space-y-1.5">
            <label class="text-xs font-bold text-slate-700 uppercase tracking-wider">Password Baru *</label>
            <input type="password" v-model="newPassword" required minlength="6" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-brand-brown focus:border-brand-brown text-slate-700 font-medium text-sm" placeholder="Minimal 6 karakter">
          </div>
          <div class="pt-2 flex justify-end gap-3">
            <button type="button" @click="showPasswordModal = false" class="px-5 py-2.5 rounded-xl font-bold text-sm text-slate-600 hover:bg-slate-100 transition-colors cursor-pointer">Batal</button>
            <button type="submit" :disabled="savingPassword" class="px-5 py-2.5 rounded-xl font-bold text-sm text-brand-cream bg-brand-brown hover:opacity-95 transition-colors shadow-md cursor-pointer disabled:opacity-70 flex items-center gap-2">
              <svg v-if="savingPassword" class="animate-spin h-4 w-4 text-brand-cream" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
              Simpan Password
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Add User Modal (Admin only) -->
    <div v-if="showAddModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm" @click="showAddModal = false"></div>
      <div class="bg-white rounded-3xl shadow-2xl w-full max-w-md relative z-10 p-8 space-y-6">
        <div class="flex justify-between items-center border-b border-slate-100 pb-4">
          <h3 class="text-lg font-black text-brand-brown">Tambah User Baru</h3>
          <button @click="showAddModal = false" class="text-slate-400 hover:text-slate-600 p-1.5 rounded-full hover:bg-slate-100 cursor-pointer">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
          </button>
        </div>
        
        <form @submit.prevent="handleAddUser" class="space-y-4">
          <div class="space-y-1.5">
            <label class="text-xs font-bold text-slate-700 uppercase tracking-wider">Nama Lengkap *</label>
            <input type="text" v-model="addForm.full_name" required class="w-full rounded-xl border border-slate-300 px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-brand-brown focus:border-brand-brown text-slate-700 font-medium text-sm" placeholder="e.g. John Doe">
          </div>
          
          <div class="space-y-1.5">
            <label class="text-xs font-bold text-slate-700 uppercase tracking-wider">Email *</label>
            <input type="email" v-model="addForm.email" required class="w-full rounded-xl border border-slate-300 px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-brand-brown focus:border-brand-brown text-slate-700 font-medium text-sm" placeholder="e.g. john@example.com">
          </div>
          
          <div class="space-y-1.5">
            <label class="text-xs font-bold text-slate-700 uppercase tracking-wider">Password *</label>
            <input type="password" v-model="addForm.password" required minlength="6" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-brand-brown focus:border-brand-brown text-slate-700 font-medium text-sm" placeholder="••••••••">
          </div>
          
          <div class="space-y-1.5">
            <label class="text-xs font-bold text-slate-700 uppercase tracking-wider">Role *</label>
            <select v-model="addForm.role" required class="w-full rounded-xl border border-slate-300 px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-brand-brown focus:border-brand-brown text-slate-700 font-bold text-sm cursor-pointer">
              <option v-for="role in activeRoles" :key="role.name" :value="role.name">
                {{ role.display_name }} 
              </option>
            </select>
          </div>

          <div class="space-y-1.5">
            <label class="text-xs font-bold text-slate-700 uppercase tracking-wider">Akses Kota</label>
            <select v-model="addForm.city" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-brand-brown focus:border-brand-brown text-slate-700 font-bold text-sm cursor-pointer">
              <option value="both">Keduanya (Pasuruan & Surabaya)</option>
              <option value="pasuruan">Pasuruan saja</option>
              <option value="sby">Surabaya saja</option>
            </select>
            <p class="text-[11px] text-slate-400">Untuk teknisi, pembatasan ini menentukan mesin mana yang bisa dilihat.</p>
          </div>
          
          <div class="pt-4 flex justify-end gap-3">
            <button type="button" @click="showAddModal = false" class="px-5 py-2.5 rounded-xl font-bold text-sm text-slate-600 hover:bg-slate-100 transition-colors cursor-pointer">Batal</button>
            <button type="submit" :disabled="saving" class="px-5 py-2.5 rounded-xl font-bold text-sm text-brand-cream bg-brand-brown hover:opacity-95 transition-colors shadow-md cursor-pointer disabled:opacity-70 flex items-center gap-2">
              <svg v-if="saving" class="animate-spin h-4 w-4 text-brand-cream" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
              Tambah User
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { useAuth } from '../composables/useAuth.js';
import { showAlert, showConfirm } from '../composables/useAlert.js';
import PageHeader from '../components/PageHeader.vue';
import SearchInput from '../components/SearchInput.vue';
import DataTable from '../components/DataTable.vue';
import TablePagination from '../components/TablePagination.vue';
import Button from '../../views/components/ui/button/Button.vue';

const props = defineProps({
  initialUsers: {
    type: Array,
    default: null
  }
});

const { user: currentUser, isAdmin, isManager, isManagerOrAdmin, authReady } = useAuth();

const searchQuery = ref('');
const currentPage = ref(1);
const perPage = 15;

const loading = ref(props.initialUsers === null);
const users = ref(props.initialUsers || []);
const roles = ref([]);
const updatingId = ref(null);
const updatingCityId = ref(null);
const deletingId = ref(null);

const userColumns = computed(() => [
  { key: 'full_name',    label: 'User',        width: 'w-[36%]', cellClass: 'break-words' },
  { key: 'role',         label: 'Role',        width: 'w-[20%]', show: !isAdmin.value },
  { key: 'role_select',  label: 'Role',        width: 'w-[20%]', show: isAdmin.value },
  { key: 'city',         label: 'Kota',        width: 'w-[14%]', cellClass: 'break-words' },
  { key: 'machines_pic', label: 'Mesin (PIC)', width: 'w-[16%]', show: isAdmin.value || isManager.value },
]);

watch(searchQuery, () => { currentPage.value = 1; });

const showAddModal = ref(false);
const saving = ref(false);
const addForm = ref({
  full_name: '',
  email: '',
  password: '',
  role: 'technician',
  city: 'both'
});

const showPasswordModal = ref(false);
const savingPassword = ref(false);
const newPassword = ref('');
const passwordTarget = ref(null);

const showEditModal = ref(false);
const savingEdit = ref(false);
const editForm = ref({
  id: '',
  full_name: '',
  email: ''
});

// Mesin/PIC Modal
const showMachinesModal = ref(false);
const machinesLoading = ref(false);
const machinesTarget = ref(null);
const userMachines = ref([]);

function openEditModal(user) {
  editForm.value = {
    id: user.id,
    full_name: user.full_name,
    email: user.email
  };
  showEditModal.value = true;
}

async function handleEditUser() {
  if (!editForm.value.full_name || !editForm.value.email) {
    showAlert('error', 'Gagal', 'Nama Lengkap dan Email wajib diisi!');
    return;
  }
  
  savingEdit.value = true;
  try {
    const response = await window.axios.put(`/api/admin/users/${editForm.value.id}`, {
      full_name: editForm.value.full_name,
      email: editForm.value.email
    });
    
    // Update user local ref in list
    const index = users.value.findIndex(u => u.id === editForm.value.id);
    if (index !== -1) {
      users.value[index].full_name = response.data.user.full_name;
      users.value[index].email = response.data.user.email;
    }
    
    // Sort list again by name
    users.value.sort((a, b) => a.full_name.localeCompare(b.full_name));
    
    showEditModal.value = false;
    showAlert('success', 'Berhasil', response.data.message || 'Profil user berhasil diperbarui.');
  } catch (error) {
    console.error('Failed to update user:', error);
    showAlert('error', 'Gagal', error.response?.data?.message || 'Gagal memperbarui profil user.');
  } finally {
    savingEdit.value = false;
  }
}

async function openMachinesModal(user) {
  machinesTarget.value = user;
  showMachinesModal.value = true;
  machinesLoading.value = true;
  userMachines.value = [];
  
  try {
    // Fetch all machines and filter by pic_mesin_id
    const response = await window.axios.get('/api/machines');
    const allMachines = response.data || [];
    userMachines.value = allMachines.filter(m => m.pic_mesin_id === user.id);
  } catch (error) {
    console.error('Failed to fetch machines:', error);
    showAlert('error', 'Gagal', 'Gagal memuat daftar mesin.');
  } finally {
    machinesLoading.value = false;
  }
}

function openAddModal() {
  addForm.value = {
    full_name: '',
    email: '',
    password: '',
    role: 'technician',
    city: 'both'
  };
  showAddModal.value = true;
}

function openPasswordModal(user) {
  passwordTarget.value = user;
  newPassword.value = '';
  showPasswordModal.value = true;
}

async function handleChangePassword() {
  if (!newPassword.value || newPassword.value.length < 6) {
    showAlert('error', 'Gagal', 'Password minimal 6 karakter.');
    return;
  }
  savingPassword.value = true;
  try {
    const res = await window.axios.put(`/api/admin/users/${passwordTarget.value.id}/password`, { password: newPassword.value });
    showAlert('success', 'Berhasil', res.data.message || 'Password berhasil diubah.');
    showPasswordModal.value = false;
  } catch (error) {
    showAlert('error', 'Gagal', error.response?.data?.message || 'Gagal mengubah password.');
  } finally {
    savingPassword.value = false;
  }
}

async function handleCityChange(user, newCity) {
  if (user.city === newCity) return;
  updatingCityId.value = user.id;
  try {
    const res = await window.axios.put(`/api/admin/users/${user.id}/city`, { city: newCity });
    user.city = newCity;
    showAlert('success', 'Kota Diperbarui', res.data.message || 'Kota user berhasil diubah.');
  } catch (error) {
    showAlert('error', 'Gagal', error.response?.data?.message || 'Gagal mengubah kota user.');
  } finally {
    updatingCityId.value = null;
  }
}

async function handleAddUser() {
  if (!addForm.value.full_name || !addForm.value.email || !addForm.value.password) {
    showAlert('error', 'Gagal', 'Semua kolom wajib diisi!');
    return;
  }
  
  saving.value = true;
  try {
    const response = await window.axios.post('/api/admin/users', addForm.value);
    users.value.push(response.data.user);
    // Sort list by name
    users.value.sort((a, b) => a.full_name.localeCompare(b.full_name));
    showAlert('success', 'Berhasil', response.data.message || 'User baru berhasil ditambahkan.');
    showAddModal.value = false;
  } catch (error) {
    console.error('Failed to add user:', error);
    showAlert('error', 'Gagal', error.response?.data?.message || 'Gagal menambahkan user baru.');
  } finally {
    saving.value = false;
  }
}

const filteredUsers = computed(() => {
  if (!searchQuery.value) return users.value;
  const q = searchQuery.value.toLowerCase();
  return users.value.filter(u =>
    u.full_name.toLowerCase().includes(q) ||
    u.email.toLowerCase().includes(q) ||
    getRoleName(u.role).toLowerCase().includes(q) ||
    getCityName(u.city).toLowerCase().includes(q)
  );
});

const paginatedUsers = computed(() => {
  const start = (currentPage.value - 1) * perPage;
  return filteredUsers.value.slice(start, start + perPage);
});

async function fetchUsers() {
  if (props.initialUsers !== null) return;
  loading.value = true;
  try {
    const [usersResponse, rolesResponse] = await Promise.all([
      window.axios.get('/api/admin/users'),
      window.axios.get('/api/roles'),
    ]);
    users.value = usersResponse.data;
    roles.value = Array.isArray(rolesResponse.data) ? rolesResponse.data : [];
  } catch (error) {
    console.error('Failed to fetch users:', error);
    showAlert('error', 'Error', 'Gagal memuat daftar user. Pastikan Anda memiliki akses yang tepat.');
  } finally {
    loading.value = false;
  }
}

const activeRoles = computed(() => roles.value.filter(r => r.is_active));

function roleName(role) {
  return roles.value.find(r => r.name === role)?.display_name ?? role;
}

async function handleRoleChange(user, newRole) {
  if (user.role === newRole) return;

  updatingId.value = user.id;
  try {
    const response = await window.axios.put(`/api/admin/users/${user.id}/role`, { role: newRole });
    user.role = newRole;
    showAlert('success', 'Role Diperbarui', response.data.message || 'Role user berhasil diubah.');
    
    // If we changed our own role, update local storage profile
    if (user.id === currentUser.value?.id) {
      currentUser.value.role = newRole;
      localStorage.setItem('user_profile', JSON.stringify(currentUser.value));
      window.dispatchEvent(new Event('auth-changed'));
    }
  } catch (error) {
    console.error('Failed to update role:', error);
    showAlert('error', 'Gagal', error.response?.data?.message || 'Gagal mengubah role user.');
    // Force reset selector by re-fetching
    fetchUsers();
  } finally {
    updatingId.value = null;
  }
}

async function handleDeleteUser(user) {
  const confirm = await showConfirm(
    'Hapus User',
    `Apakah Anda yakin ingin menghapus user "${user.full_name}"? Akun ini tidak akan dapat login lagi.`
  );

  if (!confirm) return;

  deletingId.value = user.id;
  try {
    const response = await window.axios.delete(`/api/admin/users/${user.id}`);
    users.value = users.value.filter(u => u.id !== user.id);
    showAlert('success', 'User Dihapus', response.data.message || 'User berhasil dihapus.');
  } catch (error) {
    console.error('Failed to delete user:', error);
    showAlert('error', 'Gagal', error.response?.data?.message || 'Gagal menghapus user.');
  } finally {
    deletingId.value = null;
  }
}

function getInitials(name) {
  if (!name) return '??';
  const parts = name.split(' ').filter(n => n.length > 0);
  if (parts.length >= 2) {
    return (parts[0][0] + parts[1][0]).toUpperCase();
  }
  return name.slice(0, 2).toUpperCase();
}

function getRoleName(role) {
  return roleName(role);
}

function getCityName(city) {
  const map = { pasuruan: 'Pasuruan', sby: 'Surabaya', both: 'Keduanya' };
  return map[city] ?? city ?? '-';
}

function getRoleClass(role) {
  const classes = {
    admin: 'bg-purple-50 text-purple-700 border border-purple-100',
    manager: 'bg-blue-50 text-blue-700 border border-blue-100',
    technician: 'bg-amber-50 text-amber-700 border border-amber-100'
  };
  return classes[role] ?? 'bg-slate-100 text-slate-700 border border-slate-200';
}

function formatDate(dateStr) {
  if (!dateStr) return '';
  const date = new Date(dateStr);
  return date.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
}

function getMachineStatusClass(status) {
  const classes = {
    active: 'bg-emerald-50 text-emerald-700 border border-emerald-100',
    maintenance: 'bg-amber-50 text-amber-700 border border-amber-100',
    inactive: 'bg-slate-50 text-slate-600 border border-slate-200'
  };
  return classes[status] ?? 'bg-slate-50 text-slate-600 border border-slate-200';
}

function getMachineStatusLabel(status) {
  const labels = {
    active: 'Aktif',
    maintenance: 'Maintenance',
    inactive: 'Nonaktif'
  };
  return labels[status] ?? status;
}

function getConditionColor(pct) {
  if (pct >= 80) return 'bg-emerald-500';
  if (pct >= 50) return 'bg-amber-500';
  return 'bg-rose-500';
}

onMounted(() => {
  if (authReady.value) fetchUsers();
});

watch(authReady, (ready) => {
  if (ready && users.value.length === 0 && !loading.value) fetchUsers();
});
</script>
