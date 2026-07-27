<template>
  <div v-if="alerts.length > 0">
    <div class="mb-3 flex items-center justify-between gap-3">
      <h3 class="text-base font-bold text-slate-700 flex items-center gap-2">
        <svg class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
        </svg>
        {{ title }}
        <span class="ml-1 text-xs bg-amber-100 text-amber-700 font-bold px-2 py-0.5 rounded-full">
          {{ alerts.length }}
        </span>
      </h3>
      <button
        v-if="collapsible"
        @click="isExpanded = !isExpanded"
        class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-bold text-slate-600 transition-colors hover:border-brand-brown hover:text-brand-brown cursor-pointer"
      >
        <svg class="h-3.5 w-3.5 transition-transform" :class="isExpanded ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
        {{ isExpanded ? 'Sembunyikan' : 'Tampilkan' }}
      </button>
    </div>
    <div v-show="!collapsible || isExpanded" class="space-y-2.5">
      <div
        v-for="alert in alerts"
        :key="alert.id"
        :class="getAlertClasses(alert)"
        class="flex items-center p-4 rounded-xl border transition-all hover:shadow-md cursor-pointer gap-4"
        @click="$emit('click-alert', alert)"
      >
        <div :class="getAlertIconClasses(alert)" class="shrink-0 w-10 h-10 rounded-full flex items-center justify-center">
          <svg v-if="alert.isFullyChecked" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
          </svg>
          <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
          </svg>
        </div>

        <div class="flex-1 min-w-0">
          <div class="flex justify-between items-center gap-2">
            <h4 class="text-sm font-bold text-slate-800 truncate">{{ alert.machine?.name }}</h4>
            <span :class="getAlertBadgeClasses(alert)" class="text-xs font-semibold bg-white px-2 py-0.5 rounded-full border border-current shrink-0">
              {{ alert.isFullyChecked ? 'Sudah Dicek' : getAlertTimeText(alert.next_due_date) }}
            </span>
          </div>
          <p class="text-xs text-slate-500 mt-0.5">
            <span class="capitalize">{{ alert.schedule_type }}</span> · Jadwal: {{ formatDate(alert.next_due_date) }}
            <span v-if="alert.machine?.pic_mesin" class="text-slate-400"> · PIC: <span class="font-semibold text-slate-600">{{ alert.machine.pic_mesin.full_name }}</span></span>
            <span v-if="alert.isFullyChecked" class="text-green-600 font-semibold">
              · ✅ {{ alert.totalComponents }} komponen sudah dicek
            </span>
            <span v-else-if="alert.isPartiallyChecked" class="text-amber-600 font-semibold">
              · ⚠️ Baru {{ alert.checkedCount }} dari {{ alert.totalComponents }} komponen dicek (sisa {{ alert.uncheckedCount }} lagi)
            </span>
            <span v-else class="text-red-500 font-semibold">
              · {{ alert.totalComponents }} komponen perlu dicek
            </span>
          </p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';

const isExpanded = ref(true);

defineProps({
  alerts: { type: Array, default: () => [] },
  title:  { type: String, default: 'Maintenance Alerts' },
  collapsible: { type: Boolean, default: false },
});

defineEmits(['click-alert']);

const isAlertOverdue = (dateStr) => {
  const d = new Date(dateStr); d.setHours(0,0,0,0);
  const t = new Date();        t.setHours(0,0,0,0);
  return d < t;
};

const getAlertClasses = (alert) => {
  if (alert.isFullyChecked)     return 'bg-green-50 border-green-200';
  if (alert.isPartiallyChecked) return 'bg-orange-50 border-orange-200';
  if (alert.daysUntil < 0)      return 'bg-red-50 border-red-200';
  if (alert.daysUntil === 0)    return 'bg-orange-50 border-orange-200';
  return 'bg-amber-50 border-amber-200';
};

const getAlertIconClasses = (alert) => {
  if (alert.isFullyChecked)     return 'bg-green-100 text-green-600';
  if (alert.isPartiallyChecked) return 'bg-orange-100 text-orange-600';
  if (alert.daysUntil < 0)      return 'bg-red-100 text-red-600';
  if (alert.daysUntil === 0)    return 'bg-orange-100 text-orange-600';
  return 'bg-amber-100 text-amber-600';
};

const getAlertBadgeClasses = (alert) => {
  if (alert.isFullyChecked)     return 'text-green-600';
  if (alert.isPartiallyChecked) return 'text-orange-600';
  if (alert.daysUntil < 0)      return 'text-red-600';
  if (alert.daysUntil === 0)    return 'text-orange-600';
  return 'text-amber-600';
};

const getAlertTimeText = (dateStr) => {
  const d = new Date(dateStr); d.setHours(0,0,0,0);
  const t = new Date();        t.setHours(0,0,0,0);
  const days = Math.ceil((d - t) / (1000 * 60 * 60 * 24));
  if (days === 0) return 'Hari ini';
  if (days > 0)   return 'Bisa Dicek';
  return `Telat ${Math.abs(days)} hari`;
};

const formatDate = (d) => {
  if (!d) return '-';
  return new Date(d).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
};
</script>
