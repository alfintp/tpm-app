<template>
  <div
    :class="[
      'rounded-2xl border p-4 flex items-start gap-3 transition-all',
      colorMap[color]?.bg ?? 'bg-slate-50',
      colorMap[color]?.border ?? 'border-slate-100',
    ]"
  >
    <!-- Icon slot (optional) -->
    <div
      v-if="$slots.icon"
      :class="[
        'mt-0.5 flex-shrink-0 w-10 h-10 rounded-xl flex items-center justify-center',
        colorMap[color]?.iconBg ?? 'bg-slate-200',
      ]"
    >
      <slot name="icon" />
    </div>

    <div class="flex-1 min-w-0">
      <p :class="['text-2xl font-bold leading-none', colorMap[color]?.value ?? 'text-slate-700']">
        {{ value }}
      </p>
      <p class="text-xs text-slate-500 mt-1.5 font-medium leading-snug">{{ label }}</p>
      <p v-if="sub" class="text-[10px] text-slate-400 mt-0.5">{{ sub }}</p>
    </div>
  </div>
</template>

<script setup>
defineProps({
  value: { type: [String, Number], required: true },
  label: { type: String, required: true },
  sub: { type: String, default: '' },
  color: {
    type: String,
    default: 'slate',
    validator: (v) => ['amber', 'green', 'red', 'blue', 'indigo', 'purple', 'teal', 'slate', 'brand'].includes(v),
  },
});

const colorMap = {
  amber:  { bg: 'bg-amber-50',  border: 'border-amber-100',  value: 'text-amber-600',  iconBg: 'bg-amber-100 text-amber-600' },
  green:  { bg: 'bg-green-50',  border: 'border-green-100',  value: 'text-green-600',  iconBg: 'bg-green-100 text-green-600' },
  red:    { bg: 'bg-red-50',    border: 'border-red-100',    value: 'text-red-600',    iconBg: 'bg-red-100 text-red-600' },
  blue:   { bg: 'bg-blue-50',   border: 'border-blue-100',   value: 'text-blue-600',   iconBg: 'bg-blue-100 text-blue-600' },
  indigo: { bg: 'bg-indigo-50', border: 'border-indigo-100', value: 'text-indigo-600', iconBg: 'bg-indigo-100 text-indigo-600' },
  purple: { bg: 'bg-purple-50', border: 'border-purple-100', value: 'text-purple-600', iconBg: 'bg-purple-100 text-purple-600' },
  teal:   { bg: 'bg-teal-50',   border: 'border-teal-100',   value: 'text-teal-600',   iconBg: 'bg-teal-100 text-teal-600' },
  slate:  { bg: 'bg-slate-50',  border: 'border-slate-100',  value: 'text-slate-700',  iconBg: 'bg-slate-200 text-slate-600' },
  brand:  { bg: 'bg-brand-cream', border: 'border-brand-brown/10', value: 'text-brand-brown', iconBg: 'bg-brand-brown/10 text-brand-brown' },
};
</script>
