<!-- Global Alert & Confirm Modal -->
<template>
  <Teleport to="body">
    <Transition name="alert-fade">
      <div v-if="visible" class="fixed inset-0 z-[200] flex items-center justify-center">
        <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm" @click="handleCancel"></div>
        <div class="relative bg-white rounded-3xl shadow-2xl w-full max-w-sm mx-4 overflow-hidden transform transition-all">
          <!-- Color bar top -->
          <div :class="barClass" class="h-1.5 w-full"></div>
          <div class="p-8">
            <!-- Icon -->
            <div :class="iconBgClass" class="w-14 h-14 rounded-2xl flex items-center justify-center mb-5 mx-auto">
              <!-- Success -->
              <svg v-if="type === 'success'" :class="iconClass" class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
              <!-- Warning / Confirm -->
              <svg v-else-if="type === 'warning' || type === 'confirm'" :class="iconClass" class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
              <!-- Error -->
              <svg v-else-if="type === 'error'" :class="iconClass" class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
              <!-- Info -->
              <svg v-else :class="iconClass" class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <h3 class="text-lg font-semibold text-slate-800 text-center mb-2">{{ title }}</h3>
            <p class="text-sm text-slate-500 text-center leading-relaxed">{{ message }}</p>
          </div>
          <div class="px-8 pb-8 flex gap-3" :class="type === 'confirm' ? 'justify-between' : 'justify-center'">
            <button v-if="type === 'confirm'" @click="handleCancel" class="flex-1 px-5 py-2.5 rounded-xl font-medium text-slate-600 bg-slate-100 hover:bg-slate-200 transition-colors cursor-pointer">
              {{ cancelText }}
            </button>
            <button v-if="discardText" @click="handleDiscard" class="flex-1 px-5 py-2.5 rounded-xl font-medium text-slate-700 bg-slate-200 hover:bg-slate-300 transition-colors cursor-pointer">
              {{ discardText }}
            </button>
            <button @click="handleConfirm" :class="confirmBtnClass" class="flex-1 px-5 py-2.5 rounded-xl font-medium text-white transition-colors cursor-pointer shadow-sm">
              {{ confirmText }}
            </button>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { ref, computed } from 'vue';

const visible = ref(false);
const type = ref('info'); // success, error, warning, confirm, info
const title = ref('');
const message = ref('');
const confirmText = ref('OK');
const cancelText = ref('Batal');
const discardText = ref(null);
let resolveFn = null;

const barClass = computed(() => ({
  'success': 'bg-gradient-to-r from-green-400 to-emerald-500',
  'error': 'bg-gradient-to-r from-red-400 to-rose-500',
  'warning': 'bg-gradient-to-r from-amber-400 to-orange-500',
  'confirm': 'bg-gradient-to-r from-red-400 to-rose-500',
  'info': 'bg-gradient-to-r from-blue-400 to-indigo-500',
}[type.value] ?? 'bg-indigo-500'));

const iconBgClass = computed(() => ({
  'success': 'bg-green-50',
  'error': 'bg-red-50',
  'warning': 'bg-amber-50',
  'confirm': 'bg-red-50',
  'info': 'bg-blue-50',
}[type.value] ?? 'bg-slate-50'));

const iconClass = computed(() => ({
  'success': 'text-green-500',
  'error': 'text-red-500',
  'warning': 'text-amber-500',
  'confirm': 'text-red-500',
  'info': 'text-blue-500',
}[type.value] ?? 'text-slate-500'));

const confirmBtnClass = computed(() => ({
  'success': 'bg-green-500 hover:bg-green-600',
  'error': 'bg-red-500 hover:bg-red-600',
  'warning': 'bg-amber-500 hover:bg-amber-600',
  'confirm': 'bg-red-500 hover:bg-red-600',
  'info': 'bg-indigo-500 hover:bg-indigo-600',
}[type.value] ?? 'bg-indigo-500 hover:bg-indigo-600'));

function show(opts) {
  type.value = opts.type ?? 'info';
  title.value = opts.title ?? '';
  message.value = opts.message ?? '';
  confirmText.value = opts.confirmText ?? (opts.type === 'confirm' ? 'Ya, Hapus' : 'OK');
  cancelText.value = opts.cancelText ?? 'Batal';
  discardText.value = opts.discardText ?? null;
  visible.value = true;
  return new Promise((resolve) => { resolveFn = resolve; });
}

function handleConfirm() {
  visible.value = false;
  resolveFn?.(discardText.value ? 'save' : true);
}

function handleCancel() {
  visible.value = false;
  resolveFn?.(discardText.value ? 'cancel' : false);
}

function handleDiscard() {
  visible.value = false;
  resolveFn?.('discard');
}

defineExpose({ show });
</script>

<style>
.alert-fade-enter-active, .alert-fade-leave-active {
  transition: all 0.25s ease;
}
.alert-fade-enter-from, .alert-fade-leave-to {
  opacity: 0;
}
.alert-fade-enter-from .relative, .alert-fade-leave-to .relative {
  transform: scale(0.92) translateY(16px);
}
</style>
