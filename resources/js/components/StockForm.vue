<template>
  <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40" @click.self="$emit('close')">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-md max-h-[90vh] overflow-y-auto">
      <div class="p-5 border-b border-slate-100">
        <h3 class="text-lg font-bold text-slate-800">{{ stock ? 'Edit Stok' : 'Tambah Stok' }}</h3>
      </div>

      <div class="p-5 space-y-4">
        <div>
          <label class="text-xs font-bold text-slate-600 mb-1 block">Kode Stok <span class="text-red-500">*</span></label>
          <input
            v-model="form.code"
            type="text"
            placeholder="STK-001"
            class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-brown"
          />
        </div>

        <div>
          <label class="text-xs font-bold text-slate-600 mb-1 block">Kategori</label>
          <input
            v-model="form.category"
            type="text"
            placeholder="Mechanical, Electrical, ..."
            class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-brown"
          />
        </div>

        <div>
          <label class="text-xs font-bold text-slate-600 mb-1 block">Nama Item <span class="text-red-500">*</span></label>
          <input
            v-model="form.name"
            type="text"
            placeholder="Nama suku cadang"
            class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-brown"
          />
        </div>

        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="text-xs font-bold text-slate-600 mb-1 block">Quantity <span class="text-red-500">*</span></label>
            <input
              v-model.number="form.quantity"
              type="number"
              min="0"
              class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-brown"
            />
          </div>
          <div>
            <label class="text-xs font-bold text-slate-600 mb-1 block">Satuan</label>
            <input
              v-model="form.unit"
              type="text"
              placeholder="pcs, set, meter"
              class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-brown"
            />
          </div>
        </div>

        <div>
          <label class="text-xs font-bold text-slate-600 mb-1 block">Limit Qty (Batas Bawah)</label>
          <input
            v-model.number="form.limit_qty"
            type="number"
            min="0"
            class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-brown"
          />
          <p class="text-[10px] text-slate-400 mt-1">Peringatan restock muncul saat quantity <= limit qty</p>
        </div>

        <div v-if="stock" class="flex items-center gap-2">
          <label class="flex items-center gap-2 cursor-pointer">
            <input
              v-model="form.is_active"
              type="checkbox"
              class="rounded border-slate-300 text-brand-gradation focus:ring-brand-brown cursor-pointer"
            />
            <span class="text-xs text-slate-700 font-medium">Aktif</span>
          </label>
        </div>
      </div>

      <div class="p-5 border-t border-slate-100 flex justify-end gap-2">
        <button
          @click="$emit('close')"
          class="px-4 py-2 rounded-xl border border-slate-200 text-sm font-bold text-slate-600 hover:bg-slate-50 cursor-pointer transition-colors"
        >
          Batal
        </button>
        <button
          @click="save"
          :disabled="saving"
          class="px-4 py-2 rounded-xl bg-brand-gradation text-white text-sm font-bold hover:shadow-md cursor-pointer transition-all disabled:opacity-50"
        >
          {{ saving ? 'Menyimpan...' : 'Simpan' }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import axios from 'axios';

const props = defineProps({
  stock: { type: Object, default: null },
});

const emit = defineEmits(['close', 'saved']);

const form = ref({
  code: props.stock?.code ?? '',
  category: props.stock?.category ?? '',
  name: props.stock?.name ?? '',
  quantity: props.stock?.quantity ?? 0,
  unit: props.stock?.unit ?? 'pcs',
  limit_qty: props.stock?.limit_qty ?? 1,
  is_active: props.stock?.is_active ?? true,
});

const saving = ref(false);

const save = async () => {
  if (!form.value.code || !form.value.name) {
    alert('Kode dan nama stok wajib diisi.');
    return;
  }
  saving.value = true;
  try {
    if (props.stock) {
      const res = await axios.put(`/api/stocks/${props.stock.id}`, form.value);
      emit('saved', res.data);
    } else {
      const res = await axios.post('/api/stocks', form.value);
      emit('saved', res.data);
    }
  } catch (e) {
    alert(e.response?.data?.message ?? 'Gagal menyimpan stok.');
  } finally {
    saving.value = false;
  }
};
</script>
