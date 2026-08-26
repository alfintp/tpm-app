<template>
  <div class="space-y-6">
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
      <div class="px-6 py-4 border-b border-slate-50 bg-slate-50/50 flex items-center justify-between">
        <div>
          <h3 class="text-sm font-black text-slate-800 uppercase tracking-wider">Pengajuan Buka Kunci Saya</h3>
          <p class="text-xs text-slate-400 mt-0.5">Status pengajuan buka kunci yang Anda ajukan</p>
        </div>
        <div class="flex items-center gap-2 text-xs font-semibold">
          <span class="px-2 py-1 rounded-lg bg-amber-100 text-amber-700">{{ pendingCount }} Menunggu</span>
          <span class="px-2 py-1 rounded-lg bg-emerald-100 text-emerald-700">{{ approvedCount }} Disetujui</span>
          <span class="px-2 py-1 rounded-lg bg-red-100 text-red-700">{{ rejectedCount }} Ditolak</span>
        </div>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead>
            <tr class="text-left text-slate-400 border-b border-slate-50">
              <th class="px-6 py-4 font-bold uppercase text-[10px] tracking-widest">Mesin</th>
              <th class="px-6 py-4 font-bold uppercase text-[10px] tracking-widest">Alasan Pengajuan</th>
              <th class="px-6 py-4 font-bold uppercase text-[10px] tracking-widest">Waktu Pengajuan</th>
              <th class="px-6 py-4 font-bold uppercase text-[10px] tracking-widest">Status</th>
              <th class="px-6 py-4 font-bold uppercase text-[10px] tracking-widest">Approver</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-50">
            <tr v-if="loading" class="animate-pulse">
              <td colspan="5" class="px-6 py-12 text-center text-slate-400 font-medium italic">Memuat data...</td>
            </tr>
            <tr v-else-if="items.length === 0">
              <td colspan="5" class="px-6 py-12 text-center text-slate-400 font-medium italic">Belum ada pengajuan buka kunci.</td>
            </tr>
            <tr v-for="item in items" :key="item.unlock_request_id" class="hover:bg-slate-50/50 transition-colors group">
              <td class="px-6 py-4">
                <div class="font-black text-slate-800 group-hover:text-brand-brown transition-colors cursor-pointer" @click="$emit('go-to-machine', item.id)">
                  {{ item.name }}
                </div>
                <div class="text-[10px] font-bold text-slate-400 uppercase tracking-tighter mt-0.5">{{ item.kode }} · {{ item.location }}</div>
                <div v-if="item.unlock_requested_period" class="mt-1.5 inline-flex items-center gap-1 px-1.5 py-0.5 bg-brand-brown/10 text-brand-brown rounded text-[10px] font-black uppercase tracking-widest">
                  Jadwal: {{ item.unlock_requested_period }}
                </div>
              </td>
              <td class="px-6 py-4 max-w-[200px]">
                <p class="text-slate-600 line-clamp-2 italic text-xs" :title="item.unlock_reason">"{{ item.unlock_reason }}"</p>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-slate-500 font-medium text-xs">
                {{ formatDateTime(item.last_unlock_request_at) }}
              </td>
              <td class="px-6 py-4">
                <span
                  :class="{
                    'bg-amber-100 text-amber-700 border-amber-200': item.unlock_status === 'pending',
                    'bg-emerald-100 text-emerald-700 border-emerald-200': item.unlock_status === 'approved',
                    'bg-red-100 text-red-700 border-red-200': item.unlock_status === 'rejected',
                  }"
                  class="inline-flex items-center gap-1.5 text-[10px] font-black px-2.5 py-1 rounded-full border uppercase tracking-wider"
                >
                  <span
                    class="w-1.5 h-1.5 rounded-full"
                    :class="{
                      'bg-amber-500': item.unlock_status === 'pending',
                      'bg-emerald-500': item.unlock_status === 'approved',
                      'bg-red-500': item.unlock_status === 'rejected',
                    }"
                  ></span>
                  {{ item.unlock_status_label }}
                </span>
                <div v-if="item.unlock_expires_at && item.unlock_status === 'approved'" class="text-[10px] text-emerald-600 font-semibold mt-1">
                  Berlaku hingga: {{ formatDateTime(item.unlock_expires_at) }}
                </div>
              </td>
              <td class="px-6 py-4">
                <template v-if="item.unlock_approved_at">
                  <p class="text-xs font-semibold text-slate-700">{{ item.approver_name }}</p>
                  <p class="text-[10px] text-slate-400 mt-0.5">{{ formatDateTime(item.unlock_approved_at) }}</p>
                  <p v-if="item.unlock_approval_notes" class="text-[10px] italic mt-1 max-w-45 line-clamp-2" :class="item.unlock_status === 'rejected' ? 'text-red-500 font-semibold' : 'text-slate-500'" :title="item.unlock_approval_notes">"{{ item.unlock_approval_notes }}"</p>
                </template>
                <span v-else class="text-[10px] text-slate-400 italic">Belum diproses</span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  items: { type: Array, default: () => [] },
  loading: Boolean,
  formatDateTime: Function,
});

defineEmits(['refresh', 'go-to-machine']);

const pendingCount  = computed(() => props.items.filter(i => i.unlock_status === 'pending').length);
const approvedCount = computed(() => props.items.filter(i => i.unlock_status === 'approved').length);
const rejectedCount = computed(() => props.items.filter(i => i.unlock_status === 'rejected').length);
</script>
