<template>
  <div class="space-y-6">
    <!-- Header Card -->
    <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm flex flex-col md:flex-row md:items-center md:justify-between gap-4">
      <div>
        <h1 class="text-xl font-black text-brand-brown">Manajemen User</h1>
        <p class="text-sm text-slate-500 font-medium">Ubah role user atau hapus akun pengguna sistem TPM</p>
      </div>
      <div class="flex items-center gap-2">
        <span class="text-xs bg-brand-cream text-brand-brown font-bold px-3 py-1.5 rounded-full border border-brand-brown/10">
          Total: {{ users.length }} Pengguna
        </span>
      </div>
    </div>

    <!-- Users Table Card -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
      <!-- Loading State -->
      <div v-if="loading" class="flex flex-col items-center justify-center py-16 space-y-3">
        <svg class="animate-spin h-8 w-8 text-brand-brown" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.062 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
        </svg>
        <span class="text-sm text-slate-500 font-semibold">Memuat daftar user...</span>
      </div>

      <!-- Empty State -->
      <div v-else-if="users.length === 0" class="text-center py-16">
        <svg class="mx-auto h-12 w-12 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
        </svg>
        <h3 class="mt-2 text-sm font-bold text-brand-brown">Tidak Ada User</h3>
        <p class="mt-1 text-sm text-slate-500 font-medium">Belum ada user terdaftar di dalam sistem.</p>
      </div>

      <!-- Table -->
      <div v-else class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
          <thead>
            <tr class="bg-slate-50 border-b border-slate-200">
              <th class="px-6 py-4 text-xs font-black text-slate-600 uppercase tracking-wider">User</th>
              <th class="px-6 py-4 text-xs font-black text-slate-600 uppercase tracking-wider">Email</th>
              <th class="px-6 py-4 text-xs font-black text-slate-600 uppercase tracking-wider">Role Saat Ini</th>
              <th class="px-6 py-4 text-xs font-black text-slate-600 uppercase tracking-wider">Ubah Role</th>
              <th class="px-6 py-4 text-xs font-black text-slate-600 uppercase tracking-wider text-right">Aksi</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-for="u in users" :key="u.id" class="hover:bg-slate-50/50 transition-colors">
              <!-- Name with Avatar -->
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="flex items-center">
                  <div class="h-10 w-10 rounded-full bg-gradient-to-tr from-brand-brown to-brand-gradation text-brand-cream flex items-center justify-center font-bold text-sm shadow-inner uppercase">
                    {{ getInitials(u.full_name) }}
                  </div>
                  <div class="ml-4">
                    <div class="text-sm font-bold text-brand-brown">
                      {{ u.full_name }}
                      <span v-if="u.id === currentUser?.id" class="ml-1.5 text-xs bg-slate-200 text-slate-700 font-bold px-2 py-0.5 rounded">
                        Anda
                      </span>
                    </div>
                    <div class="text-xs text-slate-400 font-medium">Terdaftar {{ formatDate(u.created_at) }}</div>
                  </div>
                </div>
              </td>

              <!-- Email -->
              <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-slate-600">
                {{ u.email }}
              </td>

              <!-- Role Badge -->
              <td class="px-6 py-4 whitespace-nowrap">
                <span :class="getRoleClass(u.role)" class="inline-flex items-center text-xs font-black px-3 py-1 rounded-full uppercase">
                  {{ getRoleName(u.role) }}
                </span>
              </td>

              <!-- Role Select -->
              <td class="px-6 py-4 whitespace-nowrap">
                <select
                  :value="u.role"
                  @change="handleRoleChange(u, $event.target.value)"
                  :disabled="updatingId === u.id"
                  class="bg-white border border-slate-300 rounded-lg text-xs font-bold text-slate-700 px-3 py-1.5 focus:outline-none focus:ring-1 focus:ring-brand-brown focus:border-brand-brown disabled:opacity-50"
                >
                  <option value="technician">Teknisi (Technician)</option>
                  <option value="manager">Manajer (Manager)</option>
                  <option value="admin">Administrator (Admin)</option>
                </select>
              </td>

              <!-- Delete Action -->
              <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                <button
                  @click="handleDeleteUser(u)"
                  :disabled="u.id === currentUser?.id || deletingId === u.id"
                  class="text-red-500 hover:text-red-700 font-bold text-xs bg-red-50 hover:bg-red-100 disabled:opacity-30 disabled:pointer-events-none px-3 py-1.5 rounded-lg transition-all border border-red-200/20"
                >
                  <span v-if="deletingId === u.id">Menghapus...</span>
                  <span v-else>Hapus</span>
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useAuth } from '../composables/useAuth.js';
import { showAlert, showConfirm } from '../composables/useAlert.js';

const { user: currentUser } = useAuth();

const users = ref([]);
const loading = ref(true);
const updatingId = ref(null);
const deletingId = ref(null);

async function fetchUsers() {
  loading.value = true;
  try {
    const response = await window.axios.get('/api/admin/users');
    users.value = response.data;
  } catch (error) {
    console.error('Failed to fetch users:', error);
    showAlert('error', 'Error', 'Gagal memuat daftar user. Pastikan Anda memiliki akses yang tepat.');
  } finally {
    loading.value = false;
  }
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
  const names = {
    admin: 'Admin',
    manager: 'Manager',
    technician: 'Teknisi'
  };
  return names[role] ?? role;
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

onMounted(() => {
  fetchUsers();
});
</script>
