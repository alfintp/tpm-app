<template>
  <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40" @click.self="$emit('close')">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-sm">
      <div class="p-5 border-b border-slate-100">
        <h3 class="text-lg font-bold text-slate-800">Restock</h3>
        <p class="text-sm text-slate-500 mt-0.5">{{ stock?.name }} ({{ stock?.code }})</p>
      </div>

      <div class="p-5 space-y-4">
        <div class="bg-slate-50 rounded-xl p-3 flex items-center justify-between">
          <div>
            <p class="text-xs text-slate-500">Stok Saat Ini</p>
            <p class="text-xl font-black text-slate-800">{{ stock?.quantity }} {{ stock?.unit }}</p>
          </div>
          <div v-if="stock?.is_low_stock" class="text-[10px] font-bold px-2 py-1 rounded-full bg-orange-50 text-orange-700 border border-orange-200">
            Menipis
          </div>
        </div>

        <div>
          <label class="text-xs font-bold text-slate-600 mb-1 block">Jumlah Penambahan</label>
          <input
            v-model.number="addedQty"
            type="number"
            min="1"
            class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-brown"
          />
        </div>

        <div class="bg-emerald-50 rounded-xl p-3">
          <p class="text-xs text-emerald-600">Total Setelah Restock</p>
          <p class="text-xl font-black text-emerald-700">{{ (stock?.quantity ?? 0) + (addedQty || 0) }} {{ stock?.unit }}</p>
        </div>
      </div>

      <div class="p-5 border-t border-slate-100 flex justify-end gap-2">
        <button
          @click="$emit('close')"
          class="px-4 py-2 rounded-xl border border-slate-200 text-sm font-bold text-slate-600 hover:bg-slate-50 cursor-pointer transition-colors"
        >
          Batal
        </button>
        <button
          @click="save"
          :disabled="saving || !addedQty || addedQty < 1"
          class="px-4 py-2 rounded-xl bg-emerald-500 text-white text-sm font-bold hover:shadow-md cursor-pointer transition-all disabled:opacity-50"
        >
          {{ saving ? 'Menyimpan...' : 'Restock' }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import axios from 'axios';
import { showAlert } from '../composables/useAlert.js';

const props = defineProps({
  stock: { type: Object, default: null },
});

const emit = defineEmits(['close', 'saved']);

const addedQty = ref(1);
const saving = ref(false);

const save = async () => {
  if (!addedQty.value || addedQty.value < 1) return;
  saving.value = true;
  try {
    const res = await axios.post(`/api/stocks/${props.stock.id}/restock`, {
      added_quantity: addedQty.value,
    });
    emit('saved', res.data);
  } catch (e) {
    showAlert('error', 'Gagal Restock', e.response?.data?.message ?? 'Gagal melakukan restock.');
  } finally {
    saving.value = false;
  }
};
</script>
