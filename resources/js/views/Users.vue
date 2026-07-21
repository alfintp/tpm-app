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

    <UsersListPanel
      v-model:search-query="searchQuery"
      v-model:current-page="currentPage"
      v-model:per-page="perPage"
      :loading="loading"
      :paginated-users="paginatedUsers"
      :filtered-users="filteredUsers"
      :user-columns="userColumns"
      :active-roles="activeRoles"
      :is-admin="isAdmin"
      :is-manager="isManager"
      :current-user="currentUser"
      :updating-id="updatingId"
      :updating-city-id="updatingCityId"
      :deleting-id="deletingId"
      :get-initials="getInitials"
      :get-role-name="getRoleName"
      :get-city-name="getCityName"
      :get-role-class="getRoleClass"
      :format-date="formatDate"
      :handle-role-change="handleRoleChange"
      :handle-city-change="handleCityChange"
      @open-edit-modal="openEditModal"
      @open-password-modal="openPasswordModal"
      @open-machines-modal="openMachinesModal"
      @delete-user="handleDeleteUser"
    />

    <UserModals
      v-model:edit-form="editForm"
      v-model:new-password="newPassword"
      v-model:add-form="addForm"
      :show-edit-modal="showEditModal"
      :saving-edit="savingEdit"
      :show-password-modal="showPasswordModal"
      :password-target="passwordTarget"
      :saving-password="savingPassword"
      :show-machines-modal="showMachinesModal"
      :machines-target="machinesTarget"
      :machines-loading="machinesLoading"
      :user-machines="userMachines"
      :show-add-modal="showAddModal"
      :saving="saving"
      :active-roles="activeRoles"
      :get-machine-status-class="getMachineStatusClass"
      :get-machine-status-label="getMachineStatusLabel"
      :get-condition-color="getConditionColor"
      @close-edit="showEditModal = false"
      @submit-edit="handleEditUser"
      @close-password="showPasswordModal = false"
      @submit-password="handleChangePassword"
      @close-machines="showMachinesModal = false"
      @close-add="showAddModal = false"
      @submit-add="handleAddUser"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { useAuth } from '../composables/useAuth.js';
import { showAlert, showConfirm } from '../composables/useAlert.js';
import PageHeader from '../components/PageHeader.vue';
import Button from '../../views/components/ui/button/Button.vue';
import UsersListPanel from '../components/UsersListPanel.vue';
import UserModals from '../components/UserModals.vue';

const props = defineProps({
  initialUsers: {
    type: Array,
    default: null
  }
});

const { user: currentUser, isAdmin, isManager, isManagerOrAdmin, authReady } = useAuth();

const searchQuery = ref('');
const currentPage = ref(1);
const perPage = ref(10);

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

watch([searchQuery, perPage], () => { currentPage.value = 1; });

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
  const start = (currentPage.value - 1) * perPage.value;
  return filteredUsers.value.slice(start, start + perPage.value);
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
