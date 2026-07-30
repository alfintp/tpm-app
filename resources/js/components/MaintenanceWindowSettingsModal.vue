<template>
  <div v-if="show" class="fixed inset-0 z-60 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl max-w-md w-full p-6 shadow-xl border border-slate-100 space-y-6">
      <!-- Header -->
      <div class="flex justify-between items-start">
        <div>
          <h3 class="text-xl font-bold text-slate-800 flex items-center gap-2">
            <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
            Periode Laporan Maintenance
          </h3>
          <p class="text-xs text-slate-500 mt-1">
            Atur kapan mesin bisa dilaporkan dan batas laporan dianggap tepat waktu.
          </p>
        </div>
        <button @click="$emit('close')" class="p-1.5 hover:bg-slate-100 rounded-xl transition-all cursor-pointer text-slate-400">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
      </div>

      <div v-if="loading" class="py-8 text-center text-sm text-slate-400">Memuat pengaturan...</div>

      <template v-else>
        <div class="space-y-4 bg-slate-50 p-4 rounded-2xl border border-slate-100">
          <div class="grid grid-cols-2 gap-4">
            <div class="flex flex-col gap-1">
              <label class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Dibuka Sejak</label>
              <select v-model.number="daysBefore" class="rounded-xl border border-slate-200 px-3 py-2 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-brand-brown cursor-pointer bg-white">
                <option v-for="n in 15" :key="'before-'+(n-1)" :value="n-1">
                  {{ n - 1 === 0 ? 'H (hari jadwal)' : `H-${n - 1}` }}
                </option>
              </select>
            </div>
            <div class="flex flex-col gap-1">
              <label class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Ditutup Setelah</label>
              <select v-model.number="daysAfter" class="rounded-xl border border-slate-200 px-3 py-2 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-brand-brown cursor-pointer bg-white">
                <option v-for="n in 15" :key="'after-'+(n-1)" :value="n-1">
                  {{ n - 1 === 0 ? 'H (hari jadwal)' : `H+${n - 1}` }}
                </option>
              </select>
            </div>
          </div>
          <div class="flex flex-col gap-1">
            <label class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Mulai Munculkan Notifikasi (Alert)</label>
            <select v-model.number="alertDaysBefore" class="rounded-xl border border-slate-200 px-3 py-2 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-brand-brown cursor-pointer bg-white">
              <option v-for="n in 31" :key="'alert-'+(n-1)" :value="n-1">
                {{ n - 1 === 0 ? 'H (hari jadwal)' : `H-${n - 1}` }}
              </option>
            </select>
            <p class="text-[10px] text-slate-400">Notifikasi maintenance (alert kuning/merah) akan mulai muncul sejak H-{{ alertDaysBefore }} dari jadwal.</p>
          </div>
          <div class="text-[11px] text-slate-400 leading-relaxed bg-white p-3 rounded-xl border border-slate-100">
            Mesin dapat dilaporkan mulai <span class="font-bold text-slate-600">H-{{ daysBefore }}</span> hingga <span class="font-bold text-slate-600">H+{{ daysAfter }}</span> dari tanggal jadwal.
            Laporan yang masuk dalam periode ini akan tercatat sebagai <span class="font-bold text-emerald-600">Tepat Waktu</span>, dan setelah periode ini akan tercatat sebagai <span class="font-bold text-rose-600">Terlambat</span>.
          </div>
        </div>

        <div v-if="error" class="text-xs font-semibold text-rose-600 bg-rose-50 border border-rose-100 rounded-xl px-3 py-2">{{ error }}</div>

        <!-- Footer -->
        <div class="flex items-center justify-end gap-3 pt-2 border-t border-slate-100">
          <button @click="$emit('close')" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-xl text-xs font-bold cursor-pointer transition-all">
            Batal
          </button>
          <button
            @click="save"
            :disabled="saving"
            class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 disabled:opacity-60 text-white rounded-xl text-xs font-bold flex items-center gap-1.5 cursor-pointer transition-all"
          >
            <svg v-if="saving" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
            <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            Simpan
          </button>
        </div>
      </template>
    </div>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue';
import axios from 'axios';
import { showAlert } from '../composables/useAlert.js';

const props = defineProps({
  show: { type: Boolean, default: false },
});

const emit = defineEmits(['close', 'saved']);

const loading = ref(false);
const saving = ref(false);
const error = ref('');
const daysBefore = ref(2);
const daysAfter = ref(0);
const alertDaysBefore = ref(7);

const loadSettings = async () => {
  loading.value = true;
  error.value = '';
  try {
    const res = await axios.get('/api/settings/maintenance-window');
    daysBefore.value = res.data.days_before ?? 2;
    daysAfter.value = res.data.days_after ?? 0;
    alertDaysBefore.value = res.data.alert_days_before ?? 7;
  } catch (e) {
    error.value = e.response?.data?.message || 'Gagal memuat pengaturan.';
  } finally {
    loading.value = false;
  }
};

watch(() => props.show, (val) => {
  if (val) loadSettings();
});

const save = async () => {
  saving.value = true;
  error.value = '';
  try {
    const res = await axios.put('/api/settings/maintenance-window', {
      days_before: daysBefore.value,
      days_after: daysAfter.value,
      alert_days_before: alertDaysBefore.value,
    });
    showAlert('success', 'Berhasil!', 'Konfigurasi periode laporan berhasil disimpan.');
    emit('saved', res.data);
    emit('close');
  } catch (e) {
    const msg = e.response?.data?.message || 'Gagal menyimpan pengaturan.';
    error.value = msg;
    showAlert('error', 'Gagal!', msg);
  } finally {
    saving.value = false;
  }
};
</script>
