<template>
  <!-- Auth loading overlay -->
  <div v-if="!authChecked" class="fixed inset-0 z-50 flex items-center justify-center bg-white">
    <div class="flex flex-col items-center gap-3">
      <svg class="animate-spin w-8 h-8 text-brand-brown" fill="none" viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
      </svg>
      <p class="text-sm font-medium text-slate-500">Memuat sesi...</p>
    </div>
  </div>

  <SidebarProvider v-if="authChecked && isAuthenticated">
    <SidebarMobileCloser />
    <Sidebar collapsible="icon">
      <!-- Header: Logo -->
      <SidebarHeader class="border-b border-sidebar-border px-3 py-3 mx-auto">
        <Link href="/" class="flex items-center gap-2 cursor-pointer overflow-hidden">
          <img :src="'/images/logo-ladang-lima.png'" alt="Logo" class="h-8 w-8 shrink-0 rounded-md object-contain">
          <span class="font-bold text-brand-brown text-sm truncate group-data-[collapsible=icon]:hidden">TPM Ladang Lima</span>
        </Link>
      </SidebarHeader>

      <!-- Nav Items -->
      <SidebarContent class="px-2 py-4">
        <SidebarGroup>
          <SidebarGroupContent>
            <SidebarMenu>
              <SidebarMenuItem>
                <SidebarMenuButton as-child :is-active="isUrl('/')" tooltip="Dashboard">
                  <Link href="/">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                    <span>Dashboard</span>
                  </Link>
                </SidebarMenuButton>
              </SidebarMenuItem>

              <SidebarMenuItem>
                <SidebarMenuButton as-child :is-active="isUrl('/machines')" tooltip="Machines">
                  <Link href="/machines">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                    <span>Machines</span>
                  </Link>
                </SidebarMenuButton>
              </SidebarMenuItem>

              <SidebarMenuItem>
                <SidebarMenuButton as-child :is-active="isUrl('/approvals')" tooltip="Approval">
                  <Link href="/approvals">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span>Approval</span>
                  </Link>
                </SidebarMenuButton>
              </SidebarMenuItem>

              <SidebarMenuItem v-if="isManagerOrAdmin">
                <SidebarMenuButton as-child :is-active="isUrl('/users')" tooltip="Users">
                  <Link href="/users">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                    <span>Users</span>
                  </Link>
                </SidebarMenuButton>
              </SidebarMenuItem>

              <SidebarMenuItem v-if="isAdmin">
                <SidebarMenuButton as-child :is-active="isUrl('/logs')" tooltip="System Logs">
                  <Link href="/logs">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                    <span>System Logs</span>
                  </Link>
                </SidebarMenuButton>
              </SidebarMenuItem>
            </SidebarMenu>
          </SidebarGroupContent>
        </SidebarGroup>
      </SidebarContent>

      <!-- Footer: User profile + logout -->
      <SidebarFooter v-if="user" class="border-t border-sidebar-border p-3">
        <div class="flex items-center gap-3 px-1 py-1.5 mb-2 group-data-[collapsible=icon]:px-0 group-data-[collapsible=icon]:justify-center group-data-[collapsible=icon]:mb-1">
          <div class="w-8 h-8 rounded-full bg-gradient-to-tr from-brand-brown to-brand-gradation flex items-center justify-center font-bold text-brand-cream shadow-md text-xs uppercase shrink-0">
            {{ initials }}
          </div>
          <div class="flex-1 min-w-0 group-data-[collapsible=icon]:hidden">
            <p class="text-sm font-bold text-brand-brown truncate leading-tight">{{ user.full_name }}</p>
            <p class="text-xs text-slate-500 font-medium truncate uppercase leading-tight">
              {{ getCityLabel(user.city, user.role) || getRoleLabel(user.role) }}
            </p>
          </div>
        </div>
        <button
          @click="handleLogout"
          class="w-full flex items-center justify-center gap-1.5 py-2 px-3 text-xs font-bold text-red-600 bg-red-50 hover:bg-red-100 rounded-xl transition-all border border-red-100/50 cursor-pointer group-data-[collapsible=icon]:px-2 group-data-[collapsible=icon]:py-2"
        >
          <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
          <span class="group-data-[collapsible=icon]:hidden">Keluar Sistem</span>
        </button>
      </SidebarFooter>
    </Sidebar>

    <!-- Main content area -->
    <SidebarInset class="bg-slate-50 overflow-hidden flex flex-col h-svh">
      <div class="flex-1 overflow-y-auto relative">
        <!-- Floating sidebar toggle — visible only when sidebar is collapsed or on mobile -->
        <div class="sticky top-0 z-10 flex items-center gap-2 px-3 py-2 pointer-events-none">
          <SidebarTrigger class="pointer-events-auto text-brand-brown hover:bg-brand-cream border border-sidebar-border shadow-sm bg-white cursor-pointer" />
        </div>
        <div class="px-6 md:px-8 pb-8 -mt-2">
          <slot />
        </div>
      </div>
    </SidebarInset>

  </SidebarProvider>

  <!-- Global Alert Modal — always mounted so alertRef is always available -->
  <AlertModal ref="alertModalRef" />
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { Link, usePage, router } from '@inertiajs/vue3';
import { useAuth } from './composables/useAuth.js';
import AlertModal from './components/AlertModal.vue';
import { alertRef } from './composables/useAlert.js';
import {
  SidebarProvider,
  Sidebar,
  SidebarHeader,
  SidebarContent,
  SidebarFooter,
  SidebarGroup,
  SidebarGroupContent,
  SidebarMenu,
  SidebarMenuItem,
  SidebarMenuButton,
  SidebarInset,
  SidebarTrigger,
} from '../views/components/ui/sidebar/index.ts';
import SidebarMobileCloser from './components/SidebarMobileCloser.vue';

const page = usePage();
const { user, isAdmin, isManagerOrAdmin, isAuthenticated, authReady, initializeAuth } = useAuth();
const alertModalRef = ref(null);
const authChecked = ref(false);

onMounted(async () => {
  alertRef.value = alertModalRef.value;
  await initializeAuth();
  authChecked.value = true;
  if (!isAuthenticated.value) {
    router.visit('/login', { replace: true });
  }
});

const isUrl = (url) => {
  if (url === '/') return page.url === '/';
  return page.url.startsWith(url);
};

const initials = computed(() => {
  if (!user.value?.full_name) return '??';
  const parts = user.value.full_name.split(' ').filter(n => n.length > 0);
  if (parts.length >= 2) return (parts[0][0] + parts[1][0]).toUpperCase();
  return user.value.full_name.slice(0, 2).toUpperCase();
});

function getRoleLabel(role) {
  const map = { admin: 'Administrator', manager: 'Manajer', technician: 'Teknisi' };
  return map[role] ?? role;
}

function getCityLabel(city, role) {
  if (role !== 'technician') return '';
  if (!city || city === 'both') return 'Teknisi';
  const map = { sby: 'Teknisi Surabaya', pasuruan: 'Teknisi Pasuruan' };
  return map[city] ?? `Teknisi ${city}`;
}

function handleLogout() {
  localStorage.removeItem('auth_token');
  localStorage.removeItem('user_profile');
  window.location.href = '/login';
}
</script>
