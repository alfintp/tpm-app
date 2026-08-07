<template>
  <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40" @click.self="$emit('close')">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
      <div class="p-5 border-b border-slate-100">
        <h3 class="text-lg font-bold text-slate-800">Import Stok</h3>
        <p class="text-sm text-slate-500 mt-0.5">Tambah atau perbarui stok secara massal. Kode yang sudah ada akan diperbarui.</p>
      </div>

      <div class="p-5 space-y-4">
        <div class="flex gap-2">
          <button
            @click="addRow"
            class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-bold text-slate-600 hover:border-brand-brown hover:text-brand-brown cursor-pointer transition-colors"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Tambah Baris
          </button>
          <button
            @click="pasteFromClipboard"
            class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-bold text-slate-600 hover:border-brand-brown hover:text-brand-brown cursor-pointer transition-colors"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
            Paste dari Excel
          </button>
        </div>

        <div class="overflow-x-auto rounded-xl border border-slate-100">
          <table class="w-full text-sm">
            <thead class="bg-slate-50 border-b border-slate-100">
              <tr>
                <th class="text-left text-xs font-semibold text-slate-500 px-3 py-2">Kode</th>
                <th class="text-left text-xs font-semibold text-slate-500 px-3 py-2">Kategori</th>
                <th class="text-left text-xs font-semibold text-slate-500 px-3 py-2">Nama</th>
                <th class="text-right text-xs font-semibold text-slate-500 px-3 py-2 w-16">Qty</th>
                <th class="text-left text-xs font-semibold text-slate-500 px-3 py-2 w-20">Unit</th>
                <th class="text-right text-xs font-semibold text-slate-500 px-3 py-2 w-16">Limit</th>
                <th class="w-10"></th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(row, i) in rows" :key="i" class="border-b border-slate-50">
                <td class="px-3 py-1.5">
                  <input v-model="row.code" type="text" placeholder="STK-001" class="w-full rounded-lg border border-slate-200 px-2 py-1 text-xs focus:outline-none focus:ring-1 focus:ring-brand-brown" />
                </td>
                <td class="px-3 py-1.5">
                  <input v-model="row.category" type="text" placeholder="Mechanical" class="w-full rounded-lg border border-slate-200 px-2 py-1 text-xs focus:outline-none focus:ring-1 focus:ring-brand-brown" />
                </td>
                <td class="px-3 py-1.5">
                  <input v-model="row.name" type="text" placeholder="Nama item" class="w-full rounded-lg border border-slate-200 px-2 py-1 text-xs focus:outline-none focus:ring-1 focus:ring-brand-brown" />
                </td>
                <td class="px-3 py-1.5">
                  <input v-model.number="row.quantity" type="number" min="0" class="w-full rounded-lg border border-slate-200 px-2 py-1 text-xs text-right focus:outline-none focus:ring-1 focus:ring-brand-brown" />
                </td>
                <td class="px-3 py-1.5">
                  <input v-model="row.unit" type="text" placeholder="pcs" class="w-full rounded-lg border border-slate-200 px-2 py-1 text-xs focus:outline-none focus:ring-1 focus:ring-brand-brown" />
                </td>
                <td class="px-3 py-1.5">
                  <input v-model.number="row.limit_qty" type="number" min="0" class="w-full rounded-lg border border-slate-200 px-2 py-1 text-xs text-right focus:outline-none focus:ring-1 focus:ring-brand-brown" />
                </td>
                <td class="px-2 py-1.5">
                  <button @click="rows.splice(i, 1)" class="p-1 text-slate-400 hover:text-red-500 cursor-pointer">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <p class="text-[10px] text-slate-400">Format paste dari Excel: kode, kategori, nama, qty, unit, limit (dipisah tab/koma)</p>
      </div>

      <div class="p-5 border-t border-slate-100 flex justify-end gap-2">
        <button
          @click="$emit('close')"
          class="px-4 py-2 rounded-xl border border-slate-200 text-sm font-bold text-slate-600 hover:bg-slate-50 cursor-pointer transition-colors"
        >
          Batal
        </button>
        <button
          @click="submit"
          :disabled="saving || validRows.length === 0"
          class="px-4 py-2 rounded-xl bg-brand-gradation text-white text-sm font-bold hover:shadow-md cursor-pointer transition-all disabled:opacity-50"
        >
          {{ saving ? 'Mengimpor...' : `Import ${validRows.length} Item` }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import axios from 'axios';

const emit = defineEmits(['close', 'imported']);

const rows = ref([
  { code: '', category: '', name: '', quantity: 0, unit: 'pcs', limit_qty: 1 },
]);

const saving = ref(false);

const validRows = computed(() => rows.value.filter(r => r.code && r.name));

const addRow = () => {
  rows.value.push({ code: '', category: '', name: '', quantity: 0, unit: 'pcs', limit_qty: 1 });
};

const pasteFromClipboard = async () => {
  try {
    const text = await navigator.clipboard.readText();
    const lines = text.trim().split('\n').filter(l => l.trim());
    const newRows = [];
    for (const line of lines) {
      const parts = line.split('\t').length > 1 ? line.split('\t') : line.split(',');
      newRows.push({
        code: (parts[0] ?? '').trim(),
        category: (parts[1] ?? '').trim(),
        name: (parts[2] ?? '').trim(),
        quantity: parseInt(parts[3] ?? '0') || 0,
        unit: (parts[4] ?? 'pcs').trim() || 'pcs',
        limit_qty: parseInt(parts[5] ?? '1') || 1,
      });
    }
    if (newRows.length > 0) {
      rows.value = newRows;
    }
  } catch (e) {
    alert('Gagal membaca clipboard. Pastikan browser mengizinkan akses clipboard.');
  }
};

const submit = async () => {
  saving.value = true;
  try {
    const res = await axios.post('/api/stocks/import', { stocks: validRows.value });
    alert(res.data.message);
    emit('imported');
  } catch (e) {
    alert(e.response?.data?.message ?? 'Gagal mengimpor stok.');
  } finally {
    saving.value = false;
  }
};
</script>
