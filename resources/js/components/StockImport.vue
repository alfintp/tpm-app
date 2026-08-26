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
          <button
            @click="triggerFileInput"
            class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-bold text-slate-600 hover:border-brand-brown hover:text-brand-brown cursor-pointer transition-colors"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
            Upload Excel
          </button>
          <input
            ref="fileInputRef"
            type="file"
            accept=".xlsx,.xls,.csv"
            class="hidden"
            @change="handleFileUpload"
          />
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

        <div class="rounded-xl bg-slate-50 border border-slate-100 p-3 space-y-1">
          <p class="text-xs font-semibold text-slate-600">Format Excel/CSV:</p>
          <p class="text-[10px] text-slate-400">Kolom: Kode | Kategori | Nama | Qty | Unit | Limit</p>
          <p class="text-[10px] text-slate-400">Wajib: Kode & Nama. Opsional: Kategori, Qty (default 0), Unit (default pcs), Limit (default 1)</p>
          <p class="text-[10px] text-slate-400">Bisa dengan atau tanpa header row. Jika ada header berisi "Kode"/"Code", otomatis dilewati.</p>
          <p class="text-[10px] text-slate-400">Paste juga didukung: copy dari Excel lalu klik "Paste dari Excel".</p>
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
import * as XLSX from 'xlsx';
import { showAlert } from '../composables/useAlert.js';

const emit = defineEmits(['close', 'imported']);

const rows = ref([
  { code: '', category: '', name: '', quantity: 0, unit: 'pcs', limit_qty: 1 },
]);

const saving = ref(false);

const validRows = computed(() => rows.value.filter(r => r.code && r.name));

const fileInputRef = ref(null);

const triggerFileInput = () => {
  fileInputRef.value?.click();
};

const handleFileUpload = async (e) => {
  const file = e.target.files?.[0];
  if (!file) return;

  try {
    const data = await file.arrayBuffer();
    const workbook = XLSX.read(data);
    const sheet = workbook.Sheets[workbook.SheetNames[0]];
    const json = XLSX.utils.sheet_to_json(sheet, { header: 1 });

    const newRows = [];
    let startIdx = 0;

    // Detect header row: if first row contains 'kode' or 'code', skip it
    const firstRow = json[0];
    if (firstRow && typeof firstRow[0] === 'string' && /^(kode|code)$/i.test(firstRow[0].trim())) {
      startIdx = 1;
    }

    for (let i = startIdx; i < json.length; i++) {
      const parts = json[i];
      if (!parts || (!parts[0] && !parts[2])) continue;
      newRows.push({
        code: String(parts[0] ?? '').trim(),
        category: String(parts[1] ?? '').trim(),
        name: String(parts[2] ?? '').trim(),
        quantity: parseInt(parts[3] ?? '0') || 0,
        unit: String(parts[4] ?? 'pcs').trim() || 'pcs',
        limit_qty: parseInt(parts[5] ?? '1') || 1,
      });
    }

    if (newRows.length > 0) {
      rows.value = newRows;
    } else {
      showAlert('warning', 'Data Kosong', 'Tidak ada data valid ditemukan di file.');
    }
  } catch (err) {
    showAlert('error', 'Gagal Membaca File', 'Pastikan format sesuai: Kode, Kategori, Nama, Qty, Unit, Limit.');
  }

  // Reset input so same file can be re-uploaded
  e.target.value = '';
};

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
    showAlert('error', 'Gagal Paste', 'Pastikan browser mengizinkan akses clipboard.');
  }
};

const submit = async () => {
  saving.value = true;
  try {
    const res = await axios.post('/api/stocks/import', { stocks: validRows.value });
    await showAlert('success', 'Import Berhasil', res.data.message);
    emit('imported');
  } catch (e) {
    showAlert('error', 'Gagal Import', e.response?.data?.message ?? 'Terjadi kesalahan saat mengimpor stok.');
  } finally {
    saving.value = false;
  }
};
</script>
