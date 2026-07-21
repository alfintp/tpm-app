<template>
  <teleport to="body">
    <transition
      enter-active-class="transition ease-out duration-200"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="transition ease-in duration-150"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div
        v-if="show"
        class="fixed inset-0 bg-black/50 z-60 flex items-center justify-center p-4"
        @click.self="emit('close')"
      >
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-3xl max-h-[80vh] flex flex-col">
          <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between gap-4">
            <h3 class="text-base font-bold text-slate-800">{{ title }}</h3>
            <button @click="emit('close')" class="text-slate-400 hover:text-slate-600 cursor-pointer p-1 rounded-lg hover:bg-slate-100">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
          </div>

          <div class="p-6 overflow-y-auto">
            <div v-if="mode === 'records'">
              <div v-if="!records.length" class="text-center text-slate-400 py-8 text-sm">Tidak ada data.</div>
              <div v-else class="overflow-x-auto rounded-xl border border-slate-100">
                <table class="w-full text-left text-xs border-collapse">
                  <thead class="bg-slate-50 text-slate-500 font-bold uppercase tracking-wider">
                    <tr>
                      <th v-if="showMachine" class="px-4 py-3">Mesin</th>
                      <th class="px-4 py-3">Tanggal</th>
                      <th class="px-4 py-3">Teknisi</th>
                      <th class="px-4 py-3">Status</th>
                      <th class="px-4 py-3">Catatan</th>
                    </tr>
                  </thead>
                  <tbody class="divide-y divide-slate-100">
                    <tr v-for="(record, idx) in records" :key="idx" class="hover:bg-slate-50/60">
                      <td v-if="showMachine" class="px-4 py-3 font-medium text-slate-700">{{ record.machine_name ?? '-' }}</td>
                      <td class="px-4 py-3 whitespace-nowrap text-slate-700">{{ formatDate(record.maintenance_date) }}</td>
                      <td class="px-4 py-3 whitespace-nowrap text-slate-600">{{ record.technician_name ?? '-' }}</td>
                      <td class="px-4 py-3 whitespace-nowrap">
                        <span class="font-bold px-2 py-0.5 rounded border" :class="statusClass(record)">{{ statusLabel(record) }}</span>
                      </td>
                      <td class="px-4 py-3 text-slate-500 italic">{{ record.notes || '-' }}</td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>

            <div v-else-if="mode === 'replacements'">
              <div v-if="!replacements.length" class="text-center text-slate-400 py-8 text-sm">Tidak ada data penggantian komponen.</div>
              <div v-else class="overflow-x-auto rounded-xl border border-slate-100">
                <table class="w-full text-left text-xs border-collapse">
                  <thead class="bg-slate-50 text-slate-500 font-bold uppercase tracking-wider">
                    <tr>
                      <th v-if="showMachine" class="px-4 py-3">Mesin</th>
                      <th class="px-4 py-3">Tanggal</th>
                      <th class="px-4 py-3">Komponen</th>
                      <th class="px-4 py-3">Teknisi</th>
                      <th class="px-4 py-3">Catatan</th>
                    </tr>
                  </thead>
                  <tbody class="divide-y divide-slate-100">
                    <tr v-for="(item, idx) in replacements" :key="idx" class="hover:bg-slate-50/60">
                      <td v-if="showMachine" class="px-4 py-3 font-medium text-slate-700">{{ item.machine_name ?? '-' }}</td>
                      <td class="px-4 py-3 whitespace-nowrap text-slate-700">{{ formatDate(item.date) }}</td>
                      <td class="px-4 py-3 font-medium text-slate-700">{{ item.component ?? '-' }}</td>
                      <td class="px-4 py-3 whitespace-nowrap text-slate-600">{{ item.technician ?? '-' }}</td>
                      <td class="px-4 py-3 text-slate-500 italic">{{ item.notes || '-' }}</td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      </div>
    </transition>
  </teleport>
</template>

<script setup>
const props = defineProps({
  show: { type: Boolean, default: false },
  title: { type: String, default: '' },
  mode: { type: String, default: 'records' }, // 'records' | 'replacements'
  records: { type: Array, default: () => [] },
  replacements: { type: Array, default: () => [] },
  showMachine: { type: Boolean, default: false },
});

const emit = defineEmits(['close']);

const formatDate = (d) => {
  if (!d) return '-';
  return new Date(d).toLocaleString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
};

const statusLabel = (record) => {
  if (record.status === 'approved') return 'Disetujui';
  if (record.status === 'rejected') return 'Ditolak';
  if (record.status === 'pending') return 'Menunggu';
  if (record.is_unscheduled) return 'Luar Jadwal';
  if (record.is_late) return 'Terlambat';
  return 'Tepat Waktu';
};

const statusClass = (record) => {
  if (record.status === 'approved') return 'bg-green-50 text-green-700 border-green-100';
  if (record.status === 'rejected') return 'bg-red-50 text-red-700 border-red-100';
  if (record.status === 'pending') return 'bg-amber-50 text-amber-700 border-amber-100';
  if (record.is_unscheduled) return 'bg-indigo-50 text-indigo-700 border-indigo-100';
  if (record.is_late) return 'bg-rose-50 text-rose-700 border-rose-100';
  return 'bg-emerald-50 text-emerald-700 border-emerald-100';
};
</script>
