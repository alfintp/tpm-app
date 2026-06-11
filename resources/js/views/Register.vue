<template>
  <div class="min-h-screen bg-slate-50 flex flex-col justify-center py-12 sm:px-6 lg:px-8">
    <div class="sm:mx-auto sm:w-full sm:max-w-md">
      <!-- Logo Container -->
      <div class="flex justify-center mb-6">
        <img :src="'/images/logo-ladang-lima.png'" alt="Logo Ladang Lima" class="h-16 w-auto">
      </div>
      <h2 class="text-center text-3xl font-extrabold text-brand-brown">
        Daftar Akun Baru
      </h2>
      <p class="mt-2 text-center text-sm text-slate-600">
        Silakan lengkapi formulir di bawah ini
      </p>
    </div>

    <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
      <div class="bg-white py-8 px-4 shadow-xl rounded-2xl sm:px-10 border border-slate-100">
        <!-- Error Alert -->
        <div v-if="error" class="mb-4 bg-red-50 border-l-4 border-red-400 p-4 rounded-md">
          <div class="flex">
            <div class="flex-shrink-0">
              <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
              </svg>
            </div>
            <div class="ml-3">
              <p class="text-sm text-red-700 font-medium">
                {{ error }}
              </p>
            </div>
          </div>
        </div>

        <form class="space-y-5" @submit.prevent="handleRegister">
          <div>
            <label for="fullName" class="block text-sm font-bold text-brand-brown">
              Nama Lengkap
            </label>
            <div class="mt-1">
              <input
                id="fullName"
                v-model="form.fullName"
                type="text"
                required
                placeholder="Ahmad Fauzi"
                class="appearance-none block w-full px-3 py-2 border border-slate-300 rounded-xl shadow-sm placeholder-slate-400 focus:outline-none focus:ring-brand-brown focus:border-brand-brown sm:text-sm font-medium"
              >
            </div>
          </div>

          <div>
            <label for="email" class="block text-sm font-bold text-brand-brown">
              Alamat Email
            </label>
            <div class="mt-1">
              <input
                id="email"
                v-model="form.email"
                type="email"
                required
                placeholder="fauzi@ladanglima.com"
                class="appearance-none block w-full px-3 py-2 border border-slate-300 rounded-xl shadow-sm placeholder-slate-400 focus:outline-none focus:ring-brand-brown focus:border-brand-brown sm:text-sm font-medium"
              >
            </div>
          </div>

          <div>
            <label for="role" class="block text-sm font-bold text-brand-brown">
              Role Jabatan
            </label>
            <div class="mt-1">
              <select
                id="role"
                v-model="form.role"
                class="appearance-none block w-full px-3 py-2 border border-slate-300 rounded-xl shadow-sm bg-white focus:outline-none focus:ring-brand-brown focus:border-brand-brown sm:text-sm font-medium text-slate-700"
              >
                <option value="technician">Teknisi (Technician)</option>
                <option value="manager">Manajer (Manager)</option>
                <option value="admin">Administrator (Admin)</option>
              </select>
            </div>
          </div>

          <div>
            <label for="password" class="block text-sm font-bold text-brand-brown">
              Kata Sandi
            </label>
            <div class="mt-1">
              <input
                id="password"
                v-model="form.password"
                type="password"
                required
                placeholder="Minimal 6 karakter"
                class="appearance-none block w-full px-3 py-2 border border-slate-300 rounded-xl shadow-sm placeholder-slate-400 focus:outline-none focus:ring-brand-brown focus:border-brand-brown sm:text-sm font-medium"
              >
            </div>
          </div>

          <div>
            <label for="confirmPassword" class="block text-sm font-bold text-brand-brown">
              Konfirmasi Kata Sandi
            </label>
            <div class="mt-1">
              <input
                id="confirmPassword"
                v-model="form.confirmPassword"
                type="password"
                required
                placeholder="Ketik ulang kata sandi"
                class="appearance-none block w-full px-3 py-2 border border-slate-300 rounded-xl shadow-sm placeholder-slate-400 focus:outline-none focus:ring-brand-brown focus:border-brand-brown sm:text-sm font-medium"
              >
            </div>
          </div>

          <div class="flex items-center justify-between">
            <div class="text-sm">
              <span class="text-slate-500 font-medium">Sudah punya akun?</span>
              <router-link to="/login" class="ml-1 font-bold text-brand-brown hover:underline">
                Masuk Di Sini
              </router-link>
            </div>
          </div>

          <div>
            <button
              type="submit"
              :disabled="loading"
              class="w-full flex justify-center py-2.5 px-4 border border-transparent rounded-xl shadow-md text-sm font-bold text-brand-cream bg-brand-brown hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-brand-brown transition-all disabled:opacity-50 hover:cursor-pointer"
            >
              <svg v-if="loading" class="animate-spin -ml-1 mr-3 h-5 w-5 text-brand-cream" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.062 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
              </svg>
              Daftarkan Akun
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue';
import { useRouter } from 'vue-router';
import { useAuth } from '../composables/useAuth.js';
import { showAlert } from '../composables/useAlert.js';

const router = useRouter();
const { register } = useAuth();

const form = reactive({
  fullName: '',
  email: '',
  role: 'technician',
  password: '',
  confirmPassword: ''
});

const loading = ref(false);
const error = ref(null);

async function handleRegister() {
  if (form.password !== form.confirmPassword) {
    error.value = 'Konfirmasi kata sandi tidak cocok.';
    return;
  }
  
  if (form.password.length < 6) {
    error.value = 'Kata sandi minimal 6 karakter.';
    return;
  }

  loading.value = true;
  error.value = null;
  
  const result = await register(form.fullName, form.email, form.password, form.role);
  
  loading.value = false;
  if (result.success) {
    await showAlert('success', 'Registrasi Berhasil', 'Akun Anda telah berhasil dibuat. Silakan masuk menggunakan email dan sandi tersebut.');
    router.push({ name: 'login' });
  } else {
    error.value = result.message;
  }
}
</script>
