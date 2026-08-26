<template>
  <div class="w-full max-w-5xl mx-auto space-y-6">
    <!-- Header -->
    <PageHeader title="Daftar Mesin" subtitle="Kelola semua mesin dalam sistem">
      <template #actions>
        <div class="flex items-center gap-2 flex-wrap md:flex-nowrap">
          <Button
            v-if="isAdmin"
            @click="triggerMachineImport"
            class="inline-flex flex-row items-center bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl font-semibold text-sm px-4 py-2 whitespace-nowrap gap-2 hover:cursor-pointer min-w-fit"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
            Import Mesin
          </Button>
          <Button
            v-if="isAdmin"
            @click="triggerComponentImport"
            class="inline-flex flex-row items-center bg-teal-600 hover:bg-teal-700 text-white rounded-xl font-semibold text-sm px-4 py-2 whitespace-nowrap gap-2 hover:cursor-pointer min-w-fit"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
            Import Komponen
          </Button>
          <Button
            v-if="isAdmin"
            @click="showIndicatorImportModal = true"
            class="inline-flex flex-row items-center bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl font-semibold text-sm px-4 py-2 whitespace-nowrap gap-2 hover:cursor-pointer min-w-fit"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6 4h6"/></svg>
            Import Indikator
          </Button>
          <Button
            @click="showCalendarModal = true"
            class="inline-flex flex-row items-center bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl font-semibold text-sm px-4 py-2 whitespace-nowrap gap-2 hover:cursor-pointer min-w-fit"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            Kalender
          </Button>
          <Button
            v-if="isAdmin"
            @click="showMaintenanceWindowModal = true"
            class="inline-flex flex-row items-center bg-slate-700 hover:bg-slate-800 text-white rounded-xl font-semibold text-sm px-4 py-2 whitespace-nowrap gap-2 hover:cursor-pointer min-w-fit"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            Periode Laporan
          </Button>
          <Button
            v-if="canAddData"
            @click="openCreate"
            class="inline-flex flex-row items-center bg-linear-to-tr from-brand-brown to-brand-gradation text-white hover:opacity-90 rounded-xl font-semibold text-sm px-4 py-2 whitespace-nowrap gap-2 shadow-md hover:cursor-pointer min-w-fit"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Tambah Mesin
          </Button>
        </div>
      </template>
    </PageHeader>

    <!-- Stats Summary Cards -->
    <div class="grid grid-cols-2 md:grid-cols-5 gap-3">
      <div 
        v-for="stat in machineStatusStats" 
        :key="stat.id"
        @click="toggleStatusFilter(stat.id)"
        class="bg-white rounded-2xl border border-slate-100 shadow-sm p-4 cursor-pointer transition-all hover:shadow-md active:scale-95 relative overflow-hidden group"
        :class="[
          filterStatus === stat.id ? 'ring-2 ring-offset-2 ' + stat.ringColor : 'hover:border-slate-200'
        ]"
      >
        <div class="flex justify-between items-start">
          <div>
            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">{{ stat.label }}</p>
            <p class="text-xl font-black text-slate-800">{{ stat.count }}</p>
          </div>
          <div :class="stat.bgClass" class="p-1.5 rounded-lg text-white">
            <component :is="stat.icon" class="w-4 h-4" />
          </div>
        </div>
        <div 
          class="absolute bottom-0 left-0 h-1 transition-all duration-300"
          :class="[stat.bgClass, filterStatus === stat.id ? 'w-full' : 'w-0 group-hover:w-full']"
        ></div>
      </div>
    </div>

    <!-- Maintenance Alerts -->
    <MaintenanceAlerts
      v-if="!loading"
      :alerts="maintenanceAlerts"
      collapsible
      @click-alert="navigateToMachine($event.machine_id)"
    />

    <!-- Search & Filter Bar -->
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-4 flex flex-wrap gap-3 items-center">
      <SearchInput
        v-model="search"
        placeholder="Cari nama mesin atau deskripsi..."
        class="flex-1 min-w-50"
      />
      
      <div class="relative group">
        <select v-model="filterStatus" class="rounded-xl border border-slate-200 pl-3 pr-8 py-2 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 cursor-pointer appearance-none bg-white min-w-35">
          <option value="">Semua Status</option>
          <option value="selesai">Selesai Dicek</option>
          <option value="sebagian">Dicek Sebagian</option>
          <option value="expired">Expired / Telat</option>
          <option value="bisa_dicek">Bisa Dicek</option>
          <option value="hari_ini">Hari Ini</option>
        </select>
        <button
          v-if="filterStatus"
          @click="clearStatusFilter"
          type="button"
          class="absolute right-2 top-1/2 -translate-y-1/2 p-1 text-slate-400 hover:text-slate-600 hover:bg-slate-100 rounded-full cursor-pointer transition-colors z-10"
          title="Hapus filter status"
        >
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
        <div class="absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none text-slate-400" v-if="!filterStatus">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
        </div>
      </div>

      <div class="relative group" v-if="isManagerOrAdmin || hasBothCities">
        <select v-model="filterKota" class="rounded-xl border border-slate-200 pl-3 pr-8 py-2 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 cursor-pointer appearance-none bg-white min-w-30">
          <option value="">Semua Kota</option>
          <option value="pasuruan">Pasuruan</option>
          <option value="sby">Surabaya</option>
        </select>
        <button
          v-if="filterKota"
          @click="clearKotaFilter"
          type="button"
          class="absolute right-2 top-1/2 -translate-y-1/2 p-1 text-slate-400 hover:text-slate-600 hover:bg-slate-100 rounded-full cursor-pointer transition-colors z-10"
          title="Hapus filter kota"
        >
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
        <div class="absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none text-slate-400" v-if="!filterKota">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
        </div>
      </div>

      <div class="relative group">
        <input
          v-model="locationSearch"
          @focus="showLocationDropdown = true"
          @blur="handleLocationBlur"
          @input="handleLocationInput"
          type="text"
          placeholder="Lokasi / Area"
          class="rounded-xl border border-slate-200 pl-3 pr-8 py-2 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 w-36"
        />
        <button
          v-if="locationSearch"
          @click="clearLocationFilter"
          type="button"
          class="absolute right-2 top-1/2 -translate-y-1/2 p-1 text-slate-400 hover:text-slate-600 hover:bg-slate-100 rounded-full cursor-pointer transition-colors"
          title="Hapus filter lokasi"
        >
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
        <div
          v-if="showLocationDropdown && filteredLocations.length > 0"
          class="absolute top-full left-0 right-0 mt-1 bg-white border border-slate-200 rounded-xl shadow-lg z-50 max-h-48 overflow-y-auto"
        >
          <div
            v-for="loc in filteredLocations"
            :key="loc"
            @mousedown="selectLocation(loc)"
            class="px-3 py-2 text-sm text-slate-700 hover:bg-slate-50 cursor-pointer"
          >
            {{ loc }}
          </div>
        </div>
      </div>

      <div class="relative group">
        <input
          v-model="machineSearch"
          @focus="showMachineDropdown = true"
          @blur="handleMachineBlur"
          @input="handleMachineInput"
          type="text"
          placeholder="Pilih Mesin"
          class="rounded-xl border border-slate-200 pl-3 pr-8 py-2 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 w-44"
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
          v-if="showMachineDropdown && filteredMachinesBySearch.length > 0"
          class="absolute top-full left-0 right-0 mt-1 bg-white border border-slate-200 rounded-xl shadow-lg z-50 max-h-48 overflow-y-auto"
        >
          <div
            v-for="m in filteredMachinesBySearch"
            :key="m.id"
            @mousedown="selectMachine(m)"
            class="px-3 py-2 text-sm text-slate-700 hover:bg-slate-50 cursor-pointer"
          >
            {{ m.name }}
          </div>
        </div>
      </div>

      <div class="relative group">
        <select v-model="filterSchedule" class="rounded-xl border border-slate-200 pl-3 pr-8 py-2 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 cursor-pointer appearance-none bg-white min-w-30">
          <option value="">Semua Jadwal</option>
          <option value="overdue">Telat / Overdue</option>
          <option value="today">Hari Ini</option>
          <option value="week">Minggu Ini</option>
        </select>
        <button
          v-if="filterSchedule"
          @click="clearScheduleFilter"
          type="button"
          class="absolute right-2 top-1/2 -translate-y-1/2 p-1 text-slate-400 hover:text-slate-600 hover:bg-slate-100 rounded-full cursor-pointer transition-colors z-10"
          title="Hapus filter jadwal"
        >
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
        <div class="absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none text-slate-400" v-if="!filterSchedule">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
        </div>
      </div>

      <select v-model="sortBy" class="rounded-xl border border-slate-200 px-3 py-2 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 cursor-pointer">
        <option value="name">Nama A-Z</option>
        <option value="name_desc">Nama Z-A</option>
        <option value="condition_asc">Kondisi Terendah</option>
        <option value="condition_desc">Kondisi Tertinggi</option>
      </select>
    </div>

    <!-- Machines Table -->
    <DataTable
      :columns="machineColumns"
      :rows="paginatedMachines"
      :loading="loading"
      loading-text="Memuat data mesin..."
      loading-subtext="Mengambil data dari server"
      empty-title="Tidak ada mesin ditemukan"
      empty-subtext="Coba ubah kata kunci pencarian atau filter"
      min-width="min-w-[700px]"
      table-class="table-fixed"
      :paginate="true"
      :current-page="currentPage"
      :total-rows="filtered.length"
      :per-page="perPage"
      :show-per-page-selector="true"
      :per-page-options="[5, 10, 15, 20, 30, 50]"
      @update:current-page="currentPage = $event"
      @update:per-page="perPage = $event"
      actions-label=""
      actions-width="w-16"
      row-clickable
      @row-click="navigateToMachine($event.id)"
    >
      <!-- Kolom: Nama Mesin -->
      <template #cell-name="{ row }">
        <p class="font-bold text-slate-800 wrap-break-word line-clamp-2" :title="row.name">{{ row.name }}</p>
        <p class="text-xs text-slate-400 mt-0.5 line-clamp-1">{{ row.description }}</p>
      </template>

      <!-- Kolom: Lokasi -->
      <template #cell-location="{ row }">
        <div class="font-medium text-slate-700 text-sm wrap-break-word line-clamp-2" :title="row.location">{{ row.location ?? '-' }}</div>
        <span v-if="row.kota" class="text-[11px] font-bold text-indigo-500 uppercase tracking-wide">
          {{ row.kota === 'sby' ? 'Surabaya' : 'Pasuruan' }}
        </span>
      </template>

      <!-- Kolom: Jadwal -->
      <template #cell-schedule="{ row }">
        <div v-if="getMachineSchedule(row)" class="space-y-1">
          <div class="flex items-center gap-2 flex-wrap">
            <span class="text-sm font-medium text-slate-700">{{ formatDate(getMachineSchedule(row).next_due_date) }}</span>
            <span :class="urgencyClass(getMachineSchedule(row).next_due_date).badge" class="px-2 py-0.5 rounded-full text-xs font-bold">
              {{ urgencyClass(getMachineSchedule(row).next_due_date).label }}
            </span>
          </div>
          <span class="text-[11px] font-semibold text-indigo-500 bg-indigo-50 px-2 py-0.5 rounded-md">
            {{ frequencyLabel(getMachineSchedule(row).interval_days) }}
          </span>
        </div>
        <span v-else class="text-sm text-slate-400">-</span>
      </template>

      <!-- Kolom: PIC -->
      <template #cell-pic_mesin_id="{ row }">
        <span v-if="row.pic_mesin" class="text-sm font-semibold text-slate-700">{{ row.pic_mesin.full_name }}</span>
        <span v-else class="text-xs text-slate-400">-</span>
      </template>

      <!-- Kolom: Kondisi -->
      <template #cell-condition_pct="{ row }">
        <span :class="condClass(row.condition_pct)" class="font-bold text-base">{{ row.condition_pct }}%</span>
      </template>

      <!-- Kolom: Komponen -->
      <template #cell-components_count="{ row }">
        <div class="flex flex-col items-center gap-1">
          <span class="text-sm text-slate-600" v-if="!getMachineProgress(row).isPartiallyChecked">{{ row.components_count ?? row.components?.length ?? 0 }}</span>
          <div v-if="getMachineProgress(row).isPartiallyChecked" 
               class="flex items-center gap-1.5 px-2.5 py-1 bg-amber-100 text-amber-700 rounded-md text-xs font-black border border-amber-300 shadow-sm animate-pulse"
               title="Progres Pengecekan (Sebagian)">
            <Clock class="w-3.5 h-3.5" />
            {{ getMachineProgress(row).count }} / {{ getMachineProgress(row).total }}
          </div>
        </div>
      </template>

      <!-- Slot Actions: Hapus -->
      <template #actions="{ row }">
        <button
          v-if="canDeleteData"
          @click.stop="deleteMachine(row, $event)"
          class="p-2 text-slate-400 hover:text-red-500 hover:bg-red-50 rounded-lg cursor-pointer transition-colors"
          title="Hapus"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
        </button>
      </template>
    </DataTable>

    <!-- Create Machine Modal -->
    <MachineCreateModal v-if="showCreate" @close="showCreate = false" @saved="onMachineSaved" />

    <!-- Import Modal -->
    <MachineImportModal
      :show="showImportModal"
      :type="importType"
      :loading="importing"
      @close="showImportModal = false"
      @download-template="handleDownloadTemplate"
      @import="handleImportFile"
    />
    <IndicatorImportModal
      v-if="showIndicatorImportModal"
      @close="showIndicatorImportModal = false"
      @imported="onIndicatorImported"
    />

    <!-- Calendar Modal -->
    <MachineCalendarModal
      :show="showCalendarModal"
      :machines="allowedMachines"
      @close="showCalendarModal = false"
      @go-to-machine="goToMachine"
    />

    <!-- Maintenance Window Settings Modal (admin only) -->
    <MaintenanceWindowSettingsModal
      :show="showMaintenanceWindowModal"
      @close="showMaintenanceWindowModal = false"
      @saved="onSettingsSaved"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import axios from 'axios';
import MachineCreateModal from '../components/MachineCreateModal.vue';
import { showConfirm, showAlert } from '../composables/useAlert.js';
import { useAuth } from '../composables/useAuth.js';
import PageHeader from '../components/PageHeader.vue';
import SearchInput from '../components/SearchInput.vue';
import DataTable from '../components/DataTable.vue';
import StatCard from '../components/StatCard.vue';
import MaintenanceAlerts from '../components/MaintenanceAlerts.vue';
import MachineImportModal from '../components/MachineImportModal.vue';
import IndicatorImportModal from '../components/IndicatorImportModal.vue';
import MachineCalendarModal from '../components/MachineCalendarModal.vue';
import MaintenanceWindowSettingsModal from '../components/MaintenanceWindowSettingsModal.vue';
import { getCurrentPeriod, getMonthlyPeriods } from '../composables/useSchedulePeriods.js';
import { 
  CheckCircle2, 
  Clock, 
  AlertTriangle, 
  Calendar, 
  AlertCircle,
  HelpCircle
} from 'lucide-vue-next';

const props = defineProps({
  initialMachines: {
    type: Array,
    default: null
  },
  machines: {
    type: Array,
    default: null
  },
  initialSchedules: {
    type: Array,
    default: null
  },
  schedules: {
    type: Array,
    default: null
  },
  initialNotifications: {
    type: Array,
    default: null
  },
  notifications: {
    type: Array,
    default: null
  }
});

const { isManagerOrAdmin, isAdmin, user, hasBothCities, canAddData, canDeleteData } = useAuth();
const machines = ref(props.machines || props.initialMachines || []);
const schedules = ref(props.schedules || props.initialSchedules || []);
const notifications = ref(props.notifications || props.initialNotifications || []);
const loading = ref(!props.machines && !props.initialMachines);
const search = ref('');
const filterSchedule = ref('');
const sortBy = ref('name');
const filterKota = ref('');
const locationSearch = ref('');
const showLocationDropdown = ref(false);
const selectedLocation = ref('');
const machineSearch = ref('');
const selectedMachineId = ref('');
const showMachineDropdown = ref(false);
const filterStatus = ref('');
const showCreate = ref(false);
const currentPage = ref(1);
const perPage = ref(10);
const alertDaysBefore = ref(7);
const daysBeforeSetting = ref(2);

const allowedMachines = computed(() => {
  let list = machines.value;
  const city = user.value?.city;
  if (city && city !== 'both') {
    list = list.filter(m => m.kota === city);
  }
  return list;
});

const filteredBase = computed(() => {
  let list = allowedMachines.value;

  // Apply kota filter (manager/admin only)
  if (filterKota.value) {
    list = list.filter(m => m.kota === filterKota.value);
  }

  // Apply location filter
  if (selectedLocation.value) {
    list = list.filter(m => m.location === selectedLocation.value);
  }

  // Apply machine filter
  if (selectedMachineId.value) {
    list = list.filter(m => m.id === selectedMachineId.value);
  }

  // Apply search filter
  if (search.value) {
    const q = search.value.toLowerCase();
    list = list.filter(m =>
      m.name.toLowerCase().includes(q) ||
      (m.location ?? '').toLowerCase().includes(q) ||
      (m.description ?? '').toLowerCase().includes(q)
    );
  }

  return list;
});

const machineColumns = [
  { key: 'name',             label: 'Nama Mesin', width: 'w-[25%]', cellClass: 'whitespace-normal overflow-hidden' },
  { key: 'location',         label: 'Lokasi',     width: 'w-[15%]', cellClass: 'whitespace-normal overflow-hidden' },
  { key: 'schedule',         label: 'Jadwal',     width: 'w-[20%]' },
  { key: 'pic_mesin_id',     label: 'PIC',        width: 'w-[15%]' },
  { key: 'condition_pct',    label: 'Kondisi',    width: 'w-[10%]', headerClass: 'text-center' },
  { key: 'components_count', label: 'Komponen',   width: 'w-[10%]', headerClass: 'text-center', cellClass: 'text-center' },
];

const showImportModal = ref(false);
const importing = ref(false);
const showIndicatorImportModal = ref(false);
const showCalendarModal = ref(false);
const showMaintenanceWindowModal = ref(false);

const loadData = async () => {
  try {
    const [machinesRes, notifRes, settingsRes] = await Promise.all([
      axios.get('/api/machines'),
      axios.get('/api/schedules/notifications'),
      axios.get('/api/settings/maintenance-window')
    ]);
    machines.value = machinesRes.data;
    notifications.value = notifRes.data;
    alertDaysBefore.value = settingsRes.data.alert_days_before ?? 7;
    daysBeforeSetting.value = settingsRes.data.days_before ?? 2;
  } catch (e) {
    console.error(e);
  } finally {
    loading.value = false;
  }
};

const navigateToMachine = (machineId) => {
  router.visit(`/machine/${machineId}`);
};

onMounted(() => {
  if (!props.machines && !props.initialMachines) {
    loadData();
  }
  window.addEventListener('refresh-data', loadData);
});
onUnmounted(() => window.removeEventListener('refresh-data', loadData));

const getDaysUntil = (dateStr) => {
  if (!dateStr) return null;
  const d = new Date(dateStr);
  d.setHours(0,0,0,0);
  const t = new Date(); t.setHours(0,0,0,0);
  return Math.ceil((d - t) / (1000*60*60*24));
};

const getMachineSchedule = (machine) => {
  if (!machine.schedules || machine.schedules.length === 0) return null;
  const active = machine.schedules
    .filter(s => s.is_active !== false)
    .sort((a, b) => new Date(a.next_due_date) - new Date(b.next_due_date));
  if (active.length === 0) return null;

  // Override next_due_date with the canonical current-period due date (same source
  // as MachineDetail page) so all schedule displays/filters/stats stay consistent —
  // the raw column can be from next month already for 1x/2x-per-month schedules.
  const period = getCurrentPeriod(machine.schedules ?? []);
  return period ? { ...active[0], next_due_date: period.due } : active[0];
};

const urgencyClass = (dateStr) => {
  const days = getDaysUntil(dateStr);
  if (days === null) return { badge: 'bg-slate-100 text-slate-600', label: '-' };
  if (days < 0) return { badge: 'bg-red-100 text-red-700', label: `Telat ${Math.abs(days)} hari` };
  if (days === 0) return { badge: 'bg-amber-100 text-amber-700', label: 'Hari ini' };
  if (days <= 7) return { badge: 'bg-blue-100 text-blue-700', label: `${days} hari lagi` };
  return { badge: 'bg-slate-100 text-slate-600', label: `${days} hari lagi` };
};

const frequencyLabel = (days) => {
  const map = { 7: '4x/bulan', 14: '2x/bulan', 28: '1x/bulan', 56: '1x/2 bulan', 84: '1x/3 bulan' };
  return map[Number(days)] ?? `Setiap ${days} hari`;
};

const formatDate = (d) => {
  if (!d) return '-';
  return new Date(d).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
};

const getMachineProgress = (machine) => {
  const total = machine.components_count ?? machine.components?.length ?? 0;
  if (total === 0) return { count: 0, total: 0, isPartiallyChecked: false };

  const today = new Date();
  today.setHours(0, 0, 0, 0);

  // Get all periods for the current month to aggregate progress across all of them
  const allPeriods = getMonthlyPeriods(machine.schedules ?? [], today);
  if (!allPeriods.length) {
    return { count: 0, total, isPartiallyChecked: false };
  }

  // Collect valid schedule IDs and period dates for this month
  // Use local date formatting (not toISOString which shifts to UTC)
  const fmtDate = (d) => {
    const y = d.getFullYear();
    const m = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    return `${y}-${m}-${day}`;
  };
  const scheduleIds = new Set(allPeriods.map(p => String(p.scheduleId)));
  const periodDates = new Set(allPeriods.map(p => fmtDate(p.due)));

  const completedComponentIds = new Set(
    (machine.records || [])
      .filter(record =>
        record.status === 'completed' &&
        !record.is_unscheduled &&
        record.latest_approval?.decision !== 'rejected' &&
        scheduleIds.has(String(record.schedule_id)) &&
        periodDates.has(String(record.scheduled_period_date).slice(0, 10))
      )
      .flatMap(record => (record.actions || []).map(action => String(action.machine_component_id)))
  );

  const count = Math.min(completedComponentIds.size, total);
  const isPartiallyChecked = count > 0 && count < total;
  return { count, total, isPartiallyChecked };
};

const stats = computed(() => {
  const m = allowedMachines.value;
  const machineSchedules = m.map(machine => getMachineSchedule(machine)).filter(s => s !== null);
  return [
    { label: 'Telat',      value: machineSchedules.filter(s => getDaysUntil(s.next_due_date) < 0).length,                                    color: 'red' },
    { label: 'Hari Ini',   value: machineSchedules.filter(s => getDaysUntil(s.next_due_date) === 0).length,                                   color: 'amber' },
    { label: 'Minggu Ini', value: machineSchedules.filter(s => getDaysUntil(s.next_due_date) > 0 && getDaysUntil(s.next_due_date) <= 7).length, color: 'blue' },
  ];
});

const uniqueLocations = computed(() => {
  const locations = new Set();
  let list = allowedMachines.value;
  if (filterKota.value) {
    list = list.filter(m => m.kota === filterKota.value);
  }
  list.forEach(m => {
    if (m.location) locations.add(m.location);
  });
  return Array.from(locations).sort();
});

const filteredLocations = computed(() => {
  if (!locationSearch.value) return uniqueLocations.value;
  const q = locationSearch.value.toLowerCase();
  return uniqueLocations.value.filter(loc => loc.toLowerCase().includes(q));
});

const uniqueMachines = computed(() => {
  let list = allowedMachines.value;
  if (filterKota.value) {
    list = list.filter(m => m.kota === filterKota.value);
  }
  if (selectedLocation.value) {
    list = list.filter(m => m.location === selectedLocation.value);
  }
  return [...list].sort((a, b) => a.name.localeCompare(b.name));
});

const filteredMachinesBySearch = computed(() => {
  if (!machineSearch.value) return uniqueMachines.value;
  const q = machineSearch.value.toLowerCase();
  return uniqueMachines.value.filter(m => m.name.toLowerCase().includes(q));
});

const machineStatusStats = computed(() => {
  const today = new Date();
  today.setHours(0, 0, 0, 0);

  const stats = {
    selesai: 0,
    sebagian: 0,
    expired: 0,
    bisa_dicek: 0,
    hari_ini: 0
  };

  filteredBase.value.forEach(machine => {
    const schedule = getMachineSchedule(machine);
    if (!schedule) return;

    const dueDate = new Date(schedule.next_due_date);
    dueDate.setHours(0, 0, 0, 0);
    const daysUntil = Math.ceil((dueDate - today) / (1000 * 60 * 60 * 24));

    // Calculate completion in current period
    const periodStart = new Date(dueDate.getFullYear(), dueDate.getMonth(), 1);
    periodStart.setHours(0, 0, 0, 0);

    const periodRecords = (machine.records || []).filter(r => {
      if (r.status !== 'completed') return false;
      const approvalStatus = r.latest_approval?.decision ?? 'pending';
      if (approvalStatus === 'rejected') return false;
      const recDate = new Date(r.maintenance_date); recDate.setHours(0, 0, 0, 0);
      return recDate >= periodStart && recDate <= today;
    });

    const checkedComponentIds = new Set();
    periodRecords.forEach(r => {
      (r.actions || []).forEach(a => {
        if (a.machine_component_id) checkedComponentIds.add(a.machine_component_id);
      });
    });

    const totalComponents = machine.components_count ?? machine.components?.length ?? 0;
    let checkedCount = 0;
    if (machine.components) {
      machine.components.forEach(c => {
        if (checkedComponentIds.has(c.id)) checkedCount++;
      });
    } else {
      checkedCount = Math.min(checkedComponentIds.size, totalComponents);
    }

    const isFullyChecked = totalComponents > 0 && checkedCount === totalComponents;
    const isPartiallyChecked = checkedCount > 0 && checkedCount < totalComponents;

    if (isFullyChecked) stats.selesai++;
    else if (isPartiallyChecked) stats.sebagian++;

    if (!isFullyChecked) {
      if (daysUntil < 0) stats.expired++;
      if (daysUntil >= 0 && daysUntil <= 5) stats.bisa_dicek++;
      if (daysUntil === 0) stats.hari_ini++;
    }
  });

  return [
    { id: 'selesai',   label: 'Selesai',    count: stats.selesai,   icon: CheckCircle2, bgClass: 'bg-emerald-500', ringColor: 'ring-emerald-500' },
    { id: 'sebagian',  label: 'Sebagian',   count: stats.sebagian,  icon: Clock,        bgClass: 'bg-blue-500',    ringColor: 'ring-blue-500' },
    { id: 'expired',   label: 'Expired',    count: stats.expired,   icon: AlertCircle,  bgClass: 'bg-red-500',     ringColor: 'ring-red-500' },
    { id: 'bisa_dicek',label: 'Bisa Dicek', count: stats.bisa_dicek,icon: Calendar,     bgClass: 'bg-amber-500',   ringColor: 'ring-amber-500' },
    { id: 'hari_ini',  label: 'Hari Ini',   count: stats.hari_ini,  icon: AlertTriangle,bgClass: 'bg-orange-500',  ringColor: 'ring-orange-500' },
  ];
});

const toggleStatusFilter = (id) => {
  if (filterStatus.value === id) {
    filterStatus.value = '';
  } else {
    filterStatus.value = id;
  }
};

const handleLocationBlur = () => {
  setTimeout(() => { showLocationDropdown.value = false; }, 200);
};

const handleLocationInput = () => {
  showLocationDropdown.value = true;
  if (!locationSearch.value) {
    selectedLocation.value = '';
  }
};

const selectLocation = (loc) => {
  locationSearch.value = loc;
  selectedLocation.value = loc;
  showLocationDropdown.value = false;
};

const clearLocationFilter = () => {
  locationSearch.value = '';
  selectedLocation.value = '';
  showLocationDropdown.value = false;
};

const handleMachineBlur = () => {
  setTimeout(() => { showMachineDropdown.value = false; }, 200);
};

const handleMachineInput = () => {
  showMachineDropdown.value = true;
  if (!machineSearch.value) {
    selectedMachineId.value = '';
  }
};

const selectMachine = (m) => {
  machineSearch.value = m.name;
  selectedMachineId.value = m.id;
  showMachineDropdown.value = false;
};

const clearMachineFilter = () => {
  machineSearch.value = '';
  selectedMachineId.value = '';
  showMachineDropdown.value = false;
};

const clearKotaFilter = () => {
  filterKota.value = '';
};

const clearScheduleFilter = () => {
  filterSchedule.value = '';
};

const clearStatusFilter = () => {
  filterStatus.value = '';
};

// Maintenance alerts - show from H-1 (1 day before) and hide if maintenance already done
const maintenanceAlerts = computed(() => {
  const today = new Date();
  today.setHours(0, 0, 0, 0);

  const isSameDay = (d1, d2) => {
    const date1 = new Date(d1);
    const date2 = new Date(d2);
    return date1.getFullYear() === date2.getFullYear() &&
           date1.getMonth() === date2.getMonth() &&
           date1.getDate() === date2.getDate();
  };

  return notifications.value.map(notif => {
    const machine = allowedMachines.value.find(m => m.id === notif.machine_id);
    if (!machine) return null;

    const isUnlockPriority = notif.is_unlock_priority === true ||
      (machine.unlock_status === 'approved' && (() => {
        if (machine.unlock_approved_at) {
          const d = new Date(machine.unlock_approved_at);
          return d.getMonth() === today.getMonth() && d.getFullYear() === today.getFullYear();
        }
        // Fallback: check unlock_expires_at is still valid
        return machine.unlock_expires_at && new Date(machine.unlock_expires_at) > new Date();
      })());

    // Use the canonical current-period due date (same source as MachineDetail page)
    // instead of the raw schedule's next_due_date, which may have already advanced
    // past the period actually being tracked for schedules that occur 1x/2x a month.
    const period = getCurrentPeriod(machine.schedules ?? []);
    const dueDate = period ? period.due : (() => { const d = new Date(notif.next_due_date); d.setHours(0, 0, 0, 0); return d; })();

    const daysUntil = period ? period.diffDays : Math.ceil((dueDate - today) / (1000 * 60 * 60 * 24));
    // Alert window: H-[alertDaysBefore] up to H (today).
    // Unlock-priority machines show even if overdue.
    if (!isUnlockPriority && (daysUntil < 0 || daysUntil > alertDaysBefore.value)) return null;

    // Period start for this schedule: 5 days before due date or start of month, whichever is earlier
    const periodStart = new Date(dueDate);
    periodStart.setDate(dueDate.getDate() - 5);
    if (periodStart < today) periodStart.setTime(today.getTime());
    periodStart.setHours(0, 0, 0, 0);

    // Calculate component check status within this period (periodStart..today)
    const periodRecords = (machine.records || []).filter(r => {
      if (r.status !== 'completed') return false;
      const approvalStatus = r.latest_approval?.decision ?? 'pending';
      if (approvalStatus === 'rejected') return false;
      const recDate = new Date(r.maintenance_date); recDate.setHours(0, 0, 0, 0);
      return recDate >= periodStart && recDate <= today;
    });
    const checkedComponentIds = new Set();
    periodRecords.forEach(r => {
      (r.actions || []).forEach(a => {
        if (a.machine_component_id) checkedComponentIds.add(a.machine_component_id);
      });
    });

    const totalComponents = machine.components_count ?? machine.components?.length ?? 0;
    let checkedCount = 0;
    if (machine.components) {
      machine.components.forEach(c => {
        if (checkedComponentIds.has(c.id)) checkedCount++;
      });
    } else {
      checkedCount = Math.min(checkedComponentIds.size, totalComponents);
    }

    const uncheckedCount = totalComponents - checkedCount;
    const isFullyChecked = totalComponents > 0 && checkedCount === totalComponents;
    const isPartiallyChecked = checkedCount > 0 && checkedCount < totalComponents;

    // Hide alert only if fully checked in this period
    if (isFullyChecked && daysUntil > 0) return null;

    return {
      ...notif,
      next_due_date: dueDate,
      machine,
      totalComponents,
      checkedCount,
      uncheckedCount,
      isFullyChecked,
      isPartiallyChecked,
      daysUntil,
      daysBeforeSetting: daysBeforeSetting.value,
      isUnlockPriority
    };
  }).filter(item => item !== null)
    .sort((a, b) => {
      // Unlock-priority items first
      if (a.isUnlockPriority && !b.isUnlockPriority) return -1;
      if (!a.isUnlockPriority && b.isUnlockPriority) return 1;
      return a.daysUntil - b.daysUntil;
    })
    .filter((item, index, self) => self.findIndex(i => i.machine_id === item.machine_id) === index);
});

const filtered = computed(() => {
  let list = filteredBase.value;

  // Apply status filter
  if (filterStatus.value) {
    const today = new Date(); today.setHours(0, 0, 0, 0);
    
    list = list.filter(machine => {
      const schedule = getMachineSchedule(machine);
      if (!schedule) return false;

      const dueDate = new Date(schedule.next_due_date);
      dueDate.setHours(0, 0, 0, 0);
      const daysUntil = Math.ceil((dueDate - today) / (1000 * 60 * 60 * 24));

      // Completion logic
      const periodStart = new Date(dueDate.getFullYear(), dueDate.getMonth(), 1);
      periodStart.setHours(0, 0, 0, 0);
      const periodRecords = (machine.records || []).filter(r => {
        if (r.status !== 'completed') return false;
        const appStat = r.latest_approval?.decision ?? 'pending';
        if (appStat === 'rejected') return false;
        const recDate = new Date(r.maintenance_date); recDate.setHours(0,0,0,0);
        return recDate >= periodStart && recDate <= today;
      });
      const checkedIds = new Set();
      periodRecords.forEach(r => (r.actions || []).forEach(a => { if (a.machine_component_id) checkedIds.add(a.machine_component_id); }));
      const total = machine.components_count ?? machine.components?.length ?? 0;
      let count = 0;
      if (machine.components) machine.components.forEach(c => { if (checkedIds.has(c.id)) count++; });
      else count = Math.min(checkedIds.size, total);

      const isFullyChecked = total > 0 && count === total;
      const isPartiallyChecked = count > 0 && count < total;

      if (filterStatus.value === 'selesai') return isFullyChecked;
      if (filterStatus.value === 'sebagian') return isPartiallyChecked;
      
      if (!isFullyChecked) {
        if (filterStatus.value === 'expired') return daysUntil < 0;
        if (filterStatus.value === 'bisa_dicek') return daysUntil >= 0 && daysUntil <= 5;
        if (filterStatus.value === 'hari_ini') return daysUntil === 0;
      }
      return false;
    });
  }

  // Apply schedule filter (backward compatible)
  if (filterSchedule.value) {
    list = list.filter(m => {
      const schedule = getMachineSchedule(m);
      if (!schedule) return false;
      const days = getDaysUntil(schedule.next_due_date);
      if (filterSchedule.value === 'overdue') return days < 0;
      if (filterSchedule.value === 'today') return days === 0;
      if (filterSchedule.value === 'week') return days > 0 && days <= 7;
      if (filterSchedule.value === 'upcoming') return days > 7;
      return true;
    });
  }

  // Apply sort
  if (sortBy.value === 'name') list = [...list].sort((a, b) => a.name.localeCompare(b.name));
  if (sortBy.value === 'name_desc') list = [...list].sort((a, b) => b.name.localeCompare(a.name));
  if (sortBy.value === 'condition_asc') list = [...list].sort((a, b) => a.condition_pct - b.condition_pct);
  if (sortBy.value === 'condition_desc') list = [...list].sort((a, b) => b.condition_pct - a.condition_pct);

  return list;
});

const paginatedMachines = computed(() => {
  const start = (currentPage.value - 1) * perPage.value;
  return filtered.value.slice(start, start + perPage.value);
});

watch(filterKota, () => {
  clearLocationFilter();
  clearMachineFilter();
});

watch(selectedLocation, () => {
  clearMachineFilter();
});

watch([search, filterSchedule, filterStatus, sortBy, filterKota, selectedLocation, selectedMachineId, machineSearch, perPage], () => { currentPage.value = 1; });

const openCreate = () => { showCreate.value = true; };
const onMachineSaved = async () => {
  showCreate.value = false;
  await loadData();
  showAlert('success', 'Berhasil!', 'Mesin baru berhasil ditambahkan.');
};

const onSettingsSaved = (newSettings) => {
  alertDaysBefore.value = newSettings.alert_days_before ?? 7;
  daysBeforeSetting.value = newSettings.days_before ?? 2;
};

const deleteMachine = async (machine, event) => {
  event.stopPropagation();
  if (!canDeleteData.value) return;
  const ok = await showConfirm('Hapus Mesin', `Apakah Anda yakin ingin menghapus "${machine.name}"? Semua data terkait (komponen, jadwal, riwayat) akan ikut terhapus.`);
  if (!ok) return;
  try {
    await axios.delete(`/api/machines/${machine.id}`);
    await loadData();
    showAlert('success', 'Dihapus!', `Mesin "${machine.name}" berhasil dihapus.`);
  } catch (e) {
    showAlert('error', 'Gagal!', 'Gagal menghapus mesin: ' + (e.response?.data?.message || e.message));
  }
};

const goToMachine = (machineId) => { navigateToMachine(machineId); };

const statusClass = (s) => ({
  active: 'bg-green-50 text-green-700',
  maintenance: 'bg-amber-50 text-amber-700',
  inactive: 'bg-slate-100 text-slate-600',
}[s] ?? 'bg-slate-100 text-slate-600');

const statusDotClass = (s) => ({
  active: 'bg-green-500',
  maintenance: 'bg-amber-500',
  inactive: 'bg-slate-400',
}[s] ?? 'bg-slate-400');

const condClass = (pct) => pct < 50 ? 'text-red-500' : pct < 80 ? 'text-amber-500' : 'text-green-500';

const importType = ref('machine'); // 'machine' or 'component'

const triggerMachineImport = () => {
  importType.value = 'machine';
  showImportModal.value = true;
};

const triggerComponentImport = () => {
  importType.value = 'component';
  showImportModal.value = true;
};

const handleImportFile = (file) => {
  if (!file) return;
  if (importType.value === 'machine') {
    importMachines(file);
  } else {
    importComponentsGlobal(file);
  }
};

const loadSheetJS = () => {
  return new Promise((resolve) => {
    if (window.XLSX) {
      resolve(window.XLSX);
      return;
    }
    const script = document.createElement('script');
    script.src = 'https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js';
    script.onload = () => resolve(window.XLSX);
    document.head.appendChild(script);
  });
};

const downloadMachineTemplate = async () => {
  try {
    const XLSX = await loadSheetJS();
    const headers = [
      ['Kode Mesin', 'Nama Mesin', 'Deskripsi', 'Kondisi (%)', 'Lokasi', 'Kota (psn/sby)', 'Status', 'Email PIC', 'Frekuensi Maintenance']
    ];
    const rows = [
      ['LL-BLR-01', 'Boiler Utama', 'Mesin pemanas uap utama pabrik', 95, 'Gedung A-1', 'psn', 'active', 'pic@ladanglima.com', '1x sebulan'],
      ['LL-PKG-01', 'Mesin Packaging 1', 'Mesin pengemas tepung singkong otomatis', 80, 'Gedung B-2', 'sby', 'active', 'pic@ladanglima.com', '2x sebulan']
    ];
    
    const wb = XLSX.utils.book_new();
    const ws = XLSX.utils.aoa_to_sheet([...headers, ...rows]);
    
    ws['!cols'] = [
      { wch: 15 }, // Kode Mesin
      { wch: 20 }, // Nama Mesin
      { wch: 35 }, // Deskripsi
      { wch: 15 }, // Kondisi (%)
      { wch: 15 }, // Lokasi
      { wch: 20 }, // Kota
      { wch: 15 }, // Status
      { wch: 25 }, // Email PIC
      { wch: 25 }, // Frekuensi Maintenance
    ];
    
    XLSX.utils.book_append_sheet(wb, ws, 'Template Import Mesin');
    XLSX.writeFile(wb, 'Format_Import_Mesin.xlsx');
  } catch (err) {
    console.error('Template download failed:', err);
    showAlert('error', 'Gagal!', 'Gagal mendownload template Excel.');
  }
};

const parseIndicatorCell = (cellText) => {
  const lines = String(cellText ?? '').split(/\n|\r\n/).map(l => l.trim()).filter(l => l);
  const result = [];
  for (const line of lines) {
    const colonIdx = line.indexOf(':');
    if (colonIdx === -1) continue;
    const name = line.substring(0, colonIdx).trim();
    const description = line.substring(colonIdx + 1).trim();
    if (name && description) result.push({ name, description });
  }
  return result;
};

const handleDownloadTemplate = (fmt) => {
  if (fmt === 'machine') {
    downloadMachineTemplate();
  } else {
    downloadComponentTemplate(fmt);
  }
};

const downloadComponentTemplate = async (format) => {
  try {
    const XLSX = await loadSheetJS();
    let headers, rows, sheetName, fileName, cols;

    if (format === 'A') {
      headers = [['Kode Mesin', 'Nama Komponen', 'Qty', 'Satuan']];
      rows = [
        ['LL-BLR-01', 'Piston Cylinder Boiler', 2, 'Pcs'],
        ['LL-PKG-01', 'Thermostat Digital TC-40', 1, 'Unit'],
      ];
      sheetName = 'Format A - Tambah Cepat';
      fileName = 'Format_A_Import_Komponen_Tambah_Cepat.xlsx';
      cols = [
        { wch: 15 }, { wch: 30 }, { wch: 10 }, { wch: 10 }
      ];
    } else if (format === 'C') {
      headers = [['Kode Mesin', 'Kategori', 'Nama Komponen', 'Spesifikasi', 'Jumlah (Qty)', 'Satuan', 'Kesulitan (ringan/sedang/berat)', 'Kondisi Awal (%)']];
      rows = [
        ['LL-BLR-01', 'Suku Cadang Utama', 'Piston Cylinder Boiler', 'Stainless Steel 316 100mm', 2, 'Pcs', 'sedang', 100],
        ['LL-PKG-01', 'Sensor & Kontrol', 'Thermostat Digital TC-40', 'Range -50C to 200C', 1, 'Unit', 'ringan', 90],
      ];
      sheetName = 'Format C - Detail Tanpa Indikator';
      fileName = 'Format_C_Import_Komponen_Detail.xlsx';
      cols = [
        { wch: 15 }, { wch: 20 }, { wch: 25 }, { wch: 30 }, { wch: 15 }, { wch: 15 }, { wch: 25 }, { wch: 20 }
      ];
    } else {
      // Format B (default)
      headers = [['Kode Mesin', 'Kategori', 'Nama Komponen', 'Spesifikasi', 'Jumlah (Qty)', 'Satuan', 'Kesulitan (ringan/sedang/berat)', 'Kondisi Awal (%)', 'Indikator']];
      rows = [
        ['LL-BLR-01', 'Suku Cadang Utama', 'Piston Cylinder Boiler', 'Stainless Steel 316 100mm', 2, 'Pcs', 'sedang', 100, 'Visual: Casing utuh, tidak ada keretakan.\nKelistrikan: Tegangan stabil sesuai spesifikasi.'],
        ['LL-PKG-01', 'Sensor & Kontrol', 'Thermostat Digital TC-40', 'Range -50C to 200C', 1, 'Unit', 'ringan', 90, 'Akurasi: Suhu terbaca sesuai alat ukur standar.\nKebersihan: Sensor bebas debu dan kotoran.'],
      ];
      sheetName = 'Format B - Massal Lengkap';
      fileName = 'Format_B_Import_Komponen_Massal.xlsx';
      cols = [
        { wch: 15 }, { wch: 20 }, { wch: 25 }, { wch: 30 }, { wch: 15 }, { wch: 15 }, { wch: 25 }, { wch: 20 }, { wch: 60 }
      ];
    }

    const wb = XLSX.utils.book_new();
    const ws = XLSX.utils.aoa_to_sheet([...headers, ...rows]);
    ws['!cols'] = cols;
    XLSX.utils.book_append_sheet(wb, ws, sheetName);
    XLSX.writeFile(wb, fileName);
  } catch (err) {
    console.error('Template download failed:', err);
    showAlert('error', 'Gagal!', 'Gagal mendownload template Excel.');
  }
};

const frequencyLabelMap = {
  '4x sebulan': 7,
  '2x sebulan': 14,
  '1x sebulan': 28,
  '1x per 2 bulan': 56,
  '1x per 3 bulan': 84,
};

const parseFrequency = (val) => {
  if (!val && val !== 0) return null;
  const key = val.toString().trim().toLowerCase();
  for (const [label, days] of Object.entries(frequencyLabelMap)) {
    if (label.toLowerCase() === key) return days;
  }
  const num = parseInt(val);
  if (!isNaN(num) && num > 0) return num;
  return null;
};

const importMachines = async (file) => {
  if (!file) return;
  importing.value = true;
  try {
    const XLSX = await loadSheetJS();
    const reader = new FileReader();
    
    reader.onload = async (e) => {
      try {
        const data = new Uint8Array(e.target.result);
        const workbook = XLSX.read(data, { type: 'array' });
        
        const firstSheetName = workbook.SheetNames[0];
        const worksheet = workbook.Sheets[firstSheetName];
        
        const rows = XLSX.utils.sheet_to_json(worksheet, { header: 1 });
        if (rows.length < 2) {
          showAlert('error', 'Gagal!', 'File Excel kosong atau tidak memiliki baris data.');
          importing.value = false;
          return;
        }
        
        const mappedMachines = [];
        for (let i = 1; i < rows.length; i++) {
          const row = rows[i];
          if (row.length === 0 || !row[0]) continue;
          
          mappedMachines.push({
            kode: row[0]?.toString()?.trim() || '',
            name: row[1]?.toString()?.trim() || '',
            description: row[2]?.toString()?.trim() || null,
            condition_pct: parseFloat(row[3]) || 100,
            location: row[4]?.toString()?.trim() || null,
            kota: (() => {
              const rk = row[5]?.toString()?.trim()?.toLowerCase() || '';
              return (rk === 'sby' || rk === 'surabaya') ? 'sby' : 'pasuruan';
            })(),
            status: row[6]?.toString()?.trim()?.toLowerCase() || 'active',
            pic_email: row[7]?.toString()?.trim() || null,
            maintenance_duration: parseFrequency(row[8]),
          });
        }
        
        if (mappedMachines.length === 0) {
          showAlert('error', 'Gagal!', 'Tidak menemukan baris data mesin yang valid.');
          importing.value = false;
          return;
        }
        
        const res = await axios.post('/api/machines/import', { machines: mappedMachines });
        showAlert('success', 'Berhasil!', res.data.message || `Berhasil mengimpor ${mappedMachines.length} mesin.`);
        showImportModal.value = false;
        await loadData();
      } catch (err) {
        console.error('File parsing/import failed:', err);
        showAlert('error', 'Gagal!', 'Gagal memproses file: ' + (err.response?.data?.message || err.message));
      } finally {
        importing.value = false;
      }
    };
    
    reader.readAsArrayBuffer(file);
  } catch (err) {
    console.error('Import failed:', err);
    showAlert('error', 'Gagal!', 'Terjadi kesalahan sistem.');
    importing.value = false;
  }
};

const formatErrors = (err) => {
  const errors = err.response?.data?.errors;
  if (errors && Object.keys(errors).length) {
    return Object.entries(errors)
      .map(([field, msgs]) => `${field}: ${Array.isArray(msgs) ? msgs.join(', ') : msgs}`)
      .join(' | ');
  }
  return err.response?.data?.message || err.message;
};

const importComponentsGlobal = async (file) => {
  if (!file) return;
  importing.value = true;
  try {
    const XLSX = await loadSheetJS();
    const reader = new FileReader();
    
    reader.onload = async (e) => {
      try {
        const data = new Uint8Array(e.target.result);
        const workbook = XLSX.read(data, { type: 'array' });
        
        const firstSheetName = workbook.SheetNames[0];
        const worksheet = workbook.Sheets[firstSheetName];
        
        const rows = XLSX.utils.sheet_to_json(worksheet, { header: 1 });
        if (rows.length < 2) {
          showAlert('error', 'Gagal!', 'File Excel kosong atau tidak memiliki baris data.');
          importing.value = false;
          return;
        }

        const headers = (rows[0] ?? []).map(h => String(h ?? '').toLowerCase().trim());
        const findIdx = (keys) => {
          for (const k of keys) {
            const idx = headers.findIndex(h => h.includes(k));
            if (idx !== -1) return idx;
          }
          return -1;
        };
        const machineCodeIdx = findIdx(['kode mesin', 'machine']);
        const categoryIdx    = findIdx(['kategori']);
        const nameIdx        = findIdx(['nama komponen', 'komponen']);
        const specIdx        = findIdx(['spesifikasi', 'spec']);
        const qtyIdx         = findIdx(['jumlah', 'qty']);
        const unitIdx        = findIdx(['satuan', 'unit']);
        const conditionIdx   = findIdx(['kondisi awal', 'kondisi']);
        const difficultyIdx  = findIdx(['kesulitan', 'difficulty']);
        const indicatorIdx   = findIdx(['indikator', 'parameter']);

        const fallback = (idx, fb) => idx !== -1 ? idx : fb;
        const cMachineCode = fallback(machineCodeIdx, 0);
        const cCategory    = fallback(categoryIdx, 1);
        const cName        = fallback(nameIdx, 2);
        const cSpec        = fallback(specIdx, 3);
        const cQty         = fallback(qtyIdx, 4);
        const cUnit        = fallback(unitIdx, 5);
        const cCondition   = fallback(conditionIdx, 7);
        const cDifficulty  = fallback(difficultyIdx, 6);
        const cIndicator   = fallback(indicatorIdx, 8);

        const mappedComponents = [];
        for (let i = 1; i < rows.length; i++) {
          const row = rows[i];
          if (!row || row.every(c => String(c ?? '').trim() === '')) continue;

          const machineCode = row[cMachineCode]?.toString()?.trim();
          const name = row[cName]?.toString()?.trim();
          if (!machineCode || !name) continue;

          const maybeDifficulty = row[cDifficulty]?.toString()?.trim().toLowerCase() || '';
          const hasDifficulty = ['ringan', 'sedang', 'berat'].includes(maybeDifficulty);
          const conditionIndex = hasDifficulty ? cCondition : cDifficulty;
          const indicatorText = row[cIndicator]?.toString() ?? '';
          const indicators = parseIndicatorCell(indicatorText);

          mappedComponents.push({
            machine_code: machineCode,
            category: row[cCategory]?.toString()?.trim() || '',
            name,
            specification: row[cSpec]?.toString()?.trim() || null,
            qty: parseInt(row[cQty]) || 1,
            unit: row[cUnit]?.toString()?.trim() || 'Pcs',
            difficulty: hasDifficulty ? maybeDifficulty : null,
            last_condition_pct: parseFloat(row[conditionIndex]) || 100,
            indicators,
          });
        }

        if (mappedComponents.length === 0) {
          showAlert('error', 'Gagal!', 'Tidak menemukan baris data komponen yang valid. Pastikan kolom "Kode Mesin" dan "Nama Komponen" sudah terisi.');
          importing.value = false;
          return;
        }

        const res = await axios.post('/api/components/import-global', { components: mappedComponents });
        showAlert('success', 'Berhasil!', res.data.message || `Berhasil mengimpor ${mappedComponents.length} komponen.`);
        showImportModal.value = false;
        await loadData();
      } catch (err) {
        console.error('File parsing/import failed:', err);
        showAlert('error', 'Gagal!', 'Gagal memproses file: ' + formatErrors(err));
      } finally {
        importing.value = false;
      }
    };
    
    reader.readAsArrayBuffer(file);
  } catch (err) {
    console.error('Import failed:', err);
    showAlert('error', 'Gagal!', 'Terjadi kesalahan sistem.');
    importing.value = false;
  }
};

const onIndicatorImported = async () => {
  showIndicatorImportModal.value = false;
  await loadData();
  showAlert('success', 'Berhasil!', 'Indikator komponen berhasil diimport.');
};

</script>
