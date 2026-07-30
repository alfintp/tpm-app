<template>
  <div class="space-y-6">
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
      <div class="px-6 py-4 border-b border-slate-50 bg-slate-50/50 flex items-center justify-between">
        <div>
          <h3 class="text-sm font-black text-slate-800 uppercase tracking-wider">Riwayat Pengajuan Buka Kunci Mesin</h3>
          <p class="text-xs text-slate-400 mt-0.5">Daftar semua pengajuan buka kunci beserta progres persetujuannya</p>
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
              <th class="px-6 py-4 font-bold uppercase text-[10px] tracking-widest">Pemohon</th>
              <th class="px-6 py-4 font-bold uppercase text-[10px] tracking-widest">Alasan Pengajuan</th>
              <th class="px-6 py-4 font-bold uppercase text-[10px] tracking-widest">Waktu Pengajuan</th>
              <th class="px-6 py-4 font-bold uppercase text-[10px] tracking-widest">Status</th>
              <th class="px-6 py-4 font-bold uppercase text-[10px] tracking-widest text-right">Aksi</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-50">
            <tr v-if="loading" class="animate-pulse">
              <td colspan="6" class="px-6 py-12 text-center text-slate-400 font-medium italic">Memuat data...</td>
            </tr>
            <tr v-else-if="items.length === 0">
              <td colspan="6" class="px-6 py-12 text-center text-slate-400 font-medium italic">Belum ada pengajuan buka kunci.</td>
            </tr>
            <tr v-for="item in items" :key="item.id" class="hover:bg-slate-50/50 transition-colors group">
              <!-- Mesin -->
              <td class="px-6 py-4">
                <div class="font-black text-slate-800 group-hover:text-brand-brown transition-colors cursor-pointer" @click="$emit('go-to-machine', item.id)">
                  {{ item.name }}
                </div>
                <div class="text-[10px] font-bold text-slate-400 uppercase tracking-tighter mt-0.5">{{ item.kode }} · {{ item.location }}</div>
                <div v-if="item.unlock_requested_period" class="mt-1.5 inline-flex items-center gap-1 px-1.5 py-0.5 bg-brand-brown/10 text-brand-brown rounded text-[10px] font-black uppercase tracking-widest">
                  <Calendar class="w-2.5 h-2.5" />
                  Jadwal: {{ item.unlock_requested_period }}
                </div>
              </td>

              <!-- Pemohon -->
              <td class="px-6 py-4">
                <div class="flex items-center gap-2">
                  <div class="w-7 h-7 rounded-full bg-gradient-to-tr from-slate-400 to-slate-600 flex items-center justify-center text-white text-[10px] font-bold shrink-0">
                    {{ initials(item.requester_name) }}
                  </div>
                  <div>
                    <p class="font-semibold text-slate-700 text-xs">{{ item.requester_name }}</p>
                    <p class="text-[10px] text-slate-400 uppercase tracking-wide">{{ item.requester_role }}</p>
                  </div>
                </div>
              </td>

              <!-- Alasan -->
              <td class="px-6 py-4 max-w-[200px]">
                <p class="text-slate-600 line-clamp-2 italic text-xs" :title="item.unlock_reason">"{{ item.unlock_reason }}"</p>
              </td>

              <!-- Waktu -->
              <td class="px-6 py-4 whitespace-nowrap text-slate-500 font-medium text-xs">
                {{ formatDateTime(item.last_unlock_request_at) }}
                <div v-if="item.unlock_expires_at && item.unlock_status === 'approved'" class="text-[10px] text-emerald-600 font-semibold mt-0.5">
                  Berlaku hingga: {{ formatDateTime(item.unlock_expires_at) }}
                </div>
              </td>

              <!-- Status -->
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
              </td>

              <!-- Aksi (hanya untuk pending) -->
              <td class="px-6 py-4 text-right">
                <div v-if="item.unlock_status === 'pending'" class="flex items-center justify-end gap-2">
                  <button
                    @click="handleAction(item, 'rejected')"
                    class="p-2 text-red-500 hover:bg-red-50 rounded-xl transition-all cursor-pointer"
                    title="Tolak Pengajuan"
                    :disabled="processingId === item.id"
                  >
                    <XCircle class="w-6 h-6" />
                  </button>
                  <button
                    @click="handleAction(item, 'approved')"
                    class="p-2 text-emerald-500 hover:bg-emerald-50 rounded-xl transition-all cursor-pointer"
                    title="Setujui Pengajuan"
                    :disabled="processingId === item.id"
                  >
                    <CheckCircle2 class="w-6 h-6" />
                  </button>
                </div>
                <span v-else class="text-[10px] text-slate-400 italic">Sudah diproses</span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { XCircle, CheckCircle2, Calendar } from 'lucide-vue-next';
import axios from 'axios';
import { showAlert } from '../composables/useAlert.js';

const props = defineProps({
  items: { type: Array, default: () => [] },
  loading: Boolean,
  formatDateTime: Function
});

const emit = defineEmits(['refresh', 'go-to-machine']);

const processingId = ref(null);

const pendingCount  = computed(() => props.items.filter(i => i.unlock_status === 'pending').length);
const approvedCount = computed(() => props.items.filter(i => i.unlock_status === 'approved').length);
const rejectedCount = computed(() => props.items.filter(i => i.unlock_status === 'rejected').length);

const initials = (name) => {
  if (!name) return '?';
  return name.split(' ').slice(0, 2).map(n => n[0]?.toUpperCase()).join('');
};

const handleAction = async (machine, decision) => {
  processingId.value = machine.id;
  try {
    const response = await axios.post(`/api/machines/${machine.id}/approve-unlock`, {
      decision: decision
    });
    await showAlert('success', 'Berhasil', response.data.message);
    emit('refresh');
  } catch (error) {
    showAlert('error', 'Gagal', error.response?.data?.message || 'Terjadi kesalahan saat memproses pengajuan.');
  } finally {
    processingId.value = null;
  }
};
</script>
