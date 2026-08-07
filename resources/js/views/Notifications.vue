<template>
  <div class="w-full max-w-5xl mx-auto space-y-6">
    <PageHeader title="Manajemen Notifikasi" subtitle="Buat dan kelola notifikasi informasi/pembaruan fitur untuk semua pengguna">
      <template #actions>
        <button
          @click="openCreate"
          class="inline-flex items-center gap-1.5 rounded-xl bg-brand-gradation text-white px-3 py-2 text-xs font-bold transition-all hover:shadow-md active:scale-95 cursor-pointer"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
          Buat Notifikasi
        </button>
      </template>
    </PageHeader>

    <!-- Stats -->
    <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
      <div class="bg-white rounded-2xl border border-slate-200 p-4">
        <p class="text-[10px] font-bold text-slate-400 uppercase">Total Notifikasi</p>
        <p class="text-2xl font-black text-brand-brown mt-1">{{ notifications.length }}</p>
      </div>
      <div class="bg-white rounded-2xl border border-slate-200 p-4">
        <p class="text-[10px] font-bold text-slate-400 uppercase">Aktif</p>
        <p class="text-2xl font-black text-emerald-600 mt-1">{{ activeCount }}</p>
      </div>
      <div class="bg-white rounded-2xl border border-slate-200 p-4 col-span-2 sm:col-span-1">
        <p class="text-[10px] font-bold text-slate-400 uppercase">Nonaktif</p>
        <p class="text-2xl font-black text-slate-400 mt-1">{{ notifications.length - activeCount }}</p>
      </div>
    </div>

    <!-- List -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
      <div class="px-5 py-3.5 border-b border-slate-100 flex items-center justify-between">
        <h2 class="text-sm font-bold text-slate-700">Daftar Notifikasi</h2>
        <span class="text-xs text-slate-400">{{ notifications.length }} item</span>
      </div>

      <div v-if="loading" class="px-5 py-20 flex items-center justify-center">
        <svg class="animate-spin w-6 h-6 text-slate-300" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
      </div>

      <div v-else-if="notifications.length === 0" class="px-5 py-20 text-center">
        <svg class="w-12 h-12 mx-auto text-slate-200 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
        <p class="text-sm font-semibold text-slate-500">Belum ada notifikasi</p>
        <p class="text-xs text-slate-400 mt-1">Klik "Buat Notifikasi" untuk menambahkan info fitur atau pembaruan.</p>
      </div>

      <div v-else class="divide-y divide-slate-100">
        <div
          v-for="n in notifications"
          :key="n.id"
          class="px-5 py-4 hover:bg-slate-50/50 transition-colors"
        >
          <div class="flex items-start justify-between gap-3">
            <div class="flex-1 min-w-0">
              <div class="flex items-center gap-2 flex-wrap">
                <h3 class="text-sm font-bold text-slate-800">{{ n.title }}</h3>
                <span
                  :class="n.is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-400'"
                  class="text-[10px] font-bold px-2 py-0.5 rounded-full"
                >{{ n.is_active ? 'Aktif' : 'Nonaktif' }}</span>
              </div>
              <p class="text-xs text-slate-500 mt-1 leading-relaxed line-clamp-2">{{ n.body }}</p>
              <p class="text-[10px] text-slate-400 mt-1.5">{{ formatDate(n.created_at) }}</p>
            </div>
            <div class="flex items-center gap-1 shrink-0">
              <button
                @click="toggleActive(n)"
                :class="n.is_active ? 'text-emerald-600 hover:bg-emerald-50' : 'text-slate-400 hover:bg-slate-100'"
                class="p-2 rounded-lg transition-colors cursor-pointer"
                :title="n.is_active ? 'Nonaktifkan' : 'Aktifkan'"
              >
                <svg v-if="n.is_active" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
              </button>
              <button
                @click="openEdit(n)"
                class="p-2 text-slate-400 hover:text-brand-brown hover:bg-brand-cream rounded-lg transition-colors cursor-pointer"
                title="Edit"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
              </button>
              <button
                @click="confirmDelete(n)"
                class="p-2 text-slate-400 hover:text-red-500 hover:bg-red-50 rounded-lg transition-colors cursor-pointer"
                title="Hapus"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-6a1 1 0 00-1 1v3M4 7h16"/></svg>
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Create/Edit Modal -->
    <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
      <div @click="showModal = false" class="absolute inset-0 bg-black/40"></div>
      <div class="relative bg-white rounded-2xl shadow-xl w-full max-w-lg max-h-[90vh] overflow-y-auto">
        <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
          <h3 class="text-sm font-bold text-slate-700">{{ editingId ? 'Edit Notifikasi' : 'Buat Notifikasi Baru' }}</h3>
          <button @click="showModal = false" class="p-1 text-slate-400 hover:text-slate-600 cursor-pointer">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
          </button>
        </div>
        <div class="p-5 space-y-4">
          <div>
            <label class="text-xs font-bold text-slate-600 block mb-1.5">Judul Notifikasi <span class="text-red-500">*</span></label>
            <input
              v-model="form.title"
              type="text"
              placeholder="contoh: Fitur Baru - Manajemen Stok"
              class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-brand-brown"
            />
          </div>
          <div>
            <label class="text-xs font-bold text-slate-600 block mb-1.5">Keterangan / Isi <span class="text-red-500">*</span></label>
            <textarea
              v-model="form.body"
              rows="4"
              placeholder="Jelaskan fitur atau pembaruan yang ingin disampaikan..."
              class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-brand-brown resize-none"
            ></textarea>
          </div>
          <label class="flex items-center gap-2 cursor-pointer">
            <input type="checkbox" v-model="form.is_active" class="rounded border-slate-300 text-brand-gradation focus:ring-brand-brown cursor-pointer" />
            <span class="text-xs text-slate-600 font-medium">Aktif (tampilkan ke semua pengguna)</span>
          </label>
        </div>
        <div class="px-5 py-4 border-t border-slate-100 flex items-center justify-end gap-2">
          <button @click="showModal = false" class="px-4 py-2 rounded-xl border border-slate-200 text-slate-600 text-xs font-bold hover:bg-slate-50 cursor-pointer transition-colors">Batal</button>
          <button
            @click="saveNotification"
            :disabled="saving || !form.title.trim() || !form.body.trim()"
            class="px-4 py-2 rounded-xl bg-brand-gradation text-white text-xs font-bold hover:shadow-md disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer transition-all"
          >{{ saving ? 'Menyimpan...' : 'Simpan' }}</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';
import PageHeader from '../components/PageHeader.vue';
import { useAuth } from '../composables/useAuth.js';
import { showAlert, showConfirm } from '../composables/useAlert.js';

const { isAdmin } = useAuth();

const notifications = ref([]);
const loading = ref(true);
const showModal = ref(false);
const editingId = ref(null);
const saving = ref(false);
const form = ref({ title: '', body: '', is_active: true });

const activeCount = computed(() => notifications.value.filter(n => n.is_active).length);

const loadNotifications = async () => {
  loading.value = true;
  try {
    const res = await axios.get('/api/notifications/admin/list');
    notifications.value = res.data;
  } catch (e) {
    showAlert('error', 'Gagal', 'Tidak dapat memuat notifikasi.');
  } finally {
    loading.value = false;
  }
};

const openCreate = () => {
  editingId.value = null;
  form.value = { title: '', body: '', is_active: true };
  showModal.value = true;
};

const openEdit = (n) => {
  editingId.value = n.id;
  form.value = { title: n.title, body: n.body, is_active: n.is_active };
  showModal.value = true;
};

const saveNotification = async () => {
  if (!form.value.title.trim() || !form.value.body.trim()) return;
  saving.value = true;
  try {
    if (editingId.value) {
      await axios.put(`/api/notifications/admin/${editingId.value}`, form.value);
      showAlert('success', 'Berhasil', 'Notifikasi diperbarui.');
    } else {
      await axios.post('/api/notifications/admin', form.value);
      showAlert('success', 'Berhasil', 'Notifikasi dibuat dan dikirim ke semua pengguna.');
    }
    showModal.value = false;
    await loadNotifications();
  } catch (e) {
    showAlert('error', 'Gagal', e.response?.data?.message || 'Tidak dapat menyimpan notifikasi.');
  } finally {
    saving.value = false;
  }
};

const toggleActive = async (n) => {
  try {
    await axios.put(`/api/notifications/admin/${n.id}`, {
      title: n.title,
      body: n.body,
      is_active: !n.is_active,
    });
    n.is_active = !n.is_active;
  } catch (e) {
    showAlert('error', 'Gagal', 'Tidak dapat mengubah status.');
  }
};

const confirmDelete = async (n) => {
  const ok = await showConfirm('Hapus Notifikasi', `Yakin hapus "${n.title}"? Tindakan ini tidak dapat dibatalkan.`);
  if (!ok) return;
  try {
    await axios.delete(`/api/notifications/admin/${n.id}`);
    notifications.value = notifications.value.filter(x => x.id !== n.id);
    showAlert('success', 'Berhasil', 'Notifikasi dihapus.');
  } catch (e) {
    showAlert('error', 'Gagal', 'Tidak dapat menghapus notifikasi.');
  }
};

const formatDate = (dateStr) => {
  return new Date(dateStr).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric', hour: '2-digit', minute: '2-digit' });
};

onMounted(() => {
  loadNotifications();
});
</script>
