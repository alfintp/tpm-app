<template>
  <div class="bg-white rounded-3xl border border-slate-100 shadow-sm overflow-hidden">

    <!-- Loading State -->
    <div v-if="loading" class="p-16 flex flex-col items-center justify-center gap-4 text-slate-400">
      <Spinner class="size-10 text-brand-brown" />
      <p class="font-semibold text-slate-600">{{ loadingText }}</p>
      <p class="text-xs text-slate-400">{{ loadingSubtext }}</p>
    </div>

    <!-- Empty State -->
    <div v-else-if="!rows.length" class="p-16 text-center text-slate-400">
      <slot name="empty">
        <svg class="w-16 h-16 mx-auto mb-4 text-slate-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
        </svg>
        <p class="font-bold text-slate-600 text-lg">{{ emptyTitle }}</p>
        <p class="text-sm text-slate-400 mt-1">{{ emptySubtext }}</p>
      </slot>
    </div>

    <!-- Table -->
    <template v-else>
      <div class="overflow-x-auto">
        <Table :class="['w-full border-none', minWidth, tableClass]">
          <TableHeader class="bg-slate-50/70 border-b border-slate-100">
            <TableRow class="border-none hover:bg-transparent">
              <TableHead
                v-for="col in visibleColumns"
                :key="col.key"
                :class="['text-left text-xs font-black text-slate-500 uppercase tracking-wider px-4 py-4 h-auto', col.width ?? '', col.headerClass ?? '']"
              >
                {{ col.label }}
              </TableHead>
              <TableHead
                v-if="hasActions"
                :class="['text-xs font-black text-slate-500 uppercase tracking-wider px-4 py-4 h-auto', actionsAlign === 'right' ? 'text-right' : 'text-left', actionsWidth]"
              >
                {{ actionsLabel }}
              </TableHead>
            </TableRow>
          </TableHeader>

          <TableBody class="divide-y divide-slate-50">
            <TableRow
              v-for="(row, rowIndex) in rows"
              :key="row.id ?? rowIndex"
              :class="['hover:bg-slate-50/30 transition-colors border-none', rowClickable ? 'cursor-pointer hover:bg-indigo-50/40' : '']"
              @click="rowClickable ? $emit('row-click', row) : undefined"
            >
              <TableCell
                v-for="col in visibleColumns"
                :key="col.key"
                :class="['px-4 py-4 h-auto', col.cellClass ?? '']"
              >
                <slot :name="`cell-${col.key}`" :row="row" :value="row[col.key]">
                  <span class="text-sm text-slate-700">{{ row[col.key] ?? '-' }}</span>
                </slot>
              </TableCell>

              <TableCell v-if="hasActions" :class="['px-4 py-4 h-auto', actionsAlign === 'right' ? 'text-right' : '']">
                <slot name="actions" :row="row" :rowIndex="rowIndex" />
              </TableCell>
            </TableRow>
          </TableBody>
        </Table>
      </div>

      <!-- Pagination -->
      <TablePagination
        v-if="paginate"
        :model-value="currentPage"
        @update:model-value="$emit('update:currentPage', $event)"
        :total="totalRows"
        :per-page="perPage"
      />
    </template>

  </div>
</template>

<script setup>
import { computed, useSlots } from 'vue';
import Spinner from '../../views/components/ui/spinner/Spinner.vue';
import {
  Table, TableBody, TableCell,
  TableHead, TableHeader, TableRow,
} from '../../views/components/ui/table';
import TablePagination from './TablePagination.vue';

const props = defineProps({
  columns: {
    type: Array,
    required: true,
  },
  rows: {
    type: Array,
    default: () => [],
  },
  loading: { type: Boolean, default: false },
  loadingText: { type: String, default: 'Memuat data...' },
  loadingSubtext: { type: String, default: 'Mengambil data dari server' },
  emptyTitle: { type: String, default: 'Tidak ada data' },
  emptySubtext: { type: String, default: 'Coba sesuaikan filter atau pencarian.' },
  minWidth: { type: String, default: 'min-w-[700px]' },
  tableClass: { type: String, default: '' },
  paginate: { type: Boolean, default: true },
  currentPage: { type: Number, default: 1 },
  totalRows: { type: Number, default: 0 },
  perPage: { type: Number, default: 10 },
  actionsLabel: { type: String, default: 'Aksi' },
  actionsAlign: { type: String, default: 'right' },
  actionsWidth: { type: String, default: 'w-[10%]' },
  rowClickable: { type: Boolean, default: false },
});

defineEmits(['update:currentPage', 'row-click']);

const slots = useSlots();
const hasActions = computed(() => !!slots.actions);
const visibleColumns = computed(() => props.columns.filter(col => col.show !== false));
</script>
