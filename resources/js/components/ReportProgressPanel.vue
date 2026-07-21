<template>
  <div class="w-full max-w-5xl mx-auto bg-white rounded-2xl border border-slate-200 shadow-sm px-5 py-4 space-y-3">
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
      <button
        v-for="summary in progressSummaries" :key="summary.key"
        @click="emit('update:difficultyFilter', summary.key)"
        :class="difficultyFilter === summary.key
          ? 'bg-emerald-100 border-emerald-300 text-slate-800'
          : 'bg-slate-50/70 border-slate-100 text-slate-700 hover:bg-slate-100'"
        class="rounded-xl border px-3 py-2 text-left transition-all cursor-pointer"
      >
        <div class="flex items-center justify-between gap-2">
          <span class="text-xs font-bold">{{ summary.label }}</span>
          <span class="text-xs font-black" :class="difficultyFilter === summary.key ? 'text-brand-brown' : (summary.done === summary.total ? 'text-emerald-600' : 'text-slate-600')">{{ summary.done }}/{{ summary.total }}</span>
        </div>
        <p class="mt-0.5 text-[10px] flex flex-wrap gap-x-2" :class="difficultyFilter === summary.key ? 'text-brand-brown/70' : 'text-slate-500'">
          <span>{{ summary.pending }} belum</span>
          <span>{{ summary.waiting }} menunggu</span>
          <span>{{ summary.approved }} disetujui</span>
        </p>
      </button>
      <button
        v-if="!isUnscheduled"
        @click="emit('toggle:showOnlyPending')"
        :class="showOnlyPending
          ? 'bg-amber-50 border-amber-500 text-amber-700'
          : 'bg-slate-50/70 border-slate-100 text-slate-700 hover:bg-slate-100'"
        class="rounded-xl border px-3 py-2 text-left transition-all cursor-pointer"
      >
        <div class="flex items-center justify-between gap-2">
          <span class="text-xs font-bold">Belum dicek</span>
          <span class="text-xs font-black" :class="showOnlyPending ? 'text-amber-700' : 'text-slate-600'">{{ pendingSummary.total }}</span>
        </div>
        <p class="mt-0.5 text-[10px]" :class="showOnlyPending ? 'text-amber-600/80' : 'text-slate-500'">{{ pendingSummary.pending }} belum dicek</p>
      </button>
    </div>
  </div>
</template>

<script setup>
defineProps({
  progressSummaries: { type: Array, default: () => [] },
  difficultyFilter: { type: String, default: 'ringan' },
  showOnlyPending: { type: Boolean, default: false },
  pendingSummary: { type: Object, default: () => ({ total: 0, pending: 0 }) },
  isUnscheduled: { type: Boolean, default: false },
});

const emit = defineEmits(['update:difficultyFilter', 'toggle:showOnlyPending']);
</script>
