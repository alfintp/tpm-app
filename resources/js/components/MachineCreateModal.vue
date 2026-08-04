<template>
  <div class="fixed inset-0 z-50 flex items-start justify-center pt-24 pb-6 overflow-y-auto">
    <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm" @click="$emit('close')"></div>
    <div class="bg-white rounded-3xl shadow-2xl w-full max-w-md relative z-10">
      <div class="px-8 py-6 border-b border-slate-100 flex justify-between items-center">
        <h3 class="text-xl font-semibold text-slate-800">Tambah Mesin Baru</h3>
        <button @click="$emit('close')" class="text-slate-400 hover:text-slate-600 p-2 rounded-full hover:bg-slate-100 cursor-pointer">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
      </div>
      <div class="p-8 space-y-5 max-h-[55vh] overflow-y-auto">
        <div class="space-y-1.5">
          <label class="text-sm font-medium text-slate-700">Kode Mesin *</label>
          <input type="text" v-model="form.kode" required class="w-full rounded-xl border border-slate-300 px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-700 font-mono" placeholder="e.g. LL-MX-01">
        </div>
        <div class="space-y-1.5">
          <label class="text-sm font-medium text-slate-700">Nama Mesin *</label>
          <input type="text" v-model="form.name" required class="w-full rounded-xl border border-slate-300 px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-700" placeholder="e.g. Mixer Rice Crunch">
        </div>
        <div class="space-y-1.5">
          <label class="text-sm font-medium text-slate-700">Deskripsi</label>
          <textarea v-model="form.description" rows="2" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-700 resize-none" placeholder="Deskripsi singkat mesin..."></textarea>
        </div>
        <div class="grid grid-cols-2 gap-4">
          <div class="space-y-1.5">
            <label class="text-sm font-medium text-slate-700">Kota *</label>
            <select v-model="form.kota" required class="w-full rounded-xl border border-slate-300 px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-700 cursor-pointer">
              <option value="pasuruan">Pasuruan</option>
              <option value="sby">Surabaya</option>
            </select>
          </div>
          <div class="space-y-1.5">
            <label class="text-sm font-medium text-slate-700">Lokasi / Area</label>
            <input type="text" v-model="form.location" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-700" placeholder="e.g. Line 1">
          </div>
        </div>
        <div class="space-y-1.5">
          <label class="text-sm font-medium text-slate-700">PIC Mesin</label>
          <select v-model="form.pic_mesin_id" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-700 cursor-pointer">
            <option value="">Pilih PIC</option>
            <option v-for="user in users" :key="user.id" :value="user.id">{{ user.full_name }} ({{ user.role }})</option>
          </select>
        </div>
        <div class="space-y-1.5">
          <label class="text-sm font-medium text-slate-700">Frekuensi Maintenance</label>
          <select v-model="frequencyOption" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-700 cursor-pointer">
            <option value="">Tidak ada jadwal</option>
            <option v-for="opt in frequencyOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
          </select>
          <p v-if="frequencyOption" class="text-xs text-slate-400">Interval: setiap {{ form.maintenance_duration }} hari. Tanggal pasti maintenance akan ditentukan otomatis oleh sistem secara merata setiap bulan.</p>
        </div>
      </div>
      <div class="px-8 pb-8 flex justify-end gap-3">
        <button @click="$emit('close')" class="px-5 py-2.5 rounded-xl font-medium text-slate-600 hover:bg-slate-100 transition-colors cursor-pointer">Batal</button>
        <button @click="submit" :disabled="saving" class="px-5 py-2.5 rounded-xl font-medium text-white bg-indigo-600 hover:bg-indigo-700 transition-colors shadow-sm cursor-pointer disabled:opacity-70 flex items-center gap-2">
          <svg v-if="saving" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
          Simpan Mesin
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch, onMounted } from 'vue';
import axios from 'axios';
import { showAlert } from '../composables/useAlert.js';

const emit = defineEmits(['close', 'saved']);
const saving = ref(false);
const users = ref([]);
const frequencyOptions = [
  { label: '4x sebulan (setiap minggu)', value: 7 },
  { label: '2x sebulan', value: 14 },
  { label: '1x sebulan', value: 28 },
  { label: '1x per 2 bulan', value: 56 },
  { label: '1x per 3 bulan', value: 84 },
];

const frequencyOption = ref('');

const form = ref({ 
  kode: '',
  name: '', 
  description: '', 
  location: '', 
  kota: 'pasuruan', 
  status: 'active', 
  condition_pct: 100,
  pic_mesin_id: '',
  maintenance_duration: null,
});

watch(frequencyOption, (val) => {
  form.value.maintenance_duration = val ? Number(val) : null;
});

const loadUsers = async () => {
  try {
    const res = await axios.get('/api/users');
    users.value = res.data;
  } catch (e) {
    console.error(e);
  }
};

onMounted(() => {
  loadUsers();
});

const submit = async () => {
  // Validasi data lengkap
  if (!form.value.kode || !form.value.name || !form.value.kota) {
    showAlert('warning', 'Data Belum Lengkap', 'Kode Mesin, Nama Mesin, dan Kota wajib diisi!');
    return;
  }

  saving.value = true;
  try {
    await axios.post('/api/machines', form.value);
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
