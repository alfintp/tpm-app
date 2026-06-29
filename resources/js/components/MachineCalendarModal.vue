<template>
  <!-- Day Detail Modal -->
  <Teleport to="body">
    <div v-if="dayModal.show" class="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm" @click.self="dayModal.show = false">
      <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md max-h-[80vh] flex flex-col">
        <!-- Header -->
        <div class="flex items-center justify-between p-4 border-b border-slate-100">
          <div>
            <div class="text-base font-bold text-slate-800">{{ dayModal.label }}</div>
            <div v-if="dayModal.holidayName" class="text-xs text-rose-500 font-semibold mt-0.5">{{ dayModal.holidayName }}</div>
            <div v-else-if="dayModal.isSunday" class="text-xs text-rose-400 font-semibold mt-0.5">Hari Minggu</div>
          </div>
          <button @click="dayModal.show = false" class="p-1.5 hover:bg-slate-100 rounded-lg transition-colors">
            <svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
          </button>
        </div>
        <!-- Machine list -->
        <div class="overflow-y-auto flex-1 p-4 space-y-2">
          <div
            v-for="machine in dayModal.machines" :key="machine.id + machine.status"
            :class="getMachineBadgeClass(machine.status)"
            class="flex items-start justify-between gap-3 p-3 rounded-xl cursor-pointer transition-all hover:scale-[1.01] shadow-sm"
            @click="navigateToMachine(machine.id)"
          >
            <div class="flex-1 min-w-0">
              <div class="font-semibold text-sm truncate">{{ machine.name }}</div>
              <div class="text-[11px] opacity-70 mt-0.5">{{ machine.kota === 'sby' ? 'Surabaya' : machine.kota === 'pasuruan' ? 'Pasuruan' : machine.kota }}</div>
              <div v-if="machine.rescheduledTo" class="text-[11px] font-semibold mt-1 text-amber-600">
                ⟶ Jadwal dipindah ke {{ formatRescheduledDate(machine.rescheduledTo) }}
              </div>
            </div>
            <div class="flex-shrink-0 pt-0.5">
              <span class="text-[10px] font-bold px-2 py-0.5 rounded-full"
                :class="{
                  'bg-green-100 text-green-700':  machine.status === 'completed',
                  'bg-rose-100 text-rose-700':    machine.status === 'overdue',
                  'bg-indigo-100 text-indigo-700': machine.status === 'pending',
                  'bg-amber-100 text-amber-700':  machine.status === 'rescheduled',
                }">
                {{ machine.status === 'completed' ? 'Selesai' : machine.status === 'overdue' ? 'Terlambat' : machine.status === 'rescheduled' ? 'Dipindah' : 'Terjadwal' }}
              </span>
            </div>
          </div>
          <p v-if="dayModal.machines.length === 0" class="text-center text-sm text-slate-400 py-6">Tidak ada jadwal pada hari ini</p>
        </div>
      </div>
    </div>
  </Teleport>

  <div v-if="show" class="fixed inset-0 z-50 flex items-start justify-center pt-16 sm:pt-20 p-2 sm:p-4 bg-black/50 backdrop-blur-sm">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl lg:max-w-4xl xl:max-w-5xl max-h-[85vh] sm:max-h-[80vh] overflow-hidden flex flex-col">

      <!-- Calendar Header -->
      <div class="bg-gradient-to-tr from-brand-brown to-brand-gradation text-white p-4 sm:p-6 flex-shrink-0">
        <div class="flex items-center justify-between gap-4">
          <div>
            <h3 class="text-lg sm:text-2xl font-bold">Kalender Maintenance</h3>
            <p class="text-indigo-100 text-xs sm:text-sm mt-1">Jadwal maintenance semua mesin</p>
          </div>
          <div class="flex items-center gap-2 sm:gap-3">
            <select
              v-if="hasBothCities"
              v-model="filterKota"
              class="rounded-xl border border-white/20 bg-white/10 px-3 py-1.5 text-xs sm:text-sm text-white focus:outline-none focus:ring-2 focus:ring-white/40 cursor-pointer hover:bg-white/20 transition-all font-semibold"
            >
              <option class="text-slate-800 font-semibold" value="">Semua Kota</option>
              <option class="text-slate-800 font-semibold" value="pasuruan">Pasuruan</option>
              <option class="text-slate-800 font-semibold" value="sby">Surabaya</option>
            </select>
            <button @click="$emit('close')" class="hover:cursor-pointer p-2 hover:bg-white/20 rounded-xl transition-colors">
              <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
              </svg>
            </button>
          </div>
        </div>
        <!-- Month Navigation -->
        <div class="flex items-center justify-between mt-3 sm:mt-4">
          <button @click="previousMonth" class="hover:cursor-pointer p-2 hover:bg-white/20 rounded-xl transition-colors">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
          </button>
          <h4 class="text-lg sm:text-xl font-semibold">{{ currentMonthYear }}</h4>
          <button @click="nextMonth" class="hover:cursor-pointer p-2 hover:bg-white/20 rounded-xl transition-colors">
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
          <div class="flex items-center gap-1.5 sm:gap-2">
            <span class="w-2.5 h-2.5 sm:w-3 sm:h-3 rounded bg-pink-100 border border-pink-200"></span>
            <span class="font-semibold text-slate-700">Libur / Minggu</span>
          </div>
          <div class="flex items-center gap-1.5 sm:gap-2">
            <span class="w-2.5 h-2.5 sm:w-3 sm:h-3 rounded-full bg-amber-400"></span>
            <span class="font-semibold text-slate-700">Dipindah</span>
          </div>
          <span v-if="holidayLoading" class="text-[10px] text-slate-400 italic ml-1">Memuat hari libur...</span>
        </div>

        <!-- Mobile View -->
        <div class="block lg:hidden">
          <div class="grid grid-cols-7 gap-0.5 sm:gap-1 mb-2">
            <div v-for="(day, idx) in dayNames" :key="day" :class="idx === 0 ? 'text-rose-500' : 'text-slate-600'" class="text-center text-xs font-semibold py-2">{{ day }}</div>
          </div>
          <div class="grid grid-cols-7 gap-0.5 sm:gap-1">
            <div
              v-for="day in calendarDays" :key="day.date"
              :class="getCalendarDayClass(day)"
              class="min-h-[60px] sm:min-h-[80px] p-1 sm:p-2 border border-slate-200 rounded cursor-pointer"
              :title="day.holidayName ?? (day.isSunday ? 'Hari Minggu' : undefined)"
              @click="openDayModal(day)"
            >
              <div class="text-xs sm:text-sm font-medium mb-0.5" :class="(day.isSunday || day.isHolidayDay) && day.isCurrentMonth ? 'text-rose-500 font-bold' : ''">{{ day.dayNumber }}</div>
              <div v-if="day.machines.length > 0" class="space-y-0.5">
                <div class="flex flex-wrap gap-0.5">
                  <div
                    v-for="(machine, index) in day.machines.slice(0, 5)" :key="index"
                    :class="getMachineDotClass(machine.status)"
                    class="w-2 h-2 rounded-full"
                    :title="machine.name"
                  ></div>
                </div>
                <div v-if="day.machines.length > 5" class="text-[9px] text-slate-500 font-bold leading-tight">
                  +{{ day.machines.length - 5 }}
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
                    @click="navigateToMachine(machine.id)"
                  >
                    <div class="font-semibold truncate flex-1">{{ machine.name }}</div>
                  </div>
                  <button v-if="day.machines.length > 3" @click="openDayModal(day)" class="w-full text-xs text-brand-brown font-semibold text-center py-1 hover:underline cursor-pointer">
                    +{{ day.machines.length - 3 }} mesin lainnya &rarr;
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Desktop View -->
        <div class="hidden lg:block">
          <div class="grid grid-cols-7 gap-1 mb-4">
            <div v-for="(day, idx) in dayNames" :key="day" :class="idx === 0 ? 'text-rose-500' : 'text-slate-600'" class="text-center text-sm font-semibold p-2 sm:p-3">{{ day }}</div>
            <div
              v-for="day in calendarDays" :key="day.date"
              :class="getCalendarDayClass(day)"
              class="min-h-[90px] xl:min-h-[110px] p-1.5 sm:p-2 border border-slate-200 rounded-lg"
            >
              <!-- Day number -->
              <div class="text-sm font-medium mb-0.5" :class="(day.isSunday || day.isHolidayDay) && day.isCurrentMonth ? 'text-rose-500 font-bold' : ''">{{ day.dayNumber }}</div>
              <div v-if="day.holidayName && day.isCurrentMonth" class="text-[9px] text-rose-400 font-semibold leading-tight mb-1 line-clamp-1">{{ day.holidayName }}</div>
              <!-- Machine badges -->
              <div v-if="day.machines.length > 0" class="space-y-0.5">
                <div
                  v-for="(machine, index) in day.machines.slice(0, 3)" :key="index"
                  :class="getMachineBadgeClass(machine.status)"
                  class="text-[10px] xl:text-xs p-1 xl:p-1.5 rounded-lg cursor-pointer transition-all flex items-center gap-1 shadow-sm font-semibold hover:scale-[1.02] hover:shadow"
                  @click.stop="navigateToMachine(machine.id)"
                  :title="machine.name"
                >
                  <div class="truncate flex-1">{{ machine.name }}</div>
                </div>
                <!-- Always-visible trigger -->
                <button
                  @click.stop="openDayModal(day)"
                  class="w-full text-[10px] xl:text-xs font-semibold text-center py-0.5 rounded cursor-pointer transition-colors"
                  :class="day.machines.length > 3 ? 'text-slate-500 hover:text-brand-brown hover:bg-slate-100' : 'text-slate-400 hover:text-brand-brown hover:bg-slate-100'"
                >
                  <span v-if="day.machines.length > 3">+{{ day.machines.length - 3 }} lagi &rarr;</span>
                  <span v-else>Lihat semua &rarr;</span>
                </button>
              </div>
              <!-- No machines: always-visible subtle link (current month only) -->
              <button
                v-else-if="day.isCurrentMonth"
                @click.stop="openDayModal(day)"
                class="w-full text-[10px] text-slate-300 hover:text-slate-500 text-center pt-1 cursor-pointer transition-colors"
              >Lihat</button>
            </div>
          </div>
        </div>
      </div>

    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import axios from 'axios';
import { useAuth } from '../composables/useAuth.js';

const props = defineProps({
  show:     { type: Boolean, required: true },
  machines: { type: Array, default: () => [] },
});

defineEmits(['close']);

const { hasBothCities } = useAuth();
const filterKota = ref('');

const dayNames = ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'];
const months   = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

// ── Day detail modal ──────────────────────────────────────────────────────────
const dayModal = ref({ show: false, label: '', isSunday: false, holidayName: null, machines: [] });

const navigateToMachine = (machineId) => {
  dayModal.value.show = false;
  window.location.href = `/machine/${machineId}`;
};

const openDayModal = (day) => {
  const d = new Date(day.date);
  const label = `${d.getDate()} ${months[d.getMonth()]} ${d.getFullYear()}`;
  dayModal.value = {
    show: true,
    label,
    isSunday: day.isSunday,
    holidayName: day.holidayName,
    machines: day.machines,
  };
};
// ──────────────────────────────────────────────────────────────────────────────

watch(() => props.show, (val) => {
  if (val) {
    filterKota.value = '';
    fetchHolidays(currentYear.value);
  }
});

const filteredMachines = computed(() => {
  if (hasBothCities.value && filterKota.value) {
    return props.machines.filter(m => m.kota === filterKota.value);
  }
  return props.machines;
});

// ── Holiday data ──────────────────────────────────────────────────────────────
// Stored as plain reactive object { 'YYYY-MM-DD': description } for Vue reactivity
const holidayMap   = ref({}); // { 'YYYY-MM-DD': 'Nama Hari Libur' }
const fetchedYears = ref({}); // { 'YYYY': true } — tracks which years are loaded
const holidayLoading = ref(false);

const fetchHolidays = async (year) => {
  if (fetchedYears.value[year]) return;
  holidayLoading.value = true;
  try {
    const res = await axios.get(`/api/holidays?year=${year}`);
    const list = res.data?.data ?? [];
    const additions = {};
    list.forEach(h => { additions[h.date] = h.description; });
    holidayMap.value   = { ...holidayMap.value, ...additions };
    fetchedYears.value = { ...fetchedYears.value, [year]: true };
  } catch (e) {
    console.warn('[MachineCalendar] Gagal mengambil data hari libur:', e.message);
    fetchedYears.value = { ...fetchedYears.value, [year]: true };
  } finally {
    holidayLoading.value = false;
  }
};

const isHoliday      = (dateStr) => !!holidayMap.value[dateStr];
const getHolidayName = (dateStr) => holidayMap.value[dateStr] ?? null;

// Use LOCAL date parts to avoid UTC→local timezone shift (e.g. WIB UTC+7)
const localDateStr = (d) => {
  const y = d.getFullYear();
  const m = String(d.getMonth() + 1).padStart(2, '0');
  const day = String(d.getDate()).padStart(2, '0');
  return `${y}-${m}-${day}`;
};

const isOffDay = (date) => {
  const d = date instanceof Date ? date : new Date(date);
  if (d.getDay() === 0) return true;
  return isHoliday(localDateStr(d));
};

const advancePastOffDays = (date) => {
  const d = new Date(date);
  d.setHours(0, 0, 0, 0);
  let limit = 0;
  while (isOffDay(d) && limit++ < 7) d.setDate(d.getDate() + 1);
  return d;
};

const currentMonth = ref(new Date().getMonth());
const currentYear  = ref(new Date().getFullYear());

watch(currentYear, (year) => fetchHolidays(year), { immediate: false });
onMounted(() => fetchHolidays(currentYear.value));
// ──────────────────────────────────────────────────────────────────────────────

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

// Build projected schedule entries: { originalTime, shiftedTime }
const buildProjections = (nextDueDate, intervalDays) => {
  const base = new Date(nextDueDate); base.setHours(0, 0, 0, 0);
  const projections = [];
  let raw = new Date(base);
  for (let k = 0; k < 20; k++) {
    const shifted = advancePastOffDays(raw);
    projections.push({ originalTime: raw.getTime(), shiftedTime: shifted.getTime(), shiftedDate: shifted });
    const next = new Date(shifted);
    next.setDate(shifted.getDate() + intervalDays);
    raw = next;
    if (Math.round((raw - base) / (1000 * 60 * 60 * 24)) > 60) break;
  }
  return projections;
};

const getMachinesForDate = (date) => {
  const targetDate = new Date(date); targetDate.setHours(0, 0, 0, 0);
  const targetTime = targetDate.getTime();
  const today = new Date(); today.setHours(0, 0, 0, 0);
  const todayTime = today.getTime();
  const result = [];

  filteredMachines.value.forEach(machine => {
    const hasRecord = (machine.records ?? []).some(record => {
      const recDate = new Date(record.maintenance_date); recDate.setHours(0, 0, 0, 0);
      return recDate.getTime() === targetTime;
    });
    if (hasRecord) {
      result.push({ id: machine.id, name: machine.name, kota: machine.kota, status: 'completed', rescheduledTo: null });
      return;
    }
    (machine.schedules ?? []).forEach(sched => {
      if (sched.is_active === false) return;
      const projections = buildProjections(sched.next_due_date, sched.interval_days);

      // Case 1: this date is a shifted (working-day) scheduled date
      const shiftedMatch = projections.find(p => p.shiftedTime === targetTime);
      if (shiftedMatch) {
        result.push({
          id: machine.id, name: machine.name, kota: machine.kota,
          status: targetTime < todayTime ? 'overdue' : 'pending',
          rescheduledTo: null,
        });
        return;
      }

      // Case 2: this date was the ORIGINAL scheduled date but got shifted to another day
      const originalMatch = projections.find(p => p.originalTime === targetTime && p.shiftedTime !== targetTime);
      if (originalMatch) {
        result.push({
          id: machine.id, name: machine.name, kota: machine.kota,
          status: 'rescheduled',
          rescheduledTo: localDateStr(originalMatch.shiftedDate),
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
    const dateStr = localDateStr(date);
    return {
      date: dateStr,
      dayNumber: date.getDate(),
      isCurrentMonth: date.getMonth() === currentMonth.value,
      isToday: date.getTime() === today.getTime(),
      isSunday: date.getDay() === 0,
      isHolidayDay: isHoliday(dateStr),
      holidayName: getHolidayName(dateStr),
      machines: getMachinesForDate(date),
    };
  });
});

const calendarDaysWithMachines = computed(() =>
  calendarDays.value.filter(d => d.machines.length > 0).slice(0, 7)
);

const getCalendarDayClass = (day) => {
  if (!day.isCurrentMonth) {
    if (day.isSunday || day.isHolidayDay) return 'bg-pink-50/40 text-slate-400';
    return 'bg-slate-50 text-slate-400';
  }
  if (day.isToday) return 'bg-indigo-50/50 border-indigo-300 ring-2 ring-indigo-100';
  if (day.isSunday || day.isHolidayDay) return 'bg-pink-50 border-pink-100';
  return 'bg-white hover:bg-slate-50';
};

const getMachineBadgeClass = (status) => ({
  completed:   'bg-green-50 text-green-700 border border-green-200 hover:bg-green-100',
  overdue:     'bg-rose-50 text-rose-700 border border-rose-200 hover:bg-rose-100',
  pending:     'bg-indigo-50 text-indigo-700 border border-indigo-200 hover:bg-indigo-100',
  rescheduled: 'bg-amber-50 text-amber-700 border border-amber-200 hover:bg-amber-100',
}[status] ?? 'bg-slate-50 text-slate-700 border border-slate-200');

const getMachineDotClass = (status) => ({
  completed:   'bg-green-500',
  overdue:     'bg-rose-500',
  pending:     'bg-indigo-500',
  rescheduled: 'bg-amber-400',
}[status] ?? 'bg-slate-400');

const formatRescheduledDate = (dateStr) => {
  const [y, m, d] = dateStr.split('-').map(Number);
  return `${d} ${months[m - 1]} ${y}`;
};
</script>
