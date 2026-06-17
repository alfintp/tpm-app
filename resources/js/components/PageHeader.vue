<template>
  <div
    :class="[
      'flex flex-col md:flex-row md:items-center md:justify-between flex-wrap gap-4 mt-4',
      card ? 'bg-white p-6 rounded-2xl border border-slate-200 shadow-sm mt-4' : ''
    ]"
  >
    <!-- Left: Title & Subtitle -->
    <div>
      <h1 v-if="title" class="text-xl font-black text-brand-brown">{{ title }}</h1>
      <p v-if="subtitle" class="text-sm text-slate-500 font-medium mt-0.5">{{ subtitle }}</p>
      <slot name="title-extra" />
    </div>

    <!-- Right: Badge, Stat, Buttons, or custom slot -->
    <div v-if="$slots.actions || badge" class="flex items-center gap-3 flex-wrap">
      <!-- Optional badge/stat chip -->
      <span
        v-if="badge"
        class="text-xs bg-brand-cream text-brand-brown font-bold px-3 py-1.5 rounded-full border border-brand-brown/10 whitespace-nowrap"
      >
        {{ badge }}
      </span>

      <!-- Custom action buttons or content -->
      <slot name="actions" />
    </div>
  </div>
</template>

<script setup>
defineProps({
  title: { type: String, default: '' },
  subtitle: { type: String, default: '' },
  badge: { type: String, default: '' },
  card: { type: Boolean, default: false },
});
</script>
