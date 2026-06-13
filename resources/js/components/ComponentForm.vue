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
            <label class="text-sm font-medium text-slate-700">Kategori *</label>
            <input type="text" v-model="form.category" required class="w-full rounded-xl border border-slate-300 px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-700" placeholder="e.g. Mechanical, Electrical">
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
            <label class="text-sm font-medium text-slate-700">Qty *</label>
            <input type="number" v-model="form.qty" min="1" required class="w-full rounded-xl border border-slate-300 px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-700">
          </div>
          <div class="space-y-1.5">
            <label class="text-sm font-medium text-slate-700">Satuan *</label>
            <input type="text" v-model="form.unit" required class="w-full rounded-xl border border-slate-300 px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-700" placeholder="e.g. pcs, set, meter">
          </div>
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

const props = defineProps({
  machineId: { type: String, required: true },
  component: { type: Object, default: null },
});
const emit = defineEmits(['close', 'saved']);

const isEdit = computed(() => !!props.component);
const saving = ref(false);

const form = ref({
  category: props.component?.category ?? 'Mechanical',
  name: props.component?.name ?? '',
  specification: props.component?.specification ?? '',
  qty: props.component?.qty ?? 1,
  unit: props.component?.unit ?? 'pcs',
  last_condition_pct: props.component?.last_condition_pct ?? 100,
  last_replaced_at: props.component?.last_replaced_at ? props.component.last_replaced_at.split('T')[0] : '',
});

const submit = async () => {
  if (!form.value.name || !form.value.qty) return;
  saving.value = true;
  try {
    if (isEdit.value) {
      await axios.put(`/api/components/${props.component.id}`, form.value);
    } else {
      await axios.post(`/api/machines/${props.machineId}/components`, form.value);
    }
    emit('saved');
  } catch (e) {
    console.error(e);
    alert('Gagal menyimpan: ' + (e.response?.data?.message || e.message));
  } finally {
    saving.value = false;
  }
};
</script>
