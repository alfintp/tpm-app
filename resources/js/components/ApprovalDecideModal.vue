<template>
  <div v-if="show" class="fixed inset-0 z-60 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm" @click="$emit('close')"></div>
    <div class="bg-white rounded-3xl shadow-2xl w-full max-w-sm relative z-10 p-6 space-y-4">
      <h3 class="text-lg font-semibold text-slate-800">
        {{ decision === 'approved' ? 'Setujui Report' : 'Tolak Report' }}
      </h3>
      <p class="text-sm text-slate-500">
        Apakah Anda yakin ingin {{ decision === 'approved' ? 'menyetujui' : 'menolak' }} report untuk mesin
        <span class="font-semibold text-slate-700">{{ item?.machine_name }}</span>?
      </p>
      <div class="space-y-1">
        <label class="text-xs font-semibold text-slate-600">
          Catatan {{ decision === 'rejected' ? '*' : '(opsional)' }}
        </label>
        <textarea
          v-model="notes"
          :placeholder="decision === 'rejected' ? 'Wajib: Tuliskan alasan penolakan...' : 'Tambahkan catatan (opsional)'"
          rows="3"
          class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-brand-brown resize-none"
          :class="decision === 'rejected' && showError && !notes.trim() ? 'border-red-400 ring-1 ring-red-400' : ''"
        ></textarea>
        <p v-if="decision === 'rejected' && showError && !notes.trim()" class="text-xs text-red-500 font-medium">
          Alasan penolakan wajib diisi.
        </p>
      </div>
      <div class="flex justify-end gap-3 pt-2">
        <button
          @click="$emit('close')"
          class="px-5 py-2.5 rounded-xl font-medium text-slate-600 hover:bg-slate-100 transition-colors cursor-pointer text-sm"
        >Batal</button>
        <button
          @click="handleSubmit"
          :disabled="loading"
          :class="decision === 'approved' ? 'bg-green-600 hover:bg-green-700' : 'bg-red-600 hover:bg-red-700'"
          class="px-5 py-2.5 rounded-xl font-medium text-white transition-colors shadow-sm cursor-pointer disabled:opacity-70 text-sm flex items-center gap-2"
        >
          <svg v-if="loading" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
          </svg>
          {{ decision === 'approved' ? 'Setujui' : 'Tolak' }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue';

const props = defineProps({
  show:     { type: Boolean, required: true },
  item:     { type: Object,  default: null },
  decision: { type: String,  default: 'approved' },
  loading:  { type: Boolean, default: false },
});

const emit = defineEmits(['close', 'submit']);

const notes = ref('');
const showError = ref(false);

watch(() => props.show, (val) => {
  if (val) {
    notes.value = '';
    showError.value = false;
  }
});

const handleSubmit = () => {
  if (props.decision === 'rejected' && !notes.value.trim()) {
    showError.value = true;
    return;
  }
  emit('submit', notes.value.trim());
};
</script>
