<template>
  <div class="bg-white rounded-3xl border border-slate-100 p-6 shadow-sm space-y-6">
    <div class="flex items-center justify-between">
      <div>
        <h3 class="text-lg font-bold text-slate-800">Alur Approval</h3>
        <p class="text-xs text-slate-500 mt-1">Alur approval disesuaikan berdasarkan role yang membuat laporan maintenance.</p>
      </div>
      <button
        v-if="!flowEditMode"
        @click="$emit('edit')"
        class="px-4 py-2 bg-indigo-50 border border-indigo-200 text-indigo-600 hover:bg-indigo-100 rounded-xl text-xs font-bold cursor-pointer transition-all flex items-center gap-1.5"
      >
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
        Edit Alur
      </button>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
      <!-- Sidebar: Reporter Roles -->
      <div class="space-y-2">
        <label class="text-xs font-bold text-slate-500 uppercase tracking-wider block mb-3">Role Pembuat Laporan</label>
        <!-- Default virtual role -->
        <button
          @click="$emit('update:activeReporterRole', 'default')"
          :class="activeReporterRole === 'default' 
            ? 'bg-indigo-50 border-indigo-200 text-indigo-700 font-bold' 
            : 'bg-slate-50 border-slate-100 hover:bg-slate-100 text-slate-600'"
          class="w-full text-left px-4 py-3 rounded-xl border text-sm font-semibold transition-all cursor-pointer flex items-center justify-between"
        >
          <span>Default</span>
          <span class="text-[10px] px-2.5 py-0.5 rounded-full bg-slate-200 text-slate-600 font-extrabold">
            {{ flowStepsForReporter.length }} Tahap
          </span>
        </button>
        <button
          v-for="role in reporterRoles"
          :key="role.name"
          @click="$emit('update:activeReporterRole', role.name)"
          :class="activeReporterRole === role.name 
            ? 'bg-indigo-50 border-indigo-200 text-indigo-700 font-bold' 
            : 'bg-slate-50 border-slate-100 hover:bg-slate-100 text-slate-600'"
          class="w-full text-left px-4 py-3 rounded-xl border text-sm font-semibold transition-all cursor-pointer flex items-center justify-between"
        >
          <span>{{ role.display_name || role.name }}</span>
          <span v-if="hasCustomFlow" class="text-[10px] px-2.5 py-0.5 rounded-full bg-emerald-100 text-emerald-700 font-extrabold">
            Custom
          </span>
          <span v-else class="text-[10px] px-2.5 py-0.5 rounded-full bg-slate-200 text-slate-500 font-extrabold">
            Default
          </span>
        </button>
      </div>

      <!-- Content: Flow Steps of the selected reporter role -->
      <div class="md:col-span-3 space-y-6">
        <div class="flex items-center justify-between">
          <h4 class="text-sm font-bold text-slate-700">Alur Approval untuk role: <span class="text-indigo-600 capitalize font-extrabold">{{ activeReporterDisplayName }}</span></h4>
          <span v-if="!isDefault && !hasCustomFlow" class="text-[10px] font-bold px-2.5 py-1 rounded-full bg-blue-50 text-blue-600 border border-blue-100">
            Menggunakan Default
          </span>
          <span v-else-if="!isDefault && hasCustomFlow" class="text-[10px] font-bold px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-600 border border-emerald-100">
            Custom
          </span>
        </div>

        <!-- Role uses default flow (not customized) -->
        <div v-if="!isDefault && !hasCustomFlow && flowStepsForReporter.length > 0" class="space-y-4">
          <div class="bg-blue-50 border border-blue-100 rounded-2xl p-4 text-center">
            <p class="text-sm text-blue-700 font-semibold">Role ini menggunakan alur Default.</p>
            <p class="text-xs text-blue-500 mt-1">Klik "Buat Alur Custom" untuk membuat alur yang berbeda.</p>
            <button
              v-if="!flowEditMode"
              @click="$emit('customize')"
              class="mt-3 px-4 py-2 bg-indigo-50 border border-indigo-200 text-indigo-600 hover:bg-indigo-100 rounded-xl text-xs font-bold transition-all cursor-pointer"
            >
              Buat Alur Custom
            </button>
          </div>
          <!-- Show the default steps read-only -->
          <div
            v-for="(step, index) in flowStepsForReporter"
            :key="index"
            class="flex items-center gap-4 bg-slate-50 p-4 rounded-2xl border border-slate-100 shadow-xs opacity-70"
          >
            <div class="w-8 h-8 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center text-sm font-bold shrink-0">
              {{ index + 1 }}
            </div>
            <div class="flex-1">
              <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Role Approver</label>
              <select
                :value="step.role"
                disabled
                class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm text-slate-700 bg-slate-100 cursor-not-allowed opacity-70 mt-1"
              >
                <option v-for="r in approvableRoles" :key="r.name" :value="r.name">
                  {{ r.display_name || r.name }}
                </option>
              </select>
            </div>
          </div>
        </div>

        <!-- No steps at all -->
        <div v-else-if="flowStepsForReporter.length === 0" class="border-2 border-dashed border-slate-200 rounded-2xl p-8 text-center text-slate-500">
          <p class="text-sm">Belum ada alur approval yang diatur untuk role ini.</p>
          <p v-if="!isDefault" class="text-xs text-slate-400 mt-1">Role ini akan menggunakan alur <strong>Default</strong> jika tidak dikustomisasi.</p>
          <button
            v-if="flowEditMode"
            @click="$emit('initializeDefault')"
            class="mt-4 px-4 py-2 bg-indigo-50 border border-indigo-200 text-indigo-600 hover:bg-indigo-100 rounded-xl text-xs font-bold transition-all cursor-pointer"
          >
            Buat Alur Baru
          </button>
        </div>

        <!-- Has custom or default steps -->
        <div v-else class="space-y-4">
          <div
            v-for="(step, index) in flowStepsForReporter"
            :key="index"
            class="flex items-center gap-4 bg-slate-50 p-4 rounded-2xl border border-slate-100 shadow-xs"
          >
            <div class="w-8 h-8 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center text-sm font-bold shrink-0">
              {{ index + 1 }}
            </div>
            <div class="flex-1">
              <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Role Approver</label>
              <select
                :value="step.role"
                @change="$emit('updateStepRole', { index, value: $event.target.value })"
                :disabled="!flowEditMode"
                :class="flowEditMode ? 'bg-white cursor-pointer' : 'bg-slate-100 cursor-not-allowed opacity-70'"
                class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 mt-1"
              >
                <option value="" disabled>Pilih role approver</option>
                <option v-for="r in approvableRoles" :key="r.name" :value="r.name">
                  {{ r.display_name || r.name }}
                </option>
              </select>
            </div>
            <button
              v-if="flowEditMode"
              @click="$emit('removeStep', index)"
              class="p-2.5 text-red-500 hover:bg-red-50 rounded-xl transition-all cursor-pointer mt-4"
              title="Hapus tahap"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
            </button>
          </div>

          <button
            v-if="flowEditMode && flowStepsForReporter.length < 10"
            @click="$emit('addStep')"
            class="w-full py-3 border-2 border-dashed border-slate-200 hover:border-indigo-400 text-slate-500 hover:text-indigo-600 rounded-xl text-xs font-bold transition-all cursor-pointer flex items-center justify-center gap-1.5 bg-slate-50/50 hover:bg-white"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Tambah Tahap Approval
          </button>
        </div>

        <!-- Reset to Default button (edit mode, custom flow) -->
        <div v-if="flowEditMode && !isDefault && hasCustomFlow" class="flex items-center justify-start pt-2">
          <button
            @click="$emit('resetToDefault')"
            class="px-4 py-2 bg-red-50 border border-red-200 text-red-600 hover:bg-red-100 rounded-xl text-xs font-bold transition-all cursor-pointer flex items-center gap-1.5"
          >
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
            Reset ke Default
          </button>
        </div>

        <!-- Bottom Action buttons (edit mode only) -->
        <div v-if="flowEditMode" class="flex items-center justify-end gap-3 pt-6 border-t border-slate-100">
          <button
            @click="$emit('cancel')"
            class="px-5 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-xl text-xs font-bold cursor-pointer transition-all"
          >
            Batal
          </button>
          <button
            @click="$emit('save')"
            :disabled="savingFlowConfig || !isFlowConfigValid"
            class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 text-white rounded-xl text-xs font-bold flex items-center gap-1.5 cursor-pointer transition-all shadow-sm"
          >
            <svg v-if="savingFlowConfig" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
            <span>Simpan Alur (Semua Role)</span>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
defineProps({
  flowEditMode: { type: Boolean, default: false },
  reporterRoles: { type: Array, default: () => [] },
  approvableRoles: { type: Array, default: () => [] },
  activeReporterRole: { type: String, default: 'default' },
  flowStepsForReporter: { type: Array, default: () => [] },
  activeReporterDisplayName: { type: String, default: '' },
  isFlowConfigValid: { type: Boolean, default: false },
  savingFlowConfig: { type: Boolean, default: false },
  hasCustomFlow: { type: Boolean, default: false },
  isDefault: { type: Boolean, default: false },
});

defineEmits(['edit', 'cancel', 'save', 'addStep', 'removeStep', 'initializeDefault', 'update:activeReporterRole', 'updateStepRole', 'customize', 'resetToDefault']);
</script>
