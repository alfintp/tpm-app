<template>
  <div>
    <!-- Edit Profil Modal (Admin only) -->
    <div v-if="showEditModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm" @click="$emit('closeEdit')"></div>
      <div class="bg-white rounded-3xl shadow-2xl w-full max-w-sm relative z-10 p-8 space-y-6">
        <div class="flex justify-between items-center border-b border-slate-100 pb-4">
          <div>
            <h3 class="text-lg font-black text-brand-brown">Edit Profil User</h3>
            <p class="text-xs text-slate-500 mt-0.5">Ubah Nama atau Email</p>
          </div>
          <button @click="$emit('closeEdit')" class="text-slate-400 hover:text-slate-600 p-1.5 rounded-full hover:bg-slate-100 cursor-pointer">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
          </button>
        </div>
        <form @submit.prevent="$emit('submitEdit')" class="space-y-4">
          <div class="space-y-1.5">
            <label class="text-xs font-bold text-slate-700 uppercase tracking-wider">Nama Lengkap *</label>
            <input type="text" :value="editForm.full_name" @input="$emit('update:editForm', { ...editForm, full_name: $event.target.value })" required class="w-full rounded-xl border border-slate-300 px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-brand-brown focus:border-brand-brown text-slate-700 font-medium text-sm" placeholder="e.g. John Doe">
          </div>
          <div class="space-y-1.5">
            <label class="text-xs font-bold text-slate-700 uppercase tracking-wider">Email *</label>
            <input type="email" :value="editForm.email" @input="$emit('update:editForm', { ...editForm, email: $event.target.value })" required class="w-full rounded-xl border border-slate-300 px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-brand-brown focus:border-brand-brown text-slate-700 font-medium text-sm" placeholder="e.g. john@example.com">
          </div>
          <div class="pt-2 flex justify-end gap-3">
            <button type="button" @click="$emit('closeEdit')" class="px-5 py-2.5 rounded-xl font-bold text-sm text-slate-600 hover:bg-slate-100 transition-colors cursor-pointer">Batal</button>
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
      <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm" @click="$emit('closeMachines')"></div>
      <div class="bg-white rounded-3xl shadow-2xl w-full max-w-lg relative z-10 p-6 space-y-4 max-h-[80vh] overflow-hidden flex flex-col">
        <div class="flex justify-between items-center border-b border-slate-100 pb-4">
          <div>
            <h3 class="text-lg font-black text-brand-brown">Mesin yang Di-PIC</h3>
            <p class="text-xs text-slate-500 mt-0.5">{{ machinesTarget?.full_name }}</p>
          </div>
          <button @click="$emit('closeMachines')" class="text-slate-400 hover:text-slate-600 p-1.5 rounded-full hover:bg-slate-100 cursor-pointer">
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
          <button @click="$emit('closeMachines')" class="w-full px-5 py-2.5 rounded-xl font-bold text-sm text-slate-600 hover:bg-slate-100 transition-colors cursor-pointer">
            Tutup
          </button>
        </div>
      </div>
    </div>

    <!-- Change Password Modal (Admin only) -->
    <div v-if="showPasswordModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm" @click="$emit('closePassword')"></div>
      <div class="bg-white rounded-3xl shadow-2xl w-full max-w-sm relative z-10 p-8 space-y-6">
        <div class="flex justify-between items-center border-b border-slate-100 pb-4">
          <div>
            <h3 class="text-lg font-black text-brand-brown">Ubah Password</h3>
            <p class="text-xs text-slate-500 mt-0.5">{{ passwordTarget?.full_name }}</p>
          </div>
          <button @click="$emit('closePassword')" class="text-slate-400 hover:text-slate-600 p-1.5 rounded-full hover:bg-slate-100 cursor-pointer">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
          </button>
        </div>
        <form @submit.prevent="$emit('submitPassword')" class="space-y-4">
          <div class="space-y-1.5">
            <label class="text-xs font-bold text-slate-700 uppercase tracking-wider">Password Baru *</label>
            <input type="password" :value="newPassword" @input="$emit('update:newPassword', $event.target.value)" required minlength="6" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-brand-brown focus:border-brand-brown text-slate-700 font-medium text-sm" placeholder="Minimal 6 karakter">
          </div>
          <div class="pt-2 flex justify-end gap-3">
            <button type="button" @click="$emit('closePassword')" class="px-5 py-2.5 rounded-xl font-bold text-sm text-slate-600 hover:bg-slate-100 transition-colors cursor-pointer">Batal</button>
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
      <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm" @click="$emit('closeAdd')"></div>
      <div class="bg-white rounded-3xl shadow-2xl w-full max-w-md relative z-10 p-8 space-y-6">
        <div class="flex justify-between items-center border-b border-slate-100 pb-4">
          <h3 class="text-lg font-black text-brand-brown">Tambah User Baru</h3>
          <button @click="$emit('closeAdd')" class="text-slate-400 hover:text-slate-600 p-1.5 rounded-full hover:bg-slate-100 cursor-pointer">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
          </button>
        </div>
        
        <form @submit.prevent="$emit('submitAdd')" class="space-y-4">
          <div class="space-y-1.5">
            <label class="text-xs font-bold text-slate-700 uppercase tracking-wider">Nama Lengkap *</label>
            <input type="text" :value="addForm.full_name" @input="$emit('update:addForm', { ...addForm, full_name: $event.target.value })" required class="w-full rounded-xl border border-slate-300 px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-brand-brown focus:border-brand-brown text-slate-700 font-medium text-sm" placeholder="e.g. John Doe">
          </div>
          
          <div class="space-y-1.5">
            <label class="text-xs font-bold text-slate-700 uppercase tracking-wider">Email *</label>
            <input type="email" :value="addForm.email" @input="$emit('update:addForm', { ...addForm, email: $event.target.value })" required class="w-full rounded-xl border border-slate-300 px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-brand-brown focus:border-brand-brown text-slate-700 font-medium text-sm" placeholder="e.g. john@example.com">
          </div>
          
          <div class="space-y-1.5">
            <label class="text-xs font-bold text-slate-700 uppercase tracking-wider">Password *</label>
            <input type="password" :value="addForm.password" @input="$emit('update:addForm', { ...addForm, password: $event.target.value })" required minlength="6" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-brand-brown focus:border-brand-brown text-slate-700 font-medium text-sm" placeholder="••••••••">
          </div>
          
          <div class="space-y-1.5">
            <label class="text-xs font-bold text-slate-700 uppercase tracking-wider">Role *</label>
            <select :value="addForm.role" @change="$emit('update:addForm', { ...addForm, role: $event.target.value })" required class="w-full rounded-xl border border-slate-300 px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-brand-brown focus:border-brand-brown text-slate-700 font-bold text-sm cursor-pointer">
              <option v-for="role in activeRoles" :key="role.name" :value="role.name">
                {{ role.display_name }} 
              </option>
            </select>
          </div>

          <div class="space-y-1.5">
            <label class="text-xs font-bold text-slate-700 uppercase tracking-wider">Akses Kota</label>
            <select :value="addForm.city" @change="$emit('update:addForm', { ...addForm, city: $event.target.value })" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-brand-brown focus:border-brand-brown text-slate-700 font-bold text-sm cursor-pointer">
              <option value="both">Keduanya (Pasuruan & Surabaya)</option>
              <option value="pasuruan">Pasuruan saja</option>
              <option value="sby">Surabaya saja</option>
            </select>
            <p class="text-[11px] text-slate-400">Untuk teknisi, pembatasan ini menentukan mesin mana yang bisa dilihat.</p>
          </div>
          
          <div class="pt-4 flex justify-end gap-3">
            <button type="button" @click="$emit('closeAdd')" class="px-5 py-2.5 rounded-xl font-bold text-sm text-slate-600 hover:bg-slate-100 transition-colors cursor-pointer">Batal</button>
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
defineProps({
  showEditModal: { type: Boolean, default: false },
  editForm: { type: Object, default: () => ({ id: '', full_name: '', email: '' }) },
  savingEdit: { type: Boolean, default: false },
  showPasswordModal: { type: Boolean, default: false },
  passwordTarget: { type: Object, default: null },
  newPassword: { type: String, default: '' },
  savingPassword: { type: Boolean, default: false },
  showMachinesModal: { type: Boolean, default: false },
  machinesTarget: { type: Object, default: null },
  machinesLoading: { type: Boolean, default: false },
  userMachines: { type: Array, default: () => [] },
  showAddModal: { type: Boolean, default: false },
  addForm: { type: Object, default: () => ({ full_name: '', email: '', password: '', role: 'technician', city: 'both' }) },
  saving: { type: Boolean, default: false },
  activeRoles: { type: Array, default: () => [] },
  getMachineStatusClass: { type: Function, required: true },
  getMachineStatusLabel: { type: Function, required: true },
  getConditionColor: { type: Function, required: true },
});

defineEmits([
  'closeEdit', 'submitEdit', 'update:editForm',
  'closePassword', 'submitPassword', 'update:newPassword',
  'closeMachines',
  'closeAdd', 'submitAdd', 'update:addForm'
]);
</script>
