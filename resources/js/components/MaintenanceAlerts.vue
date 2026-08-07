<template>
  <div v-if="alerts.length > 0">
    <div class="mb-3 flex items-center justify-between gap-3">
      <h3 class="text-sm font-bold text-slate-700 flex items-center gap-2">
        <svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
        </svg>
        {{ title }}
        <span class="ml-1 text-[10px] bg-amber-100 text-amber-700 font-bold px-2 py-0.5 rounded-full">
          {{ alerts.length }}
        </span>
      </h3>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
      <div
        v-for="alert in displayedAlerts"
        :key="alert.id"
        :class="getAlertClasses(alert)"
        class="group flex items-center p-2.5 rounded-lg border transition-all hover:shadow-sm cursor-pointer gap-3"
        @click="$emit('click-alert', alert)"
      >
        <div :class="getAlertIconClasses(alert)" class="shrink-0 w-7 h-7 rounded-lg flex items-center justify-center">
          <svg v-if="alert.isFullyChecked" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
          </svg>
          <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
          </svg>
        </div>

        <div class="flex-1 min-w-0">
          <div class="flex justify-between items-center gap-2">
            <div class="flex items-center gap-2 min-w-0">
              <h4 class="text-xs font-bold text-slate-800 truncate">{{ alert.machine?.name }}</h4>
              <span v-if="alert.isUnlockPriority" class="text-[9px] font-black px-1.5 py-0.5 rounded-full bg-emerald-100 text-emerald-700 border border-emerald-200 shrink-0">UNLOCKED</span>
            </div>
            <span :class="getAlertBadgeClasses(alert)" class="text-[10px] font-semibold bg-white px-2 py-0.5 rounded-full border border-current shrink-0">
              {{ alert.isFullyChecked ? 'Selesai' : getAlertTimeText(alert) }}
            </span>
          </div>
          <div class="flex items-center gap-1.5 text-[10px] text-slate-500 mt-0.5">
            <span class="capitalize">{{ alert.schedule_type }}</span>
            <span>·</span>
            <span>{{ formatDate(alert.next_due_date) }}</span>
            <span v-if="alert.machine?.pic_mesin" class="text-slate-400">· PIC: <span class="font-semibold text-slate-600">{{ alert.machine.pic_mesin.full_name }}</span></span>
            <span v-if="alert.isFullyChecked" class="text-green-600 font-semibold">· {{ alert.totalComponents }} komponen dicek</span>
            <span v-else-if="alert.isPartiallyChecked" class="text-amber-600 font-semibold">· {{ alert.uncheckedCount }} komponen tersisa</span>
            <span v-else-if="alert.daysUntil <= 0" class="text-red-500 font-semibold">· Belum dicek</span>
          </div>
        </div>

        <div class="shrink-0 opacity-0 group-hover:opacity-100 transition-opacity">
          <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
          </svg>
        </div>
      </div>

      <!-- Toggle show all / show less -->
      <button
        v-if="alerts.length > 4"
        @click="showAll = !showAll"
        class="md:col-span-2 w-full mt-1 py-2 rounded-lg border border-dashed border-slate-200 text-[11px] font-semibold text-slate-500 hover:text-indigo-600 hover:border-indigo-300 hover:bg-indigo-50/50 transition-all cursor-pointer flex items-center justify-center gap-1.5"
      >
        <svg class="w-3.5 h-3.5 transition-transform" :class="showAll ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
        <span v-if="!showAll">+{{ alerts.length - 4 }} pengingat lainnya &mdash; Tampilkan Semua</span>
        <span v-else>Sembunyikan</span>
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';

const showAll = ref(false);

const props = defineProps({
  alerts: { type: Array, default: () => [] },
  title:  { type: String, default: 'Maintenance Alerts' },
  collapsible: { type: Boolean, default: false },
});

defineEmits(['click-alert']);

const displayedAlerts = computed(() => {
  if (showAll.value || props.alerts.length <= 4) return props.alerts;
  return props.alerts.slice(0, 4);
});

const getAlertClasses = (alert) => {
  if (alert.isUnlockPriority)   return 'bg-emerald-50 border-emerald-300 ring-1 ring-emerald-200';
  if (alert.isFullyChecked)     return 'bg-green-50 border-green-200';
  if (alert.isPartiallyChecked) return 'bg-orange-50 border-orange-200';
  if (alert.daysUntil < 0)      return 'bg-red-50 border-red-200';
  if (alert.daysUntil === 0)    return 'bg-orange-50 border-orange-200';
  return 'bg-amber-50 border-amber-200';
};

const getAlertIconClasses = (alert) => {
  if (alert.isUnlockPriority)   return 'bg-emerald-100 text-emerald-600';
  if (alert.isFullyChecked)     return 'bg-green-100 text-green-600';
  if (alert.isPartiallyChecked) return 'bg-orange-100 text-orange-600';
  if (alert.daysUntil < 0)      return 'bg-red-100 text-red-600';
  if (alert.daysUntil === 0)    return 'bg-orange-100 text-orange-600';
  return 'bg-amber-100 text-amber-600';
};

const getAlertBadgeClasses = (alert) => {
  if (alert.isUnlockPriority)   return 'text-emerald-600';
  if (alert.isFullyChecked)     return 'text-green-600';
  if (alert.isPartiallyChecked) return 'text-orange-600';
  if (alert.daysUntil < 0)      return 'text-red-600';
  if (alert.daysUntil === 0)    return 'text-orange-600';
  return 'text-amber-600';
};

const getAlertTimeText = (alert) => {
  if (alert.isUnlockPriority) return 'Unlock disetujui';
  const days = alert.daysUntil;
  if (days === 0) return 'Hari Ini';
  if (days > 0 && days <= (alert.daysBeforeSetting ?? 2)) return `H-${days} · Bisa dicek`;
  if (days > 0)   return `${days} hari lagi`;
  return `Telat ${Math.abs(days)} hari`;
};

const formatDate = (d) => {
  if (!d) return '-';
  return new Date(d).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
};
</script>
