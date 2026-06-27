<template>
  <div
    @click="$emit('click', machine)"
    class="bg-white rounded-2xl p-6 border border-slate-100 shadow-sm hover:shadow-lg transition-all relative overflow-hidden group cursor-pointer transform hover:-translate-y-1 duration-300"
  >
    <!-- Gradient corner -->
    <div :class="['absolute top-0 right-0 w-24 h-24 rounded-bl-full -z-10 opacity-50 group-hover:scale-110 transition-transform bg-gradient-to-br', theme.gradClass]" />

    <!-- Header -->
    <div class="flex justify-between items-start mb-4">
      <div class="flex-1 min-w-0 pr-2">
        <h4 class="font-semibold text-slate-800 leading-tight truncate">{{ machine.name }}</h4>
        <p class="text-xs text-slate-400 mt-1 truncate">{{ machine.location ?? '-' }}</p>
      </div>
      <div :class="[theme.bgClass, 'p-2 rounded-lg flex-shrink-0']">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
        </svg>
      </div>
    </div>

    <!-- Condition ring -->
    <div class="flex justify-center my-5 relative">
      <svg class="w-28 h-28" viewBox="0 0 100 100" style="transform: rotate(-90deg);">
        <circle class="text-slate-100 stroke-current" stroke-width="8" cx="50" cy="50" r="45" fill="transparent"/>
        <circle
          :class="[theme.textClass, 'stroke-current transition-all duration-1000 ease-out']"
          stroke-width="8" stroke-linecap="round" cx="50" cy="50" r="45" fill="transparent"
          :stroke-dasharray="282.74"
          :stroke-dashoffset="282.74 - (machine.condition_pct / 100) * 282.74"
        />
      </svg>
      <div class="absolute inset-0 flex flex-col items-center justify-center">
        <span :class="[theme.textClass, 'text-2xl font-bold']">{{ machine.condition_pct }}%</span>
        <span class="text-[10px] text-slate-400 uppercase tracking-wide font-medium mt-0.5">Health</span>
      </div>
    </div>

    <!-- Info rows -->
    <div class="space-y-2">
      <div class="flex items-center justify-between text-xs">
        <span class="text-slate-500">PIC:</span>
        <span v-if="machine.pic_mesin" class="font-semibold text-slate-700 truncate max-w-[60%] text-right">{{ machine.pic_mesin.full_name }}</span>
        <span v-else class="text-slate-400 italic">-</span>
      </div>

      <div v-if="schedule" class="flex items-center justify-between text-xs">
        <span class="text-slate-500">Jadwal:</span>
        <span :class="urgency.badge" class="px-2 py-1 rounded-full text-xs font-bold">{{ urgency.label }}</span>
      </div>

      <div class="flex items-center text-xs text-slate-500">
        <svg class="w-3.5 h-3.5 mr-1 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
        </svg>
        <span class="truncate">{{ machine.location ?? '-' }}</span>
        <span v-if="machine.kota" class="ml-1 px-1.5 py-0.5 bg-indigo-50 text-indigo-600 rounded text-[10px] font-bold uppercase">
          {{ machine.kota === 'sby' ? 'Surabaya' : 'Pasuruan' }}
        </span>
      </div>
    </div>

    <!-- Hover arrow -->
    <div class="absolute top-4 right-4 opacity-0 group-hover:opacity-100 transition-opacity">
      <div class="bg-white rounded-lg shadow-md p-1.5 border border-slate-100">
        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
        </svg>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  machine: { type: Object, required: true },
});

defineEmits(['click']);

const theme = computed(() => {
  const pct = props.machine.condition_pct;
  if (pct < 50) return { textClass: 'text-red-500',   bgClass: 'bg-red-50 text-red-700',   gradClass: 'from-red-50 to-white' };
  if (pct < 80) return { textClass: 'text-amber-500', bgClass: 'bg-amber-50 text-amber-700', gradClass: 'from-amber-50 to-white' };
  return           { textClass: 'text-green-500', bgClass: 'bg-green-50 text-green-700', gradClass: 'from-green-50 to-white' };
});

const schedule = computed(() => {
  const schedules = props.machine.schedules;
  if (!schedules?.length) return null;
  return [...schedules]
    .filter(s => s.is_active !== false)
    .sort((a, b) => new Date(a.next_due_date) - new Date(b.next_due_date))[0] ?? null;
});

const urgency = computed(() => {
  if (!schedule.value) return { badge: '', label: '' };
  const d = new Date(schedule.value.next_due_date); d.setHours(0,0,0,0);
  const t = new Date(); t.setHours(0,0,0,0);
  const days = Math.ceil((d - t) / 86400000);
  if (days < 0)  return { badge: 'bg-red-100 text-red-700',    label: `Telat ${Math.abs(days)}` };
  if (days === 0) return { badge: 'bg-amber-100 text-amber-700', label: 'Hari Ini' };
  if (days <= 3)  return { badge: 'bg-amber-50 text-amber-600',  label: `${days} Hari` };
  if (days <= 7)  return { badge: 'bg-blue-50 text-blue-600',    label: `${days} Hari` };
  return               { badge: 'bg-slate-50 text-slate-600',   label: `${days} Hari` };
});
</script>
