<template>
  <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
    <MachineComponentTab
      :components="components"
      :is-admin="isAdmin"
      :is-manager-or-admin="isManagerOrAdmin"
      :color-theme="getColorTheme"
      :format-date="formatDate"
      :get-last-replacement="getLastReplacement"
      :get-last-maintenance="getLastMaintenance"
      @add="$emit('add')"
      @import="$emit('import')"
      @import-indicators="$emit('import-indicators')"
      @edit="$emit('edit', $event)"
      @delete="$emit('delete', $event)"
      @view-history="$emit('view-history', $event)"
    />
  </div>
</template>

<script setup>
import MachineComponentTab from './MachineComponentTab.vue';

defineProps({
  components: { type: Array, default: () => [] },
  isAdmin: { type: Boolean, default: false },
  isManagerOrAdmin: { type: Boolean, default: false },
  getColorTheme: { type: Function, required: true },
  formatDate: { type: Function, required: true },
  getLastReplacement: { type: Function, required: true },
  getLastMaintenance: { type: Function, required: true },
});

defineEmits(['add', 'import', 'import-indicators', 'edit', 'delete', 'view-history']);
</script>
