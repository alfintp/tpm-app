<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between flex-wrap gap-4">
      <div>
        <h2 class="text-2xl font-bold text-slate-800">Approval Report</h2>
        <p class="text-slate-500 text-sm mt-0.5">
          <template v-if="isManagerOrAdmin">Review dan approve laporan maintenance dari teknisi</template>
          <template v-else>Status laporan maintenance yang sudah kamu kirimkan</template>
        </p>
      </div>
    </div>

    <!-- Stats (Manager/Admin only) -->
    <div v-if="isManagerOrAdmin" class="grid grid-cols-2 md:grid-cols-4 gap-4">
      <div class="bg-amber-50 border border-amber-100 rounded-2xl p-4">
        <p class="text-2xl font-bold text-amber-600">{{ pendingCount }}</p>
        <p class="text-xs text-slate-500 mt-1 font-medium">Menunggu Approval</p>
      </div>
      <div class="bg-green-50 border border-green-100 rounded-2xl p-4">
        <p class="text-2xl font-bold text-green-600">{{ approvedCount }}</p>
        <p class="text-xs text-slate-500 mt-1 font-medium">Disetujui</p>
      </div>
      <div class="bg-red-50 border border-red-100 rounded-2xl p-4">
        <p class="text-2xl font-bold text-red-600">{{ rejectedCount }}</p>
        <p class="text-xs text-slate-500 mt-1 font-medium">Ditolak</p>
      </div>
      <div class="bg-slate-50 border border-slate-100 rounded-2xl p-4">
        <p class="text-2xl font-bold text-slate-600">{{ items.length }}</p>
        <p class="text-xs text-slate-500 mt-1 font-medium">Total Report</p>
      </div>
    </div>

    <!-- Filter Tabs -->
    <div class="flex items-center gap-2 flex-wrap">
      <button
        v-for="tab in filterTabs" :key="tab.value"
        @click="activeFilter = tab.value"
        :class="activeFilter === tab.value ? 'bg-gradient-to-tr from-brand-brown to-brand-gradation text-white shadow' : 'bg-white text-slate-500 border border-slate-200 hover:border-indigo-300'"
        class="px-4 py-1.5 rounded-xl text-sm font-semibold transition-all cursor-pointer"
      >
        {{ tab.label }}
        <span class="ml-1.5 text-xs opacity-80">({{ tab.count }})</span>
      </button>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="text-center py-16 text-slate-400">
      <div class="animate-spin w-8 h-8 border-4 border-indigo-200 border-t-indigo-600 rounded-full mx-auto mb-3"></div>
      Memuat data...
    </div>

    <!-- Empty State -->
    <div v-else-if="filteredItems.length === 0" class="py-16 text-center text-slate-400">
      <svg class="w-14 h-14 mx-auto mb-3 text-slate-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
      <p class="font-medium text-slate-500">Tidak ada laporan ditemukan</p>
    </div>

    <!-- Table -->
    <div v-else class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full min-w-[800px]">
          <thead class="bg-slate-50 border-b border-slate-100">
            <tr>
              <th class="text-left text-xs font-semibold text-slate-500 uppercase tracking-wider px-5 py-3">Mesin & Waktu</th>
              <th class="text-left text-xs font-semibold text-slate-500 uppercase tracking-wider px-5 py-3">Teknisi</th>
              <th class="text-left text-xs font-semibold text-slate-500 uppercase tracking-wider px-5 py-3">Detail Tindakan</th>
              <th class="text-left text-xs font-semibold text-slate-500 uppercase tracking-wider px-5 py-3">Status</th>
              <th v-if="isManagerOrAdmin" class="text-right text-xs font-semibold text-slate-500 uppercase tracking-wider px-5 py-3">Aksi</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-50">
            <tr v-for="item in filteredItems" :key="item.record_id" class="hover:bg-slate-50 transition-colors">
              <!-- Mesin & Waktu -->
              <td class="px-5 py-4 align-top">
                <p class="font-semibold text-slate-800 text-sm">{{ item.machine_name }}</p>
                <p class="text-xs text-slate-400 mt-0.5">{{ formatDateTime(item.maintenance_date) }}</p>
                <p v-if="item.notes" class="text-xs text-slate-500 mt-1 italic line-clamp-1">{{ item.notes }}</p>
              </td>

              <!-- Teknisi -->
              <td class="px-5 py-4 align-top">
                <div class="flex items-center gap-2">
                  <div class="w-7 h-7 rounded-full bg-gradient-to-tr from-brand-brown to-brand-gradation flex items-center justify-center text-white text-[10px] font-bold flex-shrink-0">
                    {{ initials(item.technician_name) }}
                  </div>
                  <span class="text-sm text-slate-700">{{ item.technician_name ?? '-' }}</span>
                </div>
              </td>

              <!-- Detail Tindakan -->
              <td class="px-5 py-4 align-top">
                <div class="space-y-1.5">
                  <div class="flex items-center gap-1.5 flex-wrap">
                    <span class="text-xs font-semibold px-2 py-0.5 bg-slate-100 text-slate-700 rounded-md">
                      {{ item.actions.length }} Tindakan
                    </span>
                    <span v-if="getReplaceCount(item.actions) > 0" class="text-xs font-semibold px-2 py-0.5 bg-red-50 text-red-700 border border-red-100 rounded-md">
                      {{ getReplaceCount(item.actions) }} Ganti
                    </span>
                    <span v-if="getInspectCount(item.actions) > 0" class="text-xs font-semibold px-2 py-0.5 bg-blue-50 text-blue-700 border border-blue-100 rounded-md">
                      {{ getInspectCount(item.actions) }} Inspeksi
                    </span>
                  </div>
                  <button 
                    @click="openDetails(item)"
                    class="text-indigo-600 hover:text-indigo-800 text-xs font-bold flex items-center gap-1 transition-colors cursor-pointer pt-0.5"
                  >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                    Lihat Laporan ({{ item.actions.length }})
                  </button>
                </div>
              </td>

              <!-- Status -->
              <td class="px-5 py-4 align-top">
                <span :class="statusBadgeClass(item.approval_status)" class="inline-flex items-center gap-1.5 text-xs font-semibold px-3 py-1 rounded-full">
                  <span class="w-1.5 h-1.5 rounded-full" :class="statusDotClass(item.approval_status)"></span>
                  {{ statusLabel(item.approval_status) }}
                </span>
                <p v-if="item.approved_by" class="text-[10px] text-slate-400 mt-1">oleh {{ item.approved_by }}</p>
                <p v-if="item.decided_at" class="text-[10px] text-slate-400">{{ formatDateTime(item.decided_at) }}</p>
                <p v-if="item.approval_notes" class="text-[10px] text-amber-600 mt-1 italic line-clamp-2">{{ item.approval_notes }}</p>
              </td>

              <!-- Aksi (Manager/Admin only) -->
              <td v-if="isManagerOrAdmin" class="px-5 py-4 align-top text-right">
                <template v-if="item.approval_status === 'pending'">
                  <div class="flex items-center justify-end gap-2">
                    <button
                      @click="openDecide(item, 'approved')"
                      class="px-3 py-1.5 rounded-lg bg-green-500 hover:bg-green-600 text-white text-xs font-semibold transition-colors cursor-pointer"
                    >Setujui</button>
                    <button
                      @click="openDecide(item, 'rejected')"
                      class="px-3 py-1.5 rounded-lg bg-red-500 hover:bg-red-600 text-white text-xs font-semibold transition-colors cursor-pointer"
                    >Tolak</button>
                  </div>
                </template>
                <template v-else>
                  <button
                    @click="openDecide(item, item.approval_status)"
                    class="px-3 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-600 text-xs font-semibold transition-colors cursor-pointer"
                  >Ubah</button>
                </template>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- Decide Modal -->
  <div v-if="decideModal.show" class="fixed inset-0 z-[60] flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm" @click="decideModal.show = false"></div>
    <div class="bg-white rounded-3xl shadow-2xl w-full max-w-sm relative z-10 p-6 space-y-4">
      <h3 class="text-lg font-semibold text-slate-800">
        {{ decideModal.decision === 'approved' ? '✅ Setujui Report' : '❌ Tolak Report' }}
      </h3>
      <p class="text-sm text-slate-500">Mesin: <span class="font-semibold text-slate-700">{{ decideModal.item?.machine_name }}</span></p>
      <div class="space-y-1.5">
        <label class="text-sm font-medium text-slate-700">Catatan (opsional)</label>
        <textarea
          v-model="decideModal.notes"
          rows="3"
          class="w-full rounded-xl border border-slate-300 px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-700 resize-none text-sm"
          placeholder="Tambahkan catatan..."
        ></textarea>
      </div>
      <div class="flex justify-end gap-3 pt-2">
        <button @click="decideModal.show = false" class="px-5 py-2.5 rounded-xl font-medium text-slate-600 hover:bg-slate-100 transition-colors cursor-pointer text-sm">Batal</button>
        <button
          @click="submitDecision"
          :disabled="deciding"
          :class="decideModal.decision === 'approved' ? 'bg-green-600 hover:bg-green-700' : 'bg-red-600 hover:bg-red-700'"
          class="px-5 py-2.5 rounded-xl font-medium text-white transition-colors shadow-sm cursor-pointer disabled:opacity-70 text-sm flex items-center gap-2"
        >
          <svg v-if="deciding" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
          {{ decideModal.decision === 'approved' ? 'Setujui' : 'Tolak' }}
        </button>
      </div>
    </div>
  </div>

  <!-- Detail Actions Modal -->
  <div v-if="detailModal.show" class="fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm" @click="detailModal.show = false"></div>
    <div class="bg-white rounded-3xl shadow-2xl w-full max-w-4xl max-h-[85vh] relative z-10 flex flex-col overflow-hidden">
      <!-- Modal Header -->
      <div class="p-6 border-b border-slate-100 flex items-start justify-between">
        <div>
          <h3 class="text-xl font-bold text-slate-800">Detail Laporan</h3>
          <p class="text-slate-500 text-xs mt-1">
            Mesin: <span class="font-bold text-slate-700">{{ detailModal.item?.machine_name }}</span> &bull; 
            Teknisi: <span class="font-bold text-slate-700">{{ detailModal.item?.technician_name }}</span> &bull; 
            Waktu: <span class="font-bold text-slate-700">{{ formatDateTime(detailModal.item?.maintenance_date) }}</span>
          </p>
          <p v-if="detailModal.item?.notes" class="text-xs text-slate-500 mt-2 bg-slate-50 px-3 py-1.5 rounded-xl border border-slate-100 italic">
            Catatan Teknisi: "{{ detailModal.item.notes }}"
          </p>
        </div>
        <button @click="detailModal.show = false" class="p-1.5 rounded-full hover:bg-slate-100 text-slate-400 hover:text-slate-600 transition-colors cursor-pointer">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
      </div>

      <!-- Filters & Search -->
      <div class="p-6 pb-4 border-b border-slate-50 bg-slate-50/50 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <!-- Inside-modal tabs -->
        <div class="flex items-center gap-2">
          <button
            v-for="mTab in [
              { value: 'all', label: 'Semua', count: detailModal.item?.actions.length },
              { value: 'replace', label: 'Penggantian', count: getReplaceCount(detailModal.item?.actions) },
              { value: 'inspect', label: 'Inspeksi/Lainnya', count: getInspectCount(detailModal.item?.actions) }
            ]"
            :key="mTab.value"
            @click="detailModal.activeTab = mTab.value"
            :class="detailModal.activeTab === mTab.value ? 'bg-indigo-600 text-white shadow-sm' : 'bg-white text-slate-500 border border-slate-200 hover:border-indigo-300'"
            class="px-3 py-1.5 rounded-lg text-xs font-semibold transition-all cursor-pointer"
          >
            {{ mTab.label }} ({{ mTab.count || 0 }})
          </button>
        </div>

        <!-- Inside-modal search -->
        <div class="relative w-full sm:w-64">
          <input
            v-model="detailModal.search"
            type="text"
            placeholder="Cari nama komponen..."
            class="w-full rounded-xl border border-slate-200 bg-white pl-9 pr-4 py-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-700 shadow-sm"
          />
          <svg class="w-4 h-4 text-slate-400 absolute left-3 top-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
        </div>
      </div>

      <!-- Scrollable Table -->
      <div class="flex-1 overflow-y-auto min-h-[300px]">
        <table class="w-full text-left">
          <thead class="bg-slate-50 border-b border-slate-100 sticky top-0 z-10">
            <tr>
              <th class="text-xs font-semibold text-slate-500 uppercase tracking-wider px-6 py-3">Nama Komponen</th>
              <th class="text-xs font-semibold text-slate-500 uppercase tracking-wider px-6 py-3">Tindakan</th>
              <th class="text-xs font-semibold text-slate-500 uppercase tracking-wider px-6 py-3">Kondisi</th>
              <th class="text-xs font-semibold text-slate-500 uppercase tracking-wider px-6 py-3">Keterangan / Catatan</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-for="(action, idx) in modalFilteredActions" :key="idx" class="hover:bg-slate-50/50 transition-colors">
              <td class="px-6 py-3.5">
                <span class="text-sm font-semibold text-slate-800">{{ action.component_name }}</span>
              </td>
              <td class="px-6 py-3.5">
                <span :class="actionBadgeClass(action.action_type)" class="text-[10px] font-semibold px-2.5 py-0.5 rounded-full capitalize">
                  {{ action.action_type }}
                </span>
              </td>
              <td class="px-6 py-3.5">
                <div class="flex items-center gap-1.5 text-xs text-slate-600">
                  <span>{{ action.condition_before }}%</span>
                  <svg class="w-3.5 h-3.5 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                  <span class="text-indigo-600 font-bold bg-indigo-50 px-1.5 py-0.5 rounded">{{ action.condition_after }}%</span>
                </div>
              </td>
              <td class="px-6 py-3.5">
                <span class="text-xs text-slate-600 italic">{{ action.description || '-' }}</span>
              </td>
            </tr>
            <tr v-if="modalFilteredActions.length === 0">
              <td colspan="4" class="text-center py-12 text-slate-400 text-xs">
                Tidak ada tindakan komponen yang sesuai dengan filter/pencarian.
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Footer / Quick Action -->
      <div class="p-6 border-t border-slate-100 bg-slate-50 flex items-center justify-between gap-4">
        <span class="text-xs text-slate-500 font-medium">Menampilkan {{ modalFilteredActions.length }} dari {{ detailModal.item?.actions.length }} tindakan</span>
        
        <div class="flex items-center gap-2">
          <button @click="detailModal.show = false" class="px-4 py-2 bg-white hover:bg-slate-100 border border-slate-200 rounded-xl text-slate-600 text-xs font-semibold transition-colors cursor-pointer shadow-sm">
            Tutup
          </button>
          
          <!-- Manager/Admin quick approval inside detail modal -->
          <template v-if="isManagerOrAdmin && detailModal.item?.approval_status === 'pending'">
            <button
              @click="openDecideFromDetail('approved')"
              class="px-4 py-2 rounded-xl bg-green-500 hover:bg-green-600 text-white text-xs font-bold transition-colors cursor-pointer shadow-sm"
            >Setujui Report</button>
            <button
              @click="openDecideFromDetail('rejected')"
              class="px-4 py-2 rounded-xl bg-red-500 hover:bg-red-600 text-white text-xs font-bold transition-colors cursor-pointer shadow-sm"
            >Tolak Report</button>
          </template>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';

const loading = ref(true);
const deciding = ref(false);
const items = ref([]);
const activeFilter = ref('all');
const currentUser = ref({ id: null, full_name: '', role: 'technician' });

const isManagerOrAdmin = computed(() => ['manager', 'admin'].includes(currentUser.value?.role));

const decideModal = ref({ show: false, item: null, decision: 'approved', notes: '' });
const detailModal = ref({ show: false, item: null, search: '', activeTab: 'all' });

const getReplaceCount = (actions) => {
  if (!actions) return 0;
  return actions.filter(a => a.action_type === 'replace').length;
};

const getInspectCount = (actions) => {
  if (!actions) return 0;
  return actions.filter(a => a.action_type !== 'replace').length;
};

const openDetails = (item) => {
  detailModal.value = { show: true, item, search: '', activeTab: 'all' };
};

const openDecideFromDetail = (decision) => {
  if (!detailModal.value.item) return;
  openDecide(detailModal.value.item, decision);
};

const modalFilteredActions = computed(() => {
  if (!detailModal.value.item) return [];
  let list = detailModal.value.item.actions || [];

  if (detailModal.value.activeTab === 'replace') {
    list = list.filter(a => a.action_type === 'replace');
  } else if (detailModal.value.activeTab === 'inspect') {
    list = list.filter(a => a.action_type !== 'replace');
  }

  if (detailModal.value.search.trim()) {
    const q = detailModal.value.search.toLowerCase();
    list = list.filter(a => 
      (a.component_name && a.component_name.toLowerCase().includes(q)) ||
      (a.description && a.description.toLowerCase().includes(q))
    );
  }

  return list;
});

const loadUser = async () => {
  const res = await axios.get('/api/dummy-user').catch(() => null);
  if (res?.data?.id) {
    currentUser.value = res.data;
  }
};

const loadData = async () => {
  loading.value = true;
  try {
    const res = await axios.get('/api/approvals', {
      params: {
      }
    });
    items.value = res.data;
  } catch (e) {
    console.error(e);
  } finally {
    loading.value = false;
  }
};

onMounted(async () => {
  await loadUser();
  await loadData();
});

const switchRole = async (role) => {
  currentUser.value = { ...currentUser.value, role };
  await loadData();
};

const pendingCount  = computed(() => items.value.filter(i => i.approval_status === 'pending').length);
const approvedCount = computed(() => items.value.filter(i => i.approval_status === 'approved').length);
const rejectedCount = computed(() => items.value.filter(i => i.approval_status === 'rejected').length);

const filterTabs = computed(() => [
  { value: 'all',      label: 'Semua',           count: items.value.length },
  { value: 'pending',  label: 'Menunggu',         count: pendingCount.value },
  { value: 'approved', label: 'Disetujui',        count: approvedCount.value },
  { value: 'rejected', label: 'Ditolak',          count: rejectedCount.value },
]);

const filteredItems = computed(() => {
  if (activeFilter.value === 'all') return items.value;
  return items.value.filter(i => i.approval_status === activeFilter.value);
});

const openDecide = (item, decision) => {
  decideModal.value = { show: true, item, decision, notes: item.approval_notes ?? '' };
};

const submitDecision = async () => {
  deciding.value = true;
  try {
    await axios.post(`/api/approvals/${decideModal.value.item.record_id}/decide`, {
      decision: decideModal.value.decision,
      notes: decideModal.value.notes,
    });
    decideModal.value.show = false;
    detailModal.value.show = false;
    await loadData();
  } catch (e) {
    alert('Gagal: ' + (e.response?.data?.message || e.message));
  } finally {
    deciding.value = false;
  }
};

const formatDateTime = (d) => {
  if (!d) return '-';
  return new Date(d).toLocaleString('id-ID', { day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' });
};

const initials = (name) => {
  if (!name) return '?';
  return name.split(' ').slice(0, 2).map(w => w[0]).join('').toUpperCase();
};

const statusLabel = (s) => ({ pending: 'Menunggu', approved: 'Disetujui', rejected: 'Ditolak' }[s] ?? s);

const statusBadgeClass = (s) => ({
  pending:  'bg-amber-100 text-amber-700',
  approved: 'bg-green-100 text-green-700',
  rejected: 'bg-red-100 text-red-700',
}[s] ?? 'bg-slate-100 text-slate-600');

const statusDotClass = (s) => ({
  pending:  'bg-amber-500',
  approved: 'bg-green-500',
  rejected: 'bg-red-500',
}[s] ?? 'bg-slate-400');

const actionBadgeClass = (t) => ({
  replace:   'bg-red-100 text-red-700',
  repair:    'bg-orange-100 text-orange-700',
  inspect:   'bg-blue-100 text-blue-700',
  clean:     'bg-teal-100 text-teal-700',
  lubricate: 'bg-purple-100 text-purple-700',
}[t] ?? 'bg-slate-100 text-slate-600');
</script>
