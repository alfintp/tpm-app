<template>
  <div class="min-h-screen bg-white text-slate-800 flex flex-col font-sans antialiased">
    <!-- Header Bar -->
    <header class="bg-white border-b border-slate-200 px-4 py-3.5 flex items-center justify-between shadow-sm">
      <div class="flex items-center gap-3.5">
        <button
          @click="goBack"
          class="flex items-center gap-1.5 px-3 py-1.5 bg-slate-50 hover:bg-slate-100 border border-slate-200 rounded-xl text-xs font-bold text-slate-700 transition-colors cursor-pointer"
        >
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7" />
          </svg>
          Kembali
        </button>
        <h1 class="text-sm md:text-base font-black uppercase tracking-wider text-slate-800 flex items-center gap-2 flex-wrap">
          <span>Maintenance Inspection Report:</span>
          <span class="text-brand-brown bg-brand-cream px-2.5 py-0.5 rounded-lg border border-brand-brown/20 font-mono text-xs md:text-sm">
            {{ machine?.name || 'Loading...' }} ({{ machine?.kode || '-' }})
          </span>
        </h1>
      </div>
      <div>
        <button
          @click="triggerSaveReport"
          :disabled="submitting || !hasCheckedComponents"
          class="px-5 py-2 bg-emerald-600 hover:bg-emerald-500 disabled:bg-slate-100 disabled:text-slate-400 disabled:border-slate-200 disabled:cursor-not-allowed text-white font-bold text-xs md:text-sm rounded-xl shadow-lg hover:shadow-emerald-900/10 active:scale-95 transition-all cursor-pointer flex items-center gap-1.5 border border-emerald-500/20"
        >
          <svg v-if="submitting" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
          </svg>
          <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
          </svg>
          Simpan Laporan
        </button>
      </div>
    </header>

    <div v-if="loading" class="flex-1 flex flex-col items-center justify-center gap-4 text-slate-500">
      <svg class="animate-spin h-10 w-10 text-brand-brown" fill="none" viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
      </svg>
      <p class="font-bold text-sm tracking-wide text-brand-brown/80">Memuat modul inspeksi mesin...</p>
    </div>

    <div v-else-if="machine" class="flex-1 flex flex-col overflow-hidden">
      <!-- Compliance Bar Section -->
      <div class="bg-slate-50 border-b border-slate-200 p-4">
        <div class="max-w-7xl mx-auto flex flex-col gap-2">
          <div class="flex items-center justify-between text-xs md:text-sm font-bold uppercase tracking-wider">
            <span class="text-slate-600">Total Machine Compliance</span>
            <span :class="complianceColorClass">{{ totalCompliance }}%</span>
          </div>
          <div class="w-full bg-slate-200 h-4 rounded-full overflow-hidden border border-slate-300 p-0.5">
            <div
              :class="complianceBarClass"
              class="h-full rounded-full transition-all duration-500 flex items-center justify-end pr-2 text-[10px] font-black text-white"
              :style="{ width: `${totalCompliance}%` }"
            >
              <span v-if="totalCompliance > 10">{{ totalCompliance }}%</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Main Layout: Sidebar & Content -->
      <main class="flex-1 flex flex-col md:flex-row overflow-hidden max-w-7xl w-full mx-auto p-4 gap-4 bg-white">
        
        <!-- Left Sidebar: Component List -->
        <section class="w-full md:w-80 flex flex-col bg-white rounded-2xl border border-slate-200 overflow-hidden shrink-0 shadow-lg">
          <div class="px-4 py-3 bg-slate-50 border-b border-slate-200 flex justify-between items-center">
            <span class="text-xs font-black uppercase tracking-wider text-slate-700">Daftar Komponen</span>
            <span class="px-2 py-0.5 bg-brand-cream border border-brand-brown/20 rounded-full text-[10px] font-bold text-brand-brown">
              {{ checkedCount }}/{{ componentsList.length }} Selesai
            </span>
          </div>

          <div class="flex-1 overflow-y-auto p-2.5 space-y-1.5 custom-scrollbar">
            <button
              v-for="comp in componentsList"
              :key="comp.id"
              @click="selectComponent(comp)"
              :class="[
                activeComponent?.id === comp.id
                  ? 'bg-brand-brown text-white border-brand-brown shadow-md shadow-brand-brown/10'
                  : 'bg-slate-50 hover:bg-slate-100 text-slate-700 border-slate-200',
                'w-full flex items-center justify-between p-3.5 rounded-xl border text-left transition-all cursor-pointer group'
              ]"
            >
              <div class="flex items-center gap-2.5 min-w-0">
                <span class="shrink-0 flex items-center justify-center">
                  <span v-if="comp.checked" class="text-emerald-400">
                    <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                    </svg>
                  </span>
                  <span v-else-if="comp.inProgress" class="text-amber-400">
                    <svg class="w-4.5 h-4.5 animate-pulse" fill="currentColor" viewBox="0 0 24 24">
                      <path d="M8 5v14l11-7z" />
                    </svg>
                  </span>
                  <span v-else class="text-slate-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <circle cx="12" cy="12" r="10" stroke-width="2" />
                    </svg>
                  </span>
                </span>
                <span class="font-bold text-sm truncate uppercase tracking-wide group-hover:translate-x-0.5 transition-transform duration-200">
                  {{ comp.name }}
                </span>
              </div>
              <div class="text-right shrink-0">
                <span v-if="comp.checked" class="text-xs font-black text-emerald-500">
                  {{ comp.conditionPct }}% OK
                </span>
                <span v-else-if="comp.inProgress" class="text-[10px] font-black text-amber-600 bg-amber-50 border border-amber-200 px-1.5 py-0.5 rounded-md animate-pulse">
                  {{ comp.conditionPct }}% Cek
                </span>
                <span v-else class="text-[10px] font-semibold text-slate-500 bg-slate-100 px-1.5 py-0.5 rounded-md border border-slate-200">
                  Pending
                </span>
              </div>
            </button>
          </div>
        </section>

        <!-- Right Side: Selected Component Inspection Form -->
        <section v-if="activeComponent" class="flex-1 flex flex-col bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-lg">
          <div class="px-4 py-3 bg-slate-50 border-b border-slate-200 flex flex-wrap justify-between items-center gap-2">
            <div>
              <h2 class="font-black text-sm md:text-base text-slate-800 uppercase tracking-wider">
                {{ activeComponent.name }}
              </h2>
              <p class="text-[11px] text-slate-500 font-semibold uppercase mt-0.5">
                Kategori: {{ activeComponent.category || '-' }} · Spec: {{ activeComponent.specification || '-' }}
              </p>
            </div>
            <div class="flex items-center gap-2.5">
              <span class="text-xs font-bold text-slate-600">Status:</span>
              <span
                v-if="activeComponent.checked"
                class="px-2.5 py-0.5 bg-emerald-50 text-emerald-600 border border-emerald-200 rounded-md text-xs font-black uppercase tracking-wider"
              >
                {{ activeComponent.conditionPct }}% OK
              </span>
              <span
                v-else-if="activeComponent.inProgress"
                class="px-2.5 py-0.5 bg-amber-50 text-amber-600 border border-amber-200 rounded-md text-xs font-black uppercase tracking-wider animate-pulse"
              >
                {{ activeComponent.conditionPct }}% Checking...
              </span>
              <span
                v-else
                class="px-2.5 py-0.5 bg-slate-100 text-slate-500 border border-slate-200 rounded-md text-xs font-black uppercase tracking-wider"
              >
                0% Pending
              </span>
            </div>
          </div>

          <div class="flex-1 overflow-y-auto p-5 space-y-6 custom-scrollbar">
            <!-- Parameter Checklist (If Component Has Indicators) -->
            <div v-if="hasIndicators" class="space-y-4">
              <div class="flex items-center justify-between border-b border-slate-200 pb-2">
                <span class="text-xs font-black uppercase tracking-wider text-brand-brown">Parameter Checklist</span>
                <span class="text-[10px] font-bold text-slate-500">PASS / FAIL</span>
              </div>

              <div class="divide-y divide-slate-100 space-y-4">
                <div
                  v-for="ind in activeComponent.indicators"
                  :key="ind.id"
                  class="flex items-center justify-between gap-4 pt-4 first:pt-0"
                >
                  <div class="min-w-0 flex-1">
                    <h4 class="font-black text-xs md:text-sm text-slate-800 uppercase tracking-wide">
                      {{ ind.name }}
                    </h4>
                    <p class="text-xs text-slate-500 mt-0.5 line-clamp-2 leading-relaxed">
                      {{ ind.description || 'Tidak ada deskripsi detail parameter.' }}
                    </p>
                  </div>

                  <!-- Custom Toggle Switch (Mockup style) -->
                  <button
                    @click="toggleIndicator(activeComponent, ind.id)"
                    class="shrink-0 flex items-center justify-between w-20 h-7 rounded-full p-1 transition-all duration-300 focus:outline-none cursor-pointer relative shadow-inner overflow-hidden border"
                    :class="[
                      activeComponent.indicatorValues[ind.id]
                        ? 'bg-emerald-500 border-emerald-400 text-white'
                        : 'bg-slate-200 border-slate-300 text-slate-500'
                    ]"
                  >
                    <span
                      class="text-[9px] font-black uppercase select-none z-10 transition-all duration-300"
                      :class="activeComponent.indicatorValues[ind.id] ? 'pl-2 text-white' : 'pr-2 ml-auto text-slate-600'"
                    >
                      {{ activeComponent.indicatorValues[ind.id] ? 'PASS' : 'FAIL' }}
                    </span>
                    <div
                      class="absolute top-1/2 -translate-y-1/2 w-5 h-5 rounded-full bg-white shadow-md transition-all duration-300"
                      :style="{
                        left: activeComponent.indicatorValues[ind.id] ? 'calc(100% - 22px)' : '3px'
                      }"
                    ></div>
                  </button>
                </div>
              </div>
            </div>

            <!-- Manual Slider Input (If Component Has NO Indicators) -->
            <div v-else class="space-y-4 bg-slate-50 border border-slate-200 rounded-xl p-5">
              <h3 class="text-xs font-black uppercase tracking-wider text-brand-brown border-b border-slate-200 pb-2 mb-4">
                Input Kondisi Manual
              </h3>
              <p class="text-xs text-slate-500 leading-relaxed mb-4">
                Komponen ini tidak memiliki daftar indikator khusus. Silakan geser slider atau klik preset di bawah ini untuk menilai kondisinya saat ini:
              </p>

              <!-- Condition Percent Slider -->
              <div class="space-y-2">
                <div class="flex justify-between items-center">
                  <span class="text-xs font-bold text-slate-700">Persentase Kondisi:</span>
                  <span :class="condClass(activeComponent.conditionPct)" class="text-lg font-black font-mono">
                    {{ activeComponent.conditionPct }}%
                  </span>
                </div>
                <input
                  type="range"
                  min="0"
                  max="100"
                  step="5"
                  v-model.number="activeComponent.conditionPct"
                  @input="activeComponent.inProgress = true"
                  class="w-full h-2 bg-slate-200 rounded-lg appearance-none cursor-pointer accent-brand-brown focus:outline-none"
                />
              </div>

              <!-- Preset buttons -->
              <div class="grid grid-cols-4 gap-2 pt-2">
                <button
                  v-for="p in presets"
                  :key="p.val"
                  @click="applyPreset(activeComponent, p.val)"
                  class="px-2.5 py-2 rounded-xl text-center border font-bold text-[10px] md:text-xs tracking-wider uppercase transition-colors cursor-pointer"
                  :class="[
                    activeComponent.conditionPct === p.val
                      ? 'bg-brand-brown text-white border-brand-brown'
                      : 'bg-white hover:bg-slate-50 text-slate-600 border-slate-200'
                  ]"
                >
                  {{ p.label }} ({{ p.val }}%)
                </button>
              </div>
            </div>

            <!-- Comment & Action Block -->
            <div class="border-t border-slate-200 pt-5 space-y-4">
              <!-- Replacemement component toggle -->
              <div class="flex items-center justify-between bg-slate-50 border border-slate-200 p-3.5 rounded-xl">
                <div>
                  <h4 class="font-bold text-xs md:text-sm text-slate-800 uppercase tracking-wide">
                    Penggantian Komponen
                  </h4>
                  <p class="text-[11px] text-slate-500 mt-0.5">
                    Aktifkan opsi ini jika komponen baru saja diganti baru (Replace).
                  </p>
                </div>
                <button
                  @click="activeComponent.is_component_replacement = !activeComponent.is_component_replacement"
                  class="shrink-0 flex items-center justify-between w-14 h-6 rounded-full p-1 transition-all duration-300 focus:outline-none cursor-pointer relative shadow-inner overflow-hidden border"
                  :class="[
                    activeComponent.is_component_replacement
                      ? 'bg-amber-500 border-amber-400 text-white'
                      : 'bg-slate-200 border-slate-300 text-slate-500'
                  ]"
                >
                  <div
                    class="absolute top-1/2 -translate-y-1/2 w-4 h-4 rounded-full bg-white shadow-md transition-all duration-300"
                    :style="{
                      left: activeComponent.is_component_replacement ? '33px' : '3px'
                    }"
                  ></div>
                </button>
              </div>

              <!-- Comment Input Field -->
              <div v-if="showCommentBox" class="space-y-1.5">
                <label class="text-xs font-bold text-slate-700 uppercase tracking-wider flex justify-between">
                  <span>Catatan Kerusakan / Hasil Pemeriksaan</span>
                  <button @click="showCommentBox = false" class="text-brand-brown hover:underline normal-case text-[10px]">Tutup</button>
                </label>
                <textarea
                  v-model="activeComponent.description"
                  placeholder="Ketik catatan mengenai temuan kerusakan, tindakan perbaikan, atau nomor serial komponen pengganti disini..."
                  rows="3"
                  class="w-full bg-white border border-slate-200 rounded-xl px-3.5 py-2.5 text-xs text-slate-700 placeholder-slate-400 focus:outline-none focus:ring-1 focus:ring-brand-brown focus:border-brand-brown"
                ></textarea>
              </div>
            </div>
          </div>

          <!-- Component Section Actions Footer -->
          <div class="px-5 py-3.5 bg-slate-50 border-t border-slate-200 flex items-center justify-between">
            <button
              @click="completeComponentSection(activeComponent)"
              class="px-5 py-2.5 bg-brand-brown hover:bg-brand-brown/90 text-white font-black text-xs uppercase tracking-wider rounded-xl shadow-lg transition-colors cursor-pointer flex items-center gap-1.5"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
              </svg>
              Selesaikan Bagian Ini
            </button>

            <button
              v-if="!showCommentBox"
              @click="showCommentBox = true"
              class="px-4 py-2.5 bg-white hover:bg-slate-50 border border-slate-200 text-slate-700 font-bold text-xs uppercase tracking-wider rounded-xl transition-colors cursor-pointer flex items-center gap-1.5"
            >
              <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
              </svg>
              Tambah Catatan
            </button>
          </div>
        </section>
      </main>
    </div>

    <!-- Error State -->
    <div v-else-if="loadError" class="flex-1 flex flex-col items-center justify-center gap-4 text-slate-600 px-6">
      <svg class="w-16 h-16 text-red-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
      </svg>
      <div class="text-center max-w-md">
        <h3 class="text-base font-black uppercase tracking-wider text-slate-800 mb-1">Gagal Memuat Data Mesin</h3>
        <p class="text-sm text-slate-500 mb-4">{{ loadError }}</p>
        <div class="flex items-center justify-center gap-3">
          <button
            @click="loadMachineData"
            class="px-4 py-2 bg-brand-brown hover:bg-brand-brown/90 text-white font-bold text-xs uppercase tracking-wider rounded-xl transition-colors cursor-pointer"
          >
            Coba Lagi
          </button>
          <button
            @click="goBack"
            class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs uppercase tracking-wider rounded-xl transition-colors cursor-pointer"
          >
            Kembali
          </button>
        </div>
      </div>
    </div>

    <!-- SAVE REPORT CONFIRMATION MODAL -->
    <div v-if="showSaveModal && machine" class="fixed inset-0 z-50 flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="showSaveModal = false"></div>
      <div class="bg-white border border-slate-200 rounded-3xl w-full max-w-md relative z-10 overflow-hidden shadow-2xl flex flex-col">
        <div class="px-6 py-4 bg-slate-50 border-b border-slate-200 flex justify-between items-center">
          <div>
            <h3 class="text-base font-black text-slate-800 uppercase tracking-wider">Simpan Laporan Maintenance</h3>
            <p class="text-[10px] text-slate-500 font-semibold uppercase mt-0.5">Konfirmasi waktu & catatan umum</p>
          </div>
          <button @click="showSaveModal = false" class="text-slate-400 hover:text-slate-700 p-1.5 rounded-full hover:bg-slate-200 cursor-pointer">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <div class="p-6 space-y-4 text-xs md:text-sm">
          <!-- Warnings / Compliance Summary -->
          <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 flex gap-3.5 items-start">
            <svg class="w-5 h-5 text-amber-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <div class="text-slate-700 leading-relaxed text-xs">
              <p class="font-bold text-slate-900 mb-0.5">Ringkasan Laporan:</p>
              <p>Mencatat pemeriksaan <strong class="text-brand-brown">{{ checkedCount }}</strong> dari <strong class="text-brand-brown">{{ componentsList.length }}</strong> komponen dengan rata-rata estimasi kepatuhan akhir <strong class="text-emerald-600">{{ totalCompliance }}%</strong>.</p>
            </div>
          </div>

          <!-- Time Input -->
          <div class="grid grid-cols-2 gap-4">
            <div class="space-y-1.5">
              <label class="text-xs font-bold text-slate-700 uppercase tracking-wider">Jam Mulai Kerja</label>
              <input
                type="time"
                v-model="startTime"
                class="w-full bg-white border border-slate-200 text-slate-700 rounded-xl px-3 py-2 text-xs focus:outline-none focus:ring-1 focus:ring-brand-brown"
              />
            </div>
            <div class="space-y-1.5">
              <label class="text-xs font-bold text-slate-700 uppercase tracking-wider">Jam Selesai Kerja</label>
              <input
                type="time"
                v-model="endTime"
                class="w-full bg-white border border-slate-200 text-slate-700 rounded-xl px-3 py-2 text-xs focus:outline-none focus:ring-1 focus:ring-brand-brown"
              />
            </div>
          </div>

          <!-- Duration Input -->
          <div class="space-y-1.5">
            <label class="text-xs font-bold text-slate-700 uppercase tracking-wider flex justify-between">
              <span>Durasi Pengerjaan (Menit)</span>
              <span class="text-brand-brown font-black font-mono">{{ calculatedDuration }} Menit</span>
            </label>
            <input
              type="number"
              v-model.number="durationMinutes"
              min="0"
              class="w-full bg-white border border-slate-200 text-slate-700 rounded-xl px-3 py-2 text-xs focus:outline-none focus:ring-1 focus:ring-brand-brown"
            />
          </div>

          <!-- General notes input -->
          <div class="space-y-1.5">
            <label class="text-xs font-bold text-slate-700 uppercase tracking-wider">Catatan Umum / Ringkasan Pekerjaan</label>
            <textarea
              v-model="generalNotes"
              placeholder="Masukkan ringkasan perbaikan umum atau kendala yang dihadapi selama proses pemeliharaan..."
              rows="3"
              class="w-full bg-white border border-slate-200 text-slate-700 rounded-xl px-3 py-2 text-xs focus:outline-none focus:ring-1 focus:ring-brand-brown placeholder-slate-400"
            ></textarea>
          </div>
        </div>

        <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex justify-end gap-3.5">
          <button
            @click="showSaveModal = false"
            class="px-4 py-2 bg-transparent text-slate-500 hover:text-slate-800 text-xs font-bold uppercase tracking-wider transition-colors cursor-pointer"
          >
            Batal
          </button>
          <button
            @click="submitReport"
            :disabled="submitting"
            class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white font-black text-xs uppercase tracking-wider rounded-xl shadow-md cursor-pointer flex items-center gap-1.5"
          >
            <svg v-if="submitting" class="animate-spin h-3.5 w-3.5" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
            </svg>
            Kirim Laporan
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import axios from 'axios';
import { showAlert } from '../composables/useAlert.js';
import { useAuth } from '../composables/useAuth.js';

const props = defineProps({
  machine: {
    type: Object,
    default: null
  },
  id: {
    type: String,
    default: null
  }
});

const route = useRoute();
const vueRouter = useRouter();

const { user, isAdmin } = useAuth();

// Loading state
const machine = ref(props.machine);
const loading = ref(!machine.value);
const submitting = ref(false);
const loadError = ref(null);

const componentsList = ref([]);
const activeComponent = ref(null);
const showCommentBox = ref(false);

// Save report inputs
const showSaveModal = ref(false);
const startTime = ref('');
const endTime = ref('');
const durationMinutes = ref(60);
const generalNotes = ref('');

// Presets for components without indicators
const presets = [
  { val: 100, label: 'OK' },
  { val: 80, label: 'Minor' },
  { val: 50, label: 'Medium' },
  { val: 0, label: 'Broken' }
];

const machineId = computed(() => {
  if (machine.value) return machine.value.id;
  if (props.id) return props.id;
  if (route?.params?.id) return route.params.id;
  if (typeof window !== 'undefined') {
    const parts = window.location.pathname.split('/');
    return parts[parts.length - 1] || null;
  }
  return null;
});

// Fetch machine data if not available via props
const loadMachineData = async () => {
  if (machine.value) {
    initializeComponents();
    return;
  }
  const id = machineId.value;
  if (!id) {
    loadError.value = 'ID mesin tidak ditemukan pada URL.';
    loading.value = false;
    return;
  }
  loading.value = true;
  loadError.value = null;
  try {
    const res = await axios.get(`/api/machines/${id}`);
    machine.value = res.data;
    initializeComponents();
  } catch (err) {
    console.error('Failed to load machine data:', err);
    loadError.value = err.response?.data?.error || err.response?.data?.message || err.message || 'Gagal memuat data mesin.';
    showAlert('error', 'Gagal!', 'Gagal memuat data mesin. Silakan coba lagi.');
  } finally {
    loading.value = false;
  }
};

const initializeComponents = () => {
  if (!machine.value) return;

  componentsList.value = (machine.value.components ?? []).map(comp => {
    const indicators = comp.indicators ?? [];
    const hasInd = indicators.length > 0;
    
    // Initialize indicators as true (PASS) by default
    const indicatorValues = {};
    indicators.forEach(ind => {
      indicatorValues[ind.id] = true;
    });

    return {
      id: comp.id,
      name: comp.name,
      category: comp.category,
      specification: comp.specification,
      last_condition_pct: comp.last_condition_pct ?? 100,
      indicators,
      indicatorValues,
      checked: false,
      inProgress: false,
      conditionPct: comp.last_condition_pct ?? 100,
      description: '',
      is_component_replacement: false
    };
  });

  if (componentsList.value.length > 0) {
    activeComponent.value = componentsList.value[0];
  }

  // Pre-fill time inputs
  const now = new Date();
  const oneHourAgo = new Date(now.getTime() - 60 * 60 * 1000);
  startTime.value = formatTime(oneHourAgo);
  endTime.value = formatTime(now);
};

const formatTime = (date) => {
  const h = String(date.getHours()).padStart(2, '0');
  const m = String(date.getMinutes()).padStart(2, '0');
  return `${h}:${m}`;
};

// Check if active component has indicators
const hasIndicators = computed(() => {
  return activeComponent.value && activeComponent.value.indicators && activeComponent.value.indicators.length > 0;
});

// Select a component to inspect
const selectComponent = (comp) => {
  activeComponent.value = comp;
  showCommentBox.value = !!comp.description;
};

// Toggle checklist indicators and recalculate percentage
const toggleIndicator = (comp, indId) => {
  comp.indicatorValues[indId] = !comp.indicatorValues[indId];
  comp.inProgress = true;
  
  // Dynamic recalculation
  if (comp.indicators && comp.indicators.length > 0) {
    const total = comp.indicators.length;
    const passes = comp.indicators.filter(i => comp.indicatorValues[i.id]).length;
    comp.conditionPct = Math.round((passes / total) * 100);
  }
};

// Apply preset value
const applyPreset = (comp, value) => {
  comp.conditionPct = value;
  comp.inProgress = true;
};

// Complete current component section and move to next unchecked component
const completeComponentSection = (comp) => {
  comp.checked = true;
  comp.inProgress = false;
  
  showAlert('success', 'Bagian Selesai', `Pemeriksaan ${comp.name} telah selesai disimpan.`);

  // Find next unchecked component
  const nextUnchecked = componentsList.value.find(c => !c.checked && c.id !== comp.id);
  if (nextUnchecked) {
    selectComponent(nextUnchecked);
  }
};

// Computed Stats
const checkedCount = computed(() => {
  return componentsList.value.filter(c => c.checked).length;
});

const hasCheckedComponents = computed(() => {
  return checkedCount.value > 0;
});

const totalCompliance = computed(() => {
  if (componentsList.value.length === 0) return 100;
  
  // Calculate average condition of the machine
  const total = componentsList.value.reduce((sum, comp) => {
    // If checked/modified, use current conditionPct, otherwise use last_condition_pct database value
    const pct = comp.checked ? comp.conditionPct : comp.last_condition_pct;
    return sum + pct;
  }, 0);

  return Math.round(total / componentsList.value.length);
});

// Styling Helpers
const complianceColorClass = computed(() => {
  const compliance = totalCompliance.value;
  if (compliance < 50) return 'text-red-500';
  if (compliance < 80) return 'text-amber-500';
  return 'text-emerald-400';
});

const complianceBarClass = computed(() => {
  const compliance = totalCompliance.value;
  if (compliance < 50) return 'bg-red-500';
  if (compliance < 80) return 'bg-amber-500';
  return 'bg-emerald-500';
});

const condClass = (pct) => {
  if (pct < 50) return 'text-red-500';
  if (pct < 80) return 'text-amber-500';
  return 'text-emerald-400';
};

// Calculate duration from start and end time
const calculatedDuration = computed(() => {
  if (!startTime.value || !endTime.value) return 0;
  const [sh, sm] = startTime.value.split(':').map(Number);
  const [eh, em] = endTime.value.split(':').map(Number);
  
  let diffMin = (eh * 60 + em) - (sh * 60 + sm);
  if (diffMin < 0) diffMin += 24 * 60; // Over midnight
  
  return diffMin;
});

watch(calculatedDuration, (newVal) => {
  durationMinutes.value = newVal;
});

// Trigger save confirmation modal
const triggerSaveReport = () => {
  if (!hasCheckedComponents.value) {
    showAlert('warning', 'Perhatian', 'Selesaikan pemeriksaan minimal satu komponen sebelum menyimpan.');
    return;
  }
  showSaveModal.value = true;
};

// Submit report to backend
const submitReport = async () => {
  const checkedRows = componentsList.value.filter(c => c.checked);
  if (checkedRows.length === 0) {
    showAlert('warning', 'Perhatian', 'Konfirmasi minimal satu komponen terlebih dahulu.');
    return;
  }

  submitting.value = true;
  try {
    const actions = checkedRows.map(row => {
      const type = row.is_component_replacement ? 'replace' : 'inspect';
      
      const action = {
        machine_component_id: row.id,
        action_type: type,
        condition_before_pct: row.last_condition_pct,
        condition_after_pct: row.conditionPct,
        description: row.description || null,
      };

      if (row.indicators && row.indicators.length > 0) {
        action.indicator_values = row.indicators.map(ind => ({
          component_indicator_id: ind.id,
          value: !!row.indicatorValues[ind.id]
        }));
      }

      return action;
    });

    // Schedule detection
    const scheduleId = machine.value.schedules && machine.value.schedules.length > 0
      ? machine.value.schedules[0].id
      : null;

    const payload = {
      machine_id: machineId.value,
      schedule_id: scheduleId,
      is_unscheduled: !scheduleId,
      maintenance_date: new Date().toISOString().split('T')[0],
      start_time: startTime.value || null,
      end_time: endTime.value || null,
      duration_minutes: durationMinutes.value,
      status: 'completed',
      notes: generalNotes.value || `Pemeriksaan rutin - ${checkedRows.length} komponen selesai diperiksa`,
      actions
    };

    await axios.post('/api/records', payload);

    const successMsg = user.value?.role === 'technician'
      ? `Laporan berhasil dibuat untuk ${checkedRows.length} komponen. Menunggu approval dari manager/admin.`
      : `Laporan berhasil disimpan untuk ${checkedRows.length} komponen.`;

    await showAlert('success', 'Laporan Terkirim!', successMsg);
    
    // Redirect to Machine Detail
    showSaveModal.value = false;
    const detailPath = `/machine/${machineId.value}`;
    if (vueRouter) {
      vueRouter.push(detailPath);
    } else {
      window.location.href = detailPath;
    }
  } catch (err) {
    console.error('Failed to submit report:', err);
    showAlert('error', 'Gagal!', 'Gagal mengirim laporan: ' + (err.response?.data?.error || err.response?.data?.message || err.message));
  } finally {
    submitting.value = false;
  }
};

const goBack = () => {
  const detailPath = `/machine/${machineId.value}`;
  if (vueRouter) {
    vueRouter.push(detailPath);
  } else {
    window.location.href = detailPath;
  }
};

onMounted(() => {
  loadMachineData();
});
</script>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
  width: 5px;
}
.custom-scrollbar::-webkit-scrollbar-track {
  background: #f1f5f9;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
  background: #cbd5e1;
  border-radius: 9999px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
  background: #94a3b8;
}
</style>
