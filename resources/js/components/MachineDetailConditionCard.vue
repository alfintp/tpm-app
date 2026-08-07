<template>
  <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
    <div class="flex flex-col lg:flex-row lg:flex-nowrap divide-y lg:divide-y-0 lg:divide-x divide-slate-100">

      <!-- Left: Overall Donut -->
      <div class="flex items-center gap-4 p-6 w-full lg:w-auto lg:min-w-[240px]">
        <div class="relative shrink-0">
          <svg class="w-24 h-24" viewBox="0 0 100 100" style="transform: rotate(-90deg)">
            <circle class="text-slate-100 stroke-current" stroke-width="10" cx="50" cy="50" r="42" fill="transparent"/>
            <circle :class="getColorTheme(machine.condition_pct).textClass" class="stroke-current transition-all duration-1000 ease-out" stroke-width="10" stroke-linecap="round" cx="50" cy="50" r="42" fill="transparent"
              :stroke-dasharray="264" :stroke-dashoffset="264 - (machine.condition_pct / 100) * 264"/>
          </svg>
          <div class="absolute inset-0 flex flex-col items-center justify-center">
            <span :class="getColorTheme(machine.condition_pct).textClass" class="text-xl font-bold leading-none">{{ machine.condition_pct }}%</span>
            <span class="text-[9px] text-slate-400 font-medium mt-0.5">overall</span>
          </div>
        </div>
        <div>
          <p class="text-xs text-slate-400 font-medium">Kondisi Mesin</p>
          <h3 :class="getColorTheme(machine.condition_pct).textClass" class="text-xl font-bold mt-0.5">{{ getConditionLabel(machine.condition_pct) }}</h3>
          <p class="text-xs text-slate-400 mt-1">{{ machine.components?.length ?? 0 }} komponen</p>
          <p class="text-xs text-slate-500 mt-1.5 flex items-center gap-1">
            <svg class="w-3.5 h-3.5 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
            <span v-if="machine.pic_mesin" class="font-semibold text-slate-700">{{ machine.pic_mesin.full_name }}</span>
            <span v-else class="text-slate-400 italic">Belum ada PIC</span>
          </p>
        </div>
      </div>

      <!-- Center: Per-Category Breakdown -->
      <div v-if="categoryConditionStats.length > 0" class="flex flex-col justify-center px-5 py-4">
        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Kategori</p>
        <div class="flex flex-col gap-2">
          <div
            v-for="cat in categoryConditionStats" :key="cat.name"
            class="flex items-center gap-1.5 px-3 py-1.5 rounded-full border text-xs font-semibold"
            :class="getColorTheme(cat.avg).chipClass"
          >
            <span class="capitalize">{{ cat.name }}</span>
            <span class="opacity-60 text-[10px]">·</span>
            <span :class="getColorTheme(cat.avg).textClass">{{ cat.avg }}%</span>
          </div>
        </div>
      </div>

      <!-- Right: Coverage Stats -->
      <div v-if="coverageStatsByPeriod && coverageStatsByPeriod.length" class="flex-1 min-w-[180px] p-5 flex flex-col">
        <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-2.5">Progres Laporan Bulan Ini</p>
        <div class="flex flex-wrap gap-2">
          <template v-for="(periodStats, periodIdx) in coverageStatsByPeriod" :key="periodIdx">
            <div
              v-if="periodStats.length > 0"
              class="rounded-lg border shadow-sm overflow-hidden min-w-[120px]"
              :class="getPeriodCardClass(periodIdx)"
            >
              <div class="px-2.5 py-1.5 border-b border-slate-100/50">
                <div class="flex items-center gap-1">
                  <svg class="w-3 h-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                    :class="getPeriodScheduleIconClass(periodIdx)"
                  ><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                  <span class="text-xs font-bold" :class="getPeriodScheduleTextClass(periodIdx)">
                    {{ getPeriodScheduleDate(periodIdx) }}
                  </span>
                  <span v-if="getPeriodScheduleDaysLabel(periodIdx)" class="text-[9px] font-bold ml-auto" :class="getPeriodScheduleDaysClass(periodIdx)">
                    {{ getPeriodScheduleDaysLabel(periodIdx) }}
                  </span>
                </div>
              </div>
              <div class="px-2.5 py-1.5 space-y-0.5">
                <div
                  v-for="stat in periodStats"
                  :key="stat.role"
                  class="flex items-center justify-between text-[11px] font-semibold"
                >
                  <span class="font-medium opacity-80">{{ stat.bucket === 'teknisi' ? 'Berat' : 'Ringan' }}</span>
                  <span class="font-bold tracking-wide" :class="coverageStatBadgeClass(stat)">{{ stat.checked }}/{{ stat.total }}</span>
                </div>
              </div>
            </div>
          </template>
        </div>
      </div>

    </div>
  </div>
</template>

<script setup>
defineProps({
  machine: { type: Object, default: null },
  categoryConditionStats: { type: Array, default: () => [] },
  coverageStatsByPeriod: { type: Array, default: () => [] },
  getColorTheme: { type: Function, required: true },
  getConditionLabel: { type: Function, required: true },
  getPeriodCardClass: { type: Function, required: true },
  getPeriodCardTextClass: { type: Function, required: true },
  getPeriodCardLabel: { type: Function, required: true },
  getPeriodScheduleBadgeClass: { type: Function, required: true },
  getPeriodScheduleLabel: { type: Function, required: true },
  getPeriodScheduleIconClass: { type: Function, required: true },
  getPeriodScheduleTextClass: { type: Function, required: true },
  getPeriodScheduleDaysClass: { type: Function, required: true },
  getPeriodScheduleDaysLabel: { type: Function, required: true },
  getPeriodScheduleDate: { type: Function, required: true },
  coverageStatIconClass: { type: Function, required: true },
  coverageStatIcon: { type: Function, required: true },
  coverageStatBadgeClass: { type: Function, required: true },
  coverageStatLabel: { type: Function, required: true },
});
</script>
