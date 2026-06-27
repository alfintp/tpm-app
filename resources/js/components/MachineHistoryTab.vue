<template>
  <div class="p-6">
    <div v-if="records.length === 0" class="text-center py-12 text-slate-400 text-sm">
      Belum ada riwayat pengerjaan.
    </div>

    <div v-else class="relative border-l-2 border-brand-cream ml-3 space-y-6 pb-4">
      <div v-for="record in records" :key="record.id" class="relative pl-6">
        <div class="absolute w-4 h-4 rounded-full bg-brand-brown border-4 border-white left-[-9px] top-1.5 shadow-sm"></div>
        <div class="bg-slate-50 rounded-xl p-4 border border-slate-100 hover:shadow-md transition-shadow">
          <div class="flex justify-between items-start mb-2 gap-2">
            <div>
              <span class="text-sm font-bold text-slate-800">{{ formatDateTime(record.maintenance_date) }}</span>
              <p class="text-xs text-slate-400 mt-0.5">Teknisi: {{ record.technician?.full_name ?? '-' }}</p>
              <p v-if="record.duration_minutes" class="text-xs text-brand-gradation font-semibold mt-0.5">
                <span class="inline-block bg-brand-cream px-2 py-0.5 rounded-lg">Durasi: {{ formatDuration(record.duration_minutes) }}</span>
                <span v-if="record.start_time || record.end_time" class="text-slate-400 font-normal">({{ record.start_time ?? '-' }} - {{ record.end_time ?? '-' }})</span>
              </p>
            </div>
            <div class="flex items-center gap-2 flex-wrap justify-end">
              <span class="text-xs font-semibold px-2 py-1 rounded-lg" :class="{
                'bg-green-100 text-green-700': record.status === 'completed',
                'bg-amber-100 text-amber-700': record.status === 'in_progress',
                'bg-slate-200 text-slate-700': record.status === 'planned'
              }">{{ record.status.toUpperCase() }}</span>
            </div>
          </div>

          <div class="mb-3">
            <ApprovalProgress
              v-if="flowSteps.length > 0"
              :flow-steps="flowSteps"
              v-bind="getRecordProgress(record)"
            />
            <span v-else class="text-xs font-semibold px-2 py-1 rounded-lg border bg-amber-100 text-amber-800 border-amber-200">
              MENUNGGU APPROVAL
            </span>
          </div>

          <p class="text-sm text-slate-600 italic mb-3">{{ record.notes || 'Tidak ada catatan.' }}</p>

          <div v-if="record.actions?.length > 0" class="space-y-2">
            <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Tindakan:</p>
            <div v-for="action in record.actions" :key="action.id" class="flex items-start gap-2 bg-white border border-slate-100 p-2 rounded-lg">
              <span :class="actionTypeClass(action.action_type)" class="text-xs font-semibold px-2 py-0.5 rounded-full shrink-0">{{ action.action_type }}</span>
              <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-slate-800 truncate">{{ action.component?.name ?? '-' }}</p>
                <p v-if="action.description" class="text-xs text-slate-500 mt-0.5">{{ action.description }}</p>
              </div>
              <div class="text-xs text-slate-400 shrink-0 text-right">
                {{ action.condition_before_pct ?? '-' }}% → <span class="text-brand-gradation font-medium">{{ action.condition_after_pct ?? '-' }}%</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';
import ApprovalProgress from './ApprovalProgress.vue';

const props = defineProps({
  records: { type: Array, default: () => [] },
  formatDateTime: { type: Function, required: true },
});

const flowSteps = ref([]);

const loadFlowConfig = async () => {
  try {
    const res = await axios.get('/api/approval-flow');
    flowSteps.value = res.data.steps || [];
  } catch (e) {
    console.error('Failed to load approval flow:', e);
  }
};

onMounted(loadFlowConfig);

const getRecordProgress = (record) => {
  const latest = record.latest_approval;
  const total = flowSteps.value.length;
  if (total === 0) {
    return { status: 'pending', currentStep: 1, completedSteps: 0, totalSteps: 0, pendingRole: null };
  }
  if (!latest) {
    return { status: 'pending', currentStep: 1, completedSteps: 0, totalSteps: total, pendingRole: flowSteps.value[0]?.role };
  }
  if (latest.decision === 'rejected') {
    return { status: 'rejected', currentStep: latest.step_order, completedSteps: latest.step_order - 1, totalSteps: total, pendingRole: null };
  }
  if (latest.decision === 'approved' && latest.step_order === total) {
    return { status: 'approved', currentStep: total, completedSteps: total, totalSteps: total, pendingRole: null };
  }
  const currentStep = latest.step_order + 1;
  return { status: 'pending', currentStep, completedSteps: latest.step_order, totalSteps: total, pendingRole: flowSteps.value[currentStep - 1]?.role };
};

const formatDuration = (minutes) => {
  if (minutes === null || minutes === undefined) return '-';
  if (minutes < 60) return `${minutes} menit`;
  const h = Math.floor(minutes / 60);
  const rem = minutes % 60;
  return rem ? `${h} jam ${rem} menit` : `${h} jam`;
};

const actionTypeClass = (type) => {
  const map = {
    replace:   'bg-red-100 text-red-700',
    repair:    'bg-orange-100 text-orange-700',
    inspect:   'bg-blue-100 text-blue-700',
    clean:     'bg-teal-100 text-teal-700',
    lubricate: 'bg-purple-100 text-purple-700',
  };
  return map[type] || 'bg-slate-100 text-slate-700';
};
</script>
