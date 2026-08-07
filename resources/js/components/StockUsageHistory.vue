<template>
  <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40" @click.self="$emit('close')">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
      <div class="p-5 border-b border-slate-100">
        <h3 class="text-lg font-bold text-slate-800">Riwayat Pemakaian</h3>
        <p class="text-sm text-slate-500 mt-0.5">{{ stock?.name }} ({{ stock?.code }})</p>
      </div>

      <div class="p-5">
        <div v-if="loading" class="text-center py-8 text-sm text-slate-400">Memuat data...</div>

        <div v-else-if="usages.length === 0" class="text-center py-8">
          <p class="text-sm text-slate-500">Belum ada riwayat pemakaian untuk item ini.</p>
        </div>

        <div v-else class="overflow-x-auto rounded-xl border border-slate-100">
          <table class="w-full text-sm">
            <thead class="bg-slate-50 border-b border-slate-100">
              <tr>
                <th class="text-left text-xs font-semibold text-slate-500 px-3 py-2">Tanggal</th>
                <th class="text-left text-xs font-semibold text-slate-500 px-3 py-2">Mesin</th>
                <th class="text-left text-xs font-semibold text-slate-500 px-3 py-2">Komponen</th>
                <th class="text-right text-xs font-semibold text-slate-500 px-3 py-2">Jumlah</th>
                <th class="text-left text-xs font-semibold text-slate-500 px-3 py-2">Teknisi</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="usage in usages" :key="usage.id" class="border-b border-slate-50">
                <td class="px-3 py-2 text-xs font-semibold text-slate-700">{{ formatDate(usage.used_at) }}</td>
                <td class="px-3 py-2 text-xs text-slate-700">{{ usage.machine?.name ?? '-' }}</td>
                <td class="px-3 py-2 text-xs text-slate-600">{{ usage.machine_component?.name ?? '-' }}</td>
                <td class="px-3 py-2 text-right text-xs font-bold text-red-600">{{ usage.quantity_used }} {{ stock?.unit }}</td>
                <td class="px-3 py-2 text-xs text-slate-600">{{ usage.technician?.full_name ?? '-' }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <div class="p-5 border-t border-slate-100 flex justify-end">
        <button
          @click="$emit('close')"
          class="px-4 py-2 rounded-xl border border-slate-200 text-sm font-bold text-slate-600 hover:bg-slate-50 cursor-pointer transition-colors"
        >
          Tutup
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';

const props = defineProps({
  stock: { type: Object, default: null },
});

defineEmits(['close']);

const usages = ref([]);
const loading = ref(true);

const formatDate = (d) => {
  if (!d) return '-';
  return new Date(d).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
};

onMounted(async () => {
  try {
    const res = await axios.get(`/api/stocks/${props.stock.id}/usages`);
    usages.value = res.data.usages;
  } catch (e) {
    console.error('Failed to load usage history:', e);
  } finally {
    loading.value = false;
  }
});
</script>
