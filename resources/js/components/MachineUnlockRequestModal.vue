<template>
  <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6">
    <!-- Backdrop -->
    <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" @click="handleClose"></div>

    <!-- Modal Content -->
    <div class="relative bg-white rounded-3xl shadow-2xl w-full max-w-lg overflow-hidden transform transition-all flex flex-col max-h-[90vh]">
      <!-- Header -->
      <div class="px-6 py-5 border-b border-slate-100 flex items-center justify-between bg-white sticky top-0 z-10">
        <div class="flex items-center gap-3">
          <div class="p-2.5 bg-amber-50 text-amber-600 rounded-2xl">
            <Lock class="w-6 h-6" />
          </div>
          <div>
            <h3 class="text-lg font-black text-slate-800 tracking-tight">Pengajuan Buka Kunci</h3>
            <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">
              {{ machine?.name }}
              <span v-if="requestedPeriod" class="text-brand-brown"> · {{ requestedPeriod }}</span>
            </p>
          </div>
        </div>
        <button @click="handleClose" class="p-2 text-slate-400 hover:text-slate-600 hover:bg-slate-50 rounded-xl transition-colors cursor-pointer">
          <X class="w-6 h-6" />
        </button>
      </div>

      <!-- Body -->
      <div class="p-6 overflow-y-auto">
        <div class="space-y-6">
          <div class="bg-amber-50 border border-amber-100 rounded-2xl p-4 flex gap-3">
            <AlertCircle class="w-5 h-5 text-amber-600 shrink-0 mt-0.5" />
            <div>
              <p class="text-sm font-bold text-amber-900 mb-1">Mesin Terkunci / Expired</p>
              <p class="text-xs text-amber-700 leading-relaxed">
                Jadwal maintenance mesin ini sudah terlewat atau belum masuk jendela pengerjaan. 
                Silakan isi alasan pengajuan untuk membuka pengisian laporan.
              </p>
            </div>
          </div>

          <div class="space-y-2">
            <label class="block text-xs font-black text-slate-500 uppercase tracking-widest ml-1">Alasan Pengajuan</label>
            <textarea
              v-model="reason"
              rows="4"
              placeholder="Contoh: Mesin baru bisa diperbaiki hari ini karena kendala sparepart..."
              class="w-full px-4 py-3 rounded-2xl border border-slate-200 focus:ring-4 focus:ring-brand-brown/10 focus:border-brand-brown outline-none transition-all text-sm text-slate-700 placeholder:text-slate-400 resize-none"
              :disabled="loading"
            ></textarea>
            <p class="text-[10px] text-slate-400 italic ml-1">* Alasan akan ditinjau oleh Factory Manager/Admin</p>
          </div>
        </div>
      </div>

      <!-- Footer -->
      <div class="px-6 py-5 border-t border-slate-100 bg-slate-50 flex items-center justify-end gap-3 sticky bottom-0 z-10">
        <button
          @click="handleClose"
          class="px-5 py-2.5 text-sm font-bold text-slate-500 hover:text-slate-700 hover:bg-slate-100 rounded-xl transition-all cursor-pointer"
          :disabled="loading"
        >
          Batal
        </button>
        <button
          @click="submitRequest"
          :disabled="loading || !reason.trim()"
          class="px-6 py-2.5 bg-linear-to-tr from-brand-brown to-brand-gradation hover:opacity-90 disabled:opacity-50 disabled:cursor-not-allowed text-white text-sm font-bold rounded-xl shadow-lg shadow-brand-brown/20 flex items-center gap-2 transition-all cursor-pointer"
        >
          <Loader2 v-if="loading" class="w-4 h-4 animate-spin" />
          <Send v-else class="w-4 h-4" />
          Kirim Pengajuan
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { Lock, X, AlertCircle, Send, Loader2 } from 'lucide-vue-next';
import axios from 'axios';
import { showAlert } from '../composables/useAlert.js';

const props = defineProps({
  show: Boolean,
  machine: Object,
  requestedPeriod: String
});

const emit = defineEmits(['close', 'submitted']);

const reason = ref('');
const loading = ref(false);

const handleClose = () => {
  if (loading.value) return;
  reason.value = '';
  emit('close');
};

const submitRequest = async () => {
  if (!reason.value.trim() || !props.machine?.id) return;
  
  loading.value = true;
  try {
    const response = await axios.post(`/api/machines/${props.machine.id}/request-unlock`, {
      reason: reason.value.trim(),
      requested_period: props.requestedPeriod
    });
    
    await showAlert('success', 'Berhasil', response.data.message);
    emit('submitted', response.data.machine);
    handleClose();
  } catch (error) {
    showAlert('error', 'Gagal', error.response?.data?.message || 'Gagal mengirim pengajuan.');
  } finally {
    loading.value = false;
  }
};
</script>
