<template>
  <div class="min-h-screen bg-gradient-to-br from-slate-100 to-brand-cream/30 flex flex-col items-center justify-center px-4">
    <!-- Logo & Title above card -->
    <div class="flex flex-col items-center mb-6">
      <img :src="'/images/logo-ladang-lima.png'" alt="Logo Ladang Lima" class="h-16 w-auto mb-3 drop-shadow-sm">
      <h1 class="text-2xl font-extrabold text-brand-brown tracking-tight">TPM System</h1>
      <p class="text-sm text-slate-500 mt-1">Total Productive Maintenance · Ladang Lima</p>
    </div>

    <Card class="w-full max-w-sm shadow-xl border-slate-200">
      <CardHeader class="pb-2">
        <CardTitle class="text-base text-slate-700">Masuk ke Akun Anda</CardTitle>
        <CardDescription>Masukkan email dan kata sandi untuk melanjutkan</CardDescription>
      </CardHeader>

      <CardContent>
        <!-- Error Alert -->
        <div v-if="error" class="mb-4 flex items-start gap-2.5 bg-red-50 border border-red-200 text-red-700 text-sm font-medium px-4 py-3 rounded-xl">
          <svg class="w-4 h-4 mt-0.5 shrink-0 text-red-400" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
          </svg>
          <span>{{ error }}</span>
        </div>

        <form class="space-y-4" @submit.prevent="handleLogin">
          <div class="space-y-1.5">
            <label for="email" class="block text-sm font-semibold text-slate-700">Alamat Email</label>
            <Input
              id="email"
              v-model="form.email"
              type="email"
              autocomplete="email"
              required
              placeholder="nama@ladanglima.com"
              class="rounded-xl h-10"
            />
          </div>

          <div class="space-y-1.5">
            <label for="password" class="block text-sm font-semibold text-slate-700">Kata Sandi</label>
            <div class="relative">
              <Input
                id="password"
                v-model="form.password"
                :type="showPassword ? 'text' : 'password'"
                autocomplete="current-password"
                required
                placeholder="••••••••"
                class="rounded-xl h-10 pr-10"
              />
              <button
                type="button"
                @click="showPassword = !showPassword"
                class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 cursor-pointer transition-colors"
                tabindex="-1"
              >
                <svg v-if="showPassword" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
              </button>
            </div>
          </div>

          <Button
            type="submit"
            :disabled="loading"
            class="w-full h-10 rounded-xl bg-gradient-to-tr from-brand-brown to-brand-gradation text-brand-cream font-bold text-sm hover:opacity-90 mt-2"
          >
            <svg v-if="loading" class="animate-spin w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
            </svg>
            {{ loading ? 'Memproses...' : 'Masuk Ke Sistem' }}
          </Button>
        </form>
      </CardContent>
    </Card>

    <p class="mt-6 text-xs text-slate-400">&copy; {{ new Date().getFullYear() }} Ladang Lima · TPM System</p>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import { router } from '@inertiajs/vue3';
import { useAuth } from '../composables/useAuth.js';
import { Card, CardHeader, CardTitle, CardDescription, CardContent } from '../../views/components/ui/card/index.ts';
import { Button } from '../../views/components/ui/button/index.ts';
import { Input } from '../../views/components/ui/input/index.ts';

const { login, isAuthenticated } = useAuth();

const form = reactive({ email: '', password: '' });
const loading = ref(false);
const error = ref(null);
const showPassword = ref(false);

onMounted(() => {
  if (isAuthenticated.value) {
    router.visit('/', { replace: true });
  }
});

async function handleLogin() {
  loading.value = true;
  error.value = null;

  const result = await login(form.email, form.password);

  loading.value = false;
  if (result.success) {
    router.visit('/', { replace: true });
  } else {
    error.value = result.message;
  }
}
</script>
