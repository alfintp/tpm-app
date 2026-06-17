<template>
  <div v-if="show" class="fixed inset-0 z-50 flex items-start justify-center pt-16 sm:pt-20 p-2 sm:p-4 bg-black/50 backdrop-blur-sm">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl lg:max-w-4xl xl:max-w-5xl max-h-[85vh] sm:max-h-[80vh] overflow-hidden flex flex-col">

      <!-- Calendar Header -->
      <div class="bg-gradient-to-tr from-brand-brown to-brand-gradation text-white p-4 sm:p-6 flex-shrink-0">
        <div class="flex items-center justify-between">
          <div>
            <h3 class="text-lg sm:text-2xl font-bold">Kalender Maintenance</h3>
            <p class="text-indigo-100 text-xs sm:text-sm mt-1">Jadwal maintenance semua mesin</p>
          </div>
          <button @click="$emit('close')" class="p-2 hover:bg-white/20 rounded-xl transition-colors">
            <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
          </button>
        </div>
        <!-- Month Navigation -->
        <div class="flex items-center justify-between mt-3 sm:mt-4">
          <button @click="previousMonth" class="p-2 hover:bg-white/20 rounded-xl transition-colors">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
          </button>
          <h4 class="text-lg sm:text-xl font-semibold">{{ currentMonthYear }}</h4>
          <button @click="nextMonth" class="p-2 hover:bg-white/20 rounded-xl transition-colors">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
          </button>
        </div>
      </div>

      <!-- Calendar Content -->
      <div class="flex-1 overflow-y-auto p-3 sm:p-6">
        <!-- Legend -->
        <div class="flex flex-wrap items-center gap-2 sm:gap-4 mb-4 sm:mb-6 p-3 sm:p-4 bg-slate-50 rounded-xl sm:rounded-2xl border border-slate-100 text-xs">
          <span class="font-bold text-slate-400 uppercase tracking-wider mr-1 sm:mr-2">Keterangan:</span>
          <div class="flex items-center gap-1.5 sm:gap-2">
            <span class="w-2.5 h-2.5 sm:w-3 sm:h-3 rounded-full bg-green-500"></span>
            <span class="font-semibold text-slate-700">Selesai</span>
          </div>
          <div class="flex items-center gap-1.5 sm:gap-2">
            <span class="w-2.5 h-2.5 sm:w-3 sm:h-3 rounded-full bg-indigo-500"></span>
            <span class="font-semibold text-slate-700">Terjadwal</span>
          </div>
          <div class="flex items-center gap-1.5 sm:gap-2">
            <span class="w-2.5 h-2.5 sm:w-3 sm:h-3 rounded-full bg-rose-500"></span>
            <span class="font-semibold text-slate-700">Terlambat</span>
          </div>
        </div>

        <!-- Mobile View -->
        <div class="block lg:hidden">
          <div class="grid grid-cols-7 gap-0.5 sm:gap-1 mb-2">
            <div v-for="day in dayNames" :key="day" class="text-center text-xs font-semibold text-slate-600 py-2">{{ day }}</div>
          </div>
          <div class="grid grid-cols-7 gap-0.5 sm:gap-1">
            <div
              v-for="day in calendarDays" :key="day.date"
              :class="getCalendarDayClass(day)"
              class="min-h-[60px] sm:min-h-[80px] p-1 sm:p-2 border border-slate-200 rounded"
            >
              <div class="text-xs sm:text-sm font-medium mb-0.5">{{ day.dayNumber }}</div>
              <div v-if="day.machines.length > 0" class="space-y-0.5">
                <div class="flex flex-wrap gap-0.5">
                  <div
                    v-for="(machine, index) in day.machines.slice(0, 6)" :key="index"
                    :class="getMachineDotClass(machine.status)"
                    class="w-2 h-2 rounded-full cursor-pointer"
                    @click="$emit('go-to-machine', machine.id)"
                    :title="machine.name"
                  ></div>
                </div>
                <div v-if="day.machines.length > 6" class="text-[10px] text-slate-500 text-center font-bold leading-tight">
                  +{{ day.machines.length - 6 }}
                </div>
              </div>
            </div>
          </div>
          <!-- Mobile detail list -->
          <div class="mt-4 pt-4 border-t border-slate-200">
            <p class="text-sm font-semibold text-slate-600 mb-3">Detail Jadwal Hari Ini:</p>
            <div class="space-y-2">
              <div v-for="day in calendarDaysWithMachines.slice(0, 5)" :key="day.date" class="bg-slate-50 rounded-lg p-3 border border-slate-200">
                <div class="flex items-center justify-between mb-2">
                  <span class="font-bold text-sm text-brand-brown">{{ day.dayNumber }} {{ currentMonthName }}</span>
                  <span v-if="day.isToday" class="text-xs bg-brand-brown text-white px-2 py-0.5 rounded-full">Hari Ini</span>
                </div>
                <div class="space-y-1.5">
                  <div
                    v-for="(machine, index) in day.machines.slice(0, 3)" :key="index"
                    :class="getMachineBadgeClass(machine.status)"
                    class="text-xs sm:text-sm p-2 rounded-lg cursor-pointer transition-all flex items-center gap-2 shadow-sm"
                    @click="$emit('go-to-machine', machine.id)"
                  >
                    <div class="font-semibold truncate flex-1">{{ machine.name }}</div>
                  </div>
                  <div v-if="day.machines.length > 3" class="text-xs text-slate-500 text-center font-medium py-1">
                    +{{ day.machines.length - 3 }} mesin lainnya
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Desktop View -->
        <div class="hidden lg:block">
          <div class="grid grid-cols-7 gap-1 mb-4">
            <div v-for="day in dayNames" :key="day" class="text-center text-sm font-semibold text-slate-600 p-2 sm:p-3">{{ day }}</div>
            <div
              v-for="day in calendarDays" :key="day.date"
              :class="getCalendarDayClass(day)"
              class="min-h-[90px] xl:min-h-[110px] p-1.5 sm:p-2 border border-slate-200 rounded-lg"
            >
              <div class="text-sm font-medium mb-1">{{ day.dayNumber }}</div>
              <div v-if="day.machines.length > 0" class="space-y-1">
                <div
                  v-for="(machine, index) in day.machines.slice(0, 4)" :key="index"
                  :class="getMachineBadgeClass(machine.status)"
                  class="text-[10px] xl:text-xs p-1 xl:p-1.5 rounded-lg cursor-pointer transition-all flex items-center justify-between gap-1 shadow-sm font-semibold hover:scale-[1.02]"
                  @click="$emit('go-to-machine', machine.id)"
                >
                  <div class="truncate flex-1">{{ machine.name }}</div>
                </div>
                <div v-if="day.machines.length > 4" class="text-xs text-slate-500 text-center font-bold">
                  +{{ day.machines.length - 4 }} lagi
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';

const props = defineProps({
  show:     { type: Boolean, required: true },
  machines: { type: Array, default: () => [] },
});

defineEmits(['close', 'go-to-machine']);

const dayNames = ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'];
const months   = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

const currentMonth = ref(new Date().getMonth());
const currentYear  = ref(new Date().getFullYear());

const currentMonthYear = computed(() => `${months[currentMonth.value]} ${currentYear.value}`);
const currentMonthName = computed(() => months[currentMonth.value]);

const previousMonth = () => {
  if (currentMonth.value === 0) { currentMonth.value = 11; currentYear.value--; }
  else currentMonth.value--;
};
const nextMonth = () => {
  if (currentMonth.value === 11) { currentMonth.value = 0; currentYear.value++; }
  else currentMonth.value++;
};

const getMachinesForDate = (date) => {
  const targetDate = new Date(date); targetDate.setHours(0,0,0,0);
  const targetTime = targetDate.getTime();
  const today = new Date(); today.setHours(0,0,0,0);
  const todayTime = today.getTime();
  const result = [];

  props.machines.forEach(machine => {
    const hasRecord = (machine.records ?? []).some(record => {
      const recDate = new Date(record.maintenance_date); recDate.setHours(0,0,0,0);
      return recDate.getTime() === targetTime;
    });
    if (hasRecord) {
      result.push({ id: machine.id, name: machine.name, status: 'completed' });
      return;
    }
    (machine.schedules ?? []).forEach(sched => {
      if (sched.is_active === false) return;
      const baseDueDate = new Date(sched.next_due_date); baseDueDate.setHours(0,0,0,0);
      const projectedTimes = [];
      let cur = new Date(baseDueDate);
      for (let k = 0; k < 20; k++) {
        projectedTimes.push({ time: cur.getTime(), isOriginal: k === 0 });
        const next = new Date(cur);
        next.setDate(cur.getDate() + sched.interval_days);
        if (next.getDay() === 0) next.setDate(next.getDate() + 1);
        cur = next;
        if (Math.round((cur - baseDueDate) / (1000*60*60*24)) > 60) break;
      }
      const match = projectedTimes.find(p => p.time === targetTime);
      if (match) {
        result.push({
          id: machine.id, name: machine.name,
          status: match.isOriginal ? (targetTime < todayTime ? 'overdue' : 'pending') : 'pending',
        });
      }
    });
  });
  return result.sort((a, b) => a.name.localeCompare(b.name));
};

const calendarDays = computed(() => {
  const firstDay = new Date(currentYear.value, currentMonth.value, 1);
  const startDate = new Date(firstDay);
  startDate.setDate(startDate.getDate() - firstDay.getDay());
  const today = new Date(); today.setHours(0,0,0,0);
  return Array.from({ length: 42 }, (_, i) => {
    const date = new Date(startDate);
    date.setDate(startDate.getDate() + i);
    return {
      date: date.toISOString().split('T')[0],
      dayNumber: date.getDate(),
      isCurrentMonth: date.getMonth() === currentMonth.value,
      isToday: date.getTime() === today.getTime(),
      machines: getMachinesForDate(date),
    };
  });
});

const calendarDaysWithMachines = computed(() =>
  calendarDays.value.filter(d => d.machines.length > 0).slice(0, 7)
);

const getCalendarDayClass = (day) => {
  if (!day.isCurrentMonth)  return 'bg-slate-50 text-slate-400';
  if (day.isToday)           return 'bg-indigo-50/50 border-indigo-300 ring-2 ring-indigo-100';
  return 'bg-white hover:bg-slate-50';
};

const getMachineBadgeClass = (status) => ({
  completed: 'bg-green-50 text-green-700 border border-green-200 hover:bg-green-100',
  overdue:   'bg-rose-50 text-rose-700 border border-rose-200 hover:bg-rose-100',
  pending:   'bg-indigo-50 text-indigo-700 border border-indigo-200 hover:bg-indigo-100',
}[status] ?? 'bg-slate-50 text-slate-700 border border-slate-200');

const getMachineDotClass = (status) => ({
  completed: 'bg-green-500',
  overdue:   'bg-rose-500',
  pending:   'bg-indigo-500',
}[status] ?? 'bg-slate-400');
</script>
