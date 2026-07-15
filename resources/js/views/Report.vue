<template>
  <div class="space-y-4 pb-24">

    <!-- ══════════════════════════════════════════════════════════════
         PAGE TITLE
    ═══════════════════════════════════════════════════════════════════ -->
    <div class="w-full max-w-5xl mx-auto">
      <PageHeader title="Laporan Maintenance" subtitle="Pilih mesin dan jadwal untuk memulai laporan pemeriksaan" />
    </div>

    <!-- ══════════════════════════════════════════════════════════════
         CARD 0: PILIH MESIN
    ═══════════════════════════════════════════════════════════════════ -->
    <div class="w-full max-w-5xl mx-auto bg-white rounded-2xl border border-slate-200 shadow-sm">
      <div class="px-5 py-3.5 border-b border-slate-100 flex items-center gap-2">
        <svg class="w-4 h-4 text-brand-brown shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
        <h2 class="text-sm font-bold text-slate-700">Pilih Mesin</h2>
      </div>
      <div class="p-5">
        <!-- Machine loading -->
        <div v-if="machinesLoading" class="flex items-center gap-2 text-slate-400 text-sm">
          <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
          Memuat daftar mesin...
        </div>
        <!-- Machine combobox -->
        <div v-else class="space-y-3">
          <div class="relative" v-click-outside="closeCombobox">
            <div class="relative">
              <input
                ref="comboboxInput"
                v-model="machineSearch"
                @focus="comboboxOpen = true"
                @input="comboboxOpen = true"
                @keydown.escape="closeCombobox"
                @keydown.enter.prevent="selectFirstFiltered"
                placeholder="Ketik nama mesin..."
                class="w-full text-sm border border-slate-200 rounded-xl px-4 py-2.5 focus:outline-none focus:ring-1 focus:ring-brand-brown text-slate-700 bg-white pr-10"
                autocomplete="off"
              />
              <button
                v-if="machineSearch || machine"
                @click="clearMachine"
                type="button"
                aria-label="Hapus pilihan mesin"
                class="absolute right-8 top-1/2 -translate-y-1/2 p-1 text-slate-400 hover:text-slate-600 hover:bg-slate-100 rounded-full cursor-pointer"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
              </button>
              <svg class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </div>
            <!-- Dropdown -->
            <div
              v-if="comboboxOpen && filteredMachines.length > 0"
              class="absolute z-30 mt-1 w-full bg-white border border-slate-200 rounded-xl shadow-lg overflow-hidden max-h-64 overflow-y-auto"
            >
              <template v-for="(group, city) in filteredGroupedMachines" :key="city">
                <div class="px-3 py-1.5 text-[10px] font-bold text-slate-400 uppercase tracking-wide bg-slate-50 border-b border-slate-100">{{ cityLabel(city) }}</div>
                <button
                  v-for="m in group" :key="m.id"
                  @mousedown.prevent="pickMachine(m)"
                  :class="[
                    selectedMachineId === m.id ? 'bg-brand-cream' : 'hover:bg-slate-50',
                    machineScheduleStatus(m).rowClass
                  ]"
                  class="w-full text-left px-4 py-2.5 text-sm transition-colors cursor-pointer flex items-center justify-between gap-3"
                >
                  <span :class="selectedMachineId === m.id ? 'font-bold text-brand-brown' : 'text-slate-700'">{{ m.name }}</span>
                  <span
                    v-if="machineScheduleStatus(m).label"
                    :class="machineScheduleStatus(m).badgeClass"
                    class="shrink-0 text-[10px] font-bold px-2 py-0.5 rounded-full whitespace-nowrap"
                  >{{ machineScheduleStatus(m).label }}</span>
                </button>
              </template>
            </div>
            <div
              v-else-if="comboboxOpen && machineSearch.length > 0 && filteredMachines.length === 0"
              class="absolute z-30 mt-1 w-full bg-white border border-slate-200 rounded-xl shadow-lg px-4 py-3 text-sm text-slate-400"
            >Tidak ada mesin ditemukan</div>
          </div>
          <!-- Selected machine info chips -->
          <div v-if="machine" class="flex flex-wrap gap-2 items-center">
            <span class="text-xs font-semibold px-2.5 py-1 rounded-lg bg-slate-100 text-slate-600">{{ machine.kode }}</span>
            <span v-if="machine.kota" class="text-xs font-bold px-2.5 py-1 rounded-lg bg-brand-cream text-brand-brown">{{ machine.kota === 'sby' ? 'Surabaya' : 'Pasuruan' }}</span>
            <span v-if="machine.location" class="text-xs text-slate-400">{{ machine.location }}</span>
            <span class="text-xs text-slate-400">·</span>
            <span class="text-xs font-semibold text-slate-500">{{ machine.components?.length || 0 }} komponen</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Loading mesin data -->
    <div v-if="loading" class="w-full max-w-5xl mx-auto bg-white rounded-2xl border border-slate-200 shadow-sm px-5 py-12 flex flex-col items-center gap-3 text-slate-400">
      <svg class="animate-spin w-8 h-8 text-brand-brown" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
      <p class="text-sm font-semibold text-brand-brown/70">Memuat data mesin...</p>
    </div>

    <!-- Error -->
    <div v-else-if="loadError" class="w-full max-w-5xl mx-auto bg-white rounded-2xl border border-red-100 shadow-sm px-5 py-8 flex flex-col items-center gap-3 text-slate-500">
      <svg class="w-10 h-10 text-red-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
      <p class="text-sm font-bold text-slate-700">{{ loadError }}</p>
      <button @click="loadMachineData" class="px-4 py-2 bg-brand-brown text-white text-xs font-bold rounded-xl cursor-pointer">Coba Lagi</button>
    </div>

    <template v-else-if="machine">

      <!-- ══════════════════════════════════════════════════════════════
           CARD 1: PILIH JADWAL
      ═══════════════════════════════════════════════════════════════════ -->
      <div class="w-full max-w-5xl mx-auto bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="px-5 py-3.5 border-b border-slate-100 flex items-center gap-2">
          <svg class="w-4 h-4 text-brand-brown shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
          <h2 class="text-sm font-bold text-slate-700">Pilih Jadwal Laporan</h2>
        </div>
        <div class="p-5 space-y-4">

          <!-- Period buttons -->
          <div>
            <p class="text-xs font-semibold text-slate-500 mb-2.5">
              {{ schedulePeriods.length > 0 ? 'Jadwal tersedia bulan ini:' : 'Tidak ada jadwal aktif untuk mesin ini.' }}
            </p>
            <div class="flex flex-wrap gap-2">
              <button
                v-for="(period, idx) in schedulePeriods"
                :key="idx"
                @click="selectPeriod(period)"
                :class="[
                  selectedPeriod?.label === period.label
                    ? 'border-brand-brown bg-brand-cream text-brand-brown ring-1 ring-brand-brown/30'
                    : 'border-slate-200 bg-white text-slate-600 hover:border-slate-300 hover:bg-slate-50',
                  'flex flex-col items-start px-4 py-2.5 rounded-xl border text-left transition-all cursor-pointer min-w-[140px]'
                ]"
              >
                <span class="text-xs font-bold">{{ period.label }}</span>
                <span class="text-[11px] mt-0.5" :class="selectedPeriod?.label === period.label ? 'text-brand-brown/70' : 'text-slate-400'">{{ period.dateStr }}</span>
                <span class="text-[10px] font-semibold mt-1 px-1.5 py-0.5 rounded-md" :class="period.statusClass">{{ period.statusLabel }}</span>
              </button>

              <button
                @click="selectUnscheduled"
                :class="[
                  isUnscheduled
                    ? 'border-indigo-400 bg-indigo-50 text-indigo-700 ring-1 ring-indigo-300'
                    : 'border-slate-200 bg-white text-slate-500 hover:border-slate-300 hover:bg-slate-50',
                  'flex flex-col items-start px-4 py-2.5 rounded-xl border text-left transition-all cursor-pointer min-w-[140px]'
                ]"
              >
                <span class="text-xs font-bold">Di Luar Jadwal</span>
                <span class="text-[11px] mt-0.5" :class="isUnscheduled ? 'text-indigo-500' : 'text-slate-400'">Maintenance di luar jadwal</span>
                <span class="text-[10px] font-semibold mt-1 px-1.5 py-0.5 rounded-md bg-indigo-100 text-indigo-600">Unscheduled</span>
              </button>
            </div>
          </div>

          <!-- Time inputs -->
        </div>
      </div>

      <!-- ══════════════════════════════════════════════════════════════
           PROGRESS
      ═══════════════════════════════════════════════════════════════════ -->
      <div class="w-full max-w-5xl mx-auto bg-white rounded-2xl border border-slate-200 shadow-sm px-5 py-4 space-y-3">
        <!-- Filter tabs -->
        <div class="flex items-center gap-2">
          <button
            v-for="tab in difficultyTabs" :key="tab.value"
            @click="difficultyFilter = tab.value"
            :class="difficultyFilter === tab.value
              ? 'bg-brand-brown text-white border-brand-brown'
              : 'bg-white text-slate-500 border-slate-200 hover:border-slate-300'"
            class="px-3 py-1 rounded-lg border text-xs font-bold transition-all cursor-pointer"
          >{{ tab.label }} <span class="ml-1 font-mono">{{ tab.count }}</span></button>
        </div>
        <!-- Bar -->
        <div>
          <div class="flex items-center justify-between mb-1.5">
            <div class="flex items-center gap-2">
              <span class="text-sm font-bold text-slate-700">Progress Inspeksi</span>
              <span class="text-xs font-semibold px-2 py-0.5 rounded-full" :class="filteredCheckedCount === filteredComponentsList.length ? 'bg-emerald-50 text-emerald-600' : 'bg-slate-100 text-slate-500'">
                {{ filteredCheckedCount }} / {{ filteredComponentsList.length }} komponen
              </span>
              <span v-if="filteredPendingComponents.length > 0" class="text-xs font-semibold px-2 py-0.5 rounded-full bg-amber-50 text-amber-600">
                {{ filteredPendingComponents.length }} belum
              </span>
            </div>
            <span class="text-sm font-black" :class="complianceColorClass">{{ totalCompliance }}%</span>
          </div>
          <div class="w-full bg-slate-100 h-2.5 rounded-full overflow-hidden">
            <div :class="complianceBarClass" class="h-full rounded-full transition-all duration-500"
              :style="{ width: `${Math.round((filteredCheckedCount / Math.max(filteredComponentsList.length,1)) * 100)}%` }"></div>
          </div>
        </div>
        <!-- Pending chips -->
        <!-- <div v-if="filteredPendingComponents.length > 0" class="flex flex-wrap gap-1.5">
          <span class="text-[11px] text-slate-400 font-semibold self-center">Belum:</span>
          <span
            v-for="c in filteredPendingComponents" :key="c.id"
            @click="scrollToComponent(c.id)"
            class="text-[11px] px-2 py-0.5 rounded-md bg-amber-50 border border-amber-200 text-amber-700 font-semibold cursor-pointer hover:bg-amber-100 transition-colors"
          >{{ c.name }}</span>
        </div> -->
      </div>

      <!-- ══════════════════════════════════════════════════════════════
           CARD 2: TABEL KOMPONEN
      ═══════════════════════════════════════════════════════════════════ -->
      <div class="w-full max-w-5xl mx-auto bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="px-5 py-3.5 border-b border-slate-100 flex items-center justify-between">
          <div class="flex items-center gap-2">
            <svg class="w-4 h-4 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
            <h2 class="text-sm font-bold text-slate-700">Daftar Komponen & Indikator</h2>
          </div>
          <span class="text-xs text-slate-400">{{ filteredComponentsList.length }} komponen ditampilkan</span>
        </div>

        <div class="overflow-x-auto">
          <table class="w-full min-w-[700px] lg:w-auto lg:min-w-0 text-sm">
            <thead>
              <tr class="bg-slate-50 border-b border-slate-100">
                <th class="px-4 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wide">Komponen</th>
                <th class="px-4 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wide hidden sm:table-cell">Deskripsi</th>
                <th class="py-3 px-1 text-center text-xs font-bold text-slate-500 uppercase tracking-wide sm:hidden" style="width:100px">Status</th>
                <th class="py-3 px-2 text-center text-xs font-bold text-slate-500 uppercase tracking-wide hidden sm:table-cell w-40">Status</th>
              </tr>
            </thead>
            <tbody>
              <template v-for="comp in filteredComponentsList" :key="comp.id">

                <!-- Component header row -->
                <tr :id="`comp-${comp.id}`"
                  :class="comp.hasError ? 'border-l-2 border-l-red-400 bg-red-50/40' : 'bg-slate-50/60'"
                  class="border-t border-slate-100"
                >
                  <td colspan="4" class="pl-2 pr-2 sm:px-4 py-2.5">
                    <div class="flex items-start justify-between gap-2">
                      <!-- Kiri: ikon status + nama -->
                      <div class="flex items-start gap-2 min-w-0 flex-1">
                      <span v-if="isCompDone(comp)" class="shrink-0 mt-0.5 w-6 h-6 rounded-full bg-emerald-100 flex items-center justify-center">
                        <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                      </span>
                      <span v-else-if="comp.hasError" class="shrink-0 mt-0.5 w-6 h-6 rounded-full bg-red-100 flex items-center justify-center">
                        <svg class="w-3.5 h-3.5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01"/><circle cx="12" cy="12" r="9" stroke-width="2"/></svg>
                      </span>
                      <span v-else-if="comp.inProgress" class="shrink-0 mt-0.5 w-6 h-6 rounded-full bg-amber-100 flex items-center justify-center">
                        <svg class="w-3.5 h-3.5 text-amber-500" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                      </span>
                      <span v-else class="shrink-0 mt-0.5 w-6 h-6 rounded-full bg-slate-100 flex items-center justify-center">
                        <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9" stroke-width="2"/></svg>
                      </span>
                      <div class="min-w-0">
                        <span class="font-bold text-sm text-slate-800">{{ comp.name }}</span>
                        <div class="flex flex-wrap items-center gap-1 mt-0.5">
                          <span v-if="comp.category" class="text-[10px] px-1.5 py-0.5 rounded bg-slate-200 text-slate-500 font-semibold">{{ comp.category }}</span>
                          <span v-if="comp.difficulty" :class="comp.difficulty === 'berat' ? 'bg-red-100 text-red-600' : 'bg-sky-100 text-sky-600'" class="text-[10px] px-1.5 py-0.5 rounded font-semibold capitalize">{{ comp.difficulty }}</span>
                          <span v-if="isCompDone(comp)" class="text-[10px] px-1.5 py-0.5 rounded bg-emerald-100 text-emerald-600 font-semibold">{{ comp.conditionPct }}% OK</span>
                          <span v-else-if="comp.inProgress" class="text-[10px] px-1.5 py-0.5 rounded bg-amber-100 text-amber-600 font-semibold">{{ comp.conditionPct }}%</span>
                          <span v-if="comp.hasError" class="text-[10px] px-1.5 py-0.5 rounded bg-red-100 text-red-600 font-semibold">Ada indikator belum diisi</span>
                        </div>
                      </div>
                      </div>
                      <!-- Kanan: tombol desktop (besar) + mobile (kecil) -->
                      <div class="flex items-center gap-1.5 shrink-0 self-center">
                      <button
                        @click="comp.is_component_replacement = !comp.is_component_replacement"
                        :class="comp.is_component_replacement ? 'bg-amber-500 border-amber-400 text-white' : 'bg-white border-amber-200 text-slate-500 hover:border-amber-500'"
                        class="hidden sm:flex items-center gap-1.5 px-2.5 py-2 rounded-lg border text-[11px] font-semibold transition-all cursor-pointer"
                      >
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        {{ comp.is_component_replacement ? 'Diganti' : 'Ganti?' }}
                      </button>
                      <button
                        @click="comp.is_component_replacement = !comp.is_component_replacement"
                        :class="comp.is_component_replacement ? 'bg-amber-500 border-amber-400 text-white' : 'bg-white border-amber-200 text-slate-500'"
                        class="sm:hidden flex items-center gap-1 px-2 py-2 rounded border text-[10px] font-semibold transition-all cursor-pointer"
                      >
                        <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        {{ comp.is_component_replacement ? 'Diganti' : 'Ganti?' }}
                      </button>
                      <button
                        @click="comp.showNote = !comp.showNote"
                        :class="comp.showNote || comp.description ? 'bg-indigo-50 border-indigo-200 text-indigo-600' : 'bg-white border-slate-200 text-slate-500 hover:border-slate-300'"
                        class="hidden sm:flex items-center gap-1.5 px-2.5 py-2 rounded-lg border text-[11px] font-semibold transition-all cursor-pointer"
                      >
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/></svg>
                        Catatan
                      </button>
                      <button
                        @click="comp.showNote = !comp.showNote"
                        :class="comp.showNote || comp.description ? 'bg-indigo-50 border-indigo-200 text-indigo-600' : 'bg-white border-slate-200 text-slate-500'"
                        class="sm:hidden flex items-center gap-1 px-2 py-2 rounded border text-[10px] font-semibold transition-all cursor-pointer"
                      >
                        <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/></svg>
                        Catatan
                      </button>
                      </div>
                    </div>
                    <div v-if="comp.showNote || comp.description" class="mt-2.5">
                      <textarea
                        v-model="comp.description"
                        placeholder="Catatan: temuan, tindakan perbaikan, nomor seri komponen pengganti..."
                        rows="2"
                        class="w-full text-xs border border-slate-200 rounded-xl px-3 py-2 focus:outline-none focus:ring-1 focus:ring-indigo-400 text-slate-700 placeholder-slate-400 resize-none"
                      ></textarea>
                    </div>
                  </td>
                </tr>

                <!-- Indicator rows -->
                <template v-if="comp.indicators.length > 0">
                  <tr
                    v-for="(ind, iIdx) in comp.indicators" :key="ind.id"
                    :class="[
                      iIdx % 2 === 0 ? 'bg-white' : 'bg-slate-50/40',
                      comp.indicatorValues[ind.id] === null && comp.hasError ? 'outline outline-1 outline-red-200' : ''
                    ]"
                    class="border-t border-slate-100/60"
                  >
                    <td class="pl-2 pr-1 sm:px-4 py-2.5">
                      <span class="text-xs font-semibold text-slate-600 pl-3 sm:pl-8">{{ ind.name }}</span>
                      <p v-if="ind.description" class="text-[11px] text-slate-400 pl-3 sm:pl-8 mt-0.5 leading-relaxed sm:hidden">{{ ind.description }}</p>
                    </td>
                    <td class="px-4 py-2.5 hidden sm:table-cell">
                      <span class="text-xs text-slate-500 leading-relaxed">{{ ind.description || '-' }}</span>
                    </td>
                    <td class="py-2 pl-1 pr-2 text-center sm:hidden" style="width:100px">
                      <div class="flex items-center justify-center gap-2">
                        <button
                          @click="setIndicator(comp, ind.id, true)"
                          :class="comp.indicatorValues[ind.id] === true ? 'bg-emerald-500 text-white border-emerald-400 shadow-sm' : 'bg-white text-slate-500 border-slate-200'"
                          class="w-14 py-4 rounded border text-[10px] font-bold transition-all cursor-pointer text-center"
                        >OK</button>
                        <button
                          @click="setIndicator(comp, ind.id, false)"
                          :class="comp.indicatorValues[ind.id] === false ? 'bg-red-500 text-white border-red-400 shadow-sm' : 'bg-white text-slate-500 border-slate-200'"
                          class="w-14 py-4 rounded border text-[10px] font-bold transition-all cursor-pointer text-center"
                        >Not OK</button>
                        <span v-if="comp.indicatorValues[ind.id] === null && comp.hasError" class="text-[10px] text-red-500 font-semibold">!</span>
                      </div>
                    </td>
                    <td class="py-2.5 px-2 text-center hidden sm:table-cell">
                      <div class="flex flex-nowrap items-center justify-center gap-2">
                        <button
                          @click="setIndicator(comp, ind.id, true)"
                          :class="comp.indicatorValues[ind.id] === true ? 'bg-emerald-500 text-white border-emerald-400 shadow-sm' : 'bg-white text-slate-500 border-slate-200 hover:border-emerald-300 hover:text-emerald-600'"
                          class="px-4 py-2 rounded-lg border text-[11px] font-bold transition-all cursor-pointer"
                        >OK</button>
                        <button
                          @click="setIndicator(comp, ind.id, false)"
                          :class="comp.indicatorValues[ind.id] === false ? 'bg-red-500 text-white border-red-400 shadow-sm' : 'bg-white text-slate-500 border-slate-200 hover:border-red-300 hover:text-red-500'"
                          class="px-4 py-2 rounded-lg border text-[11px] font-bold transition-all cursor-pointer"
                        >Not OK</button>
                        <span v-if="comp.indicatorValues[ind.id] === null && comp.hasError" class="text-[10px] text-red-500 font-semibold">!</span>
                      </div>
                    </td>
                  </tr>
                </template>

                <!-- Manual input row -->
                <tr v-else class="border-t border-slate-100/60 bg-white">
                  <td class="px-4 py-3">
                    <span class="text-xs text-slate-400 pl-8 italic">Input manual</span>
                  </td>
                  <td class="px-4 py-3 hidden sm:table-cell">
                    <div class="flex items-center gap-3">
                      <input type="range" min="0" max="100" step="5" v-model.number="comp.conditionPct"
                        @input="comp.inProgress = true"
                        class="flex-1 h-1.5 accent-brand-brown cursor-pointer"/>
                      <div class="flex items-center gap-1">
                        <input type="number" min="0" max="100" step="1" v-model.number="comp.conditionPct"
                          @input="comp.inProgress = true"
                          class="w-14 text-right text-xs font-black font-mono border border-slate-200 rounded-lg px-2 py-1 focus:outline-none focus:ring-1 focus:ring-brand-brown" :class="condClass(comp.conditionPct)"/>
                        <span class="text-xs font-black font-mono text-slate-400">%</span>
                      </div>
                    </div>
                    <div class="flex gap-1.5 mt-2">
                      <button v-for="p in presets" :key="p.val" @click="applyPreset(comp, p.val)"
                        :class="comp.conditionPct === p.val ? 'bg-brand-brown text-white border-brand-brown' : 'bg-slate-50 text-slate-600 border-slate-200 hover:bg-slate-100'"
                        class="px-2 py-0.5 rounded-lg border text-[10px] font-bold cursor-pointer transition-colors"
                      >{{ p.label }}</button>
                    </div>
                  </td>
                  <td class="py-2 pl-1 pr-2 text-center sm:hidden" style="width:100px">
                    <div class="flex items-center justify-center gap-1">
                      <input type="number" min="0" max="100" step="5" v-model.number="comp.conditionPct"
                        @input="comp.inProgress = true"
                        class="w-12 text-center text-xs font-bold border border-slate-200 rounded px-1 py-1 focus:outline-none focus:ring-1 focus:ring-brand-brown" :class="condClass(comp.conditionPct)"/>
                      <span class="text-[10px] text-slate-400">%</span>
                    </div>
                  </td>
                  <td class="px-4 py-3 text-center hidden sm:table-cell">
                    <span class="text-xs font-bold" :class="condClass(comp.conditionPct)">{{ comp.conditionPct }}%</span>
                  </td>
                </tr>

              </template>
            </tbody>
          </table>
        </div>
      </div>

    </template>

    <button
      v-if="machine"
      @click="triggerSaveReport"
      :disabled="submitting || !hasCheckedComponents"
      class="fixed bottom-4 right-4 z-40 flex items-center gap-2 px-4 py-3 sm:px-5 sm:py-2.5 bg-emerald-600 hover:bg-emerald-500 disabled:bg-slate-100 disabled:text-slate-400 disabled:cursor-not-allowed text-white text-sm font-bold rounded-2xl shadow-lg transition-all cursor-pointer"
    >
      <svg v-if="submitting" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
      <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
      Simpan Laporan
    </button>

    <!-- ══════════════════════════════════════════════════════════════
         SAVE MODAL
    ═══════════════════════════════════════════════════════════════════ -->
    <div v-if="showSaveModal && machine" class="fixed inset-0 z-50 flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm" @click="showSaveModal = false"></div>
      <div class="bg-white rounded-2xl w-full max-w-md relative z-10 shadow-2xl overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100 flex justify-between items-center">
          <div>
            <h3 class="text-base font-bold text-slate-800">Konfirmasi Laporan</h3>
            <p class="text-xs text-slate-400 mt-0.5">Periksa ringkasan sebelum mengirim</p>
          </div>
          <button @click="showSaveModal = false" class="text-slate-400 hover:text-slate-600 p-1.5 rounded-full hover:bg-slate-100 cursor-pointer">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
          </button>
        </div>
        <div class="p-6 space-y-4">
          <div class="bg-slate-50 rounded-xl p-4 space-y-2 text-sm">
            <div class="flex justify-between gap-4"><span class="text-slate-500">Mesin</span><span class="font-semibold text-slate-700 text-right">{{ machine.name }}</span></div>
            <div class="flex justify-between gap-4"><span class="text-slate-500">Jadwal bulan ini</span><span class="font-semibold text-slate-700 text-right">{{ reportScheduleLabel }}</span></div>
            <div class="flex justify-between gap-4"><span class="text-slate-500">Status jadwal</span><span class="font-bold text-right" :class="reportScheduleClass">{{ reportScheduleStatus }}</span></div>
            <div class="flex justify-between"><span class="text-slate-500">Komponen ringan</span><span class="font-bold text-emerald-600">{{ lightReportedCount }} / {{ lightComponentCount }}</span></div>
            <div class="flex justify-between"><span class="text-slate-500">Komponen berat</span><span class="font-bold text-emerald-600">{{ heavyReportedCount }} / {{ heavyComponentCount }}</span></div>
          </div>
          <div class="grid grid-cols-3 gap-3">
            <div class="space-y-1">
              <label class="text-[11px] font-bold text-slate-500 uppercase tracking-wide">Jam Mulai</label>
              <input type="time" v-model="startTime" required class="w-full text-sm border border-slate-200 rounded-xl px-3 py-2 focus:outline-none focus:ring-1 focus:ring-brand-brown text-slate-700"/>
            </div>
            <div class="space-y-1">
              <label class="text-[11px] font-bold text-slate-500 uppercase tracking-wide">Jam Selesai</label>
              <input type="time" v-model="endTime" required class="w-full text-sm border border-slate-200 rounded-xl px-3 py-2 focus:outline-none focus:ring-1 focus:ring-brand-brown text-slate-700"/>
            </div>
            <div class="space-y-1">
              <label class="text-[11px] font-bold text-slate-500 uppercase tracking-wide">Durasi</label>
              <div class="w-full text-sm border border-slate-100 rounded-xl px-3 py-2 bg-slate-50 text-slate-500 font-mono select-none">
                {{ calculatedDuration > 0 ? calculatedDuration + ' menit' : '-' }}
              </div>
            </div>
          </div>
          <div class="space-y-1.5">
            <label class="text-xs font-bold text-slate-600 uppercase tracking-wide">Catatan Umum</label>
            <textarea v-model="generalNotes" placeholder="Ringkasan pekerjaan atau kendala..." rows="3"
              class="w-full text-sm border border-slate-200 rounded-xl px-3.5 py-2.5 focus:outline-none focus:ring-1 focus:ring-brand-brown text-slate-700 placeholder-slate-400 resize-none"></textarea>
          </div>
        </div>
        <div class="px-6 py-4 border-t border-slate-100 bg-slate-50 flex justify-end gap-3">
          <button @click="showSaveModal = false" class="px-4 py-2 text-slate-500 hover:text-slate-800 text-sm font-semibold cursor-pointer">Batal</button>
          <button @click="submitReport" :disabled="submitting"
            class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-sm rounded-xl shadow cursor-pointer disabled:opacity-60 flex items-center gap-2">
            <svg v-if="submitting" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
            Kirim Laporan
          </button>
        </div>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, computed, onMounted, nextTick } from 'vue';
import axios from 'axios';
import { showAlert } from '../composables/useAlert.js';
import { useAuth } from '../composables/useAuth.js';
import PageHeader from '../components/PageHeader.vue';

const { user } = useAuth();

// ── v-click-outside directive ──────────────────────────────────────────────
const vClickOutside = {
  mounted(el, binding) {
    el._clickOutside = (e) => { if (!el.contains(e.target)) binding.value(); };
    document.addEventListener('mousedown', el._clickOutside);
  },
  unmounted(el) { document.removeEventListener('mousedown', el._clickOutside); }
};

// ── Machines list / combobox ───────────────────────────────────────────────
const machinesList = ref([]);
const machinesLoading = ref(true);
const selectedMachineId = ref('');
const machineSearch = ref('');
const comboboxOpen = ref(false);
const comboboxInput = ref(null);

const filteredMachines = computed(() => {
  const q = machineSearch.value.trim().toLowerCase();
  if (!q) return machinesList.value;
  return machinesList.value.filter(m => m.name.toLowerCase().includes(q));
});

const filteredGroupedMachines = computed(() => {
  const groups = {};
  for (const m of filteredMachines.value) {
    const city = m.kota || 'lainnya';
    if (!groups[city]) groups[city] = [];
    groups[city].push(m);
  }
  return groups;
});

const cityLabel = (city) => {
  const map = { sby: 'Surabaya', pasuruan: 'Pasuruan', lainnya: 'Lainnya' };
  return map[city] ?? city;
};

// ── Machine schedule status for dropdown badge ────────────────────────────
const machineScheduleStatus = (m) => {
  const today = new Date(); today.setHours(0,0,0,0);
  const monthStart = new Date(today.getFullYear(), today.getMonth(), 1);
  const monthEnd = new Date(today.getFullYear(), today.getMonth() + 1, 0);

  // Check if already has approved/completed record this month
  const records = m.records ?? [];
  const doneThisMonth = records.some(r => {
    if (!r.maintenance_date) return false;
    const d = new Date(r.maintenance_date);
    return d >= monthStart && d <= monthEnd && r.status === 'completed';
  });
  if (doneThisMonth) return { label: 'Sudah dicek', badgeClass: 'bg-emerald-100 text-emerald-700', rowClass: '' };

  const schedules = (m.schedules ?? []).filter(s => s.is_active !== false && s.next_due_date);
  if (!schedules.length) return { label: null, badgeClass: '', rowClass: '' };

  const nextDue = new Date(schedules[0].next_due_date); nextDue.setHours(0,0,0,0);
  const diffDays = Math.ceil((nextDue - today) / 86400000);

  if (diffDays < 0) return { label: `Terlambat ${Math.abs(diffDays)}h`, badgeClass: 'bg-red-100 text-red-700', rowClass: 'bg-red-50/30' };
  if (diffDays === 0) return { label: 'Hari ini', badgeClass: 'bg-emerald-100 text-emerald-700', rowClass: 'bg-emerald-50/20' };
  if (diffDays === 1) return { label: 'Besok', badgeClass: 'bg-amber-100 text-amber-700', rowClass: '' };
  if (diffDays <= 7) return { label: `${diffDays} hari lagi`, badgeClass: 'bg-blue-100 text-blue-600', rowClass: '' };
  return { label: `${diffDays}h lagi`, badgeClass: 'bg-slate-100 text-slate-500', rowClass: '' };
};

const closeCombobox = () => { comboboxOpen.value = false; };

const pickMachine = (m) => {
  selectedMachineId.value = m.id;
  machineSearch.value = m.name;
  comboboxOpen.value = false;
  loadMachineData(m.id);
};

const clearMachine = () => {
  machineSearch.value = '';
  comboboxOpen.value = true;
  nextTick(() => comboboxInput.value?.focus());
};

const selectFirstFiltered = () => {
  if (filteredMachines.value.length > 0) pickMachine(filteredMachines.value[0]);
};

const loadMachinesList = async () => {
  machinesLoading.value = true;
  try {
    const res = await axios.get('/api/machines');
    machinesList.value = res.data?.data ?? res.data ?? [];
  } catch (err) {
    console.error('Failed to load machines list:', err);
  } finally {
    machinesLoading.value = false;
  }
};

// ── Selected machine data ──────────────────────────────────────────────────
const machine = ref(null);
const loading = ref(false);
const submitting = ref(false);
const loadError = ref(null);
const componentsList = ref([]);

const machineId = computed(() => machine.value?.id ?? null);

// ── Schedule selection ─────────────────────────────────────────────────────
const selectedPeriod = ref(null);
const isUnscheduled = ref(false);

// ── Time inputs (no date — auto today) ────────────────────────────────────
const maintenanceDate = ref(new Date().toISOString().split('T')[0]);
const startTime = ref('');
const endTime = ref('');
const generalNotes = ref('');
const showSaveModal = ref(false);

const presets = [
  { val: 100, label: 'OK' },
  { val: 80, label: 'Minor' },
  { val: 50, label: 'Medium' },
  { val: 0, label: 'Broken' }
];

// ── Duration (computed, read-only) ─────────────────────────────────────────
const calculatedDuration = computed(() => {
  if (!startTime.value || !endTime.value) return 0;
  const [sh, sm] = startTime.value.split(':').map(Number);
  const [eh, em] = endTime.value.split(':').map(Number);
  let diff = (eh * 60 + em) - (sh * 60 + sm);
  if (diff < 0) diff += 1440;
  return diff;
});

// ── Helpers ────────────────────────────────────────────────────────────────
const formatTime = (date) =>
  `${String(date.getHours()).padStart(2,'0')}:${String(date.getMinutes()).padStart(2,'0')}`;

const formatDateStr = (date) =>
  date.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });

const condClass = (pct) => {
  if (pct < 50) return 'text-red-500';
  if (pct < 80) return 'text-amber-500';
  return 'text-emerald-500';
};

// ── Schedule Periods ───────────────────────────────────────────────────────
const schedulePeriods = computed(() => {
  const schedules = (machine.value?.schedules ?? []).filter(s => s.is_active !== false && s.interval_days && s.next_due_date);
  if (!schedules.length) return [];

  const today = new Date(); today.setHours(0,0,0,0);
  const sched = schedules[0];
  const intervalDays = Number(sched.interval_days);
  const intervalMs = intervalDays * 86400000;

  const monthStart = new Date(today.getFullYear(), today.getMonth(), 1);
  const monthEnd = new Date(today.getFullYear(), today.getMonth() + 1, 0);
  monthStart.setHours(0,0,0,0); monthEnd.setHours(0,0,0,0);

  let cursor = new Date(sched.next_due_date); cursor.setHours(0,0,0,0);
  while (cursor > monthStart) cursor = new Date(cursor.getTime() - intervalMs);
  if (cursor < monthStart) cursor = new Date(cursor.getTime() + intervalMs);

  const periods = [];
  let idx = 1;
  while (cursor <= monthEnd) {
    const due = new Date(cursor);
    const diffDays = Math.ceil((due - today) / 86400000);
    let statusLabel, statusClass;
    if (diffDays > 1) { statusLabel = `${diffDays} hari lagi`; statusClass = 'bg-blue-50 text-blue-600'; }
    else if (diffDays === 1) { statusLabel = 'Besok'; statusClass = 'bg-amber-50 text-amber-600'; }
    else if (diffDays === 0) { statusLabel = 'Hari ini'; statusClass = 'bg-emerald-50 text-emerald-600'; }
    else { statusLabel = `Terlambat ${Math.abs(diffDays)} hari`; statusClass = 'bg-red-50 text-red-600'; }
    const label = intervalDays <= 14 ? `Week ${idx}` : intervalDays <= 21 ? `Periode ${idx}` : 'Bulan Ini';
    periods.push({ label, dateStr: formatDateStr(due), dueDate: due, scheduleId: sched.id, statusLabel, statusClass, diffDays });
    cursor = new Date(cursor.getTime() + intervalMs);
    idx++;
  }
  return periods;
});

const selectPeriod = (period) => {
  selectedPeriod.value = period;
  isUnscheduled.value = false;
  maintenanceDate.value = period.dueDate.toISOString().split('T')[0];
};

const selectUnscheduled = () => {
  isUnscheduled.value = true;
  selectedPeriod.value = null;
  maintenanceDate.value = new Date().toISOString().split('T')[0];
};

// ── Components Init ────────────────────────────────────────────────────────
const initializeComponents = () => {
  if (!machine.value) return;
  componentsList.value = (machine.value.components ?? []).map(comp => {
    const indicators = comp.indicators ?? [];
    const indicatorValues = {};
    indicators.forEach(ind => { indicatorValues[ind.id] = null; });
    return {
      id: comp.id,
      name: comp.name,
      category: comp.category,
      difficulty: comp.difficulty ?? null,
      specification: comp.specification,
      last_condition_pct: comp.last_condition_pct ?? 100,
      indicators,
      indicatorValues,
      inProgress: false,
      conditionPct: comp.last_condition_pct ?? 100,
      description: '',
      showNote: false,
      hasError: false,
      is_component_replacement: false
    };
  });

  startTime.value = '';
  endTime.value = '';

  if (schedulePeriods.value.length > 0) {
    const urgent = schedulePeriods.value.find(p => p.diffDays <= 0) ?? schedulePeriods.value[0];
    selectPeriod(urgent);
  } else {
    selectUnscheduled();
  }
};

// ── Machine load ───────────────────────────────────────────────────────────
const loadMachineData = async (id) => {
  if (!id) return;
  loading.value = true;
  loadError.value = null;
  machine.value = null;
  componentsList.value = [];
  try {
    const res = await axios.get(`/api/machines/${id}`);
    machine.value = res.data;
    initializeComponents();
  } catch (err) {
    loadError.value = err.response?.data?.message || err.message || 'Gagal memuat data mesin.';
    showAlert('error', 'Gagal!', loadError.value);
  } finally {
    loading.value = false;
  }
};

// ── Indicator / manual actions ─────────────────────────────────────────────
const setIndicator = (comp, indId, value) => {
  comp.indicatorValues[indId] = value;
  comp.inProgress = true;
  comp.hasError = false;
  const passes = comp.indicators.filter(i => comp.indicatorValues[i.id] === true).length;
  comp.conditionPct = Math.round((passes / comp.indicators.length) * 100);
};

const applyPreset = (comp, value) => {
  comp.conditionPct = value;
  comp.inProgress = true;
};

// ── isCompDone: komponen dianggap selesai jika semua indikator terisi (atau manual diisi) ──
const isCompDone = (comp) => {
  if (comp.indicators.length === 0) return comp.inProgress;
  return comp.indicators.every(ind => comp.indicatorValues[ind.id] !== null);
};

// ── Difficulty filter ──────────────────────────────────────────────────────
const difficultyFilter = ref('semua');

const difficultyTabs = computed(() => {
  const all = componentsList.value;
  const ringan = all.filter(c => c.difficulty === 'ringan');
  const berat = all.filter(c => c.difficulty !== 'ringan');
  return [
    { value: 'semua', label: 'Semua', count: all.length },
    { value: 'ringan', label: 'Ringan', count: ringan.length },
    { value: 'berat', label: 'Berat', count: berat.length },
  ];
});

const filteredComponentsList = computed(() => {
  if (difficultyFilter.value === 'semua') return componentsList.value;
  if (difficultyFilter.value === 'ringan') return componentsList.value.filter(c => c.difficulty === 'ringan');
  return componentsList.value.filter(c => c.difficulty !== 'ringan');
});

const filteredCheckedCount = computed(() => filteredComponentsList.value.filter(c => isCompDone(c)).length);
const filteredPendingComponents = computed(() => filteredComponentsList.value.filter(c => !isCompDone(c)));

// ── Global stats ───────────────────────────────────────────────────────────
const doneCount = computed(() => componentsList.value.filter(c => isCompDone(c)).length);
const hasCheckedComponents = computed(() => doneCount.value > 0);
const lightComponentCount = computed(() => componentsList.value.filter(c => c.difficulty === 'ringan').length);
const heavyComponentCount = computed(() => componentsList.value.filter(c => c.difficulty !== 'ringan').length);
const lightReportedCount = computed(() => componentsList.value.filter(c => c.difficulty === 'ringan' && isCompDone(c)).length);
const heavyReportedCount = computed(() => componentsList.value.filter(c => c.difficulty !== 'ringan' && isCompDone(c)).length);
const reportScheduleLabel = computed(() => {
  if (isUnscheduled.value) return 'Di Luar Jadwal';
  if (!selectedPeriod.value) return '-';
  return `${selectedPeriod.value.label} · ${selectedPeriod.value.dateStr}`;
});
const reportScheduleStatus = computed(() => {
  if (isUnscheduled.value) return 'Di luar jadwal';
  if (!selectedPeriod.value) return '-';
  return selectedPeriod.value.diffDays < 0
    ? `Terlambat ${Math.abs(selectedPeriod.value.diffDays)} hari`
    : 'Tepat waktu';
});
const reportScheduleClass = computed(() => {
  if (isUnscheduled.value) return 'text-indigo-600';
  if (selectedPeriod.value?.diffDays < 0) return 'text-red-600';
  return 'text-emerald-600';
});

const totalCompliance = computed(() => {
  if (!componentsList.value.length) return 100;
  const total = componentsList.value.reduce((sum, comp) => {
    return sum + (isCompDone(comp) ? comp.conditionPct : comp.last_condition_pct);
  }, 0);
  return Math.round(total / componentsList.value.length);
});

const complianceColorClass = computed(() => {
  const v = totalCompliance.value;
  if (v < 50) return 'text-red-500';
  if (v < 80) return 'text-amber-500';
  return 'text-emerald-500';
});

const complianceBarClass = computed(() => {
  const v = totalCompliance.value;
  if (v < 50) return 'bg-red-500';
  if (v < 80) return 'bg-amber-500';
  return 'bg-emerald-500';
});

const scrollToComponent = (compId) => {
  nextTick(() => {
    const el = document.getElementById(`comp-${compId}`);
    if (el) el.scrollIntoView({ behavior: 'smooth', block: 'center' });
  });
};

// ── Save / Submit ──────────────────────────────────────────────────────────
const triggerSaveReport = () => {
  if (!hasCheckedComponents.value) {
    showAlert('warning', 'Perhatian', 'Isi minimal satu komponen sebelum menyimpan.');
    return;
  }

  // Validate: only components that have been partially touched must be fully complete
  let hasValidationError = false;
  let firstErrorId = null;
  for (const comp of componentsList.value) {
    if (comp.indicators.length === 0) continue;
    const anyFilled = comp.indicators.some(ind => comp.indicatorValues[ind.id] !== null);
    if (!anyFilled) {
      comp.hasError = false;
      continue;
    }
    const allFilled = comp.indicators.every(ind => comp.indicatorValues[ind.id] !== null);
    comp.hasError = !allFilled;
    if (!allFilled) {
      hasValidationError = true;
      if (!firstErrorId) firstErrorId = comp.id;
    }
  }

  if (hasValidationError) {
    if (firstErrorId) scrollToComponent(firstErrorId);
    showAlert('warning', 'Ada Indikator Belum Diisi', 'Komponen yang sudah mulai diisi harus dilengkapi semua indikatornya.');
    return;
  }

  showSaveModal.value = true;
};

const submitReport = async () => {
  const doneRows = componentsList.value.filter(c => isCompDone(c));
  if (!doneRows.length) return;
  if (!startTime.value || !endTime.value) {
    showAlert('warning', 'Jam Belum Lengkap', 'Isi jam mulai dan jam selesai sebelum mengirim laporan.');
    return;
  }
  submitting.value = true;
  try {
    const actions = doneRows.map(row => {
      const action = {
        machine_component_id: row.id,
        action_type: row.is_component_replacement ? 'replace' : 'inspect',
        condition_before_pct: row.last_condition_pct,
        condition_after_pct: row.conditionPct,
        description: row.description || null,
      };
      if (row.indicators.length > 0) {
        action.indicator_values = row.indicators.map(ind => ({
          component_indicator_id: ind.id,
          value: row.indicatorValues[ind.id] === true
        }));
      }
      return action;
    });

    const scheduleId = selectedPeriod.value?.scheduleId ?? null;
    const payload = {
      machine_id: machineId.value,
      schedule_id: scheduleId,
      is_unscheduled: isUnscheduled.value || !scheduleId,
      maintenance_date: maintenanceDate.value,
      start_time: startTime.value || null,
      end_time: endTime.value || null,
      duration_minutes: calculatedDuration.value,
      status: 'completed',
      notes: generalNotes.value || `Pemeriksaan rutin - ${doneRows.length} komponen diperiksa`,
      actions
    };

    await axios.post('/api/records', payload);
    const msg = user.value?.role === 'technician'
      ? 'Laporan berhasil dibuat. Menunggu approval manager/admin.'
      : `Laporan berhasil disimpan untuk ${doneRows.length} komponen.`;
    await showAlert('success', 'Laporan Terkirim!', msg);
    showSaveModal.value = false;
    window.location.href = `/machine/${machineId.value}`;
  } catch (err) {
    showAlert('error', 'Gagal!', err.response?.data?.message || err.message);
  } finally {
    submitting.value = false;
  }
};

// ── Mount ──────────────────────────────────────────────────────────────────
onMounted(async () => {
  await loadMachinesList();
  const queryId = new URLSearchParams(window.location.search).get('machine');
  if (queryId) {
    // Pre-fill combobox text from machines list or just ID
    selectedMachineId.value = String(queryId);
    await loadMachineData(queryId);
    // Set combobox display name after list loaded
    const found = machinesList.value.find(m => String(m.id) === String(queryId));
    if (found) machineSearch.value = found.name;
  }
});
</script>
