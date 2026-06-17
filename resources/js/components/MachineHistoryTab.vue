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
            </div>
            <div class="flex items-center gap-2 flex-wrap justify-end">
              <span class="text-xs font-semibold px-2 py-1 rounded-lg" :class="{
                'bg-green-100 text-green-700': record.status === 'completed',
                'bg-amber-100 text-amber-700': record.status === 'in_progress',
                'bg-slate-200 text-slate-700': record.status === 'planned'
              }">{{ record.status.toUpperCase() }}</span>

              <span v-if="record.approval" class="text-xs font-semibold px-2 py-1 rounded-lg border" :class="{
                'bg-amber-100 text-amber-800 border-amber-200': record.approval.decision === 'pending',
                'bg-emerald-100 text-emerald-800 border-emerald-200': record.approval.decision === 'approved',
                'bg-rose-100 text-rose-800 border-rose-200': record.approval.decision === 'rejected'
              }">
                {{ record.approval.decision === 'pending' ? 'MENUNGGU APPROVAL' : record.approval.decision === 'approved' ? 'DISETUJUI' : 'DITOLAK' }}
              </span>
              <span v-else class="text-xs font-semibold px-2 py-1 rounded-lg border bg-amber-100 text-amber-800 border-amber-200">
                MENUNGGU APPROVAL
              </span>
            </div>
          </div>

          <p class="text-sm text-slate-600 italic mb-3">{{ record.notes || 'Tidak ada catatan.' }}</p>

          <div v-if="record.actions?.length > 0" class="space-y-2">
            <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Tindakan:</p>
            <div v-for="action in record.actions" :key="action.id" class="flex items-start gap-2 bg-white border border-slate-100 p-2 rounded-lg">
              <span :class="actionTypeClass(action.action_type)" class="text-xs font-semibold px-2 py-0.5 rounded-full flex-shrink-0">{{ action.action_type }}</span>
              <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-slate-800 truncate">{{ action.component?.name ?? '-' }}</p>
                <p v-if="action.description" class="text-xs text-slate-500 mt-0.5">{{ action.description }}</p>
              </div>
              <div class="text-xs text-slate-400 flex-shrink-0 text-right">
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
const props = defineProps({
  records: { type: Array, default: () => [] },
  formatDateTime: { type: Function, required: true },
});

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
