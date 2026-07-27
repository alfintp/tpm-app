<template>
  <div class="flex gap-2 w-full md:w-auto overflow-x-auto pb-1 md:pb-0 scrollbar-hide">
    <Button
      v-for="tab in tabs"
      :key="tab.value"
      @click="$emit('update:modelValue', tab.value)"
      variant="outline"
      :class="[
        'rounded-xl text-xs font-bold transition-all cursor-pointer whitespace-nowrap px-4 py-1.5 h-auto',
        modelValue === tab.value
          ? 'bg-gradient-to-tr from-brand-brown to-brand-gradation text-brand-cream border-transparent shadow-md hover:text-brand-cream hover:opacity-90'
          : 'bg-white text-slate-600 border-slate-200 hover:border-brand-brown'
      ]"
    >
      {{ tab.label }}<span v-if="tab.count !== undefined" class="ml-1.5 opacity-70">{{ tab.count }}</span>
    </Button>
  </div>
</template>

<script setup>
import Button from '../../views/components/ui/button/Button.vue';

defineProps({
  modelValue: { type: String, default: '' },
  tabs: {
    type: Array,
    default: () => [],
  },
});
defineEmits(['update:modelValue']);
</script>
