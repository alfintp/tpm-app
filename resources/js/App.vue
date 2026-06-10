<template>
  <div class="flex h-full w-full">
    <!-- Sidebar -->
    <aside class="w-64 bg-slate-900 text-white flex flex-col hidden md:flex h-full shadow-2xl relative z-20">
      <div class="h-20 flex items-center justify-center border-b border-slate-800">
        <h1 class="text-2xl font-bold tracking-wider text-transparent bg-clip-text bg-gradient-to-r from-blue-400 to-indigo-500 cursor-pointer" @click="$router.push('/')">TPM</h1>
      </div>
      <nav class="flex-1 px-4 py-6 space-y-1">
        <router-link to="/" exact-active-class="active-link" class="nav-link">
          <svg class="w-5 h-5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
          Dashboard
        </router-link>
        <router-link to="/machines" active-class="active-link" class="nav-link">
          <svg class="w-5 h-5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
          Machines
        </router-link>
        <router-link to="/schedules" active-class="active-link" class="nav-link">
          <svg class="w-5 h-5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
          Schedules
        </router-link>
        <div class="pt-2 border-t border-slate-800 mt-2">
          <a class="nav-link opacity-60 cursor-not-allowed">
            <svg class="w-5 h-5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
            Reports
          </a>
        </div>
      </nav>
      <div class="p-4 border-t border-slate-800">
        <div class="flex items-center cursor-pointer hover:opacity-80 transition-opacity">
          <div class="w-9 h-9 rounded-full bg-gradient-to-tr from-indigo-500 to-purple-500 flex items-center justify-center font-bold text-white shadow-lg text-xs">AF</div>
          <div class="ml-3">
            <p class="text-sm font-medium text-white">Ahmad Fauzi</p>
            <p class="text-xs text-slate-400">Maintenance Dept</p>
          </div>
        </div>
      </div>
    </aside>

    <!-- Main Content -->
    <main class="flex-1 flex flex-col h-full overflow-hidden bg-slate-50 relative">
      <header class="h-16 bg-white/90 backdrop-blur-md border-b border-slate-200 flex items-center justify-between px-8 z-10 sticky top-0 shadow-sm">
        <div class="flex items-center gap-3">
          <div class="w-2 h-2 rounded-full bg-indigo-500"></div>
          <h2 class="text-base font-semibold text-slate-700">{{ pageTitle }}</h2>
        </div>
        
      </header>

      <div class="flex-1 overflow-y-auto p-8 z-0">
        <router-view />
      </div>
    </main>

    <MaintenanceForm
      v-if="showModal"
      :initialMachineId="selectedMachineId"
      @close="closeModal"
    />

    <!-- Global Alert Modal -->
    <AlertModal ref="alertModalRef" />
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { useRoute } from 'vue-router';
import MaintenanceForm from './components/MaintenanceForm.vue';
import AlertModal from './components/AlertModal.vue';
import { alertRef } from './composables/useAlert.js';

const route = useRoute();
const showModal = ref(false);
const selectedMachineId = ref(null);
const alertModalRef = ref(null);

// Wire up global alert ref
onMounted(() => {
  alertRef.value = alertModalRef.value;
  window.addEventListener('open-report-modal', handleOpenReportModal);
});
onUnmounted(() => {
  window.removeEventListener('open-report-modal', handleOpenReportModal);
});

const pageTitle = computed(() => {
  const map = {
    'dashboard': 'Dashboard',
    'machines': 'Daftar Mesin',
    'machine-detail': 'Detail Mesin',
    'machine-report': 'Maintenance Report',
    'schedules': 'Jadwal Maintenance',
  };
  return map[route.name] ?? 'TPM System';
});

function openModal(machineId) {
  selectedMachineId.value = machineId;
  showModal.value = true;
}

function closeModal(refresh = false) {
  showModal.value = false;
  selectedMachineId.value = null;
  if (refresh) window.dispatchEvent(new Event('refresh-data'));
}

function handleOpenReportModal(e) {
  openModal(e.detail?.machineId ?? null);
}
</script>

<style>
.nav-link {
  display: flex;
  align-items: center;
  padding: 0.75rem 1rem;
  border-radius: 0.75rem;
  font-weight: 500;
  font-size: 0.875rem;
  color: #94a3b8;
  transition: all 0.2s;
  cursor: pointer;
  text-decoration: none;
}
.nav-link:hover {
  color: #fff;
  background-color: rgb(30 41 59);
}
.nav-link.active-link {
  color: #818cf8;
  background-color: rgba(99, 102, 241, 0.15);
}
</style>
