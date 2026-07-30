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
                : (period.isLocked ? 'border-slate-100 bg-slate-50 text-slate-400 opacity-75' : 'border-slate-200 bg-white text-slate-600 hover:border-slate-300 hover:bg-slate-50'),
              'flex flex-col items-start px-4 py-2.5 rounded-xl border text-left transition-all cursor-pointer min-w-35 relative overflow-hidden'
            ]"
          >
            <div v-if="period.isLocked" class="absolute top-0 right-0 p-1">
              <svg class="w-3 h-3 text-slate-300" fill="currentColor" viewBox="0 0 24 24"><path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm-6 9c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm3.1-9H8.9V6c0-1.71 1.39-3.1 3.1-3.1 1.71 0 3.1 1.39 3.1 3.1v2z"/></svg>
            </div>
            <span class="text-xs font-bold">{{ period.label }}</span>
            <span class="text-[11px] mt-0.5" :class="selectedPeriod?.label === period.label ? 'text-brand-brown/70' : 'text-slate-400'">{{ period.dateStr }}</span>
            <span v-if="period.isLocked" class="text-[9px] font-bold mt-1 px-1.5 py-0.5 rounded bg-slate-200 text-slate-500 uppercase tracking-tighter">Locked</span>
            <span v-else class="text-[10px] font-semibold mt-1 px-1.5 py-0.5 rounded-md" :class="period.statusClass">{{ period.statusLabel }}</span>
            <span class="text-[10px] font-semibold mt-1" :class="period.progressPct === 100 ? 'text-emerald-600' : 'text-slate-500'">{{ period.progressCount }} / {{ period.componentCount }} komponen</span>
          </button>

          <button
            @click="emit('select-unscheduled')"
            :class="[
              isUnscheduled
                ? 'border-indigo-400 bg-indigo-50 text-indigo-700 ring-1 ring-indigo-300'
                : 'border-slate-200 bg-white text-slate-500 hover:border-slate-300 hover:bg-slate-50',
              'flex flex-col items-start px-4 py-2.5 rounded-xl border text-left transition-all cursor-pointer min-w-35'
            ]"
          >
            <span class="text-xs font-bold">Di Luar Jadwal</span>
            <span class="text-[11px] mt-0.5" :class="isUnscheduled ? 'text-indigo-500' : 'text-slate-400'">Maintenance di luar jadwal</span>
            <span class="text-[10px] font-semibold mt-1 px-1.5 py-0.5 rounded-md bg-indigo-100 text-indigo-600">Unscheduled</span>
          </button>
        </div>
      </div>

      <!-- Unlock Request Banner -->
      <div v-if="selectedPeriod?.isLocked && !isUnscheduled && machine" class="mt-4 p-4 bg-amber-50 rounded-2xl border border-amber-100 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
        <div class="flex items-center gap-3">
          <div class="p-2 bg-amber-100 text-amber-600 rounded-xl">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
          </div>
          <div>
            <p class="text-sm font-bold text-amber-900">Jadwal ini terkunci</p>
            <p class="text-xs text-amber-700">Jadwal maintenance sudah terlewat atau belum masuk jendela pengerjaan.</p>
            <p v-if="machine.unlock_status === 'pending'" class="text-[10px] font-bold text-amber-600 mt-0.5 italic">* Pengajuan sedang menunggu persetujuan Factory Manager.</p>
          </div>
        </div>
        <button
          v-if="machine.unlock_status !== 'pending'"
          @click="emit('request-unlock')"
          class="w-full sm:w-auto px-4 py-2 bg-amber-500 hover:bg-amber-600 text-white text-xs font-bold rounded-xl transition-all cursor-pointer shadow-sm flex items-center justify-center gap-2"
        >
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M8 11V7a4 4 0 118 0v4m0 0a2 2 0 100 4 2 2 0 000-4zm-8 4a2 2 0 110-4m0 4v5h8v-5"/></svg>
          Ajukan Buka Kunci
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  schedulePeriods: { type: Array, default: () => [] },
  selectedPeriod: { type: Object, default: null },
  isUnscheduled: { type: Boolean, default: false },
  machine: { type: Object, default: null },
});

const emit = defineEmits(['select-period', 'select-unscheduled', 'request-unlock']);

const hasLockedPeriods = computed(() => props.schedulePeriods.some(p => p.isLocked));
</script>
