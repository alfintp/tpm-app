<template>
  <div class="fixed inset-0 z-50 flex items-center justify-center">
    <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm" @click="$emit('close')"></div>
    <div class="bg-white rounded-3xl shadow-2xl w-full max-w-md relative z-10 transform transition-all">
      <div class="px-8 py-6 border-b border-slate-100 flex justify-between items-center">
        <h3 class="text-xl font-semibold text-slate-800">{{ isEdit ? 'Edit Komponen' : 'Tambah Komponen' }}</h3>
        <button @click="$emit('close')" class="text-slate-400 hover:text-slate-600 p-2 rounded-full hover:bg-slate-100 cursor-pointer">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
      </div>
      <div class="p-8 space-y-5 max-h-[70vh] overflow-y-auto">
          <div class="space-y-1.5">
            <label class="text-sm font-medium text-slate-700">Kategori</label>
            <input type="text" v-model="form.category" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-700" placeholder="e.g. Mechanical, Electrical">
          </div>
         
        <div class="space-y-1.5">
          <label class="text-sm font-medium text-slate-700">Nama Komponen *</label>
          <input type="text" v-model="form.name" required class="w-full rounded-xl border border-slate-300 px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-700" placeholder="e.g. Mold Die Set">
        </div>
        <div class="space-y-1.5">
          <label class="text-sm font-medium text-slate-700">Spesifikasi</label>
          <textarea v-model="form.specification" rows="2" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-700 resize-none" placeholder="e.g. Titanium Coated, 60/70/80 mm"></textarea>
        </div>
        <div class="grid grid-cols-2 gap-4">
          <div class="space-y-1.5">
            <label class="text-sm font-medium text-slate-700">Qty</label>
            <input type="text" v-model="form.qty" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-700" placeholder="e.g. 6-8 atau 10">
          </div>
          <div class="space-y-1.5">
            <label class="text-sm font-medium text-slate-700">Satuan</label>
            <input type="text" v-model="form.unit" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-700" placeholder="e.g. pcs, set, meter">
          </div>
        </div>
        <div class="space-y-1.5">
          <label class="text-sm font-medium text-slate-700">Tingkat Kesulitan</label>
          <select v-model="form.difficulty" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-700 bg-white">
            <option value="">Pilih kesulitan</option>
            <option value="ringan">Ringan</option>
            <option value="sedang">Sedang</option>
            <option value="berat">Berat</option>
          </select>
        </div>

        <!-- Indicators Section -->
        <div class="space-y-3 pt-2 border-t border-slate-100">
          <div class="flex items-center justify-between">
            <label class="text-sm font-medium text-slate-700">Indikator Penilaian</label>
            <span class="text-xs text-slate-400">{{ form.indicators.length }} indikator</span>
          </div>
          <p class="text-xs text-slate-500">Tambahkan indikator untuk menghitung persentase kondisi komponen secara otomatis.</p>

          <div v-if="form.indicators.length === 0" class="text-xs text-slate-400 bg-slate-50 rounded-xl p-3 text-center">
            Belum ada indikator. Persentase kondisi diisi manual.
          </div>

          <div v-for="(indicator, idx) in form.indicators" :key="indicator._key" class="bg-slate-50 rounded-xl p-3 space-y-2">
            <div class="flex items-start gap-2">
              <div class="flex-1 space-y-2">
                <input
                  v-model="indicator.name"
                  type="text"
                  placeholder="Nama Indikator (contoh: Kelistrikan)"
                  class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-700"
                />
                <input
                  v-model="indicator.description"
                  type="text"
                  placeholder="Deskripsi (contoh: Tegangan input/output stabil...)"
                  class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-700"
                />
              </div>
              <button
                @click="removeIndicator(idx)"
                type="button"
                class="text-slate-400 hover:text-red-500 p-1.5 rounded-lg hover:bg-red-50 transition-colors"
                title="Hapus indikator"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
              </button>
            </div>
          </div>

          <button
            @click="addIndicator"
            type="button"
            class="w-full py-2 rounded-xl border border-dashed border-indigo-300 text-indigo-600 text-sm font-medium hover:bg-indigo-50 transition-colors flex items-center justify-center gap-1.5"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Tambah Indikator
          </button>
        </div>
      </div>
      <div class="px-8 py-5 border-t border-slate-100 bg-slate-50 flex justify-end space-x-3 rounded-b-3xl">
        <button @click="$emit('close')" class="px-5 py-2.5 rounded-xl font-medium text-slate-600 hover:bg-slate-200 transition-colors cursor-pointer">Batal</button>
        <button @click="submit" :disabled="saving" class="px-5 py-2.5 rounded-xl font-medium text-white bg-indigo-600 hover:bg-indigo-700 transition-colors shadow-sm cursor-pointer disabled:opacity-70 flex items-center gap-2">
          <svg v-if="saving" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
          {{ isEdit ? 'Simpan Perubahan' : 'Tambah Komponen' }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import axios from 'axios';
import { showAlert } from '../composables/useAlert.js';

const props = defineProps({
  machineId: { type: String, required: true },
  component: { type: Object, default: null },
});
const emit = defineEmits(['close', 'saved']);

const isEdit = computed(() => !!props.component);
const saving = ref(false);

const indicatorKey = ref(0);

const form = ref({
  category: props.component?.category ?? '',
  name: props.component?.name ?? '',
  specification: props.component?.specification ?? '',
  qty: props.component?.qty ?? '',
  unit: props.component?.unit ?? '',
  difficulty: props.component?.difficulty ?? '',
  last_condition_pct: props.component?.last_condition_pct ?? 100,
  last_replaced_at: props.component?.last_replaced_at ? props.component.last_replaced_at.split('T')[0] : '',
  indicators: (props.component?.indicators ?? []).map((i, idx) => ({
    id: i.id,
    name: i.name,
    description: i.description ?? '',
    sort_order: i.sort_order ?? idx,
    _key: indicatorKey.value++,
  })),
});

const addIndicator = () => {
  form.value.indicators.push({
    id: null,
    name: '',
    description: '',
    sort_order: form.value.indicators.length,
    _key: indicatorKey.value++,
  });
};

const removeIndicator = (idx) => {
  form.value.indicators.splice(idx, 1);
};

const submitPayload = computed(() => {
  const payload = { ...form.value };
  payload.indicators = payload.indicators.map((i, idx) => ({
    id: i.id,
    name: i.name,
    description: i.description || null,
    sort_order: idx,
  })).filter(i => i.name.trim() !== '');
  return payload;
});

const submit = async () => {
  // Validasi hanya nama yang wajib
  if (!form.value.name || form.value.name.trim() === '') {
    showAlert('warning', 'Data Belum Lengkap', 'Nama Komponen wajib diisi!');
    return;
  }

  saving.value = true;
  try {
    if (isEdit.value) {
      await axios.put(`/api/components/${props.component.id}`, submitPayload.value);
    } else {
      await axios.post(`/api/machines/${props.machineId}/components`, submitPayload.value);
    }
    emit('saved');
  } catch (e) {
    console.error(e);
    const errorMsg = e.response?.data?.message || e.response?.data?.error || e.message;
    showAlert('error', 'Gagal Menyimpan', errorMsg);
  } finally {
    saving.value = false;
  }
};
</script>
