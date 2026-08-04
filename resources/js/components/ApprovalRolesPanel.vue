<template>
  <div class="bg-white rounded-3xl border border-slate-100 p-6 shadow-sm space-y-6">
    <div class="flex items-center justify-between">
      <div>
        <h3 class="text-lg font-bold text-slate-800 flex items-center gap-2">
          <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
          </svg>
          Kelola Role
        </h3>
        <p class="text-xs text-slate-500 mt-1">Tambah, ubah, atau hapus role. Role dengan akses report dapat dikonfigurasi kategori komponen wajibnya.</p>
      </div>
      <button
        v-if="!roleEditMode"
        @click="$emit('edit')"
        class="px-4 py-2 bg-indigo-50 border border-indigo-200 text-indigo-600 hover:bg-indigo-100 rounded-xl text-xs font-bold cursor-pointer transition-all flex items-center gap-1.5"
      >
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
        Edit Role
      </button>
    </div>

    <!-- New Role Form (edit mode only) -->
    <div v-if="roleEditMode" class="bg-slate-50 p-4 rounded-2xl border border-slate-100 space-y-3">
      <h4 class="text-xs font-bold text-slate-600 uppercase tracking-wider">Tambah Role Baru</h4>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
        <div>
          <label class="text-[10px] font-semibold text-slate-500 uppercase tracking-wider">Nama Role (slug)</label>
          <input
            :value="newRole.name"
            @input="$emit('update:newRole', { ...newRole, name: $event.target.value })"
            type="text"
            placeholder="contoh: supervisor"
            class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 bg-white"
          />
        </div>
        <div>
          <label class="text-[10px] font-semibold text-slate-500 uppercase tracking-wider">Nama Tampilan</label>
          <input
            :value="newRole.display_name"
            @input="$emit('update:newRole', { ...newRole, display_name: $event.target.value })"
            type="text"
            placeholder="contoh: Supervisor"
            class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 bg-white"
          />
        </div>
      </div>
      <div class="flex items-center gap-4 flex-wrap">
        <div class="flex items-center gap-2">
          <input id="newRoleCanApprove" :checked="newRole.can_approve" @change="$emit('update:newRole', { ...newRole, can_approve: $event.target.checked })" type="checkbox" class="w-4 h-4 text-indigo-600 rounded border-slate-300 focus:ring-indigo-500" />
          <label for="newRoleCanApprove" class="text-xs text-slate-600">Dapat Approval</label>
        </div>
        <div class="flex items-center gap-2">
          <input id="newRoleCanReport" :checked="newRole.can_report" @change="$emit('update:newRole', { ...newRole, can_report: $event.target.checked })" type="checkbox" class="w-4 h-4 text-indigo-600 rounded border-slate-300 focus:ring-indigo-500" />
          <label for="newRoleCanReport" class="text-xs text-slate-600">Dapat Report</label>
        </div>
        <div class="flex items-center gap-2">
          <input id="newRoleCanApproveUnlock" :checked="newRole.can_approve_unlock" @change="$emit('update:newRole', { ...newRole, can_approve_unlock: $event.target.checked })" type="checkbox" class="w-4 h-4 text-indigo-600 rounded border-slate-300 focus:ring-indigo-500" />
          <label for="newRoleCanApproveUnlock" class="text-xs text-slate-600">Dapat Approve Kunci</label>
        </div>
      </div>
      <button
        @click="$emit('addRole')"
        :disabled="!isNewRoleValid || roleConfigLoading"
        class="w-full md:w-auto px-4 py-2 bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 text-white rounded-xl text-xs font-bold cursor-pointer transition-all flex items-center justify-center gap-1.5"
      >
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        Tambah Role
      </button>
    </div>

    <!-- Roles Table -->
    <div class="overflow-x-auto">
      <table class="w-full text-xs">
        <thead class="bg-slate-50 text-slate-500 uppercase tracking-wider">
          <tr>
            <th class="px-3 py-2 text-left font-semibold">Nama</th>
            <th class="px-3 py-2 text-left font-semibold">Tampilan</th>
            <th class="px-3 py-2 text-center font-semibold">Approve</th>
            <th class="px-3 py-2 text-center font-semibold">Report</th>
            <th class="px-3 py-2 text-center font-semibold">Acc Kunci</th>
            <th class="px-3 py-2 text-left font-semibold">Kategori Wajib</th>
            <th v-if="roleEditMode" class="px-3 py-2 text-right font-semibold">Aksi</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <tr v-for="role in localRoles" :key="role.id" class="hover:bg-slate-50">
            <td class="px-3 py-2">
              <input
                v-if="roleEditMode && !isCoreSystemRole(role)"
                :value="role.name"
                @input="$emit('updateRoleName', { role, value: $event.target.value })"
                type="text"
                class="w-full rounded-lg border border-slate-200 px-2 py-1 text-xs text-slate-700 focus:outline-none focus:ring-1 focus:ring-indigo-500 bg-white"
              />
              <span v-else class="text-xs text-slate-700 font-medium">{{ role.name }}</span>
            </td>
            <td class="px-3 py-2">
              <input
                v-if="roleEditMode"
                :value="role.display_name"
                @input="$emit('updateRoleDisplayName', { role, value: $event.target.value })"
                type="text"
                class="w-full rounded-lg border border-slate-200 px-2 py-1 text-xs text-slate-700 focus:outline-none focus:ring-1 focus:ring-indigo-500 bg-white"
              />
              <span v-else class="text-xs text-slate-700">{{ role.display_name }}</span>
            </td>
            <td class="px-3 py-2 text-center">
              <input :checked="role.can_approve" @change="$emit('toggleCanApprove', { role, value: $event.target.checked })" type="checkbox" :disabled="!roleEditMode" class="w-4 h-4 text-indigo-600 rounded border-slate-300 focus:ring-indigo-500" />
            </td>
            <td class="px-3 py-2 text-center">
              <input :checked="role.can_report" @change="$emit('toggleCanReport', { role, value: $event.target.checked })" type="checkbox" :disabled="!roleEditMode" class="w-4 h-4 text-indigo-600 rounded border-slate-300 focus:ring-indigo-500" />
            </td>
            <td class="px-3 py-2 text-center">
              <input :checked="role.can_approve_unlock" @change="$emit('toggleCanApproveUnlock', { role, value: $event.target.checked })" type="checkbox" :disabled="!roleEditMode" class="w-4 h-4 text-indigo-600 rounded border-slate-300 focus:ring-indigo-500" />
            </td>
            <td class="px-3 py-2">
              <div v-if="role.can_report" class="flex items-center gap-2 flex-wrap">
                <label v-for="diff in difficultyOptions" :key="diff.value" class="flex items-center gap-1 text-[11px] text-slate-600" :class="roleEditMode ? 'cursor-pointer' : 'cursor-not-allowed opacity-70'">
                  <input
                    type="checkbox"
                    :checked="(role.required_difficulties || []).includes(diff.value)"
                    @change="$emit('toggleDifficulty', { role, diffValue: diff.value })"
                    :disabled="!roleEditMode"
                    class="w-3.5 h-3.5 text-indigo-600 rounded border-slate-300 focus:ring-indigo-500"
                  />
                  {{ diff.label }}
                </label>
              </div>
              <span v-else class="text-[10px] text-slate-400 italic">—</span>
            </td>
            <td v-if="roleEditMode" class="px-3 py-2 text-right">
              <button
                v-if="!isSystemRole(role)"
                @click="$emit('deleteRole', role)"
                :disabled="roleConfigLoading"
                class="p-1.5 text-red-500 hover:bg-red-50 rounded-lg transition-all cursor-pointer"
                title="Hapus"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Bottom Action buttons (edit mode only) -->
    <div v-if="roleEditMode" class="flex items-center justify-end gap-3 pt-6 border-t border-slate-100">
      <button
        @click="$emit('cancel')"
        class="px-5 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-xl text-xs font-bold cursor-pointer transition-all"
      >
        Batal
      </button>
      <button
        @click="$emit('save')"
        :disabled="roleConfigLoading || !isRoleNamesValid"
        class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 text-white rounded-xl text-xs font-bold flex items-center gap-1.5 cursor-pointer transition-all shadow-sm"
      >
        <svg v-if="roleConfigLoading" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
        <span>Simpan Semua Role</span>
      </button>
    </div>
  </div>
</template>

<script setup>
defineProps({
  roleEditMode: { type: Boolean, default: false },
  localRoles: { type: Array, default: () => [] },
  difficultyOptions: { type: Array, default: () => [] },
  roleConfigLoading: { type: Boolean, default: false },
  newRole: { type: Object, default: () => ({ name: '', display_name: '', can_approve: true, can_report: false, can_approve_unlock: false, is_active: true }) },
  isNewRoleValid: { type: Boolean, default: false },
  isRoleNamesValid: { type: Boolean, default: false },
  isCoreSystemRole: { type: Function, default: () => false },
  isSystemRole: { type: Function, default: () => false },
});

defineEmits(['edit', 'cancel', 'save', 'addRole', 'deleteRole', 'toggleDifficulty', 'update:newRole', 'updateRoleName', 'updateRoleDisplayName', 'toggleCanApprove', 'toggleCanReport', 'toggleCanApproveUnlock']);
</script>
