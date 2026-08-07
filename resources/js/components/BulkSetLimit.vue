<template>
  <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40" @click.self="$emit('close')">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
      <div class="p-5 border-b border-slate-100">
        <h3 class="text-lg font-bold text-slate-800">Set Limit Qty Massal</h3>
        <p class="text-sm text-slate-500 mt-0.5">Atur batas bawah stok untuk beberapa item sekaligus. Peringatan restock muncul saat quantity <= limit qty.</p>
      </div>

      <div class="p-5 space-y-3">
        <div class="flex gap-2 items-center">
          <input
            v-model="search"
            type="text"
            placeholder="Cari nama atau kode stok..."
            class="flex-1 rounded-xl border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-brown"
          />
          <button
            @click="applyToAll"
            class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-bold text-slate-600 hover:border-brand-brown hover:text-brand-brown cursor-pointer transition-colors whitespace-nowrap"
          >
            Terapkan ke Semua
          </button>
        </div>

        <div class="overflow-x-auto rounded-xl border border-slate-100 max-h-[50vh] overflow-y-auto">
          <table class="w-full text-sm">
            <thead class="bg-slate-50 border-b border-slate-100 sticky top-0">
              <tr>
                <th class="text-left text-xs font-semibold text-slate-500 px-3 py-2">Kode</th>
                <th class="text-left text-xs font-semibold text-slate-500 px-3 py-2">Nama</th>
                <th class="text-right text-xs font-semibold text-slate-500 px-3 py-2 w-20">Qty</th>
                <th class="text-center text-xs font-semibold text-slate-500 px-3 py-2 w-24">Limit Qty</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="item in filteredItems" :key="item.id" class="border-b border-slate-50 hover:bg-slate-50/50">
                <td class="px-3 py-2 font-mono text-xs font-bold text-slate-700">{{ item.code }}</td>
                <td class="px-3 py-2 text-xs text-slate-700">{{ item.name }}</td>
                <td class="px-3 py-2 text-right text-xs" :class="item.is_low_stock ? 'text-red-600 font-bold' : 'text-slate-600'">{{ item.quantity }} {{ item.unit }}</td>
                <td class="px-3 py-2">
                  <input
                    v-model.number="item.limit_qty"
                    type="number"
                    min="0"
                    class="w-20 rounded-lg border border-slate-200 px-2 py-1 text-xs text-center focus:outline-none focus:ring-1 focus:ring-brand-brown"
                  />
                </td>
              </tr>
            </tbody>
          </table>
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
          :disabled="saving"
          class="px-4 py-2 rounded-xl bg-brand-gradation text-white text-sm font-bold hover:shadow-md cursor-pointer transition-all disabled:opacity-50"
        >
          {{ saving ? 'Menyimpan...' : 'Simpan Semua' }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import axios from 'axios';

const props = defineProps({
  stocks: { type: Array, default: () => [] },
});

const emit = defineEmits(['close', 'saved']);

const search = ref('');
const saving = ref(false);

const items = ref(props.stocks.map(s => ({ id: s.id, code: s.code, name: s.name, quantity: s.quantity, unit: s.unit, is_low_stock: s.is_low_stock, limit_qty: s.limit_qty ?? 1 })));

const filteredItems = computed(() => {
  if (!search.value) return items.value;
  const q = search.value.toLowerCase();
  return items.value.filter(i => i.code.toLowerCase().includes(q) || i.name.toLowerCase().includes(q));
});

const applyToAll = () => {
  const val = items.value[0]?.limit_qty;
  if (val === undefined) return;
  items.value.forEach(i => { i.limit_qty = val; });
};

const save = async () => {
  saving.value = true;
  try {
    const payload = items.value.map(i => ({ id: i.id, limit_qty: i.limit_qty }));
    const res = await axios.put('/api/stocks/bulk-limit', { items: payload });
    alert(res.data.message);
    emit('saved');
  } catch (e) {
    alert(e.response?.data?.message ?? 'Gagal menyimpan limit qty.');
  } finally {
    saving.value = false;
  }
};
</script>
