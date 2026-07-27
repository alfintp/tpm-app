<script setup>
import { onMounted, onUnmounted, watch } from 'vue';
import { useSidebar } from '../../views/components/ui/sidebar/index.ts';
import { usePage } from '@inertiajs/vue3';

const { openMobile, setOpenMobile } = useSidebar();
const page = usePage();

// Watch for route changes (Inertia)
watch(() => page.url, () => {
  if (openMobile.value) {
    setOpenMobile(false);
  }
});

function handleAlertOpen() {
  if (openMobile.value) {
    setOpenMobile(false);
  }
}

onMounted(() => {
  window.addEventListener('alert-modal-open', handleAlertOpen);
});

onUnmounted(() => {
  window.removeEventListener('alert-modal-open', handleAlertOpen);
});
</script>

<template>
  <span style="display:none" />
</template>
