<template>
  <div class="min-h-screen flex items-center justify-center bg-slate-50 p-4">
    <div class="max-w-md w-full bg-white rounded-2xl shadow-lg border border-slate-100 p-8 text-center">
      <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-red-50 flex items-center justify-center">
        <svg class="w-8 h-8 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
        </svg>
      </div>
      <h1 class="text-2xl font-black text-slate-800 mb-2">404 — Halaman Tidak Ditemukan</h1>
      <p class="text-sm text-slate-500 mb-6">Halaman yang Anda cari tidak tersedia atau telah dipindahkan.</p>
      <button
        @click="goHome"
        class="px-5 py-2.5 bg-brand-brown text-white text-sm font-bold rounded-xl hover:bg-brand-brown/90 cursor-pointer transition-colors"
      >
        Kembali ke Dashboard
      </button>
    </div>
  </div>
</template>

<script setup>
import { router } from '@inertiajs/vue3';

const goHome = () => router.visit('/');
</script>
