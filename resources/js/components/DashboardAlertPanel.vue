<template>
  <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
    <!-- Panel header -->
    <div class="p-6 border-b border-slate-100 flex items-center justify-between">
      <h3 class="text-lg font-semibold text-slate-800 flex items-center">
        <svg class="w-5 h-5 mr-2 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
        </svg>
        Maintenance Alerts
      </h3>
      <button @click="$emit('view-all')" class="text-sm text-indigo-600 hover:text-indigo-800 font-medium flex items-center gap-1 hover:cursor-pointer">
        Lihat Semua
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
        </svg>
      </button>
    </div>

    <div class="p-6">
      <!-- Loading skeleton -->
      <div v-if="loading" class="space-y-3">
        <div v-for="i in 3" :key="i" class="animate-pulse flex space-x-4 p-4 rounded-xl bg-slate-50">
          <div class="rounded-full bg-slate-200 h-10 w-10 shrink-0"></div>
          <div class="flex-1 space-y-3 py-1">
            <div class="h-2 bg-slate-200 rounded w-3/4"></div>
            <div class="h-2 bg-slate-200 rounded"></div>
          </div>
        </div>
      </div>

      <!-- Empty state -->
      <div v-else-if="alerts.length === 0" class="text-center py-8">
        <div class="w-16 h-16 bg-green-50 rounded-full flex items-center justify-center mx-auto mb-4">
          <svg class="w-8 h-8 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
          </svg>
        </div>
        <h4 class="text-lg font-semibold text-slate-700 mb-1">Semua Aman! 🎉</h4>
        <p class="text-sm text-slate-500">Tidak ada jadwal maintenance yang perlu perhatian hari ini.</p>
      </div>

      <!-- Alert list -->
      <div v-else class="space-y-3">
        <div
          v-for="alert in alerts"
          :key="alert.id"
          :class="alertClass(alert)"
          class="group flex items-center p-4 rounded-xl border transition-all hover:shadow-md cursor-pointer gap-4"
          @click="$emit('click-alert', alert)"
        >
          <!-- Icon -->
          <div :class="iconClass(alert)" class="flex-shrink-0 w-12 h-12 rounded-xl flex items-center justify-center">
            <svg v-if="alert.isFullyChecked" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
            <svg v-else class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
          </div>

          <!-- Content -->
          <div class="flex-1 min-w-0">
            <div class="flex justify-between items-start gap-2 mb-2">
              <div>
                <h4 class="text-base font-bold text-slate-800 truncate">{{ alert.machine?.name }}</h4>
                <p class="text-xs text-slate-500 mt-0.5">
                  <span class="capitalize">{{ alert.schedule_type }}</span> · {{ formatDate(alert.next_due_date) }}
                  <span v-if="alert.machine?.pic_mesin" class="text-slate-400"> · PIC: <span class="font-semibold text-slate-600">{{ alert.machine.pic_mesin.full_name }}</span></span>
                </p>
              </div>
              <span :class="badgeClass(alert)" class="text-xs font-semibold bg-white px-3 py-1 rounded-full border border-current flex-shrink-0">
                {{ alert.isFullyChecked ? 'Selesai' : timeText(alert.next_due_date) }}
              </span>
            </div>

            <!-- Progress bar (hanya saat H-0 atau overdue) -->
            <div v-if="!alert.isFullyChecked && alert.totalComponents > 0 && alert.daysUntil <= 0" class="mb-2">
              <div class="flex justify-between text-xs text-slate-600 mb-1">
                <span>Progress Komponen</span>
                <span class="font-medium">{{ alert.checkedCount }}/{{ alert.totalComponents }}</span>
              </div>
              <div class="w-full bg-slate-100 rounded-full h-2">
                <div
                  :class="alert.isPartiallyChecked ? 'bg-amber-500' : 'bg-red-500'"
                  class="h-2 rounded-full transition-all duration-300"
                  :style="{ width: `${(alert.checkedCount / alert.totalComponents) * 100}%` }"
                />
              </div>
            </div>

            <!-- Status text -->
            <div v-if="alert.daysUntil <= 0" class="flex items-center gap-2 text-xs">
              <span v-if="alert.isFullyChecked" class="text-green-600 font-semibold flex items-center gap-1">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                Semua komponen sudah dicek
              </span>
              <span v-else-if="alert.isPartiallyChecked" class="text-amber-600 font-semibold flex items-center gap-1">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                {{ alert.uncheckedCount }} komponen tersisa
              </span>
              <span v-else class="text-red-500 font-semibold flex items-center gap-1">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                Belum ada yang dicek
              </span>
            </div>
          </div>

          <div class="flex-shrink-0 opacity-0 group-hover:opacity-100 transition-opacity">
            <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
          </div>
        </div>
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
  if (a.isPartiallyChecked) return 'bg-amber-50 border-amber-200';
  return a.daysUntil < 0   ? 'bg-red-50 border-red-200' : 'bg-amber-50 border-amber-200';
};

const iconClass = (a) => {
  if (a.isFullyChecked)     return 'bg-green-100 text-green-600';
  if (a.isPartiallyChecked) return 'bg-amber-100 text-amber-600';
  return a.daysUntil < 0   ? 'bg-red-100 text-red-600' : 'bg-amber-100 text-amber-600';
};

const badgeClass = (a) => {
  if (a.isFullyChecked)     return 'text-green-600';
  if (a.isPartiallyChecked) return 'text-amber-600';
  return a.daysUntil < 0   ? 'text-red-600' : 'text-amber-600';
};

const timeText = (dateStr) => {
  const d = new Date(dateStr); d.setHours(0,0,0,0);
  const t = new Date();        t.setHours(0,0,0,0);
  const days = Math.ceil((d - t) / 86400000);
  if (days < 0)   return `Telat ${Math.abs(days)} hari`;
  if (days === 0) return 'Hari Ini';
  if (days === 1) return 'Besok';
  return `${days} Hari Lagi`;
};

const formatDate = (d) =>
  new Date(d).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
</script>
