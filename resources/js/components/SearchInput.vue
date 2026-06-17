<template>
  <div class="relative w-full" :class="wrapperClass">
    <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none z-10">
      <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
      </svg>
    </span>
    <Input
      :model-value="modelValue"
      @update:model-value="$emit('update:modelValue', $event)"
      type="text"
      :placeholder="placeholder"
      class="w-full rounded-2xl border-slate-200 bg-white pl-10 pr-4 py-2.5 text-sm text-slate-700 placeholder-slate-400 shadow-sm transition-all focus-visible:ring-indigo-500/50 focus-visible:border-indigo-500 h-10"
    />
  </div>
</template>

<script setup>
import Input from '../../views/components/ui/input/Input.vue';

defineProps({
  modelValue: { type: String, default: '' },
  placeholder: { type: String, default: 'Cari...' },
  wrapperClass: { type: String, default: 'md:max-w-md' },
});
defineEmits(['update:modelValue']);
</script>
