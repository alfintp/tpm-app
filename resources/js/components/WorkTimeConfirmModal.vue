<template>
  <div v-if="show" class="fixed inset-0 z-60 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl max-w-md w-full p-6 shadow-xl border border-slate-100 space-y-6">
      <!-- Header -->
      <div class="flex justify-between items-start">
        <div>
          <h3 class="text-xl font-bold text-slate-800 flex items-center gap-2">
            <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            Konfirmasi Waktu Pengerjaan
          </h3>
          <p class="text-xs text-slate-500 mt-1">
            Periksa jam mulai dan selesai sebelum menyimpan laporan.
          </p>
        </div>
        <button
          @click="$emit('close')"
          class="p-1.5 hover:bg-slate-100 rounded-xl transition-all cursor-pointer text-slate-400"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
          </svg>
        </button>
      </div>

      <!-- Work Time Fields -->
      <div class="flex flex-wrap items-end gap-4 bg-slate-50 p-4 rounded-2xl border border-slate-100">
        <div class="flex flex-col gap-1">
          <label class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Jam Mulai</label>
          <input
            :value="startTime"
            @input="$emit('update:startTime', $event.target.value)"
            type="time"
            required
            class="rounded-xl border border-slate-200 px-3 py-2 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-brand-brown"
          />
        </div>
        <div class="flex flex-col gap-1">
          <label class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Jam Selesai</label>
          <input
            :value="endTime"
            @input="$emit('update:endTime', $event.target.value)"
            type="time"
            required
            class="rounded-xl border border-slate-200 px-3 py-2 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-brand-brown"
          />
        </div>
        <div v-if="durationLabel" class="text-sm font-semibold text-brand-gradation bg-white border border-brand-cream px-3 py-2 rounded-xl shadow-sm">
          Durasi: {{ durationLabel }}
        </div>
      </div>

      <!-- Footer -->
      <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
        <button
          @click="$emit('close')"
          class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-xl text-xs font-bold cursor-pointer transition-all"
        >
          Batal
        </button>
        <button
          @click="$emit('confirm')"
          class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold flex items-center gap-1.5 cursor-pointer transition-all"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
          </svg>
          Simpan Laporan
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
defineProps({
  show: { type: Boolean, required: true },
  startTime: { type: String, default: '' },
  endTime: { type: String, default: '' },
  durationLabel: { type: String, default: '' },
});

defineEmits(['close', 'confirm', 'update:startTime', 'update:endTime']);
</script>
