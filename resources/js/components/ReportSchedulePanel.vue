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
              'flex flex-col items-start px-3 py-2 rounded-lg border text-left transition-all cursor-pointer min-w-[130px] relative overflow-hidden'
            ]"
          >
            <div v-if="period.isLocked" class="absolute top-0 right-0 p-1">
              <svg class="w-3 h-3 text-slate-300" fill="currentColor" viewBox="0 0 24 24"><path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm-6 9c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm3.1-9H8.9V6c0-1.71 1.39-3.1 3.1-3.1 1.71 0 3.1 1.39 3.1 3.1v2z"/></svg>
            </div>
            <div class="flex items-center gap-1">
              <svg class="w-3 h-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
              <span class="text-xs font-bold">{{ period.dateStr }}</span>
            </div>
            <span class="text-[10px] mt-0.5 opacity-70">{{ period.label }}</span>
            <div class="flex items-center gap-1.5 mt-1">
              <span v-if="period.isLocked" class="text-[9px] font-bold px-1.5 py-0.5 rounded bg-slate-200 text-slate-500 uppercase tracking-tighter">Locked</span>
              <span v-else class="text-[10px] font-semibold px-1.5 py-0.5 rounded-md" :class="period.statusClass">{{ period.statusLabel }}</span>
              <span class="text-[10px] font-semibold" :class="period.progressPct === 100 ? 'text-emerald-600' : 'text-slate-500'">{{ period.progressCount }}/{{ period.componentCount }}</span>
            </div>
          </button>

          <button
            @click="emit('select-unscheduled')"
            :class="[
              isUnscheduled
                ? 'border-indigo-400 bg-indigo-50 text-indigo-700 ring-1 ring-indigo-300'
                : 'border-slate-200 bg-white text-slate-500 hover:border-slate-300 hover:bg-slate-50',
              'flex flex-col items-start px-3 py-2 rounded-lg border text-left transition-all cursor-pointer min-w-[130px]'
            ]"
          >
            <div class="flex items-center gap-1">
              <svg class="w-3 h-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
              <span class="text-xs font-bold">Di Luar Jadwal</span>
            </div>
            <span class="text-[10px] mt-0.5 opacity-70">Maintenance tambahan</span>
            <span class="text-[10px] font-semibold mt-1 px-1.5 py-0.5 rounded-md bg-indigo-100 text-indigo-600">Unscheduled</span>
          </button>
        </div>
      </div>

      <!-- Unlock Request Banner -->
      <div v-if="selectedPeriod?.isLocked && !isUnscheduled && machine" class="mt-4 p-4 rounded-2xl border flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3"
        :class="selectedPeriod.diffDays > 0
          ? 'bg-blue-50 border-blue-100'
          : 'bg-red-50 border-red-100'">
        <div class="flex items-center gap-3">
          <div class="p-2 rounded-xl"
            :class="selectedPeriod.diffDays > 0
              ? 'bg-blue-100 text-blue-600'
              : 'bg-red-100 text-red-600'">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
          </div>
          <div>
            <p class="text-sm font-bold"
              :class="selectedPeriod.diffDays > 0 ? 'text-blue-900' : 'text-red-900'">
              {{ selectedPeriod.diffDays > 0 ? 'Belum waktunya pengecekan' : 'Jadwal terlewat' }}
            </p>
            <p class="text-xs"
              :class="selectedPeriod.diffDays > 0 ? 'text-blue-700' : 'text-red-700'">
              {{ selectedPeriod.diffDays > 0
                ? `Jadwal maintenance: ${selectedPeriod.dateStr} (${selectedPeriod.diffDays} hari lagi). Pengecekan bisa dilakukan ${daysBefore} hari sebelum jadwal.`
                : `Terlambat ${Math.abs(selectedPeriod.diffDays)} hari dari jadwal (${selectedPeriod.dateStr})` }}
            </p>
          </div>
        </div>
        <button
          v-if="selectedPeriod.diffDays < 0 && machine.unlock_status !== 'pending' && machine.unlock_status !== 'approved'"
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
  daysBefore: { type: Number, default: 2 },
});

const emit = defineEmits(['select-period', 'select-unscheduled', 'request-unlock']);

const hasLockedPeriods = computed(() => props.schedulePeriods.some(p => p.isLocked));
</script>
