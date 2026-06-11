<template>
  <!-- Full Screen Auth Pages without sidebar/header -->
  <div v-if="isAuthPage" class="h-full w-full bg-slate-50">
    <router-view />
  </div>

  <!-- Standard Dashboard Layout -->
  <div v-else class="flex h-full w-full">
    <!-- Sidebar -->
    <aside class="w-64 bg-brand-cream border-r border-slate-200 text-brand-brown flex flex-col hidden md:flex h-full relative z-20">
      <div class="h-20 flex items-center justify-center border-b border-slate-200 px-4">
        <img :src="'/images/logo-ladang-lima.png'" alt="Logo Ladang Lima" class="h-12 w-auto cursor-pointer" @click="$router.push('/')">
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
        <router-link to="/approvals" active-class="active-link" class="nav-link">
          <svg class="w-5 h-5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
          Approval
        </router-link>
        <router-link v-if="isManagerOrAdmin" to="/users" active-class="active-link" class="nav-link">
          <svg class="w-5 h-5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
          Users
        </router-link>
        <router-link v-if="isAdmin" to="/logs" active-class="active-link" class="nav-link">
          <svg class="w-5 h-5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
          System Logs
        </router-link>
      </nav>
      
      <!-- Profile & Logout Footer -->
      <div v-if="user" class="p-4 border-t border-slate-200 flex flex-col gap-3">
        <div class="flex items-center">
          <div class="w-9 h-9 rounded-full bg-gradient-to-tr from-brand-brown to-brand-gradation flex items-center justify-center font-bold text-brand-cream shadow-md text-xs uppercase">
            {{ initials }}
          </div>
          <div class="ml-3 flex-1 min-w-0">
            <p class="text-sm font-bold text-brand-brown truncate">{{ user.full_name }}</p>
            <p class="text-xs text-slate-500 font-medium truncate uppercase">{{ getRoleLabel(user.role) }}</p>
          </div>
        </div>
        <button 
          @click="handleLogout" 
          class="w-full flex items-center justify-center py-2 px-3 text-xs font-bold text-red-600 bg-red-50 hover:bg-red-100 rounded-xl transition-all border border-red-100/50 hover:cursor-pointer"
        >
          <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
          Keluar Sistem
        </button>
      </div>
    </aside>

    <!-- Main Content -->
    <main class="flex-1 flex flex-col h-full overflow-hidden bg-slate-50 relative">
      <header class="h-16 bg-white/90 backdrop-blur-md border-b border-slate-200 flex items-center justify-between px-8 z-10 sticky top-0 shadow-sm">
        <div class="flex items-center gap-3">
          <div class="w-2 h-2 rounded-full bg-gradient-to-tr from-bred-500 to-brand-gradation"></div>
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
import { useRoute, useRouter } from 'vue-router';
import { useAuth } from './composables/useAuth.js';
import MaintenanceForm from './components/MaintenanceForm.vue';
import AlertModal from './components/AlertModal.vue';
import { alertRef } from './composables/useAlert.js';

const route = useRoute();
const router = useRouter();
const { user, isManagerOrAdmin, isAdmin, logout, initializeAuth } = useAuth();

const showModal = ref(false);
const selectedMachineId = ref(null);
const alertModalRef = ref(null);

const isAuthPage = computed(() => ['login', 'register'].includes(route.name));

const initials = computed(() => {
  if (!user.value?.full_name) return '??';
  const parts = user.value.full_name.split(' ').filter(n => n.length > 0);
  if (parts.length >= 2) {
    return (parts[0][0] + parts[1][0]).toUpperCase();
  }
  return user.value.full_name.slice(0, 2).toUpperCase();
});

// Wire up global alert ref & initialize auth
onMounted(async () => {
  alertRef.value = alertModalRef.value;
  window.addEventListener('open-report-modal', handleOpenReportModal);
  
  // Fetch initial profile if we have a token saved
  await initializeAuth();
});

onUnmounted(() => {
  window.removeEventListener('open-report-modal', handleOpenReportModal);
});

const pageTitle = computed(() => {
  const map = {
    'dashboard': 'Dashboard',
    'machines': 'Daftar Mesin',
    'machine-detail': 'Detail Mesin',
    'approvals': 'Approval Report',
    'users': 'Manajemen User',
    'logs': 'Log Aktivitas Sistem',
  };
  return map[route.name] ?? 'TPM System';
});

function getRoleLabel(role) {
  const map = {
    admin: 'Administrator',
    manager: 'Manajer',
    technician: 'Teknisi',
  };
  return map[role] ?? role;
}

async function handleLogout() {
  await logout();
  router.push({ name: 'login' });
}

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
  font-weight: 600;
  font-size: 0.875rem;
  color: #5c4d43;
  transition: all 0.2s;
  cursor: pointer;
  text-decoration: none;
}
.nav-link:hover {
  color: #402c1f;
  background-color: rgba(255, 255, 255, 0.6);
}
.nav-link.active-link {
  color: #402c1f;
  background-color: #ffffff;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
}
</style>

