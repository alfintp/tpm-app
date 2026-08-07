<template>
  <Teleport to="body">
    <Transition name="fade">
      <div
        v-if="show"
        class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6"
        @click.self="emit('close')"
      >
        <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm"></div>
        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-4xl max-h-[90vh] overflow-hidden flex flex-col">
          <!-- Header -->
          <div class="px-6 py-4 border-b border-slate-100 flex items-start justify-between gap-4 bg-slate-50/50 shrink-0">
            <div class="min-w-0">
              <h3 class="text-lg font-bold text-slate-800 truncate">{{ machine.name }}</h3>
              <p class="text-xs text-slate-500 mt-0.5 truncate">
                {{ machine.location ?? '-' }}{{ machine.kota ? ` • ${formatCity(machine.kota)}` : '' }}
              </p>
            </div>
            <div class="flex items-center gap-2 shrink-0">
              <button
                @click="router.visit(`/machine/${machine.id}`)"
                class="text-xs font-semibold text-indigo-600 hover:text-indigo-800 bg-indigo-50 hover:bg-indigo-100 px-3 py-1.5 rounded-lg transition-colors cursor-pointer"
              >
                Lihat Detail Mesin
              </button>
              <button
                @click="emit('close')"
                class="p-1.5 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition-colors cursor-pointer"
              >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
              </button>
            </div>
          </div>

          <!-- Body -->
          <div class="overflow-y-auto p-6 space-y-6">
            <!-- Top stats -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
              <div class="bg-slate-50 rounded-xl p-3 border border-slate-100">
                <p class="text-[10px] font-bold uppercase text-slate-400">Kondisi Mesin</p>
                <p class="text-xl font-bold" :class="conditionClass">{{ machine.condition_pct ?? 0 }}%</p>
              </div>
              <div class="bg-slate-50 rounded-xl p-3 border border-slate-100">
                <p class="text-[10px] font-bold uppercase text-slate-400">Total Laporan</p>
                <p class="text-xl font-bold text-slate-700">{{ records.length }}</p>
              </div>
              <div class="bg-slate-50 rounded-xl p-3 border border-slate-100">
                <p class="text-[10px] font-bold uppercase text-slate-400">Ganti Komponen</p>
                <p class="text-xl font-bold text-orange-600">{{ replacementActions.length }}</p>
              </div>
              <div class="bg-slate-50 rounded-xl p-3 border border-slate-100">
                <p class="text-[10px] font-bold uppercase text-slate-400">Komponen</p>
                <p class="text-xl font-bold text-slate-700">{{ components.length }}</p>
              </div>
            </div>

            <!-- Components condition (collapsible) -->
            <div v-if="components.length" class="bg-white rounded-xl border border-slate-200 p-4">
              <button @click="showComponents = !showComponents" class="w-full flex items-center justify-between mb-3 cursor-pointer">
                <h4 class="text-xs font-bold uppercase text-slate-400">Kondisi Komponen</h4>
                <svg class="w-4 h-4 text-slate-400 transition-transform" :class="showComponents ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
              </button>
              <div v-show="showComponents" class="space-y-3">
                <div v-for="component in components" :key="component.id" class="flex items-center gap-3">
                  <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2">
                      <span class="font-semibold text-sm text-slate-700 truncate">{{ component.name }}</span>
                      <span class="text-[10px] font-bold px-1.5 py-0.5 rounded border" :class="difficultyClass(component.difficulty)">
                        {{ difficultyLabel(component.difficulty) }}
                      </span>
                    </div>
                    <p v-if="component.specification" class="text-[10px] text-slate-400 truncate mt-0.5">{{ component.specification }}</p>
                    <div class="w-full h-2 bg-slate-100 rounded-full overflow-hidden mt-1.5">
                      <div
                        class="h-full rounded-full"
                        :class="barClass(component.last_condition_pct)"
                        :style="{ width: (component.last_condition_pct ?? 0) + '%' }"
                      ></div>
                    </div>
                  </div>
                  <span class="text-sm font-bold w-10 text-right" :class="barTextClass(component.last_condition_pct)">
                    {{ component.last_condition_pct ?? 0 }}%
                  </span>
                </div>
              </div>
            </div>

            <!-- Replacement history -->
            <div v-if="replacementActions.length" class="bg-white rounded-xl border border-slate-200 p-4">
              <h4 class="text-xs font-bold uppercase text-slate-400 mb-3">Riwayat Ganti Komponen</h4>
              <div class="space-y-2">
                <div
                  v-for="(item, idx) in replacementActions.slice(0, 10)"
                  :key="idx"
                  class="flex flex-col sm:flex-row sm:items-center justify-between gap-1 py-2 border-b border-slate-50 last:border-0"
                >
                  <div class="flex items-center gap-2">
                    <span class="font-semibold text-sm text-slate-700">{{ item.component?.name ?? '-' }}</span>
                    <span class="text-xs text-slate-400">{{ formatDate(item.record.maintenance_date) }}</span>
                  </div>
                  <div class="flex items-center gap-2">
                    <span class="text-xs font-semibold text-slate-500">{{ item.record.technician?.full_name ?? '-' }}</span>
                  </div>
                </div>
              </div>
            </div>

            <!-- Recent reports (collapsible per report) -->
            <div v-if="records.length" class="bg-white rounded-xl border border-slate-200 p-4">
              <h4 class="text-xs font-bold uppercase text-slate-400 mb-3">Riwayat Laporan</h4>
              <div class="space-y-2">
                <div
                  v-for="record in recentRecords"
                  :key="record.id"
                  class="border-b border-slate-50 last:border-0"
                >
                  <button
                    @click="toggleReport(record.id)"
                    class="w-full flex flex-col sm:flex-row sm:items-center justify-between gap-2 py-2 cursor-pointer text-left hover:bg-slate-50/50 rounded-lg px-2 -mx-2 transition-colors"
                  >
                    <div class="flex flex-wrap items-center gap-2">
                      <svg class="w-3.5 h-3.5 text-slate-400 transition-transform shrink-0" :class="expandedReports.has(record.id) ? 'rotate-90' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>
                      <span class="font-semibold text-sm text-slate-700">{{ formatDate(record.maintenance_date) }}</span>
                      <span class="text-[10px] font-bold px-1.5 py-0.5 rounded border" :class="statusBadgeClass(record)">{{ statusLabel(record) }}</span>
                      <span v-if="record.is_late" class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-rose-50 text-rose-700 border border-rose-100">Terlambat</span>
                      <span v-if="record.is_unscheduled" class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-indigo-50 text-indigo-700 border border-indigo-100">Luar Jadwal</span>
                    </div>
                    <div class="flex items-center gap-1.5 text-xs text-slate-500">
                      <span>{{ actionCount(record, 'replace') }} ganti</span>
                      <span>•</span>
                      <span>{{ actionCount(record, 'inspect') }} inspeksi</span>
                      <span>•</span>
                      <span>{{ record.technician?.full_name ?? '-' }}</span>
                    </div>
                  </button>
                  <!-- Expanded report detail -->
                  <div v-if="expandedReports.has(record.id)" class="pb-3 px-2 space-y-2">
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs bg-slate-50 rounded-lg p-3 border border-slate-100">
                      <div>
                        <p class="text-slate-400 text-[10px] font-bold uppercase">Teknisi</p>
                        <p class="font-semibold text-slate-700">{{ record.technician?.full_name ?? '-' }}</p>
                      </div>
                      <div>
                        <p class="text-slate-400 text-[10px] font-bold uppercase">Status</p>
                        <p class="font-semibold" :class="statusClass(record)">{{ statusLabel(record) }}</p>
                      </div>
                      <div>
                        <p class="text-slate-400 text-[10px] font-bold uppercase">Kondisi</p>
                        <p class="font-semibold text-slate-700">{{ record.condition_before_pct ?? '-' }}% → {{ record.condition_after_pct ?? '-' }}%</p>
                      </div>
                      <div>
                        <p class="text-slate-400 text-[10px] font-bold uppercase">Durasi</p>
                        <p class="font-semibold text-slate-700">{{ record.duration_minutes ? record.duration_minutes + ' min' : '-' }}</p>
                      </div>
                    </div>
                    <div v-if="record.notes" class="text-xs text-slate-600 bg-slate-50 p-3 rounded-lg border border-slate-100 italic">
                      "{{ record.notes }}"
                    </div>
                    <div v-if="recordActions(record).length" class="space-y-1.5">
                      <p class="text-[10px] font-bold uppercase text-slate-400">Komponen Dicek:</p>
                      <div v-for="action in recordActions(record)" :key="action.id" class="bg-slate-50 rounded-lg px-3 py-2 border border-slate-100">
                        <div class="flex items-center justify-between">
                          <div class="flex flex-col min-w-0">
                            <span class="font-medium text-slate-700">{{ action.component?.name ?? componentMap[action.machine_component_id]?.name ?? '-' }}</span>
                            <span v-if="action.component?.specification ?? componentMap[action.machine_component_id]?.specification" class="text-[10px] text-slate-400 truncate">{{ action.component?.specification ?? componentMap[action.machine_component_id]?.specification }}</span>
                          </div>
                          <div class="flex items-center gap-2 shrink-0">
                            <span class="text-[10px] font-bold px-1.5 py-0.5 rounded" :class="action.action_type === 'replace' ? 'bg-orange-50 text-orange-700 border border-orange-100' : 'bg-blue-50 text-blue-700 border border-blue-100'">
                              {{ action.action_type === 'replace' ? 'Ganti' : 'Inspeksi' }}
                            </span>
                            <span v-if="action.condition_before_pct != null || action.condition_after_pct != null" class="text-slate-500 font-semibold">
                              {{ action.condition_before_pct ?? '-' }}% → {{ action.condition_after_pct ?? '-' }}%
                            </span>
                          </div>
                        </div>
                        <!-- Indicator values -->
                        <div v-if="action.indicator_values && action.indicator_values.length > 0" class="mt-2 space-y-1">
                          <div v-for="(iv, ivIdx) in action.indicator_values" :key="ivIdx" class="flex items-center gap-2 text-[11px]">
                            <span class="font-semibold w-4 h-4 rounded flex items-center justify-center shrink-0" :class="iv.value ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700'">
                              {{ iv.value ? '✓' : '✗' }}
                            </span>
                            <span class="text-slate-600">{{ iv.indicator?.name ?? '-' }}</span>
                            <span class="text-slate-400">: {{ iv.value ? 'OK' : 'Not OK' }}</span>
                          </div>
                        </div>
                        <!-- Description -->
                        <p v-if="action.description" class="text-[11px] text-slate-500 italic mt-1.5">{{ action.description }}</p>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <div v-if="!records.length" class="text-center text-slate-400 text-sm py-8">
              Belum ada laporan untuk mesin ini.
            </div>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { ref, computed, reactive, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import axios from 'axios';

const props = defineProps({
  show: { type: Boolean, default: false },
  machine: { type: Object, default: () => ({}) },
});
const emit = defineEmits(['close']);

const showComponents = ref(true);
const expandedReports = reactive(new Set());
const fetchedComponents = ref([]);

watch(() => props.show, async (show) => {
  if (show && props.machine?.id) {
    fetchedComponents.value = [];
    try {
      const res = await axios.get(`/api/machines/${props.machine.id}/components`);
      fetchedComponents.value = res.data;
    } catch (e) {
      console.error('Failed to load components:', e);
    }
  }
});

const toggleReport = (id) => {
  if (expandedReports.has(id)) expandedReports.delete(id);
  else expandedReports.add(id);
};

const records = computed(() => [...(props.machine.records ?? [])].sort((a, b) => new Date(b.maintenance_date) - new Date(a.maintenance_date)));
const components = computed(() => fetchedComponents.value.length ? fetchedComponents.value : (props.machine.components ?? []));
const latestRecord = computed(() => records.value[0] ?? null);
const recentRecords = computed(() => records.value.slice(0, 10));

const recordActions = (record) => (record.actions ?? []).filter(a => a.action_type === 'inspect' || a.action_type === 'replace');

const componentMap = computed(() => {
  const map = {};
  for (const c of components.value) map[c.id] = c;
  return map;
});

const replacementActions = computed(() => {
  const items = [];
  for (const record of props.machine.records ?? []) {
    for (const action of record.actions ?? []) {
      if (action.action_type === 'replace') {
        const component = action.component ?? componentMap.value[action.machine_component_id] ?? { name: '-' };
        items.push({ record, action, component });
      }
    }
  }
  return items.sort((a, b) => new Date(b.record.maintenance_date) - new Date(a.record.maintenance_date));
});

const conditionClass = computed(() => {
  const pct = props.machine.condition_pct ?? 0;
  if (pct < 50) return 'text-red-500';
  if (pct < 80) return 'text-amber-500';
  return 'text-emerald-600';
});

const formatCity = (kota) => {
  if (kota === 'sby') return 'Surabaya';
  if (kota === 'pasuruan') return 'Pasuruan';
  return kota ?? '-';
};

const formatDate = (date) => {
  if (!date) return '-';
  return new Date(date).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
};

const difficultyLabel = (difficulty) => {
  if (!difficulty) return 'None';
  return difficulty.charAt(0).toUpperCase() + difficulty.slice(1);
};

const difficultyClass = (difficulty) => {
  if (difficulty === 'ringan') return 'bg-emerald-50 text-emerald-700 border border-emerald-100';
  if (difficulty === 'sedang') return 'bg-amber-50 text-amber-700 border border-amber-100';
  if (difficulty === 'berat') return 'bg-red-50 text-red-700 border border-red-100';
  return 'bg-slate-100 text-slate-500 border border-slate-200';
};

const barClass = (pct) => {
  const v = pct ?? 0;
  if (v < 50) return 'bg-red-500';
  if (v < 80) return 'bg-amber-500';
  return 'bg-emerald-500';
};

const barTextClass = (pct) => {
  const v = pct ?? 0;
  if (v < 50) return 'text-red-500';
  if (v < 80) return 'text-amber-500';
  return 'text-emerald-600';
};

const getDecision = (record) => record.latest_approval?.decision ?? null;

const statusLabel = (record) => {
  const d = getDecision(record);
  if (d === 'approved') return 'Disetujui';
  if (d === 'rejected') return 'Ditolak';
  return 'Menunggu';
};

const statusClass = (record) => {
  const d = getDecision(record);
  if (d === 'approved') return 'text-emerald-600';
  if (d === 'rejected') return 'text-red-600';
  return 'text-amber-600';
};

const statusBadgeClass = (record) => {
  const d = getDecision(record);
  if (d === 'approved') return 'bg-emerald-50 text-emerald-700 border border-emerald-100';
  if (d === 'rejected') return 'bg-red-50 text-red-700 border border-red-100';
  return 'bg-amber-50 text-amber-700 border border-amber-100';
};

const actionCount = (record, type) => (record.actions ?? []).filter(a => a.action_type === type).length;
</script>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.2s ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
