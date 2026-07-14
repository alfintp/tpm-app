<template>
  <div class="fixed inset-0 z-50 flex items-center justify-center">
    <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm" @click="$emit('close')"></div>
    <div class="bg-white rounded-3xl shadow-2xl w-full max-w-2xl relative z-10 transform transition-all">
      <div class="px-8 py-6 border-b border-slate-100 flex justify-between items-center">
        <div>
          <h3 class="text-xl font-semibold text-slate-800">Import Indikator Komponen</h3>
          <p class="text-sm text-slate-500 mt-1">Upload file Excel untuk mengisi indikator per komponen.</p>
        </div>
        <button @click="$emit('close')" class="text-slate-400 hover:text-slate-600 p-2 rounded-full hover:bg-slate-100 cursor-pointer">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
      </div>

      <div class="p-8 space-y-5 max-h-[65vh] overflow-y-auto">

          <!-- Format detected badge -->
          <div v-if="detectedFormat === 'compact-with-machine'" class="flex items-start gap-3 bg-green-50 border border-green-200 rounded-xl p-4 text-sm text-green-800">
            <svg class="w-5 h-5 shrink-0 mt-0.5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <div>
              <p class="font-semibold">Format ringkas multi-mesin terdeteksi</p>
              <p class="text-xs mt-0.5">Kolom <strong>Kode Mesin</strong> + <strong>Nama Komponen</strong> + kolom teks parameter (<code class="bg-green-100 px-1 rounded">Nama: Keterangan</code>). Data langsung diparse otomatis.</p>
            </div>
          </div>
          <div v-else-if="detectedFormat === 'compact'" class="flex items-start gap-3 bg-green-50 border border-green-200 rounded-xl p-4 text-sm text-green-800">
            <svg class="w-5 h-5 shrink-0 mt-0.5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <div>
              <p class="font-semibold">Format ringkas terdeteksi otomatis</p>
              <p class="text-xs mt-0.5">Kolom <strong>Nama Komponen</strong> + kolom teks parameter (<code class="bg-green-100 px-1 rounded">Nama: Keterangan</code>). Isi Kode Mesin di bawah lalu klik <strong>Parse File</strong>.</p>
            </div>
          </div>
          <div v-else-if="detectedFormat === 'standard'" class="flex items-start gap-3 bg-blue-50 border border-blue-200 rounded-xl p-4 text-sm text-blue-800">
            <svg class="w-5 h-5 shrink-0 mt-0.5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <div>
              <p class="font-semibold">Format standar terdeteksi</p>
              <p class="text-xs mt-0.5">Kolom: Kode Mesin, Nama Komponen, Nama Indikator, Deskripsi.</p>
            </div>
          </div>
          <div v-else class="bg-amber-50 border border-amber-200 rounded-xl p-4 text-sm text-amber-800">
            <div class="flex items-start justify-between gap-4">
              <div>
                <p class="font-semibold mb-1">Format Excel yang didukung:</p>
                <p class="text-xs mb-1"><strong>Format A (Standar):</strong> Kode Mesin · Nama Komponen · Nama Indikator · Deskripsi Indikator — 1 baris per indikator.</p>
                <p class="text-xs mb-1"><strong>Format B (Ringkas multi-mesin):</strong> Kode Mesin · Nama Komponen · Parameter — 1 sel berisi teks <code class="bg-amber-100 px-1 rounded">Nama: Keterangan</code> per baris.</p>
                <p class="text-xs"><strong>Format C (Ringkas 1 mesin):</strong> Nama Komponen · Parameter — sama seperti B, tanpa kolom Kode Mesin (isi manual).</p>
              </div>
              <button
                @click="downloadTemplate"
                type="button"
                class="shrink-0 flex items-center gap-1.5 bg-white border border-amber-300 hover:bg-amber-100 text-amber-700 text-xs font-bold px-3 py-2 rounded-xl transition-colors"
              >
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                Contoh Excel
              </button>
            </div>
          </div>

          <!-- Kode Mesin input: hanya untuk format compact tanpa kode mesin di file -->
          <div v-if="detectedFormat === 'compact'" class="space-y-1.5">
            <label class="text-sm font-medium text-slate-700">Kode Mesin <span class="text-red-500">*</span> <span class="text-slate-400 font-normal text-xs">(tidak ditemukan di file, isi manual)</span></label>
            <div class="flex gap-2">
              <input v-model="excelMachineCode" type="text" placeholder="e.g. LL-PKG-01"
                class="flex-1 rounded-xl border border-slate-300 px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-700" />
              <button @click="parseCompactFile" type="button"
                :disabled="!excelMachineCode.trim()"
                class="flex items-center gap-1.5 text-sm font-bold text-white bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 px-4 py-2.5 rounded-xl transition-colors shrink-0">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                Parse File
              </button>
            </div>
          </div>

          <div class="space-y-1.5">
            <label class="text-sm font-medium text-slate-700">File Excel (.xlsx / .xls)</label>
            <input
              type="file"
              accept=".xlsx,.xls"
              @change="handleFile"
              class="block w-full text-sm text-slate-600 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100"
            />
          </div>

          <div v-if="preview.length > 0" class="space-y-2">
            <div class="flex items-center justify-between">
              <label class="text-sm font-medium text-slate-700">Preview Data</label>
              <span class="text-xs text-slate-500">{{ preview.length }} indikator dari {{ previewGroups }} komponen</span>
            </div>
            <div class="border border-slate-200 rounded-xl overflow-hidden max-h-64 overflow-y-auto">
              <table class="w-full text-sm">
                <thead class="bg-slate-50 sticky top-0">
                  <tr>
                    <th class="px-3 py-2 text-left text-xs font-semibold text-slate-600">Kode Mesin</th>
                    <th class="px-3 py-2 text-left text-xs font-semibold text-slate-600">Komponen</th>
                    <th class="px-3 py-2 text-left text-xs font-semibold text-slate-600">Indikator</th>
                    <th class="px-3 py-2 text-left text-xs font-semibold text-slate-600">Deskripsi</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                  <tr v-for="(item, idx) in preview" :key="idx" class="hover:bg-slate-50">
                    <td class="px-3 py-2 text-slate-700">{{ item.machine_code }}</td>
                    <td class="px-3 py-2 text-slate-700">{{ item.component_name }}</td>
                    <td class="px-3 py-2 font-medium text-slate-800">{{ item.name }}</td>
                    <td class="px-3 py-2 text-slate-500 text-xs">{{ item.description || '-' }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

      </div>
      <div class="px-8 py-5 border-t border-slate-100 bg-slate-50 flex justify-end space-x-3 rounded-b-3xl">
        <button @click="$emit('close')" class="px-5 py-2.5 rounded-xl font-medium text-slate-600 hover:bg-slate-200 transition-colors cursor-pointer">Batal</button>
        <button @click="submit" :disabled="!canSubmit || importing" class="px-5 py-2.5 rounded-xl font-medium text-white bg-indigo-600 hover:bg-indigo-700 transition-colors shadow-sm cursor-pointer disabled:opacity-70 flex items-center gap-2">
          <svg v-if="importing" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
          Import Indikator
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import axios from 'axios';
import { showAlert } from '../composables/useAlert.js';

const emit = defineEmits(['close', 'imported']);

const importing = ref(false);

const preview = ref([]);
const file = ref(null);
const detectedFormat = ref(''); // 'standard' | 'compact' | ''
const excelMachineCode = ref('');
const compactRawRows = ref([]); // store raw rows for compact re-parse

const previewGroups = computed(() => {
  const keys = new Set(preview.value.map(i => `${i.machine_code}|${i.component_name}`));
  return keys.size;
});

const loadSheetJS = () => {
  return new Promise((resolve) => {
    if (window.XLSX) {
      resolve(window.XLSX);
      return;
    }
    const script = document.createElement('script');
    script.src = 'https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js';
    script.onload = () => resolve(window.XLSX);
    document.head.appendChild(script);
  });
};

const canSubmit = computed(() => preview.value.length > 0 && !importing.value);

const downloadTemplate = async () => {
  const XLSX = await loadSheetJS();
  const wb = XLSX.utils.book_new();

  // Sheet 1: Format A (Standar) - 4 kolom, 1 baris per indikator
  const wsA = XLSX.utils.aoa_to_sheet([
    ['Kode Mesin', 'Nama Komponen', 'Nama Indikator', 'Deskripsi Indikator'],
    ['LL-PKG-01', 'Motor Penggerak Utama', 'Kelistrikan', 'Tegangan input/output stabil sesuai spesifikasi kerja'],
    ['LL-PKG-01', 'Motor Penggerak Utama', 'Kebersihan', 'Modul bebas dari debu konduktif, jelaga, dan kelembapan'],
    ['LL-PKG-01', 'Thermostat Digital TC-40', 'Akurasi Sensor', 'Suhu terbaca sesuai dengan alat ukur standar'],
  ]);
  wsA['!cols'] = [{ wch: 15 }, { wch: 28 }, { wch: 25 }, { wch: 50 }];
  XLSX.utils.book_append_sheet(wb, wsA, 'Format A - Standar');

  // Sheet 2: Format B (Ringkas multi-mesin) - 3 kolom, 1 sel berisi teks multi-baris
  const paramInverter = 'Visual: Casing utuh, display terbaca jelas, kipas bersih.\nKelistrikan: Parameter frekuensi dan arus sesuai beban.\nTemperatur: Suhu heatsink normal, kipas internal lancar.\nKebersihan: Kisi-kisi udara bersih dari sumbatan debu.';
  const paramMotor = 'Visual: Tidak ada keretakan atau kebocoran oli.\nGetaran: Getaran dalam batas normal.\nSuhu: Suhu operasional dalam rentang yang diizinkan.';
  const wsB = XLSX.utils.aoa_to_sheet([
    ['Kode Mesin', 'Nama Komponen', 'Parameter'],
    ['LL-PKG-01', 'Inverter Utama', paramInverter],
    ['LL-PKG-01', 'Motor Penggerak', paramMotor],
    ['LL-PKG-02', 'Panel Kontrol', 'Visual: Lampu indikator menyala normal.\nSuhu: Suhu dalam panel tidak melebihi batas.'],
  ]);
  wsB['!cols'] = [{ wch: 15 }, { wch: 28 }, { wch: 60 }];
  XLSX.utils.book_append_sheet(wb, wsB, 'Format B - Ringkas Multi-Mesin');

  XLSX.writeFile(wb, 'Format_Import_Indikator_Komponen.xlsx');
};

const parseIndicatorCell = (cellText) => {
  const lines = String(cellText).split(/\n|\r\n/).map(l => l.trim()).filter(l => l);
  const result = [];
  for (const line of lines) {
    const colonIdx = line.indexOf(':');
    if (colonIdx === -1) continue;
    const name = line.substring(0, colonIdx).trim();
    const description = line.substring(colonIdx + 1).trim();
    if (name && description) result.push({ name, description });
  }
  return result;
};

const parseCompactFile = (machineCodeColIdx = -1) => {
  if (!compactRawRows.value.length) return;
  if (machineCodeColIdx === -1 && !excelMachineCode.value.trim()) return;
  const rows = compactRawRows.value;
  const headers = rows[0].map(h => String(h).toLowerCase().trim());
  const componentNameIdx = headers.findIndex(h => h.includes('komponen') || h.includes('component'));
  // find the column with the most colon-separated content (skip machine/component cols)
  let textColIdx = -1;
  let maxColons = 0;
  const skipCols = new Set([componentNameIdx, machineCodeColIdx].filter(i => i !== -1));
  for (let c = 0; c < headers.length; c++) {
    if (skipCols.has(c)) continue;
    let colons = 0;
    for (let r = 1; r < Math.min(rows.length, 6); r++) {
      colons += (String(rows[r][c] ?? '')).split(':').length - 1;
    }
    if (colons > maxColons) { maxColons = colons; textColIdx = c; }
  }
  if (textColIdx === -1) textColIdx = componentNameIdx === 0 ? 1 : 0;

  const items = [];
  for (let i = 1; i < rows.length; i++) {
    const row = rows[i];
    const machineCode = machineCodeColIdx !== -1
      ? String(row[machineCodeColIdx] ?? '').trim()
      : excelMachineCode.value.trim();
    const componentName = String(row[componentNameIdx] ?? '').trim();
    const cellText = String(row[textColIdx] ?? '').trim();
    if (!machineCode || !componentName || !cellText) continue;
    const indicators = parseIndicatorCell(cellText);
    for (const iv of indicators) {
      items.push({ machine_code: machineCode, component_name: componentName, name: iv.name, description: iv.description });
    }
  }
  preview.value = items;
  if (!items.length) showAlert('warning', 'Tidak ada data', 'Tidak ada indikator yang berhasil diparsing dari file.');
};

const handleFile = async (e) => {
  const f = e.target.files?.[0];
  if (!f) return;
  file.value = f;
  preview.value = [];
  detectedFormat.value = '';
  compactRawRows.value = [];

  const XLSX = await loadSheetJS();
  const reader = new FileReader();
  reader.onload = (event) => {
    try {
      const data = new Uint8Array(event.target.result);
      const workbook = XLSX.read(data, { type: 'array' });
      const sheet = workbook.Sheets[workbook.SheetNames[0]];
      const rows = XLSX.utils.sheet_to_json(sheet, { header: 1, defval: '' });

      if (rows.length < 2) {
        showAlert('warning', 'File Kosong', 'File Excel tidak memiliki data.');
        return;
      }

      const headers = rows[0].map(h => String(h).toLowerCase().trim());
      const machineCodeIdx = headers.findIndex(h => (h.includes('kode') && h.includes('mesin')) || h === 'machine' || h === 'kode mesin');
      const componentNameIdx = headers.findIndex(h => h.includes('komponen') || h.includes('component'));
      const indicatorNameIdx = headers.findIndex(h => h.includes('indikator') && (h.includes('nama') || h.includes('name')));
      const descriptionIdx = headers.findIndex(h => h.includes('deskripsi') || h.includes('description'));

      // Format A (Standar): Kode Mesin + Nama Komponen + Nama Indikator
      if (machineCodeIdx !== -1 && componentNameIdx !== -1 && indicatorNameIdx !== -1) {
        detectedFormat.value = 'standard';
        const items = [];
        for (let i = 1; i < rows.length; i++) {
          const row = rows[i];
          const machineCode = String(row[machineCodeIdx] ?? '').trim();
          const componentName = String(row[componentNameIdx] ?? '').trim();
          const name = String(row[indicatorNameIdx] ?? '').trim();
          const description = descriptionIdx !== -1 ? String(row[descriptionIdx] ?? '').trim() : '';
          if (!machineCode || !componentName || !name) continue;
          items.push({ machine_code: machineCode, component_name: componentName, name, description });
        }
        preview.value = items;
      } else if (machineCodeIdx !== -1 && componentNameIdx !== -1) {
        // Format B (Ringkas multi-mesin): Kode Mesin + Nama Komponen + sel teks parameter
        detectedFormat.value = 'compact-with-machine';
        compactRawRows.value = rows;
        parseCompactFile(machineCodeIdx);
      } else if (componentNameIdx !== -1) {
        // Format C (Ringkas 1 mesin): Nama Komponen + sel teks parameter
        detectedFormat.value = 'compact';
        compactRawRows.value = rows;
        if (excelMachineCode.value.trim()) parseCompactFile();
      } else {
        showAlert('warning', 'Format Tidak Dikenali', 'Pastikan ada kolom "Kode Mesin" dan/atau "Nama Komponen".');
      }
    } catch (err) {
      console.error(err);
      preview.value = [];
      showAlert('error', 'Gagal Membaca File', 'Pastikan file adalah Excel yang valid.');
    }
  };
  reader.readAsArrayBuffer(f);
};

const groupByComponent = (items) => {
  const grouped = {};
  for (const item of items) {
    const key = `${item.machine_code}|${item.component_name}`;
    if (!grouped[key]) {
      grouped[key] = { machine_code: item.machine_code, component_name: item.component_name, indicators: [] };
    }
    grouped[key].indicators.push({ name: item.name, description: item.description });
  }
  return Object.values(grouped);
};

const submit = async () => {
  const items = preview.value;
  if (!items.length) return;
  importing.value = true;
  try {
    const grouped = groupByComponent(items);
    const res = await axios.post('/api/components/import-indicators', { items: grouped });
    showAlert('success', 'Import Berhasil', res.data.message || 'Indikator berhasil diimport.');
    emit('imported');
  } catch (e) {
    console.error(e);
    const errorMsg = e.response?.data?.message || e.response?.data?.error || e.message;
    showAlert('error', 'Gagal Import', errorMsg);
  } finally {
    importing.value = false;
  }
};
</script>
