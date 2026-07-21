<template>
  <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm" @click="emit('close')"></div>
    <div class="bg-white rounded-2xl w-full max-w-sm relative z-10 shadow-2xl p-6 text-center">
      <div class="w-14 h-14 mx-auto bg-red-100 text-red-600 rounded-full flex items-center justify-center mb-4">
        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v.01M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
      </div>
      <h3 class="text-base font-bold text-slate-800 mb-1">Akses Ditolak</h3>
      <p class="text-sm text-slate-500 mb-6">Akun tidak memiliki akses membuat laporan.</p>
      <button
        @click="emit('back')"
        class="w-full px-4 py-2.5 bg-brand-brown hover:bg-brand-gradation text-white text-sm font-bold rounded-xl transition-colors cursor-pointer"
      >Kembali ke Beranda</button>
    </div>
  </div>
</template>

<script setup>
defineProps({
  show: { type: Boolean, default: false },
});

const emit = defineEmits(['close', 'back']);
</script>
