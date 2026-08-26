<template>
  <div class="relative">
    <!-- Bell button -->
    <button
      @click="togglePanel"
      class="relative flex items-center justify-center w-9 h-9 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-brand-brown shadow-sm transition-all cursor-pointer"
      title="Notifikasi"
    >
      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
      </svg>
      <!-- Red badge -->
      <span
        v-if="unreadCount > 0"
        class="absolute -top-1 -right-1 flex items-center justify-center min-w-[18px] h-[18px] px-1 rounded-full bg-red-500 text-white text-[9px] font-bold border-2 border-white shadow"
      >{{ unreadCount > 9 ? '9+' : unreadCount }}</span>
    </button>

    <!-- PC: Dropdown card -->
    <div
      v-if="open && !isMobile"
      class="absolute right-0 top-11 z-50 w-96 bg-white rounded-2xl border border-slate-200 shadow-xl overflow-hidden"
    >
      <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between">
        <h3 class="text-sm font-bold text-slate-700">Notifikasi</h3>
        <button
          v-if="unreadCount > 0"
          @click="markAllRead"
          class="text-[11px] font-semibold text-brand-brown hover:underline cursor-pointer"
        >Tandai semua dibaca</button>
      </div>
      <div class="max-h-96 overflow-y-auto">
        <div v-if="notifications.length === 0" class="px-4 py-10 text-center">
          <svg class="w-10 h-10 mx-auto text-slate-200 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
          <p class="text-xs text-slate-400">Belum ada notifikasi</p>
        </div>
        <div
          v-for="n in notifications"
          :key="n.id"
          @click="markRead(n)"
          :class="n.is_read ? 'bg-white' : 'bg-amber-50/50'"
          class="px-4 py-3 border-b border-slate-50 last:border-0 cursor-pointer hover:bg-slate-50 transition-colors"
        >
          <div class="flex items-start gap-2">
            <span v-if="!n.is_read" class="shrink-0 w-2 h-2 rounded-full bg-red-500 mt-1.5"></span>
            <div class="flex-1 min-w-0">
              <p class="text-xs font-bold text-slate-800">{{ n.title }}</p>
              <p class="text-[11px] text-slate-500 mt-0.5 leading-relaxed line-clamp-3">{{ n.body }}</p>
              <p class="text-[10px] text-slate-400 mt-1">{{ formatNotifDate(n.created_at) }}</p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Mobile: Full screen modal -->
    <Teleport to="body">
      <div
        v-if="open && isMobile"
        class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:hidden"
      >
        <div @click="open = false" class="absolute inset-0 bg-black/30"></div>
        <div class="relative w-full bg-white rounded-2xl shadow-xl max-h-[80vh] flex flex-col overflow-hidden">
          <div class="flex items-center justify-between px-4 py-3 border-b border-slate-100">
            <h3 class="text-sm font-bold text-slate-700">Notifikasi</h3>
            <div class="flex items-center gap-3">
              <button
                v-if="unreadCount > 0"
                @click="markAllRead"
                class="text-[11px] font-semibold text-brand-brown hover:underline cursor-pointer"
              >Tandai semua</button>
              <button @click="open = false" class="p-1 text-slate-400 hover:text-slate-600 cursor-pointer">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
              </button>
            </div>
          </div>
          <div class="overflow-y-auto flex-1">
            <div v-if="notifications.length === 0" class="px-4 py-10 text-center">
              <p class="text-xs text-slate-400">Belum ada notifikasi</p>
            </div>
            <div
              v-for="n in notifications"
              :key="n.id"
              @click="markRead(n)"
              :class="n.is_read ? 'bg-white' : 'bg-amber-50/50'"
              class="px-4 py-3 border-b border-slate-50 last:border-0 cursor-pointer hover:bg-slate-50 transition-colors"
            >
              <div class="flex items-start gap-2">
                <span v-if="!n.is_read" class="shrink-0 w-2 h-2 rounded-full bg-red-500 mt-1.5"></span>
                <div class="flex-1 min-w-0">
                  <p class="text-xs font-bold text-slate-800">{{ n.title }}</p>
                  <p class="text-[11px] text-slate-500 mt-0.5 leading-relaxed">{{ n.body }}</p>
                  <p class="text-[10px] text-slate-400 mt-1">{{ formatNotifDate(n.created_at) }}</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import axios from 'axios';
import { router } from '@inertiajs/vue3';

const open = ref(false);
const broadcastNotifications = ref([]);
const dynamicNotifications = ref([]);
const isMobile = ref(false);

const notifications = computed(() => {
  const all = [...dynamicNotifications.value, ...broadcastNotifications.value];
  all.sort((a, b) => new Date(b.created_at) - new Date(a.created_at));
  return all;
});

const unreadCount = computed(() => notifications.value.filter(n => !n.is_read).length);

const checkMobile = () => {
  isMobile.value = window.innerWidth < 640;
};

const togglePanel = () => {
  open.value = !open.value;
  if (open.value) {
    checkMobile();
  }
};

const fetchNotifications = async () => {
  try {
    const [broadcastRes, dynamicRes] = await Promise.all([
      axios.get('/api/notifications'),
      axios.get('/api/notifications/dynamic'),
    ]);
    broadcastNotifications.value = broadcastRes.data.notifications ?? [];
    dynamicNotifications.value = dynamicRes.data.notifications ?? [];
  } catch (e) {
    console.error('Failed to load notifications:', e);
  }
};

const markRead = async (n) => {
  if (n.is_read && !n.link) return;
  if (n.id && !String(n.id).startsWith('dynamic_') && !n.is_read) {
    try {
      await axios.post(`/api/notifications/${n.id}/read`);
    } catch (e) {
      console.error('Failed to mark notification as read:', e);
      return;
    }
  }
  n.is_read = true;
  if (n.link) {
    open.value = false;
    router.visit(n.link);
  }
};

const markAllRead = async () => {
  try {
    await axios.post('/api/notifications/read-all');
    broadcastNotifications.value.forEach(n => { n.is_read = true; });
    dynamicNotifications.value.forEach(n => { n.is_read = true; });
  } catch (e) {
    console.error('Failed to mark all as read:', e);
  }
};

const formatNotifDate = (dateStr) => {
  const d = new Date(dateStr);
  const now = new Date();
  const diffMs = now - d;
  const diffMin = Math.floor(diffMs / 60000);
  const diffHr = Math.floor(diffMin / 60);
  const diffDay = Math.floor(diffHr / 24);

  if (diffMin < 1) return 'Baru saja';
  if (diffMin < 60) return `${diffMin} menit lalu`;
  if (diffHr < 24) return `${diffHr} jam lalu`;
  if (diffDay === 1) return 'Kemarin';
  if (diffDay < 7) return `${diffDay} hari lalu`;
  return d.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
};

const handleClickOutside = (e) => {
  if (!e.target.closest('.relative') || !e.target.closest('[title="Notifikasi"]')) {
    if (open.value && !isMobile.value) {
      const panel = e.target.closest('.absolute');
      if (!panel) open.value = false;
    }
  }
};

onMounted(() => {
  fetchNotifications();
  checkMobile();
  window.addEventListener('resize', checkMobile);
  document.addEventListener('click', handleClickOutside);
  // Poll every 60 seconds for new notifications
  setInterval(fetchNotifications, 60000);
});

onUnmounted(() => {
  window.removeEventListener('resize', checkMobile);
  document.removeEventListener('click', handleClickOutside);
});
</script>
