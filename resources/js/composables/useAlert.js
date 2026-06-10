import { ref } from 'vue';

// Global alert ref - will be set by App.vue
export const alertRef = ref(null);

export async function showAlert(type, title, message, opts = {}) {
  return alertRef.value?.show({ type, title, message, ...opts });
}

export async function showConfirm(title, message, opts = {}) {
  return alertRef.value?.show({ type: 'confirm', title, message, ...opts });
}

export async function showUnsavedConfirm(title, message) {
  return alertRef.value?.show({
    type: 'confirm',
    title,
    message,
    confirmText: 'Simpan',
    cancelText: 'Batal',
    discardText: 'Tidak Simpan',
  });
}
