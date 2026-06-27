<template>
  <div class="space-y-2">
    <div class="flex items-center justify-between text-xs">
      <span class="font-semibold text-slate-600">Proses Approval</span>
      <span class="text-slate-500">{{ completedSteps }} dari {{ totalSteps }} tahap</span>
    </div>

    <div class="relative flex items-center justify-between">
      <!-- Connecting line -->
      <div class="absolute left-0 right-0 top-1/2 -translate-y-1/2 h-1 bg-slate-200 rounded-full z-0"></div>
      <div
        class="absolute left-0 top-1/2 -translate-y-1/2 h-1 bg-indigo-500 rounded-full transition-all duration-500 z-0"
        :style="{ width: progressWidth }"
      ></div>

      <!-- Step points -->
      <div
        v-for="step in flowSteps"
        :key="step.step_order"
        class="relative z-10 flex flex-col items-center gap-1"
      >
        <div
          class="w-6 h-6 rounded-full flex items-center justify-center text-[10px] font-bold border-2 transition-all duration-300"
          :class="stepPointClass(step.step_order)"
        >
          <svg v-if="isStepRejected(step.step_order)" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12"/>
          </svg>
          <svg v-else-if="isStepCompleted(step.step_order)" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
          </svg>
          <span v-else>{{ step.step_order }}</span>
        </div>
        <span class="text-[10px] font-semibold uppercase tracking-wide text-center" :class="stepLabelClass(step.step_order)">
          {{ step.role }}
        </span>
      </div>
    </div>

    <p class="text-xs text-slate-500 text-center">
      <span v-if="status === 'approved'" class="text-green-600 font-semibold">Laporan sudah disetujui sepenuhnya.</span>
      <span v-else-if="status === 'rejected'" class="text-red-600 font-semibold">Laporan ditolak pada tahap {{ currentStep }}.</span>
      <span v-else-if="pendingRole">Menunggu approval dari <span class="font-bold text-indigo-600">{{ roleLabel(pendingRole) }}</span>.</span>
      <span v-else>Menunggu konfigurasi alur approval.</span>
    </p>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  flowSteps: { type: Array, required: true },
  currentStep: { type: Number, default: 1 },
  completedSteps: { type: Number, default: 0 },
  totalSteps: { type: Number, default: 0 },
  status: { type: String, default: 'pending' },
  pendingRole: { type: String, default: null },
});

const isStepRejected = (step) => props.status === 'rejected' && step === props.currentStep;
const isStepCompleted = (step) => !isStepRejected(step) && step <= props.completedSteps;
const isStepCurrent = (step) => step === props.currentStep && props.status === 'pending';

const stepPointClass = (step) => {
  if (props.status === 'rejected' && step === props.currentStep) {
    return 'bg-red-100 border-red-500 text-red-600';
  }
  if (isStepCompleted(step)) {
    return 'bg-indigo-500 border-indigo-500 text-white';
  }
  if (isStepCurrent(step)) {
    return 'bg-indigo-100 border-indigo-500 text-indigo-600 ring-2 ring-indigo-200';
  }
  return 'bg-white border-slate-300 text-slate-400';
};

const stepLabelClass = (step) => {
  if (props.status === 'rejected' && step === props.currentStep) {
    return 'text-red-600';
  }
  if (isStepCompleted(step)) {
    return 'text-indigo-600';
  }
  if (isStepCurrent(step)) {
    return 'text-indigo-600';
  }
  return 'text-slate-400';
};

const progressWidth = computed(() => {
  if (props.totalSteps <= 1) return '100%';
  const fraction = props.status === 'approved'
    ? 1
    : (props.status === 'rejected' ? (props.currentStep - 1) / (props.totalSteps - 1) : Math.max(0, (props.currentStep - 1) / (props.totalSteps - 1)));
  return `${fraction * 100}%`;
});

const roleLabel = (role) => {
  const labels = {
    karo: 'Karo',
    qc: 'QC',
    wpv: 'WPV',
    manager: 'Manager',
    admin: 'Admin',
  };
  return labels[role] || role;
};
</script>
