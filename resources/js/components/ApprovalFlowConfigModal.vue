<template>
  <div v-if="show" class="fixed inset-0 z-60 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl max-w-lg w-full p-6 shadow-xl border border-slate-100 space-y-6">
      <!-- Header -->
      <div class="flex justify-between items-start">
        <div>
          <h3 class="text-xl font-bold text-slate-800 flex items-center gap-2">
            <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37 1 .608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
            Pengaturan Alur Approval
          </h3>
          <p class="text-xs text-slate-500 mt-1">
            Atur urutan dan role untuk setiap tahap approval.
          </p>
        </div>
        <button
          @click="$emit('close')"
          class="p-1.5 hover:bg-slate-100 rounded-xl transition-all cursor-pointer text-slate-400"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
      </div>

      <!-- Steps List -->
      <div class="space-y-3">
        <div
          v-for="(step, index) in localSteps"
          :key="index"
          class="flex items-center gap-3 bg-slate-50 p-3 rounded-2xl border border-slate-100"
        >
          <div class="w-7 h-7 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center text-xs font-bold">
            {{ index + 1 }}
          </div>
          <div class="flex-1">
            <label class="text-[10px] font-semibold text-slate-500 uppercase tracking-wider">Role Approval</label>
            <select
              v-model="step.role"
              class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-brand-brown bg-white"
            >
              <option value="" disabled>Pilih role</option>
              <option v-for="role in approvableRoles" :key="role.name" :value="role.name">
                {{ role.display_name || role.name }}
              </option>
            </select>
          </div>
          <button
            v-if="localSteps.length > 1"
            @click="removeStep(index)"
            class="p-2 text-red-400 hover:bg-red-50 rounded-xl transition-all cursor-pointer"
            title="Hapus tahap"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
          </button>
        </div>
      </div>

      <button
        v-if="localSteps.length < 10"
        @click="addStep"
        class="w-full py-2 border-2 border-dashed border-slate-200 hover:border-indigo-400 text-slate-500 hover:text-indigo-600 rounded-xl text-xs font-bold transition-all cursor-pointer flex items-center justify-center gap-1.5"
      >
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        Tambah Tahap
      </button>

      <!-- Footer -->
      <div class="flex items-center justify-between gap-3 pt-4 border-t border-slate-100">
        <button
          @click="$emit('manage-roles')"
          class="px-4 py-2 bg-white border border-slate-200 hover:border-indigo-400 text-slate-600 hover:text-indigo-600 rounded-xl text-xs font-bold cursor-pointer transition-all flex items-center gap-1.5"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
          Kelola Role
        </button>
        <div class="flex items-center gap-3">
          <button
            @click="$emit('close')"
            class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-xl text-xs font-bold cursor-pointer transition-all"
          >
            Batal
          </button>
          <button
            @click="save"
            :disabled="loading || !isValid"
            class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 text-white rounded-xl text-xs font-bold flex items-center gap-1.5 cursor-pointer transition-all"
          >
            <svg v-if="loading" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
            <span>Simpan Alur</span>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue';

const props = defineProps({
  show: { type: Boolean, required: true },
  steps: { type: Array, default: () => [] },
  roles: { type: Array, default: () => [] },
  loading: { type: Boolean, default: false },
});

const emit = defineEmits(['close', 'save', 'manage-roles']);

const approvableRoles = computed(() => props.roles.filter(r => r.can_approve && r.is_active));

const localSteps = ref([]);

watch(() => props.steps, (newSteps) => {
  localSteps.value = newSteps.map(s => ({ ...s }));
}, { immediate: true });

const isValid = computed(() => {
  return localSteps.value.length > 0 && localSteps.value.every(s => s.role);
});

const addStep = () => {
  if (localSteps.value.length < 10) {
    localSteps.value.push({ role: '' });
  }
};

const removeStep = (index) => {
  localSteps.value.splice(index, 1);
};

const save = () => {
  if (!isValid.value) return;
  emit('save', localSteps.value.map((s, idx) => ({ role: s.role, step_order: idx + 1 })));
};
</script>
