<template>
  <div v-if="show && machine" class="fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm" @click="emit('close')"></div>
    <div class="bg-white rounded-2xl w-full max-w-md relative z-10 shadow-2xl overflow-hidden">
      <div class="px-6 py-4 border-b border-slate-100 flex justify-between items-center">
        <div>
          <h3 class="text-base font-bold text-slate-800">Konfirmasi Laporan</h3>
          <p class="text-xs text-slate-400 mt-0.5">Periksa ringkasan sebelum mengirim</p>
        </div>
        <button @click="emit('close')" class="text-slate-400 hover:text-slate-600 p-1.5 rounded-full hover:bg-slate-100 cursor-pointer">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
      </div>
      <div class="p-6 space-y-4">
        <div class="bg-slate-50 rounded-xl p-4 space-y-2 text-sm">
          <div class="flex justify-between gap-4"><span class="text-slate-500">Mesin</span><span class="font-semibold text-slate-700 text-right">{{ machine.name }}</span></div>
          <div class="flex justify-between gap-4"><span class="text-slate-500">Jadwal bulan ini</span><span class="font-semibold text-slate-700 text-right">{{ scheduleLabel }}</span></div>
          <div class="flex justify-between gap-4"><span class="text-slate-500">Status jadwal</span><span class="font-bold text-right" :class="scheduleClass">{{ scheduleStatus }}</span></div>
          <div class="flex justify-between"><span class="text-slate-500">Komponen ringan</span><span class="font-bold text-emerald-600">{{ lightReportedCount }} / {{ lightComponentCount }}</span></div>
          <div class="flex justify-between"><span class="text-slate-500">Komponen berat</span><span class="font-bold text-emerald-600">{{ heavyReportedCount }} / {{ heavyComponentCount }}</span></div>
        </div>
        <div class="grid grid-cols-3 gap-3">
          <div class="space-y-1">
            <label class="text-[11px] font-bold text-slate-500 uppercase tracking-wide">Jam Mulai</label>
            <input type="time" :value="startTime" @input="emit('update:startTime', $event.target.value)" required class="w-full text-sm border border-slate-200 rounded-xl px-3 py-2 focus:outline-none focus:ring-1 focus:ring-brand-brown text-slate-700"/>
          </div>
          <div class="space-y-1">
            <label class="text-[11px] font-bold text-slate-500 uppercase tracking-wide">Jam Selesai</label>
            <input type="time" :value="endTime" @input="emit('update:endTime', $event.target.value)" required class="w-full text-sm border border-slate-200 rounded-xl px-3 py-2 focus:outline-none focus:ring-1 focus:ring-brand-brown text-slate-700"/>
          </div>
          <div class="space-y-1">
            <label class="text-[11px] font-bold text-slate-500 uppercase tracking-wide">Durasi</label>
            <div class="w-full text-sm border border-slate-100 rounded-xl px-3 py-2 bg-slate-50 text-slate-500 font-mono select-none">
              {{ duration > 0 ? duration + ' menit' : '-' }}
            </div>
          </div>
        </div>
        <div class="space-y-1.5">
          <label class="text-xs font-bold text-slate-600 uppercase tracking-wide">Catatan Umum</label>
          <textarea :value="notes" @input="emit('update:notes', $event.target.value)" placeholder="Ringkasan pekerjaan atau kendala..." rows="3"
            class="w-full text-sm border border-slate-200 rounded-xl px-3.5 py-2.5 focus:outline-none focus:ring-1 focus:ring-brand-brown text-slate-700 placeholder-slate-400 resize-none"></textarea>
        </div>
      </div>
      <div class="px-6 py-4 border-t border-slate-100 bg-slate-50 flex justify-end gap-3">
        <button @click="emit('close')" class="px-4 py-2 text-slate-500 hover:text-slate-800 text-sm font-semibold cursor-pointer">Batal</button>
        <button @click="emit('submit')" :disabled="submitting"
          class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-sm rounded-xl shadow cursor-pointer disabled:opacity-60 flex items-center gap-2">
          <svg v-if="submitting" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
          Kirim Laporan
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
defineProps({
  show: { type: Boolean, default: false },
  machine: { type: Object, default: null },
  scheduleLabel: { type: String, default: '' },
  scheduleStatus: { type: String, default: '' },
  scheduleClass: { type: String, default: '' },
  lightReportedCount: { type: Number, default: 0 },
  lightComponentCount: { type: Number, default: 0 },
  heavyReportedCount: { type: Number, default: 0 },
  heavyComponentCount: { type: Number, default: 0 },
  startTime: { type: String, default: '' },
  endTime: { type: String, default: '' },
  duration: { type: Number, default: 0 },
  notes: { type: String, default: '' },
  submitting: { type: Boolean, default: false },
});

const emit = defineEmits(['close', 'submit', 'update:startTime', 'update:endTime', 'update:notes']);
</script>
