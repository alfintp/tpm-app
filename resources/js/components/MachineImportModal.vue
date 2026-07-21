<template>
  <div v-if="show" class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl max-w-lg w-full p-6 shadow-xl border border-slate-100 space-y-6">

      <!-- Header -->
      <div class="flex justify-between items-start">
        <div>
          <h3 class="text-xl font-bold text-slate-800 flex items-center gap-2">
            <svg v-if="type === 'machine'" class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            <svg v-else class="w-6 h-6 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
            {{ type === 'machine' ? 'Import Data Mesin' : 'Import Komponen Mesin' }}
          </h3>
          <p class="text-xs text-slate-500 mt-1">
            {{ type === 'machine'
              ? 'Unggah file Excel untuk mengimpor data mesin secara massal'
              : 'Unggah file Excel untuk mengimpor komponen massal berdasarkan Kode Mesin' }}
          </p>
        </div>
        <button @click="$emit('close')" class="p-1.5 hover:bg-slate-100 rounded-xl transition-all cursor-pointer text-slate-400">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
          </svg>
        </button>
      </div>

      <div class="space-y-4">
        <!-- Format note for component import -->
        <div v-if="type === 'component'" class="bg-amber-50 border border-amber-200 rounded-xl p-4 text-sm text-amber-800 space-y-2">
          <div>
            <p class="font-semibold mb-1">Format Excel untuk Import Komponen</p>
            <p class="text-xs">Sesuaikan dengan template yang Anda unduh. Dua format didukung:</p>
          </div>
          <div class="text-xs space-y-1">
            <p><strong>Format A — detail mesin (tanpa Kode Mesin):</strong></p>
            <p class="text-slate-600">Kategori · Nama Komponen · Spesifikasi · Jumlah (Qty) · Satuan · Kondisi Awal (%) · Kesulitan (ringan/sedang/berat) · Indikator</p>
          </div>
          <div class="text-xs space-y-1">
            <p><strong>Format B — massal (dengan Kode Mesin):</strong></p>
            <p class="text-slate-600">Kode Mesin · Kategori · Nama Komponen · Spesifikasi · Jumlah (Qty) · Satuan · Kesulitan (ringan/sedang/berat) · Kondisi Awal (%) · Indikator</p>
          </div>
          <div class="text-xs">
            <p class="mb-1"><strong>Kolom Indikator</strong> diisi teks multi-baris dengan format <code class="bg-amber-100 px-1 rounded">Nama: Keterangan</code> (satu indikator per baris).</p>
            <p class="text-slate-600">Contoh isian kolom Indikator:</p>
            <ul class="list-disc list-inside text-slate-600 mt-0.5 space-y-0.5">
              <li><code class="bg-amber-100 px-1 rounded">Visual: Casing utuh, tidak ada keretakan.</code></li>
              <li><code class="bg-amber-100 px-1 rounded">Kelistrikan: Tegangan stabil sesuai spesifikasi.</code></li>
            </ul>
          </div>
        </div>

        <!-- Step 1: Download Template -->
        <div class="bg-slate-50 border border-slate-100 p-4 rounded-2xl flex items-center justify-between gap-4">
          <div>
            <p class="text-xs font-bold text-slate-700">1. Unduh Format Template</p>
            <p class="text-[11px] text-slate-400 mt-0.5">Gunakan template resmi agar susunan kolom sesuai dengan sistem</p>
          </div>
          <button
            @click="$emit('download-template')"
            class="flex items-center gap-1.5 bg-white border border-emerald-200 hover:border-emerald-300 hover:bg-emerald-50 text-emerald-700 text-xs font-bold px-3 py-2 rounded-xl transition-all cursor-pointer shadow-sm"
          >
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
            </svg>
            Format Excel
          </button>
        </div>

        <!-- Step 2: Choose File -->
        <div class="space-y-2">
          <p class="text-xs font-bold text-slate-700">2. Pilih File Excel (.xlsx, .xls, .csv)</p>
          <div class="border-2 border-dashed border-slate-200 hover:border-indigo-400 bg-white hover:bg-indigo-50/20 p-6 rounded-2xl text-center transition-all relative">
            <input
              type="file"
              ref="fileInput"
              @change="handleFileChange"
              accept=".xlsx,.xls,.csv"
              class="absolute inset-0 opacity-0 w-full h-full cursor-pointer"
            />
            <svg class="w-8 h-8 text-slate-400 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
            </svg>
            <p class="text-xs font-semibold text-slate-700">
              {{ selectedFile ? selectedFile.name : 'Klik untuk cari file atau seret file ke sini' }}
            </p>
            <p v-if="selectedFile" class="text-[10px] text-slate-400 mt-1">
              Ukuran: {{ (selectedFile.size / 1024).toFixed(1) }} KB
            </p>
          </div>
        </div>
      </div>

      <!-- Footer -->
      <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
        <button
          @click="$emit('close')"
          class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-xl text-xs font-bold cursor-pointer transition-all"
        >Batal</button>
        <button
          @click="$emit('import', selectedFile)"
          :disabled="!selectedFile || loading"
          :class="type === 'machine' ? 'bg-emerald-600 hover:bg-emerald-700' : 'bg-teal-600 hover:bg-teal-700'"
          class="px-4 py-2 disabled:bg-slate-200 disabled:text-slate-400 text-white rounded-xl text-xs font-bold flex items-center gap-1.5 cursor-pointer disabled:cursor-not-allowed transition-all"
        >
          <span v-if="loading" class="animate-spin w-3 h-3 border-2 border-white/20 border-t-white rounded-full"></span>
          {{ loading ? 'Mengimpor...' : 'Mulai Import' }}
        </button>
      </div>

    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';

defineProps({
  show:    { type: Boolean, required: true },
  type:    { type: String,  default: 'machine' },
  loading: { type: Boolean, default: false },
});

defineEmits(['close', 'import', 'download-template']);

const selectedFile = ref(null);
const fileInput    = ref(null);

const handleFileChange = (e) => {
  const file = e.target.files[0];
  if (file) selectedFile.value = file;
};

defineExpose({ resetFile: () => { selectedFile.value = null; } });
</script>
