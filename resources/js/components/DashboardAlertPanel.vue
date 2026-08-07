<template>
  <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
    <!-- Panel header -->
    <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between">
      <h3 class="text-sm font-bold text-slate-800 flex items-center gap-2">
        <svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
        </svg>
        Maintenance Alerts
      </h3>
    </div>

    <div class="p-3">
      <!-- Loading skeleton -->
      <div v-if="loading" class="space-y-2">
        <div v-for="i in 3" :key="i" class="animate-pulse flex items-center gap-3 p-2.5 rounded-lg bg-slate-50">
          <div class="rounded-full bg-slate-200 h-7 w-7 shrink-0"></div>
          <div class="flex-1 space-y-2 py-1">
            <div class="h-2 bg-slate-200 rounded w-3/4"></div>
            <div class="h-2 bg-slate-200 rounded w-1/2"></div>
          </div>
        </div>
      </div>

      <!-- Empty state -->
      <div v-else-if="alerts.length === 0" class="text-center py-6">
        <div class="w-12 h-12 bg-green-50 rounded-full flex items-center justify-center mx-auto mb-3">
          <svg class="w-6 h-6 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
          </svg>
        </div>
        <h4 class="text-sm font-semibold text-slate-700 mb-1">Semua Aman! 🎉</h4>
        <p class="text-xs text-slate-500">Tidak ada jadwal maintenance yang perlu perhatian hari ini.</p>
      </div>

      <!-- Alert list -->
      <div v-else class="space-y-2">
        <div
          v-for="alert in alerts.slice(0, 3)"
          :key="alert.id"
          :class="alertClass(alert)"
          class="group flex items-center p-2.5 rounded-lg border transition-all hover:shadow-sm cursor-pointer gap-3"
          @click="$emit('click-alert', alert)"
        >
          <!-- Icon -->
          <div :class="iconClass(alert)" class="shrink-0 w-7 h-7 rounded-lg flex items-center justify-center">
            <svg v-if="alert.isFullyChecked" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
            <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
          </div>

          <!-- Content -->
          <div class="flex-1 min-w-0">
            <div class="flex justify-between items-center gap-2">
              <h4 class="text-xs font-bold text-slate-800 truncate">{{ alert.machine?.name }}</h4>
              <span :class="badgeClass(alert)" class="text-[10px] font-semibold bg-white px-2 py-0.5 rounded-full border border-current shrink-0">
                {{ alert.isFullyChecked ? 'Selesai' : timeText(alert) }}
              </span>
            </div>
            <div class="flex items-center gap-1.5 text-[10px] text-slate-500 mt-0.5">
              <span class="capitalize">{{ alert.schedule_type }}</span>
              <span>·</span>
              <span>{{ formatDate(alert.next_due_date) }}</span>
              <span v-if="alert.machine?.pic_mesin" class="text-slate-400">· PIC: <span class="font-semibold text-slate-600">{{ alert.machine.pic_mesin.full_name }}</span></span>
              <span v-if="!alert.isFullyChecked && alert.daysUntil <= 0" :class="alert.isPartiallyChecked ? 'text-amber-600 font-semibold' : 'text-red-500 font-semibold'">
                · {{ alert.isPartiallyChecked ? `${alert.uncheckedCount} komponen tersisa` : 'Belum dicek' }}
              </span>
            </div>
          </div>

          <div class="shrink-0 opacity-0 group-hover:opacity-100 transition-opacity">
            <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
          </div>
        </div>

        <!-- More alerts link -->
        <button
          v-if="alerts.length > 3"
          @click="$emit('view-all')"
          class="w-full mt-1 py-2 rounded-lg border border-dashed border-slate-200 text-[11px] font-semibold text-slate-500 hover:text-indigo-600 hover:border-indigo-300 hover:bg-indigo-50/50 transition-all cursor-pointer flex items-center justify-center gap-1.5"
        >
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
          +{{ alerts.length - 3 }} pengingat lainnya &mdash; Lihat Semua
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
defineProps({
  alerts:  { type: Array,   default: () => [] },
  loading: { type: Boolean, default: false },
});

defineEmits(['view-all', 'click-alert']);

const alertClass = (a) => {
  if (a.isFullyChecked)     return 'bg-green-50 border-green-200';
  if (a.isPartiallyChecked) return 'bg-orange-50 border-orange-200';
  if (a.daysUntil < 0)      return 'bg-red-50 border-red-200';
  if (a.daysUntil === 0)    return 'bg-orange-50 border-orange-200';
  return 'bg-amber-50 border-amber-200';
};

const iconClass = (a) => {
  if (a.isFullyChecked)     return 'bg-green-100 text-green-600';
  if (a.isPartiallyChecked) return 'bg-orange-100 text-orange-600';
  if (a.daysUntil < 0)      return 'bg-red-100 text-red-600';
  if (a.daysUntil === 0)    return 'bg-orange-100 text-orange-600';
  return 'bg-amber-100 text-amber-600';
};

const badgeClass = (a) => {
  if (a.isFullyChecked)     return 'text-green-600';
  if (a.isPartiallyChecked) return 'text-orange-600';
  if (a.daysUntil < 0)      return 'text-red-600';
  if (a.daysUntil === 0)    return 'text-orange-600';
  return 'text-amber-600';
};

const timeText = (alert) => {
  const days = alert.daysUntil;
  if (days === 0) return 'Hari Ini';
  if (days > 0 && days <= (alert.daysBeforeSetting ?? 2)) return `H-${days} · Sudah bisa dicek`;
  if (days > 0)   return `${days} hari lagi`;
  return `Telat ${Math.abs(days)} hari`;
};

const formatDate = (d) =>
  new Date(d).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
</script>
