<template>
  <div class="fixed inset-0 z-50 flex items-center justify-center">
    <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm" @click="$emit('close')"></div>
    <div class="bg-white rounded-3xl shadow-2xl w-full max-w-md relative z-10">
      <div class="px-8 py-6 border-b border-slate-100 flex justify-between items-center">
        <h3 class="text-xl font-semibold text-slate-800">Tambah Mesin Baru</h3>
        <button @click="$emit('close')" class="text-slate-400 hover:text-slate-600 p-2 rounded-full hover:bg-slate-100 cursor-pointer">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
      </div>
      <div class="p-8 space-y-5 max-h-[70vh] overflow-y-auto">
        <div class="space-y-1.5">
          <label class="text-sm font-medium text-slate-700">Nama Mesin *</label>
          <input type="text" v-model="form.name" required class="w-full rounded-xl border border-slate-300 px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-700" placeholder="e.g. Mixer Rice Crunch">
        </div>
        <div class="space-y-1.5">
          <label class="text-sm font-medium text-slate-700">Deskripsi</label>
          <textarea v-model="form.description" rows="2" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-700 resize-none" placeholder="Deskripsi singkat mesin..."></textarea>
        </div>
        <div class="space-y-1.5">
          <label class="text-sm font-medium text-slate-700">Lokasi / Area</label>
          <input type="text" v-model="form.location" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-700" placeholder="e.g. Line 1">
        </div>
        <div class="space-y-1.5">
          <label class="text-sm font-medium text-slate-700">PIC Mesin</label>
          <select v-model="form.pic_mesin_id" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-700 cursor-pointer">
            <option value="">Pilih PIC</option>
            <option v-for="user in users" :key="user.id" :value="user.id">{{ user.full_name }} ({{ user.role }})</option>
          </select>
        </div>
        <div class="grid grid-cols-2 gap-4">
          <div class="space-y-1.5">
            <label class="text-sm font-medium text-slate-700">Kondisi Awal (%) *</label>
            <input type="number" v-model="form.condition_pct" min="0" max="100" required class="w-full rounded-xl border border-slate-300 px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-700">
            <p class="text-xs text-slate-400">Nilai ini akan dihitung ulang otomatis saat komponen ditambahkan.</p>
          </div>
          <div class="space-y-1.5">
            <label class="text-sm font-medium text-slate-700">Durasi Maintenance (hari)</label>
            <input type="number" v-model="form.maintenance_duration" min="1" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-700" placeholder="e.g. 30">
          </div>
        </div>
        <div class="space-y-1.5">
          <label class="text-sm font-medium text-slate-700">Tanggal Mulai Maintenance</label>
          <input type="date" v-model="form.maintenance_start_date" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-700">
          <p class="text-xs text-slate-400">Pengingat otomatis akan dikirim berdasarkan durasi maintenance.</p>
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
import { ref, onMounted } from 'vue';
import axios from 'axios';

const emit = defineEmits(['close', 'saved']);
const saving = ref(false);
const users = ref([]);
const form = ref({ 
  name: '', 
  description: '', 
  location: '', 
  status: 'active', 
  condition_pct: 100,
  pic_mesin_id: '',
  maintenance_duration: null,
  maintenance_start_date: ''
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
  if (!form.value.name) return;
  saving.value = true;
  try {
    await axios.post('/api/machines', form.value);
    emit('saved');
  } catch (e) {
    console.error(e);
    alert('Gagal menyimpan: ' + (e.response?.data?.message || e.message));
  } finally {
    saving.value = false;
  }
};
</script>
