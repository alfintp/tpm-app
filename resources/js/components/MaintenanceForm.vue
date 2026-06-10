<template>
  <div class="fixed inset-0 z-50 flex items-center justify-center">
    <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm" @click="$emit('close')"></div>

    <div class="bg-white rounded-3xl shadow-2xl w-full max-w-4xl max-h-[92vh] overflow-hidden flex flex-col relative z-10">
      <!-- Header -->
      <div class="px-8 py-5 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
        <h3 class="text-xl font-semibold text-slate-800">Submit Maintenance Report</h3>
        <button @click="$emit('close')" class="text-slate-400 hover:text-slate-600 p-2 rounded-full hover:bg-slate-100 cursor-pointer">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
      </div>

      <div class="p-8 overflow-y-auto flex-1 flex flex-col md:flex-row gap-8">
        <!-- Left: Form -->
        <form class="space-y-6 flex-1" @submit.prevent>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <!-- Machine -->
            <div class="space-y-1.5">
              <label class="text-sm font-medium text-slate-700">Mesin <span class="text-red-500">*</span></label>
              <select v-model="form.machine_id" @change="onMachineChange" required class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:outline-none focus:ring-2 focus:ring-indigo-500 bg-white text-slate-700 cursor-pointer">
                <option value="" disabled>Pilih Mesin</option>
                <option v-for="m in machines" :key="m.id" :value="m.id">{{ m.name }}</option>
              </select>
            </div>

            <!-- Date -->
            <div class="space-y-1.5">
              <label class="text-sm font-medium text-slate-700">Tanggal Maintenance <span class="text-red-500">*</span></label>
              <input type="datetime-local" v-model="form.maintenance_date" required class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-700 cursor-pointer">
            </div>
          </div>

          <!-- Notes -->
          <div class="space-y-1.5">
            <label class="text-sm font-medium text-slate-700">Catatan Umum</label>
            <textarea v-model="form.notes" rows="3" class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-700 resize-none" placeholder="Deskripsi keseluruhan pekerjaan maintenance..."></textarea>
          </div>

          <!-- Actions / Components -->
          <div class="pt-4 border-t border-slate-100">
            <div class="flex justify-between items-center mb-4">
              <h4 class="text-md font-semibold text-slate-800">Tindakan Komponen</h4>
              <button type="button" @click="addAction" :disabled="!form.machine_id" class="text-sm text-indigo-600 hover:text-indigo-800 font-medium flex items-center bg-indigo-50 hover:bg-indigo-100 px-3 py-1.5 rounded-lg transition-colors cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed gap-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                Tambah
              </button>
            </div>

            <p v-if="!form.machine_id" class="text-sm text-slate-400 text-center py-4 bg-slate-50 rounded-xl border border-dashed border-slate-200">
              Pilih mesin terlebih dahulu untuk menambah tindakan.
            </p>

            <div v-else-if="form.actions.length === 0" class="text-sm text-slate-400 text-center py-4 bg-slate-50 rounded-xl border border-dashed border-slate-200">
              Klik "Tambah" untuk menambah tindakan pada komponen.
            </div>

            <div v-else class="space-y-4">
              <div v-for="(action, index) in form.actions" :key="index" class="bg-slate-50 p-4 rounded-xl border border-slate-100 relative group space-y-3">
                <button type="button" @click="removeAction(index)" class="absolute top-3 right-3 text-slate-300 hover:text-red-500 cursor-pointer transition-colors opacity-0 group-hover:opacity-100">
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>

                <div class="grid grid-cols-2 gap-3 pr-8">
                  <!-- Component Dropdown -->
                  <div class="space-y-1 col-span-2 md:col-span-1">
                    <label class="text-xs font-medium text-slate-600">Komponen *</label>
                    <select v-model="action.machine_component_id" @change="onComponentSelect(action)" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 bg-white text-slate-700 cursor-pointer">
                      <option value="" disabled>Pilih Komponen</option>
                      <option v-for="comp in machineComponents" :key="comp.id" :value="comp.id">{{ comp.name }}</option>
                    </select>
                  </div>

                  <!-- Action Type -->
                  <div class="space-y-1">
                    <label class="text-xs font-medium text-slate-600">Tindakan *</label>
                    <select v-model="action.action_type" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 bg-white text-slate-700 cursor-pointer">
                      <option value="inspect">Inspect</option>
                      <option value="repair">Repair</option>
                      <option value="replace">Replace</option>
                      <option value="clean">Clean</option>
                      <option value="lubricate">Lubricate</option>
                    </select>
                  </div>
                </div>

                <!-- Condition -->
                <div class="grid grid-cols-2 gap-3">
                  <div class="space-y-1">
                    <label class="text-xs font-medium text-slate-600">Kondisi Sebelum (%)</label>
                    <input type="number" v-model="action.condition_before_pct" readonly class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm bg-slate-100 text-slate-500 cursor-not-allowed" placeholder="Otomatis">
                  </div>
                  <div class="space-y-1">
                    <label class="text-xs font-medium text-slate-600">Kondisi Sesudah (%) *</label>
                    <input type="number" v-model="action.condition_after_pct" min="0" max="100" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-700">
                  </div>
                </div>

                <!-- Description -->
                <div class="space-y-1">
                  <label class="text-xs font-medium text-slate-600">Deskripsi</label>
                  <input type="text" v-model="action.description" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-700" placeholder="Deskripsi singkat tindakan...">
                </div>
              </div>
            </div>
          </div>
        </form>

        <!-- Right: History Reference -->
        <div class="w-full md:w-72 flex-shrink-0 bg-slate-50 p-5 rounded-2xl border border-slate-100 self-start sticky top-0">
          <h4 class="text-sm font-bold text-slate-700 mb-4 flex items-center gap-2">
            <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            Maintenance Terakhir
          </h4>

          <div v-if="loadingHistory" class="animate-pulse space-y-2">
            <div class="h-3 bg-slate-200 rounded w-full"></div>
            <div class="h-3 bg-slate-200 rounded w-2/3"></div>
          </div>
          <div v-else-if="latestRecord">
            <p class="text-xs text-slate-400 mb-1">{{ formatDateTime(latestRecord.maintenance_date) }}</p>
            <p class="text-sm text-slate-700 font-medium mb-3 leading-relaxed">{{ latestRecord.notes || 'Tidak ada catatan' }}</p>
            <div v-if="latestRecord.actions?.length > 0">
              <p class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Komponen yg dikerjakan:</p>
              <ul class="space-y-2">
                <li v-for="act in latestRecord.actions" :key="act.id" class="text-xs text-slate-600 bg-white border border-slate-100 p-2 rounded-lg">
                  <p class="font-bold text-slate-700">{{ act.component?.name ?? '-' }}</p>
                  <p class="mt-0.5">{{ act.condition_before_pct ?? '-' }}% → <span class="text-indigo-600 font-medium">{{ act.condition_after_pct ?? '-' }}%</span></p>
                </li>
              </ul>
            </div>
          </div>
          <div v-else class="text-xs text-slate-400 italic">Pilih mesin untuk melihat riwayat.</div>
        </div>
      </div>

      <!-- Footer -->
      <div class="px-8 py-5 border-t border-slate-100 bg-slate-50 flex justify-end space-x-3">
        <button @click="$emit('close')" class="px-6 py-2.5 rounded-xl font-medium text-slate-600 hover:bg-slate-200 transition-colors cursor-pointer">Batal</button>
        <button @click="submitReport" :disabled="submitting || !form.machine_id" class="px-6 py-2.5 rounded-xl font-medium text-white bg-indigo-600 hover:bg-indigo-700 transition-colors shadow-md flex items-center gap-2 cursor-pointer disabled:opacity-60">
          <svg v-if="submitting" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
          Submit Report
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';

const props = defineProps(['initialMachineId']);
const emit = defineEmits(['close']);

const machines = ref([]);
const machineComponents = ref([]);
const latestRecord = ref(null);
const loadingHistory = ref(false);
const submitting = ref(false);

const now = new Date();
const localNow = new Date(now.getTime() - now.getTimezoneOffset() * 60000).toISOString().slice(0, 16);

const form = ref({
  machine_id: props.initialMachineId || '',
  maintenance_date: localNow,
  status: 'completed',
  notes: '',
  actions: [],
});

onMounted(async () => {
  const res = await axios.get('/api/machines');
  machines.value = res.data;
  if (form.value.machine_id) await fetchMachineData(form.value.machine_id);
});

const onMachineChange = () => {
  form.value.actions = [];
  machineComponents.value = [];
  if (form.value.machine_id) fetchMachineData(form.value.machine_id);
};

const fetchMachineData = async (id) => {
  loadingHistory.value = true;
  try {
    const [machRes, compRes] = await Promise.all([
      axios.get(`/api/machines/${id}`),
      axios.get(`/api/machines/${id}/components`),
    ]);
    machineComponents.value = compRes.data;
    const records = machRes.data.records ?? [];
    const sorted = [...records].sort((a, b) => new Date(b.maintenance_date) - new Date(a.maintenance_date));
    latestRecord.value = sorted[0] ?? null;
  } catch (e) {
    console.error(e);
  } finally {
    loadingHistory.value = false;
  }
};

const addAction = () => {
  form.value.actions.push({
    machine_component_id: '',
    action_type: 'inspect',
    condition_before_pct: null,
    condition_after_pct: null,
    description: '',
  });
};

const removeAction = (index) => {
  form.value.actions.splice(index, 1);
};

const onComponentSelect = (action) => {
  const comp = machineComponents.value.find(c => c.id === action.machine_component_id);
  if (comp) action.condition_before_pct = comp.last_condition_pct;
};

const formatDateTime = (d) => new Date(d).toLocaleString('id-ID', { day: 'numeric', month: 'long', year: 'numeric', hour: '2-digit', minute: '2-digit' });

const submitReport = async () => {
  const validActions = form.value.actions.filter(a => a.machine_component_id && a.condition_after_pct !== null && a.condition_after_pct !== '');
  if (validActions.length === 0 && form.value.actions.length > 0) {
    alert('Lengkapi pilihan komponen dan kondisi sesudah pada setiap tindakan.');
    return;
  }
  submitting.value = true;
  try {
    const uRes = await axios.get('/api/dummy-user').catch(() => null);
    const payload = {
      ...form.value,
      technician_id: uRes?.data?.id,
      actions: validActions,
    };
    await axios.post('/api/records', payload);
    emit('close', true);
  } catch (e) {
    alert('Gagal submit: ' + (e.response?.data?.error || e.message));
  } finally {
    submitting.value = false;
  }
};
</script>
