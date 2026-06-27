<template>
  <div v-if="show" class="fixed inset-0 z-60 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl max-w-2xl w-full p-6 shadow-xl border border-slate-100 space-y-6">
      <!-- Header -->
      <div class="flex justify-between items-start">
        <div>
          <h3 class="text-xl font-bold text-slate-800 flex items-center gap-2">
            <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
            </svg>
            Kelola Role
          </h3>
          <p class="text-xs text-slate-500 mt-1">
            Tambah, ubah, atau hapus role. Role yang dapat approve dapat dipakai di alur approval.
          </p>
        </div>
        <button
          @click="$emit('close')"
          class="p-1.5 hover:bg-slate-100 rounded-xl transition-all cursor-pointer text-slate-400"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
      </div>

      <!-- New Role Form -->
      <div class="bg-slate-50 p-4 rounded-2xl border border-slate-100 space-y-3">
        <h4 class="text-xs font-bold text-slate-600 uppercase tracking-wider">Tambah Role Baru</h4>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
          <div>
            <label class="text-[10px] font-semibold text-slate-500 uppercase tracking-wider">Nama Role (slug)</label>
            <input
              v-model="newRole.name"
              type="text"
              placeholder="contoh: supervisor"
              class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 bg-white"
            />
          </div>
          <div>
            <label class="text-[10px] font-semibold text-slate-500 uppercase tracking-wider">Nama Tampilan</label>
            <input
              v-model="newRole.display_name"
              type="text"
              placeholder="contoh: Supervisor"
              class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 bg-white"
            />
          </div>
        </div>
        <div class="flex items-center gap-4 flex-wrap">
          <div class="flex items-center gap-2">
            <input
              id="newRoleCanApprove"
              v-model="newRole.can_approve"
              type="checkbox"
              class="w-4 h-4 text-indigo-600 rounded border-slate-300 focus:ring-indigo-500"
            />
            <label for="newRoleCanApprove" class="text-xs text-slate-600">Dapat Approval</label>
          </div>
          <div class="flex items-center gap-2">
            <input
              id="newRoleCanReport"
              v-model="newRole.can_report"
              type="checkbox"
              class="w-4 h-4 text-indigo-600 rounded border-slate-300 focus:ring-indigo-500"
            />
            <label for="newRoleCanReport" class="text-xs text-slate-600">Dapat Report</label>
          </div>
        </div>
        <button
          @click="addRole"
          :disabled="!isNewRoleValid || loading"
          class="w-full md:w-auto px-4 py-2 bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 text-white rounded-xl text-xs font-bold cursor-pointer transition-all flex items-center justify-center gap-1.5"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
          Tambah Role
        </button>
      </div>

      <!-- Roles List -->
      <div class="overflow-x-auto">
        <table class="w-full text-xs">
          <thead class="bg-slate-50 text-slate-500 uppercase tracking-wider">
            <tr>
              <th class="px-3 py-2 text-left font-semibold">Nama</th>
              <th class="px-3 py-2 text-left font-semibold">Tampilan</th>
              <th class="px-3 py-2 text-center font-semibold">Approve</th>
              <th class="px-3 py-2 text-center font-semibold">Report</th>
              <th class="px-3 py-2 text-center font-semibold">Aktif</th>
              <th class="px-3 py-2 text-right font-semibold">Aksi</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-for="role in localRoles" :key="role.id" class="hover:bg-slate-50">
              <td class="px-3 py-2 text-slate-700 font-medium">{{ role.name }}</td>
              <td class="px-3 py-2">
                <input
                  v-model="role.display_name"
                  type="text"
                  class="w-full rounded-lg border border-slate-200 px-2 py-1 text-xs text-slate-700 focus:outline-none focus:ring-1 focus:ring-indigo-500 bg-white"
                />
              </td>
              <td class="px-3 py-2 text-center">
                <input
                  v-model="role.can_approve"
                  type="checkbox"
                  class="w-4 h-4 text-indigo-600 rounded border-slate-300 focus:ring-indigo-500"
                />
              </td>
              <td class="px-3 py-2 text-center">
                <input
                  v-model="role.can_report"
                  type="checkbox"
                  class="w-4 h-4 text-indigo-600 rounded border-slate-300 focus:ring-indigo-500"
                />
              </td>
              <td class="px-3 py-2 text-center">
                <input
                  v-model="role.is_active"
                  type="checkbox"
                  class="w-4 h-4 text-indigo-600 rounded border-slate-300 focus:ring-indigo-500"
                />
              </td>
              <td class="px-3 py-2 text-right">
                <div class="flex items-center justify-end gap-1">
                  <button
                    @click="save(role)"
                    :disabled="loading"
                    class="p-1.5 text-indigo-600 hover:bg-indigo-50 rounded-lg transition-all cursor-pointer"
                    title="Simpan"
                  >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                  </button>
                  <button
                    v-if="!isSystemRole(role)"
                    @click="confirmDelete(role)"
                    :disabled="loading"
                    class="p-1.5 text-red-500 hover:bg-red-50 rounded-lg transition-all cursor-pointer"
                    title="Hapus"
                  >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Footer -->
      <div class="flex justify-end pt-4 border-t border-slate-100">
        <button
          @click="$emit('close')"
          class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-xl text-xs font-bold cursor-pointer transition-all"
        >
          Tutup
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { showConfirm } from '../composables/useAlert.js';

const props = defineProps({
  show: { type: Boolean, required: true },
  roles: { type: Array, default: () => [] },
  loading: { type: Boolean, default: false },
});

const emit = defineEmits(['close', 'save', 'delete']);

const localRoles = ref([]);
const newRole = ref({ name: '', display_name: '', can_approve: true, can_report: false, is_active: true });

watch(() => props.roles, (newRoles) => {
  localRoles.value = newRoles.map(r => ({ ...r }));
}, { immediate: true, deep: true });

const isNewRoleValid = computed(() => {
  return props.roles.every(r => r.name !== newRole.value.name) &&
    /^[a-z0-9_]+$/.test(newRole.value.name) &&
    newRole.value.display_name.trim().length > 0;
});

const isSystemRole = (role) => {
  return ['admin', 'technician', 'manager'].includes(role.name);
};

const addRole = () => {
  if (!isNewRoleValid.value) return;
  emit('save', { ...newRole.value });
  newRole.value = { name: '', display_name: '', can_approve: true, can_report: false, is_active: true };
};

const save = (role) => {
  emit('save', { ...role });
};

const confirmDelete = async (role) => {
  const confirmed = await showConfirm('Hapus Role?', `Yakin ingin menghapus role ${role.display_name}?`);
  if (confirmed) {
    emit('delete', role);
  }
};
</script>
