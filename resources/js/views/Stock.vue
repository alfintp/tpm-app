<template>
  <div class="w-full max-w-5xl mx-auto space-y-6">
    <!-- Header -->
    <PageHeader title="Manajemen Stok" subtitle="Kelola stok suku cadang penggantian komponen">
      <template #actions>
        <button
          v-if="canAddData"
          @click="showBulkLimit = true"
          class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-bold text-slate-600 transition-colors hover:border-brand-brown hover:text-brand-brown cursor-pointer"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3"/></svg>
          Set Limit Qty
        </button>
        <button
          v-if="isAdmin"
          @click="showImport = true"
          class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-bold text-slate-600 transition-colors hover:border-brand-brown hover:text-brand-brown cursor-pointer"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
          Import
        </button>
        <button
          v-if="canAddData"
          @click="openCreate"
          class="inline-flex items-center gap-1.5 rounded-xl bg-brand-gradation text-white px-3 py-2 text-xs font-bold transition-all hover:shadow-md active:scale-95 cursor-pointer"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
          Tambah Stok
        </button>
      </template>
    </PageHeader>

    <!-- Stats Summary Cards -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
      <div
        class="bg-white rounded-2xl border border-slate-100 shadow-sm p-4 cursor-pointer transition-all hover:shadow-md active:scale-95"
        :class="filterLowStock ? 'ring-2 ring-offset-2 ring-red-400' : ''"
        @click="filterLowStock = !filterLowStock"
      >
        <div class="flex justify-between items-start">
          <div>
            <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Stok Menipis</p>
            <p class="text-2xl font-black text-slate-800">{{ lowStockCount }}</p>
          </div>
          <div class="p-2 rounded-xl text-white bg-red-500">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-4">
        <div class="flex justify-between items-start">
          <div>
            <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Total Item</p>
            <p class="text-2xl font-black text-slate-800">{{ stocks.length }}</p>
          </div>
          <div class="p-2 rounded-xl text-white bg-indigo-500">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-4">
        <div class="flex justify-between items-start">
          <div>
            <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Kategori</p>
            <p class="text-2xl font-black text-slate-800">{{ uniqueCategories.length }}</p>
          </div>
          <div class="p-2 rounded-xl text-white bg-emerald-500">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-4">
        <div class="flex justify-between items-start">
          <div>
            <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Pemakaian Bulan Ini</p>
            <p class="text-2xl font-black text-slate-800">{{ monthlyUsageCount }}</p>
          </div>
          <div class="p-2 rounded-xl text-white bg-amber-500">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
          </div>
        </div>
      </div>
    </div>

    <!-- Tabs -->
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
      <div class="px-4 py-3 border-b border-slate-100 flex items-center gap-1 bg-slate-50">
        <button
          @click="activeTab = 'data'"
          :class="activeTab === 'data' ? 'bg-white text-brand-gradation shadow-sm' : 'text-slate-500 hover:text-slate-700'"
          class="px-4 py-2 rounded-lg text-xs font-bold transition-all cursor-pointer flex items-center gap-1.5"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
          Data Stok
        </button>
        <button
          @click="activeTab = 'usage'"
          :class="activeTab === 'usage' ? 'bg-white text-brand-gradation shadow-sm' : 'text-slate-500 hover:text-slate-700'"
          class="px-4 py-2 rounded-lg text-xs font-bold transition-all cursor-pointer flex items-center gap-1.5"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
          Laporan Pemakaian
        </button>
      </div>

      <!-- Tab: Data Stok -->
      <div v-if="activeTab === 'data'" class="p-4 space-y-4">
        <!-- Search & Filter Bar -->
        <div class="flex flex-wrap gap-3 items-center">
          <SearchInput
            v-model="search"
            placeholder="Cari kode, nama, atau kategori stok..."
            class="flex-1 min-w-50"
          />

          <div class="relative group">
            <select v-model="filterCategory" class="rounded-xl border border-slate-200 pl-3 pr-8 py-2 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 cursor-pointer appearance-none bg-white">
              <option value="all">Semua Kategori</option>
              <option v-for="cat in uniqueCategories" :key="cat" :value="cat">{{ cat }}</option>
            </select>
            <div class="absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none text-slate-400">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </div>
          </div>

          <select v-model="sortBy" class="rounded-xl border border-slate-200 px-3 py-2 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 cursor-pointer">
            <option value="name">Nama A-Z</option>
            <option value="name_desc">Nama Z-A</option>
            <option value="quantity_asc">Qty Terendah</option>
            <option value="quantity_desc">Qty Tertinggi</option>
            <option value="code">Kode</option>
          </select>
        </div>

        <!-- Stock Table -->
        <DataTable
          :columns="stockColumns"
          :rows="paginatedStocks"
          :loading="loading"
          loading-text="Memuat data stok..."
          loading-subtext="Mengambil data dari server"
          empty-title="Tidak ada stok ditemukan"
          empty-subtext="Coba ubah kata kunci pencarian atau filter"
          min-width="min-w-[700px]"
          table-class="table-fixed"
          actions-label=""
          actions-width="w-[24%]"
          :paginate="true"
          :current-page="currentPage"
          :total-rows="filteredStocks.length"
          :per-page="perPage"
          :show-per-page-selector="true"
          :per-page-options="[10, 20, 30, 50, 100]"
          @update:current-page="currentPage = $event"
          @update:per-page="perPage = $event"
        >
          <template #cell-code="{ row }">
            <span class="font-mono text-xs font-bold text-slate-700">{{ row.code }}</span>
          </template>

          <template #cell-name="{ row }">
            <div class="min-w-0">
              <p class="font-bold text-slate-800 text-sm truncate" :title="row.name">{{ row.name }}</p>
              <p v-if="row.category" class="text-[10px] font-bold text-slate-500 bg-slate-100 px-1.5 py-0.5 rounded w-fit mt-0.5">{{ row.category }}</p>
            </div>
          </template>

          <template #cell-quantity="{ row }">
            <div class="flex flex-col items-center">
              <span class="font-bold text-base" :class="row.is_low_stock ? 'text-red-600' : 'text-slate-800'">{{ row.quantity }}</span>
              <span class="text-[10px] text-slate-400">{{ row.unit }}</span>
            </div>
          </template>

          <template #cell-limit_qty="{ row }">
            <div class="flex flex-col items-center">
              <span class="font-semibold text-sm text-slate-600">{{ row.limit_qty }}</span>
              <span class="text-[10px] text-slate-400">min</span>
            </div>
          </template>

          <template #cell-status="{ row }">
            <span v-if="row.quantity <= 0" class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-red-50 text-red-700 border border-red-200">Habis</span>
            <span v-else-if="row.is_low_stock" class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-orange-50 text-orange-700 border border-orange-200">Menipis</span>
            <span v-else class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-green-50 text-green-700 border border-green-200">Normal</span>
          </template>

          <template #actions="{ row }">
            <div class="flex items-center justify-end gap-1">
              <button
                v-if="canAddData"
                @click="openRestock(row)"
                class="p-2 text-emerald-500 hover:bg-emerald-50 rounded-lg cursor-pointer transition-colors"
                title="Restock"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
              </button>
              <button
                @click="viewUsageHistory(row)"
                class="p-2 text-indigo-500 hover:bg-indigo-50 rounded-lg cursor-pointer transition-colors"
                title="Riwayat Pemakaian"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
              </button>
              <button
                v-if="canAddData"
                @click="openEdit(row)"
                class="p-2 text-slate-400 hover:text-brand-gradation hover:bg-brand-cream rounded-lg cursor-pointer transition-colors"
                title="Edit Stok"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
              </button>
              <button
                v-if="canDeleteData"
                @click="deleteStock(row)"
                class="p-2 text-slate-400 hover:text-red-500 hover:bg-red-50 rounded-lg cursor-pointer transition-colors"
                title="Hapus Stok"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
              </button>
            </div>
          </template>
        </DataTable>
      </div>

      <!-- Tab: Laporan Pemakaian -->
      <div v-if="activeTab === 'usage'" class="p-4 space-y-4">
        <!-- Usage Filters -->
        <div class="flex flex-wrap gap-3 items-center">
          <div class="flex items-center gap-2">
            <label class="text-xs font-semibold text-slate-500">Dari</label>
            <input
              v-model="usageFilter.from_date"
              type="date"
              class="rounded-xl border border-slate-200 px-3 py-2 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-indigo-500"
            />
          </div>
          <div class="flex items-center gap-2">
            <label class="text-xs font-semibold text-slate-500">Sampai</label>
            <input
              v-model="usageFilter.to_date"
              type="date"
              class="rounded-xl border border-slate-200 px-3 py-2 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-indigo-500"
            />
          </div>
          <div class="relative">
            <input
              v-model="machineSearch"
              @focus="showMachineDropdown = true"
              @blur="handleMachineBlur"
              @input="handleMachineInput"
              type="text"
              placeholder="Semua Mesin"
              class="rounded-xl border border-slate-200 pl-3 pr-8 py-2 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 w-48"
            />
            <button
              v-if="machineSearch"
              @click="clearMachineFilter"
              type="button"
              class="absolute right-2 top-1/2 -translate-y-1/2 p-1 text-slate-400 hover:text-slate-600 hover:bg-slate-100 rounded-full cursor-pointer transition-colors"
              title="Hapus filter mesin"
            >
              <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
            <div
              v-if="showMachineDropdown && filteredMachines.length > 0"
              class="absolute top-full left-0 right-0 mt-1 bg-white border border-slate-200 rounded-xl shadow-lg z-50 max-h-48 overflow-y-auto"
            >
              <div
                v-for="m in filteredMachines"
                :key="m.id"
                @mousedown="selectMachine(m)"
                class="px-3 py-2 text-sm text-slate-700 hover:bg-slate-50 cursor-pointer"
              >
                {{ m.name }}
              </div>
            </div>
          </div>
          <div class="relative">
            <input
              v-model="stockSearch"
              @focus="showStockDropdown = true"
              @blur="handleStockBlur"
              @input="handleStockInput"
              type="text"
              placeholder="Semua Stok"
              class="rounded-xl border border-slate-200 pl-3 pr-8 py-2 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 w-48"
            />
            <button
              v-if="stockSearch"
              @click="clearStockFilter"
              type="button"
              class="absolute right-2 top-1/2 -translate-y-1/2 p-1 text-slate-400 hover:text-slate-600 hover:bg-slate-100 rounded-full cursor-pointer transition-colors"
              title="Hapus filter stok"
            >
              <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
            <div
              v-if="showStockDropdown && filteredStocksBySearch.length > 0"
              class="absolute top-full left-0 right-0 mt-1 bg-white border border-slate-200 rounded-xl shadow-lg z-50 max-h-48 overflow-y-auto"
            >
              <div
                v-for="s in filteredStocksBySearch"
                :key="s.id"
                @mousedown="selectStock(s)"
                class="px-3 py-2 text-sm text-slate-700 hover:bg-slate-50 cursor-pointer"
              >
                {{ s.code }} - {{ s.name }}
              </div>
            </div>
          </div>
          <button
            @click="loadUsages"
            class="rounded-xl bg-brand-gradation text-white px-4 py-2 text-xs font-bold cursor-pointer hover:shadow-md transition-all"
          >
            Terapkan
          </button>
        </div>

        <!-- Usage Table -->
        <DataTable
          :columns="usageColumns"
          :rows="paginatedUsages"
          :loading="usageLoading"
          loading-text="Memuat laporan pemakaian..."
          loading-subtext="Mengambil data dari server"
          empty-title="Tidak ada data pemakaian"
          empty-subtitle="Belum ada pemakaian stok pada periode ini"
          min-width="min-w-[700px]"
          table-class="table-fixed"
          :paginate="true"
          :current-page="usagePage"
          :total-rows="filteredUsages.length"
          :per-page="usagePerPage"
          :show-per-page-selector="true"
          :per-page-options="[10, 20, 30, 50]"
          @update:current-page="usagePage = $event"
          @update:per-page="usagePerPage = $event"
        >
          <template #cell-used_at="{ row }">
            <span class="text-sm font-semibold text-slate-700">{{ formatDate(row.used_at) }}</span>
          </template>

          <template #cell-stock_name="{ row }">
            <div class="min-w-0">
              <p class="font-bold text-slate-800 text-sm truncate">{{ row.stock?.name }}</p>
              <p class="text-[10px] font-mono text-slate-400">{{ row.stock?.code }}</p>
            </div>
          </template>

          <template #cell-machine_name="{ row }">
            <div class="min-w-0">
              <p class="font-semibold text-slate-700 text-sm truncate">{{ row.machine?.name }}</p>
              <p class="text-[10px] text-slate-400">{{ row.machine?.kota }} · {{ row.machine?.location }}</p>
            </div>
          </template>

          <template #cell-component_name="{ row }">
            <span class="text-sm text-slate-600">{{ row.machine_component?.name ?? '-' }}</span>
          </template>

          <template #cell-quantity_used="{ row }">
            <span class="font-bold text-sm text-red-600">{{ row.quantity_used }}</span>
            <span class="text-[10px] text-slate-400 ml-1">{{ row.stock?.unit }}</span>
          </template>

          <template #cell-technician="{ row }">
            <span class="text-sm text-slate-600">{{ row.technician?.full_name ?? '-' }}</span>
          </template>
        </DataTable>
      </div>
    </div>

    <!-- Stock Form Modal -->
    <StockForm
      v-if="showStockForm"
      :stock="editingStock"
      @close="showStockForm = false"
      @saved="onStockSaved"
    />

    <!-- Restock Modal -->
    <RestockModal
      v-if="showRestock"
      :stock="restockStock"
      @close="showRestock = false"
      @saved="onRestockSaved"
    />

    <!-- Import Modal -->
    <StockImport
      v-if="showImport"
      @close="showImport = false"
      @imported="onImported"
    />

    <!-- Bulk Set Limit Modal -->
    <BulkSetLimit
      v-if="showBulkLimit"
      :stocks="stocks"
      @close="showBulkLimit = false"
      @saved="onBulkLimitSaved"
    />

    <!-- Usage History Modal -->
    <StockUsageHistory
      v-if="showUsageHistory"
      :stock="historyStock"
      @close="showUsageHistory = false"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch, defineAsyncComponent } from 'vue';
import axios from 'axios';
import PageHeader from '../components/PageHeader.vue';
import SearchInput from '../components/SearchInput.vue';
import DataTable from '../components/DataTable.vue';
import { useAuth } from '../composables/useAuth.js';
import { showAlert, showConfirm } from '../composables/useAlert.js';

const StockForm = defineAsyncComponent(() => import('../components/StockForm.vue'));
const RestockModal = defineAsyncComponent(() => import('../components/RestockModal.vue'));
const StockImport = defineAsyncComponent(() => import('../components/StockImport.vue'));
const BulkSetLimit = defineAsyncComponent(() => import('../components/BulkSetLimit.vue'));
const StockUsageHistory = defineAsyncComponent(() => import('../components/StockUsageHistory.vue'));

const { isAdmin, canAddData, canDeleteData } = useAuth();

const stocks = ref([]);
const machines = ref([]);
const usages = ref([]);
const loading = ref(true);
const usageLoading = ref(false);
const search = ref('');
const filterCategory = ref('all');
const filterLowStock = ref(false);
const sortBy = ref('name');
const currentPage = ref(1);
const perPage = ref(20);
const activeTab = ref('data');

const usageFilter = ref({ from_date: '', to_date: '', machine_id: '', stock_id: '' });
const usagePage = ref(1);
const usagePerPage = ref(20);

// Usage tab: machine & stock search dropdowns
const machineSearch = ref('');
const showMachineDropdown = ref(false);
const stockSearch = ref('');
const showStockDropdown = ref(false);

const showStockForm = ref(false);
const editingStock = ref(null);
const showRestock = ref(false);
const restockStock = ref(null);
const showImport = ref(false);
const showBulkLimit = ref(false);
const showUsageHistory = ref(false);
const historyStock = ref(null);

const stockColumns = [
  { key: 'code', label: 'Kode', width: 'w-[12%]' },
  { key: 'name', label: 'Nama & Kategori', width: 'w-[30%]' },
  { key: 'quantity', label: 'Qty', width: 'w-[12%]', align: 'center' },
  { key: 'limit_qty', label: 'Limit', width: 'w-[10%]', align: 'center' },
  { key: 'status', label: 'Status', width: 'w-[12%]', align: 'center' },
];

const usageColumns = [
  { key: 'used_at', label: 'Tanggal', width: 'w-[12%]' },
  { key: 'stock_name', label: 'Stok', width: 'w-[25%]' },
  { key: 'machine_name', label: 'Mesin', width: 'w-[22%]' },
  { key: 'component_name', label: 'Komponen', width: 'w-[18%]' },
  { key: 'quantity_used', label: 'Dipakai', width: 'w-[10%]', align: 'center' },
  { key: 'technician', label: 'Teknisi', width: 'w-[13%]' },
];

const uniqueCategories = computed(() => {
  const cats = new Set();
  stocks.value.forEach(s => { if (s.category) cats.add(s.category); });
  return Array.from(cats).sort();
});

const lowStockCount = computed(() => stocks.value.filter(s => s.is_low_stock).length);

const monthlyUsageCount = computed(() => {
  const now = new Date();
  return usages.value.filter(u => {
    const d = new Date(u.used_at);
    return d.getMonth() === now.getMonth() && d.getFullYear() === now.getFullYear();
  }).length;
});

const filteredStocks = computed(() => {
  let list = stocks.value;
  if (search.value) {
    const q = search.value.toLowerCase();
    list = list.filter(s =>
      s.code.toLowerCase().includes(q) ||
      s.name.toLowerCase().includes(q) ||
      (s.category ?? '').toLowerCase().includes(q)
    );
  }
  if (filterCategory.value !== 'all') {
    list = list.filter(s => s.category === filterCategory.value);
  }
  if (filterLowStock.value) {
    list = list.filter(s => s.is_low_stock);
  }
  return list;
});

const paginatedStocks = computed(() => {
  const start = (currentPage.value - 1) * perPage.value;
  return filteredStocks.value.slice(start, start + perPage.value);
});

const filteredUsages = computed(() => {
  let list = usages.value;
  if (usageFilter.value.from_date) {
    list = list.filter(u => u.used_at >= usageFilter.value.from_date);
  }
  if (usageFilter.value.to_date) {
    list = list.filter(u => u.used_at <= usageFilter.value.to_date);
  }
  if (usageFilter.value.machine_id) {
    list = list.filter(u => u.machine_id === usageFilter.value.machine_id);
  }
  if (usageFilter.value.stock_id) {
    list = list.filter(u => u.stock_id === usageFilter.value.stock_id);
  }
  return list;
});

const paginatedUsages = computed(() => {
  const start = (usagePage.value - 1) * usagePerPage.value;
  return filteredUsages.value.slice(start, start + usagePerPage.value);
});

const formatDate = (d) => {
  if (!d) return '-';
  return new Date(d).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
};

// Usage tab: machine & stock dropdown filter helpers
const filteredMachines = computed(() => {
  if (!machineSearch.value) return machines.value;
  const q = machineSearch.value.toLowerCase();
  return machines.value.filter(m => m.name.toLowerCase().includes(q));
});

const filteredStocksBySearch = computed(() => {
  if (!stockSearch.value) return stocks.value;
  const q = stockSearch.value.toLowerCase();
  return stocks.value.filter(s =>
    s.code.toLowerCase().includes(q) || s.name.toLowerCase().includes(q)
  );
});

const handleMachineBlur = () => {
  setTimeout(() => { showMachineDropdown.value = false; }, 200);
};

const handleMachineInput = () => {
  showMachineDropdown.value = true;
  if (!machineSearch.value) {
    usageFilter.value.machine_id = '';
  }
};

const selectMachine = (m) => {
  machineSearch.value = m.name;
  usageFilter.value.machine_id = m.id;
  showMachineDropdown.value = false;
};

const clearMachineFilter = () => {
  machineSearch.value = '';
  usageFilter.value.machine_id = '';
  showMachineDropdown.value = false;
};

const handleStockBlur = () => {
  setTimeout(() => { showStockDropdown.value = false; }, 200);
};

const handleStockInput = () => {
  showStockDropdown.value = true;
  if (!stockSearch.value) {
    usageFilter.value.stock_id = '';
  }
};

const selectStock = (s) => {
  stockSearch.value = `${s.code} - ${s.name}`;
  usageFilter.value.stock_id = s.id;
  showStockDropdown.value = false;
};

const clearStockFilter = () => {
  stockSearch.value = '';
  usageFilter.value.stock_id = '';
  showStockDropdown.value = false;
};

const loadStocks = async () => {
  loading.value = true;
  try {
    const res = await axios.get('/api/stocks');
    stocks.value = res.data;
  } catch (e) {
    console.error('Failed to load stocks:', e);
  } finally {
    loading.value = false;
  }
};

const loadMachines = async () => {
  try {
    const res = await axios.get('/api/machines');
    machines.value = res.data;
  } catch (e) {
    console.error('Failed to load machines:', e);
  }
};

const loadUsages = async () => {
  usageLoading.value = true;
  try {
    const params = {};
    if (usageFilter.value.from_date) params.from_date = usageFilter.value.from_date;
    if (usageFilter.value.to_date) params.to_date = usageFilter.value.to_date;
    if (usageFilter.value.machine_id) params.machine_id = usageFilter.value.machine_id;
    if (usageFilter.value.stock_id) params.stock_id = usageFilter.value.stock_id;
    const res = await axios.get('/api/stocks/usages', { params });
    usages.value = res.data;
  } catch (e) {
    console.error('Failed to load usages:', e);
  } finally {
    usageLoading.value = false;
  }
};

const openCreate = () => {
  editingStock.value = null;
  showStockForm.value = true;
};

const openEdit = (stock) => {
  editingStock.value = stock;
  showStockForm.value = true;
};

const openRestock = (stock) => {
  restockStock.value = stock;
  showRestock.value = true;
};

const viewUsageHistory = (stock) => {
  historyStock.value = stock;
  showUsageHistory.value = true;
};

const deleteStock = async (stock) => {
  const ok = await showConfirm('Hapus Stok', `Hapus stok "${stock.name}" (${stock.code})?`);
  if (!ok) return;
  try {
    await axios.delete(`/api/stocks/${stock.id}`);
    stocks.value = stocks.value.filter(s => s.id !== stock.id);
    showAlert('success', 'Berhasil', 'Stok berhasil dihapus.');
  } catch (e) {
    if (e.response?.data?.message) {
      showAlert('error', 'Gagal', e.response.data.message);
      stocks.value = stocks.value.map(s => s.id === stock.id ? { ...s, is_active: false } : s);
    }
  }
};

const onStockSaved = (stock) => {
  const idx = stocks.value.findIndex(s => s.id === stock.id);
  const isEdit = idx >= 0;
  stock.is_low_stock = stock.quantity <= stock.limit_qty;
  if (isEdit) {
    stocks.value[idx] = stock;
  } else {
    stocks.value.push(stock);
  }
  showStockForm.value = false;
  showAlert('success', 'Berhasil', isEdit ? 'Stok berhasil diperbarui.' : 'Stok baru berhasil ditambahkan.');
};

const onRestockSaved = (stock) => {
  const idx = stocks.value.findIndex(s => s.id === stock.id);
  if (idx >= 0) {
    stock.is_low_stock = stock.quantity <= stock.limit_qty;
    stocks.value[idx] = stock;
  }
  showRestock.value = false;
  showAlert('success', 'Berhasil', 'Restock stok berhasil.');
};

const onImported = () => {
  showImport.value = false;
  loadStocks();
};

const onBulkLimitSaved = () => {
  showBulkLimit.value = false;
  loadStocks();
};

watch([search, filterCategory, filterLowStock, sortBy], () => { currentPage.value = 1; });
watch(activeTab, (tab) => {
  if (tab === 'usage' && usages.value.length === 0) {
    loadUsages();
  }
});

onMounted(() => {
  loadStocks();
  loadMachines();
});
</script>
