<template>
  <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm" @click="$emit('close')"></div>
    <div class="bg-white rounded-3xl shadow-2xl w-full max-w-lg relative z-10 flex flex-col max-h-[85vh]">
      <!-- Header -->
      <div class="p-6 border-b border-slate-100 flex items-center justify-between shrink-0">
        <div>
          <h3 class="text-lg font-bold text-slate-800">Catatan Approval</h3>
          <p class="text-xs text-slate-500 mt-0.5">
            Mesin: <span class="font-semibold text-slate-700">{{ item?.machine_name ?? '-' }}</span>
          </p>
        </div>
        <button
          @click="$emit('close')"
          class="p-1.5 rounded-full hover:bg-slate-100 text-slate-400 hover:text-slate-600 transition-colors cursor-pointer"
        >
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
          </svg>
        </button>
      </div>

      <!-- List -->
      <div class="p-6 overflow-y-auto">
        <div v-if="!item?.approvals?.length" class="text-center py-10 text-slate-400 text-sm">
          Belum ada catatan approval untuk laporan ini.
        </div>
        <div v-else class="space-y-4">
          <div
            v-for="(approval, idx) in item.approvals"
            :key="idx"
            class="border border-slate-100 rounded-2xl p-4 bg-slate-50/50"
          >
            <div class="flex items-center justify-between gap-3 mb-2">
              <div class="flex items-center gap-2">
                <span class="text-xs font-bold text-slate-500 bg-white border border-slate-200 rounded-lg px-2 py-0.5">
                  Tahap {{ approval.step_order }}
                </span>
                <span
                  :class="approval.decision === 'approved'
                    ? 'bg-emerald-100 text-emerald-700 border-emerald-200'
                    : 'bg-red-100 text-red-700 border-red-200'"
                  class="text-[10px] font-bold px-2 py-0.5 rounded-full border uppercase tracking-wider"
                >
                  {{ approval.decision === 'approved' ? 'Disetujui' : 'Ditolak' }}
                </span>
              </div>
              <span class="text-[10px] text-slate-400">{{ formatDateTime(approval.decided_at) }}</span>
            </div>
            <p class="text-sm font-semibold text-slate-700 mb-1">{{ approval.approver ?? '-' }}</p>
            <p class="text-xs text-slate-500 leading-relaxed">
              Catatan:
              <span :class="!approval.notes ? 'italic text-slate-400' : 'text-slate-700'">
                {{ approval.notes || 'Tidak ada catatan' }}
              </span>
            </p>
          </div>
        </div>
      </div>

      <!-- Footer -->
      <div class="p-4 border-t border-slate-100 bg-slate-50 shrink-0 flex justify-end">
        <button
          @click="$emit('close')"
          class="px-4 py-2 bg-white hover:bg-slate-100 border border-slate-200 rounded-xl text-slate-600 text-xs font-semibold transition-colors cursor-pointer shadow-sm"
        >Tutup</button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { defineProps, defineEmits } from 'vue';

defineProps({
  show: { type: Boolean, required: true },
  item: { type: Object, default: null },
});

defineEmits(['close']);

const formatDateTime = (d) => {
  if (!d) return '-';
  return new Date(d).toLocaleString('id-ID', {
    day: 'numeric', month: 'short', year: 'numeric',
    hour: '2-digit', minute: '2-digit',
  });
};
</script>
