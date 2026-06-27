<script setup>
import { onMounted, onUnmounted } from 'vue';
import { useSidebar } from '../../views/components/ui/sidebar/index.ts';

const { openMobile, setOpenMobile } = useSidebar();

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
