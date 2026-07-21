<template>
  <div class="w-full max-w-5xl mx-auto bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
    <div class="px-5 py-3.5 border-b border-slate-100 flex items-center gap-2">
      <svg class="w-4 h-4 text-brand-brown shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
      <h2 class="text-sm font-bold text-slate-700">Pilih Jadwal Laporan</h2>
    </div>
    <div class="p-5 space-y-4">
      <div>
        <p class="text-xs font-semibold text-slate-500 mb-2.5">
          {{ schedulePeriods.length > 0 ? 'Jadwal tersedia bulan ini:' : 'Tidak ada jadwal aktif untuk mesin ini.' }}
        </p>
        <div class="flex flex-wrap gap-2">
          <button
            v-for="(period, idx) in schedulePeriods"
            :key="idx"
            @click="emit('select-period', period)"
            :class="[
              selectedPeriod?.label === period.label
                ? 'border-brand-brown bg-brand-cream text-brand-brown ring-1 ring-brand-brown/30'
                : 'border-slate-200 bg-white text-slate-600 hover:border-slate-300 hover:bg-slate-50',
              'flex flex-col items-start px-4 py-2.5 rounded-xl border text-left transition-all cursor-pointer min-w-[140px]'
            ]"
          >
            <span class="text-xs font-bold">{{ period.label }}</span>
            <span class="text-[11px] mt-0.5" :class="selectedPeriod?.label === period.label ? 'text-brand-brown/70' : 'text-slate-400'">{{ period.dateStr }}</span>
            <span class="text-[10px] font-semibold mt-1 px-1.5 py-0.5 rounded-md" :class="period.statusClass">{{ period.statusLabel }}</span>
            <span class="text-[10px] font-semibold mt-1" :class="period.progressPct === 100 ? 'text-emerald-600' : 'text-slate-500'">{{ period.progressCount }} / {{ period.componentCount }} komponen</span>
          </button>

          <button
            @click="emit('select-unscheduled')"
            :class="[
              isUnscheduled
                ? 'border-indigo-400 bg-indigo-50 text-indigo-700 ring-1 ring-indigo-300'
                : 'border-slate-200 bg-white text-slate-500 hover:border-slate-300 hover:bg-slate-50',
              'flex flex-col items-start px-4 py-2.5 rounded-xl border text-left transition-all cursor-pointer min-w-[140px]'
            ]"
          >
            <span class="text-xs font-bold">Di Luar Jadwal</span>
            <span class="text-[11px] mt-0.5" :class="isUnscheduled ? 'text-indigo-500' : 'text-slate-400'">Maintenance di luar jadwal</span>
            <span class="text-[10px] font-semibold mt-1 px-1.5 py-0.5 rounded-md bg-indigo-100 text-indigo-600">Unscheduled</span>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
defineProps({
  schedulePeriods: { type: Array, default: () => [] },
  selectedPeriod: { type: Object, default: null },
  isUnscheduled: { type: Boolean, default: false },
});

const emit = defineEmits(['select-period', 'select-unscheduled']);
</script>
