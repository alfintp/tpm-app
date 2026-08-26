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
            <tr v-else-if="paginatedItems.length === 0">
              <td colspan="6" class="px-6 py-12 text-center text-slate-400 font-medium italic">Tidak ada data pada halaman ini.</td>
            </tr>
            <tr v-for="item in paginatedItems" :key="item.unlock_request_id" class="hover:bg-slate-50/50 transition-colors group">
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
              <td class="px-6 py-4 max-w-[320px]">
                <p class="text-slate-600 italic text-xs whitespace-normal break-words" :title="item.unlock_reason">"{{ item.unlock_reason }}"</p>
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
                    @click="openModal(item, 'rejected')"
                    class="p-2 text-red-500 hover:bg-red-50 rounded-xl transition-all cursor-pointer"
                    title="Tolak Pengajuan"
                    :disabled="processingId === item.id"
                  >
                    <XCircle class="w-6 h-6" />
                  </button>
                  <button
                    @click="openModal(item, 'approved')"
                    class="p-2 text-emerald-500 hover:bg-emerald-50 rounded-xl transition-all cursor-pointer"
                    title="Setujui Pengajuan"
                    :disabled="processingId === item.id"
                  >
                    <CheckCircle2 class="w-6 h-6" />
                  </button>
                </div>
                <span v-else class="text-[10px] text-slate-400 italic">Sudah diproses</span>
                <div v-if="item.unlock_approved_at && item.unlock_status !== 'pending'" class="text-[10px] text-slate-500 mt-1">
                  <span class="font-semibold">{{ item.approver_name }}</span>
                  <span v-if="item.unlock_approval_notes" class="italic block whitespace-normal break-words" :class="item.unlock_status === 'rejected' ? 'text-red-500' : ''" :title="item.unlock_approval_notes">"{{ item.unlock_approval_notes }}"</span>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <TablePagination
        v-if="!loading && items.length > 0"
        :model-value="currentPage"
        @update:model-value="$emit('update:currentPage', $event)"
        :per-page="perPage"
        @update:per-page="$emit('update:perPage', $event)"
        :total="items.length"
        :show-per-page-selector="true"
      />
    </div>
  </div>

  <!-- Approval/Rejection Modal -->
  <Teleport to="body">
    <Transition name="fade">
      <div
        v-if="modal.show"
        class="fixed inset-0 z-50 flex items-center justify-center p-4"
        @click.self="closeModal"
      >
        <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm"></div>
        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden">
          <!-- Header -->
          <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between"
            :class="modal.decision === 'approved' ? 'bg-emerald-50' : 'bg-red-50'"
          >
            <h3 class="text-sm font-bold"
              :class="modal.decision === 'approved' ? 'text-emerald-800' : 'text-red-800'"
            >
              {{ modal.decision === 'approved' ? 'Setujui Pengajuan Buka Kunci' : 'Tolak Pengajuan Buka Kunci' }}
            </h3>
            <button @click="closeModal" class="text-slate-400 hover:text-slate-600 cursor-pointer p-1 rounded-lg hover:bg-white/50">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
          </div>

          <!-- Body -->
          <div class="p-6 space-y-4">
            <div v-if="modal.item" class="space-y-2">
              <div class="flex items-center gap-2">
                <span class="text-[10px] font-bold uppercase text-slate-400">Mesin</span>
                <span class="text-sm font-bold text-slate-800">{{ modal.item.name }}</span>
              </div>
              <div class="flex items-center gap-2">
                <span class="text-[10px] font-bold uppercase text-slate-400">Pemohon</span>
                <span class="text-sm font-semibold text-slate-700">{{ modal.item.requester_name }}</span>
              </div>
              <div class="bg-slate-50 rounded-lg p-3 border border-slate-100">
                <p class="text-[10px] font-bold uppercase text-slate-400 mb-1">Alasan Pengajuan</p>
                <p class="text-xs text-slate-600 italic">"{{ modal.item.unlock_reason }}"</p>
              </div>
            </div>

            <div>
              <label class="text-xs font-bold uppercase text-slate-400 mb-1.5 block">
                {{ modal.decision === 'approved' ? 'Catatan (Opsional)' : 'Alasan Penolakan (Wajib)' }}
              </label>
              <textarea
                v-model="modal.notes"
                :placeholder="modal.decision === 'approved' ? 'Tambahkan catatan untuk persetujuan ini...' : 'Masukkan alasan penolakan... (wajib diisi)'"
                rows="3"
                class="w-full rounded-xl border px-3 py-2 text-sm text-slate-700 focus:outline-none focus:ring-2 transition-all resize-none"
                :class="modal.decision === 'rejected' && !modal.notes.trim() ? 'border-red-300 focus:ring-red-200' : 'border-slate-200 focus:ring-brand-brown'"
              ></textarea>
              <p v-if="modal.decision === 'rejected' && !modal.notes.trim()" class="text-[10px] text-red-500 mt-1 font-semibold">Alasan penolakan wajib diisi.</p>
            </div>
          </div>

          <!-- Footer -->
          <div class="px-6 py-4 border-t border-slate-100 flex items-center justify-end gap-3 bg-slate-50/50">
            <button @click="closeModal" class="px-4 py-2 text-xs font-bold text-slate-600 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 cursor-pointer transition-all">
              Batal
            </button>
            <button
              @click="confirmAction"
              :disabled="modal.decision === 'rejected' && !modal.notes.trim()"
              class="px-4 py-2 text-xs font-bold text-white rounded-xl cursor-pointer transition-all disabled:opacity-50 disabled:cursor-not-allowed"
              :class="modal.decision === 'approved' ? 'bg-emerald-500 hover:bg-emerald-600' : 'bg-red-500 hover:bg-red-600'"
            >
              {{ modal.decision === 'approved' ? 'Setujui' : 'Tolak' }}
            </button>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { ref, computed } from 'vue';
import { XCircle, CheckCircle2, Calendar } from 'lucide-vue-next';
import axios from 'axios';
import { showAlert } from '../composables/useAlert.js';
import TablePagination from './TablePagination.vue';

const props = defineProps({
  items: { type: Array, default: () => [] },
  loading: Boolean,
  formatDateTime: Function,
  currentPage: { type: Number, default: 1 },
  perPage: { type: Number, default: 10 },
});

const emit = defineEmits(['refresh', 'go-to-machine', 'update:currentPage', 'update:perPage']);

const processingId = ref(null);
const modal = ref({ show: false, item: null, decision: 'approved', notes: '' });

const paginatedItems = computed(() => {
  const start = (props.currentPage - 1) * props.perPage;
  return props.items.slice(start, start + props.perPage);
});

const pendingCount  = computed(() => props.items.filter(i => i.unlock_status === 'pending').length);
const approvedCount = computed(() => props.items.filter(i => i.unlock_status === 'approved').length);
const rejectedCount = computed(() => props.items.filter(i => i.unlock_status === 'rejected').length);

const initials = (name) => {
  if (!name) return '?';
  return name.split(' ').slice(0, 2).map(n => n[0]?.toUpperCase()).join('');
};

const openModal = (item, decision) => {
  modal.value = { show: true, item, decision, notes: '' };
};

const closeModal = () => {
  modal.value = { show: false, item: null, decision: 'approved', notes: '' };
};

const confirmAction = async () => {
  const { item, decision, notes } = modal.value;
  if (decision === 'rejected' && !notes.trim()) return;
  processingId.value = item.id;
  closeModal();
  try {
    const response = await axios.post(`/api/machines/${item.id}/approve-unlock`, {
      decision: decision,
      notes: notes.trim() || null
    });
    await showAlert('success', 'Berhasil', response.data.message);
    emit('refresh');
  } catch (error) {
    const msg = error.response?.data?.message || 'Terjadi kesalahan saat memproses pengajuan.';
    const detail = error.response?.data?.errors?.notes?.[0];
    showAlert('error', 'Gagal', detail || msg);
  } finally {
    processingId.value = null;
  }
};
</script>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.2s ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
