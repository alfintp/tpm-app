<template>
  <div class="p-6">
    <!-- Semua komponen sudah dilaporkan dalam periode ini -->
    <div v-if="isReportBlocked && !forceReport && allDone" class="mb-5 bg-green-50 border border-green-200 rounded-2xl p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div class="flex items-start gap-3">
        <svg class="w-5 h-5 text-green-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <div>
          <p class="text-sm font-bold text-green-800">Semua Komponen Sudah Dilaporkan</p>
          <p class="text-xs text-green-700 mt-0.5">
            Seluruh komponen periode ini sudah tercatat. Jadwal berikutnya: <strong>{{ nextScheduleDateFormatted }}</strong> {{ maintenanceDaysLabel }}.
          </p>
        </div>
      </div>
      <button
        @click="$emit('force-report')"
        :disabled="forceReportLoading"
        class="shrink-0 flex items-center gap-2 bg-green-100 hover:bg-green-200 border border-green-300 text-green-900 text-xs font-bold px-4 py-2.5 rounded-xl transition-all cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed"
      >
        <svg v-if="forceReportLoading" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
        <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
        {{ forceReportLoading ? 'Mengubah Status...' : 'Tambah Laporan Tambahan' }}
      </button>
    </div>

    <!-- Belum Waktunya Pengecekan -->
    <div v-if="isReportBlocked && !forceReport && !allDone" class="mb-5 bg-amber-50 border border-amber-200 rounded-2xl p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div class="flex items-start gap-3">
        <svg class="w-5 h-5 text-amber-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
        <div>
          <p class="text-sm font-bold text-amber-800">Belum Waktunya Pengecekan</p>
          <p class="text-xs text-amber-700 mt-0.5">
            Jadwal maintenance mesin ini adalah <strong>{{ nextScheduleDateFormatted }}</strong> {{ maintenanceDaysLabel }}.
            Laporan hanya disarankan dibuat pada atau setelah tanggal tersebut.
          </p>
        </div>
      </div>
      <button
        @click="$emit('force-report')"
        :disabled="forceReportLoading"
        class="shrink-0 flex items-center gap-2 bg-amber-100 hover:bg-amber-200 border border-amber-300 text-amber-900 text-xs font-bold px-4 py-2.5 rounded-xl transition-all cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed"
      >
        <svg v-if="forceReportLoading" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
        <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
        {{ forceReportLoading ? 'Mengubah Status...' : 'Tetap Maintenance di Luar Jadwal' }}
      </button>
    </div>

    <!-- Blocked Overlay Wrapper -->
    <div :class="isReportBlocked && !forceReport ? 'relative' : ''">
      <!-- Dimming overlay -->
      <div
        v-if="isReportBlocked && !forceReport"
        class="absolute inset-0 bg-white/70 backdrop-blur-[2px] z-10 rounded-xl flex items-center justify-center"
      >
        <div class="text-center px-6">
          <svg class="w-10 h-10 text-amber-400 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
          <p class="text-sm font-bold text-slate-600">Form laporan dikunci</p>
          <p class="text-xs text-slate-400 mt-1">Klik "Tetap Maintenance di Luar Jadwal" di atas untuk membuka</p>
        </div>
      </div>

      <div v-if="!hasComponents" class="text-center py-12 text-slate-400 text-sm">
        Belum ada komponen pada mesin ini.
      </div>
      <template v-else>
        <!-- Filter Bar -->
        <div class="flex flex-wrap items-center justify-between gap-4 mb-4 pb-4 border-b border-slate-100">
          <!-- Mode Toggle -->
          <!-- <div class="flex bg-slate-100 rounded-xl p-1 shadow-inner">
            <button
              @click="viewMode = 'table'"
              :class="viewMode === 'table' ? 'bg-white text-indigo-600 shadow-sm' : 'text-slate-500 hover:text-slate-700'"
              class="px-3 py-1.5 rounded-lg text-xs font-bold transition-all cursor-pointer flex items-center gap-1.5"
            >
              <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
              Tampilan Tabel
            </button>
            <button
              @click="viewMode = 'wizard'"
              :class="viewMode === 'wizard' ? 'bg-white text-indigo-600 shadow-sm' : 'text-slate-500 hover:text-slate-700'"
              class="px-3 py-1.5 rounded-lg text-xs font-bold transition-all cursor-pointer flex items-center gap-1.5"
            >
              <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
              Mode Mobile
            </button>
          </div> -->

          <div class="flex flex-wrap items-center gap-3 flex-1 sm:justify-end">
            <SearchInput v-model="search" placeholder="Cari nama komponen..." class="flex-1 max-w-xs" />
            <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Filter:</span>
            <button
              v-for="opt in filterOptions"
              :key="opt.value"
              @click="$emit('update:filter', opt.value)"
              :class="activeFilter === opt.value
                ? 'bg-linear-to-tr from-brand-brown to-brand-gradation text-white'
                : 'bg-white text-slate-600 border-slate-200 hover:border-brand-brown/50'"
              class="px-3 py-1.5 rounded-lg text-xs font-semibold border transition-colors cursor-pointer"
            >
              {{ opt.label }}
              <span class="ml-1 opacity-80">({{ opt.count }})</span>
            </button>
            <div class="text-xs text-slate-400">
              Menampilkan {{ filteredRows.length }} dari {{ rows.length }} komponen
            </div>
          </div>
        </div>

        <!-- Waktu Pengerjaan (per laporan) -->
        <div v-if="filteredRows.length > 0" class="max-w-xl mx-auto mb-4">
          <div
            class="bg-white border border-slate-100 rounded-3xl p-6 shadow-sm transition-all"
            :class="isWorkTimeDisabled ? 'opacity-60 bg-slate-50' : ''"
          >
            <div class="flex items-center justify-between mb-3">
              <label class="text-xs font-bold text-slate-600 uppercase">Waktu Pengerjaan</label>
              <span v-if="isWorkTimeReadonly" class="text-[10px] font-bold text-blue-500 bg-blue-50 px-2 py-0.5 rounded-full flex items-center gap-1">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <template v-if="filteredRows.find(r => r.checkedAt)?.checkedAt">
                  {{ formatDateTime(filteredRows.find(r => r.checkedAt).checkedAt) }}
                </template>
                <template v-else>Sudah tercatat</template>
              </span>
              <span v-else-if="isWorkTimeDisabled" class="text-[10px] font-bold text-slate-400 bg-slate-100 px-2 py-0.5 rounded-full flex items-center gap-1">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                Dikunci
              </span>
            </div>
            <div class="flex flex-wrap items-end gap-4 bg-slate-50 p-4 rounded-2xl border border-slate-100">
              <div class="flex flex-col gap-1">
                <label class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Jam Mulai</label>
                <input
                  :value="displayStartTime"
                  @input="$emit('update:startTime', $event.target.value)"
                  type="time"
                  required
                  :disabled="isWorkTimeDisabled"
                  class="rounded-xl border border-slate-200 px-3 py-2 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-brand-brown disabled:bg-slate-100 disabled:text-slate-500 disabled:cursor-not-allowed"
                />
              </div>
              <div class="flex flex-col gap-1">
                <label class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Jam Selesai</label>
                <input
                  :value="displayEndTime"
                  @input="$emit('update:endTime', $event.target.value)"
                  type="time"
                  required
                  :disabled="isWorkTimeDisabled"
                  class="rounded-xl border border-slate-200 px-3 py-2 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-brand-brown disabled:bg-slate-100 disabled:text-slate-500 disabled:cursor-not-allowed"
                />
              </div>
              <div v-if="durationLabel" class="text-sm font-semibold text-brand-gradation bg-white border border-brand-cream px-3 py-2 rounded-xl shadow-sm">
                Durasi: {{ durationLabel }}
              </div>
            </div>
          </div>
        </div>

        <div v-if="filteredRows.length === 0" class="text-center py-10 text-slate-400 text-sm">
          Tidak ada komponen untuk filter ini.
        </div>
        <template v-else>
          <!-- ── TABLE VIEW ── -->
          <div v-if="viewMode === 'table'" class="overflow-x-auto -mx-6 px-6">
            <table class="w-full min-w-[900px] border-collapse">
              <thead>
                <tr class="bg-slate-50 border-y border-slate-100">
                  <th class="text-left text-xs font-semibold text-slate-500 uppercase tracking-wider px-4 py-3 w-[25%]">Nama & Spesifikasi</th>
                  <th class="text-left text-xs font-semibold text-slate-500 uppercase tracking-wider px-4 py-3 w-[13%]">Penggantian Terakhir</th>
                  <th class="text-left text-xs font-semibold text-slate-500 uppercase tracking-wider px-4 py-3 w-[15%]">Kondisi Sebelumnya</th>
                  <th class="text-left text-xs font-semibold text-slate-500 uppercase tracking-wider px-4 py-3 w-[22%]">Catatan & Penggantian</th>
                  <th class="text-left text-xs font-semibold text-slate-500 uppercase tracking-wider px-4 py-3 w-[25%]">Kondisi & Konfirmasi</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100">
                <tr
                  v-for="row in filteredRows"
                  :key="row.id"
                  :class="rowClass(row)"
                  class="transition-colors"
                >
                  <!-- Nama & Spesifikasi -->
                  <td class="px-4 py-3 align-top">
                    <div class="flex gap-2 mb-1 flex-wrap">
                      <span class="text-[10px] font-semibold text-brand-gradation bg-brand-cream px-2 py-0.5 rounded-full">{{ row.category }}</span>
                      <span v-if="row.maintenance_schedule" class="text-[10px] font-semibold text-slate-500 bg-slate-100 px-2 py-0.5 rounded-full">{{ row.maintenance_schedule }}</span>
                      <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full" :class="getDifficultyBadgeClass(row.difficulty)">
                        {{ getDifficultyLabel(row.difficulty) }}
                      </span>
                    </div>
                    <p class="font-semibold text-slate-800 text-sm">{{ row.name }}</p>
                    <p class="text-xs text-slate-500 mt-0.5 leading-relaxed">{{ row.specification || '-' }}</p>
                    <p class="text-[10px] text-slate-400 mt-1">Qty: {{ row.qty }} {{ row.unit }}</p>
                  </td>

                  <!-- Penggantian Terakhir -->
                  <td class="px-4 py-3 align-top">
                    <p class="text-sm text-slate-700 font-medium">{{ formatDate(getLastReplacement(row.id)) }}</p>
                    <p class="text-[10px] text-slate-400 mt-0.5">{{ getLastReplacement(row.id) ? 'dari riwayat maintenance' : 'Belum pernah diganti' }}</p>
                  </td>

                  <!-- Kondisi Sebelumnya -->
                  <td class="px-4 py-3 align-top">
                    <template v-if="getPrevCheck(row.id)">
                      <p :class="colorTheme(getPrevCheck(row.id).condition).textClass" class="text-sm font-bold">{{ getPrevCheck(row.id).condition }}%</p>
                      <p v-if="getPrevCheck(row.id).date" class="text-[10px] text-slate-400 mt-0.5">{{ formatDateTime(getPrevCheck(row.id).date) }}</p>
                      <p v-else class="text-[10px] text-slate-400 mt-0.5">belum ada riwayat pengecekan</p>
                      <div v-if="getPrevCheck(row.id).description" class="mt-1.5 bg-amber-50/60 border border-amber-100 rounded-lg p-1.5 text-[10px] text-slate-600 italic leading-normal max-w-[180px]">
                        <span class="font-semibold text-amber-800 not-italic block mb-0.5">Catatan:</span>
                        {{ getPrevCheck(row.id).description }}
                      </div>
                    </template>
                    <p v-else class="text-sm text-slate-400">-</p>
                  </td>

                  <!-- Catatan & Ganti Komponen -->
                  <td class="px-4 py-3 align-top min-w-[200px]">
                    <div class="space-y-2">
                      <textarea
                        v-model="row.description"
                        :disabled="isInputDisabled(row)"
                        rows="2"
                        class="w-full px-2.5 py-1.5 text-xs border border-slate-200 bg-white rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-brown disabled:bg-slate-100 disabled:text-slate-500 disabled:cursor-not-allowed resize-none"
                        placeholder="Masukkan catatan..."
                      ></textarea>
                      <label class="flex items-center gap-2" :class="isInputDisabled(row) ? 'opacity-60 cursor-not-allowed' : 'cursor-pointer'">
                        <input
                          type="checkbox"
                          v-model="row.is_component_replacement"
                          :disabled="isInputDisabled(row)"
                          class="rounded border-slate-300 text-brand-gradation focus:ring-brand-brown disabled:cursor-not-allowed cursor-pointer"
                        >
                        <span class="text-xs text-slate-700 font-medium">Ganti komponen</span>
                      </label>
                    </div>
                  </td>

                  <!-- Kondisi & Konfirmasi -->
                  <td class="px-4 py-3 align-top">
                    <div class="flex items-start gap-3">
                      <div class="flex-1 space-y-2">
                        <!-- Indicator Checklist -->
                        <div v-if="hasIndicators(row)" class="space-y-1.5">
                          <div class="flex items-center gap-2 mb-1">
                            <span class="text-xs font-semibold text-slate-500">Indikator Penilaian</span>
                            <span v-if="isConditionValid(row.conditionPct)" :class="colorTheme(row.conditionPct).textClass" class="text-xs font-semibold">{{ row.conditionPct }}%</span>
                          </div>
                          <label
                            v-for="indicator in row.indicators"
                            :key="indicator.id"
                            class="flex items-start gap-2 text-xs"
                            :class="isInputDisabled(row) ? 'opacity-60 cursor-not-allowed' : 'cursor-pointer'"
                          >
                            <input
                              type="checkbox"
                              :checked="getIndicatorValue(row, indicator.id)"
                              @change="toggleIndicator(row, indicator.id)"
                              :disabled="isInputDisabled(row)"
                              class="mt-0.5 rounded border-slate-300 text-brand-gradation focus:ring-brand-brown disabled:cursor-not-allowed cursor-pointer"
                            />
                            <div class="leading-tight">
                              <span class="font-medium text-slate-700">{{ indicator.name }}</span>
                              <p v-if="indicator.description" class="text-slate-400 text-[10px]">{{ indicator.description }}</p>
                            </div>
                          </label>
                        </div>

                        <!-- Manual Percentage Input -->
                        <div v-else class="flex items-center gap-2">
                          <input
                            type="number" inputmode="numeric" pattern="[0-9]*"
                            v-model.number="row.conditionPct"
                            @input="$emit('condition-change', row)"
                            min="0" max="100" placeholder="0–100"
                            :disabled="isInputDisabled(row)"
                            class="w-20 rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-brand-brown disabled:bg-slate-100 disabled:cursor-not-allowed"
                          />
                          <span class="text-sm text-slate-500">%</span>
                          <span v-if="isConditionValid(row.conditionPct)" :class="colorTheme(row.conditionPct).textClass" class="text-xs font-semibold">{{ conditionLabel(row.conditionPct) }}</span>
                        </div>

                        <p v-if="row.checkedToday && !row.editing"
                          :class="row.todayApprovalStatus === 'pending' ? 'text-amber-600' : 'text-blue-600'"
                          class="text-[11px] font-medium flex items-center gap-1">
                          <svg v-if="row.todayApprovalStatus === 'pending'" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                          <svg v-else class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                          {{ row.todayApprovalStatus === 'pending' ? 'Laporan terkirim, menunggu approval' : 'Komponen sudah dicek hari ini' }}
                          <span class="text-slate-400 font-normal">({{ formatDateTime(row.todayCheckedAt) }})</span>
                        </p>
                        <p v-else-if="row.rejectedToday" class="text-[11px] text-red-600 font-medium flex items-center gap-1">
                          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                          Laporan sebelumnya ditolak
                          <span v-if="row.rejectedNotes" class="text-slate-400 font-normal">({{ row.rejectedNotes }})</span>
                        </p>
                        <p v-else-if="row.checked && row.checkedAt" class="text-[11px] text-green-600 font-medium flex items-center gap-1">
                          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                          Dikonfirmasi: {{ formatDateTime(row.checkedAt) }}
                        </p>
                      </div>

                      <!-- Tombol Edit -->
                      <button
                        v-if="row.checkedToday && !row.editing && isManagerOrAdmin"
                        @click="$emit('start-edit', row)"
                        title="Edit kondisi"
                        class="shrink-0 px-3 h-10 rounded-xl border-2 border-blue-200 bg-blue-50 text-blue-600 hover:bg-blue-100 hover:border-blue-300 flex items-center justify-center gap-1.5 transition-all cursor-pointer text-xs font-semibold"
                      >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                        Edit
                      </button>

                      <!-- Tombol Checklist + Batal Edit -->
                      <template v-else-if="!row.checkedToday || row.editing">
                        <button
                          v-if="row.editing"
                          @click="$emit('cancel-edit', row)"
                          title="Batalkan edit"
                          class="shrink-0 w-10 h-10 rounded-xl border-2 border-red-200 bg-red-50 text-red-500 hover:bg-red-100 hover:border-red-300 flex items-center justify-center transition-all cursor-pointer"
                        >
                          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                        <button
                          @click="$emit('toggle-check', row)"
                          :disabled="!canCheck(row)"
                          :title="row.checked ? 'Batalkan konfirmasi' : 'Konfirmasi sudah dicek'"
                          :class="row.checked
                            ? 'bg-green-500 text-white border-green-500 hover:bg-green-600'
                            : canCheck(row)
                              ? 'bg-white text-slate-600 border-slate-300 hover:border-brand-brown hover:text-brand-gradation'
                              : 'bg-slate-50 text-slate-300 border-slate-200 cursor-not-allowed'"
                          class="shrink-0 w-10 h-10 rounded-xl border-2 flex items-center justify-center transition-all cursor-pointer disabled:cursor-not-allowed"
                        >
                          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                        </button>
                      </template>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- ── WIZARD / MOBILE VIEW ── -->
          <div v-else-if="viewMode === 'wizard'" class="max-w-xl mx-auto space-y-4">
            <!-- Progress Bar -->
            <div class="bg-white rounded-2xl border border-slate-100 p-4 shadow-sm">
              <div class="flex items-center justify-between text-xs text-slate-500 font-bold mb-2">
                <span>PROGRESS PENGISIAN</span>
                <span>{{ wizardIndex + 1 }} dari {{ filteredRows.length }} Komponen</span>
              </div>
              <div class="w-full bg-slate-100 h-2.5 rounded-full overflow-hidden">
                <div
                  class="bg-indigo-600 h-full transition-all duration-300 rounded-full"
                  :style="{ width: `${((wizardIndex + 1) / filteredRows.length) * 100}%` }"
                ></div>
              </div>
            </div>

            <!-- Main Wizard Card -->
            <div
              v-if="currentRow"
              class="bg-white rounded-3xl border-2 transition-all duration-300 p-6 shadow-md space-y-6"
              :class="currentRow.checked ? 'border-green-400 ring-4 ring-green-50' : 'border-slate-100 hover:border-brand-cream'"
            >
              <!-- Card Header -->
              <div class="flex items-start justify-between pb-4 border-b border-slate-100">
                <div>
                  <div class="flex gap-1.5 mb-2 flex-wrap">
                    <span class="text-[10px] font-bold text-brand-gradation bg-brand-cream px-2.5 py-1 rounded-full uppercase">{{ currentRow.category }}</span>
                    <span v-if="currentRow.maintenance_schedule" class="text-[10px] font-bold text-slate-500 bg-slate-100 px-2.5 py-1 rounded-full uppercase">{{ currentRow.maintenance_schedule }}</span>
                    <span class="text-[10px] font-bold px-2.5 py-1 rounded-full uppercase" :class="getDifficultyBadgeClass(currentRow.difficulty)">
                      {{ getDifficultyLabel(currentRow.difficulty) }}
                    </span>
                  </div>
                  <h4 class="text-lg font-bold text-slate-800 leading-tight">{{ currentRow.name }}</h4>
                  <p class="text-xs text-slate-500 mt-1">{{ currentRow.specification || 'Tidak ada spesifikasi' }}</p>
                </div>
                <div class="text-right shrink-0 pl-4">
                  <span class="text-xs font-semibold text-slate-400">Qty:</span>
                  <p class="text-sm font-bold text-slate-700 leading-none mt-0.5">{{ currentRow.qty }} {{ currentRow.unit }}</p>
                </div>
              </div>

              <!-- Info Grid -->
              <div class="grid grid-cols-2 gap-4 bg-slate-50/50 p-4 rounded-2xl border border-slate-100 text-xs">
                <div>
                  <span class="text-slate-400 font-semibold uppercase block mb-1">Penggantian Terakhir</span>
                  <p class="text-sm font-bold text-slate-700">{{ formatDate(getLastReplacement(currentRow.id)) }}</p>
                  <span class="text-[9px] text-slate-400">{{ getLastReplacement(currentRow.id) ? 'dari riwayat maintenance' : 'belum pernah diganti' }}</span>
                </div>
                <div>
                  <span class="text-slate-400 font-semibold uppercase block mb-1">Kondisi Sebelumnya</span>
                  <template v-if="getPrevCheck(currentRow.id)">
                    <div class="flex items-center gap-1.5">
                      <span :class="colorTheme(getPrevCheck(currentRow.id).condition).textClass" class="text-sm font-bold">{{ getPrevCheck(currentRow.id).condition }}%</span>
                      <span v-if="getPrevCheck(currentRow.id).date" class="text-[10px] text-slate-400">({{ formatDate(getPrevCheck(currentRow.id).date) }})</span>
                    </div>
                    <p v-if="getPrevCheck(currentRow.id).description" class="text-[9px] text-slate-500 mt-1 bg-amber-50 border border-amber-100/50 px-1.5 py-0.5 rounded italic truncate max-w-xs" :title="getPrevCheck(currentRow.id).description">
                      "{{ getPrevCheck(currentRow.id).description }}"
                    </p>
                  </template>
                  <p v-else class="text-sm font-bold text-slate-400">-</p>
                </div>
              </div>

              <!-- Input Kondisi -->
              <div class="space-y-3">
                <div class="flex items-center justify-between">
                  <label class="text-xs font-bold text-slate-600 uppercase">
                    {{ hasIndicators(currentRow) ? 'Indikator Penilaian' : 'Kondisi Komponen saat ini' }}
                  </label>
                  <span v-if="isConditionValid(currentRow.conditionPct)" :class="colorTheme(currentRow.conditionPct).textClass" class="text-xs font-bold bg-slate-50 px-2.5 py-1 border border-slate-100 rounded-lg flex items-center gap-1">
                    <span class="w-1.5 h-1.5 rounded-full" :class="colorTheme(currentRow.conditionPct).dotClass"></span>
                    {{ hasIndicators(currentRow) ? currentRow.conditionPct + '%' : conditionLabel(currentRow.conditionPct) }}
                  </span>
                </div>

                <!-- Indicator Checklist -->
                <div v-if="hasIndicators(currentRow)" class="bg-slate-50 p-4 rounded-2xl border border-slate-100 space-y-2">
                  <label
                    v-for="indicator in currentRow.indicators"
                    :key="indicator.id"
                    class="flex items-start gap-2 text-sm"
                    :class="isInputDisabled(currentRow) ? 'opacity-60 cursor-not-allowed' : 'cursor-pointer'"
                  >
                    <input
                      type="checkbox"
                      :checked="getIndicatorValue(currentRow, indicator.id)"
                      @change="toggleIndicator(currentRow, indicator.id)"
                      :disabled="isInputDisabled(currentRow)"
                      class="mt-0.5 rounded border-slate-300 text-brand-gradation focus:ring-brand-brown disabled:cursor-not-allowed cursor-pointer"
                    />
                    <div class="leading-tight">
                      <span class="font-medium text-slate-700">{{ indicator.name }}</span>
                      <p v-if="indicator.description" class="text-slate-400 text-xs">{{ indicator.description }}</p>
                    </div>
                  </label>
                </div>

                <!-- Manual Percentage Input -->
                <template v-else>
                  <div class="flex items-center gap-4 bg-slate-50 p-4 rounded-2xl border border-slate-100">
                    <input type="range" v-model.number="currentRow.conditionPct" @input="$emit('condition-change', currentRow)" :disabled="isInputDisabled(currentRow)" min="0" max="100" step="5" class="flex-1 accent-indigo-600 cursor-pointer disabled:opacity-50" />
                    <div class="flex items-center gap-1.5 shrink-0">
                      <input type="number" inputmode="numeric" pattern="[0-9]*" v-model.number="currentRow.conditionPct" @input="$emit('condition-change', currentRow)" min="0" max="100" placeholder="0–100" :disabled="isInputDisabled(currentRow)" class="w-16 rounded-xl border border-slate-200 bg-white text-center font-bold px-2 py-2 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-brand-brown disabled:bg-slate-100 disabled:text-slate-400 disabled:cursor-not-allowed" />
                      <span class="text-sm font-bold text-slate-500">%</span>
                    </div>
                  </div>
                  <div v-if="!isInputDisabled(currentRow)" class="flex items-center justify-between gap-2 pt-1">
                    <button v-for="preset in [60, 70, 80, 90, 100]" :key="preset" @click="$emit('wizard-preset', { row: currentRow, val: preset })" class="flex-1 py-1.5 border border-slate-200 hover:border-indigo-600 bg-white rounded-lg text-[10px] font-bold text-slate-600 hover:text-indigo-600 transition-all cursor-pointer">{{ preset }}%</button>
                  </div>
                </template>
              </div>

              <!-- Catatan & Ganti Komponen -->
              <div class="space-y-3 pt-2">
                <label class="text-xs font-bold text-slate-600 uppercase block">Catatan & Tindakan Tambahan</label>
                <div class="bg-slate-50 p-4 rounded-2xl border border-slate-100 space-y-3">
                  <textarea v-model="currentRow.description" :disabled="isInputDisabled(currentRow)" rows="2" class="w-full px-3 py-2 text-xs border border-slate-200 bg-white rounded-xl focus:outline-none focus:ring-2 focus:ring-brand-brown disabled:bg-slate-100 disabled:text-slate-500 disabled:cursor-not-allowed resize-none" placeholder="Masukkan catatan jika ada..."></textarea>
                  <label class="flex items-center gap-2" :class="isInputDisabled(currentRow) ? 'opacity-60 cursor-not-allowed' : 'cursor-pointer'">
                    <input type="checkbox" v-model="currentRow.is_component_replacement" :disabled="isInputDisabled(currentRow)" class="rounded border-slate-300 text-brand-gradation focus:ring-brand-brown disabled:cursor-not-allowed cursor-pointer">
                    <span class="text-xs text-slate-700 font-semibold">Ganti komponen (Tindakan Replace)</span>
                  </label>
                </div>
              </div>

              <!-- Konfirmasi -->
              <div class="pt-2 border-t border-slate-100">
                <!-- Checked Today -->
                <div v-if="currentRow.checkedToday && !currentRow.editing"
                  :class="currentRow.todayApprovalStatus === 'pending' ? 'bg-amber-50 border-amber-100' : 'bg-blue-50 border-blue-100'"
                  class="flex items-center justify-between gap-3 border p-4 rounded-2xl">
                  <div :class="currentRow.todayApprovalStatus === 'pending' ? 'text-amber-700' : 'text-blue-700'" class="flex items-center gap-2 text-xs font-semibold">
                    <svg v-if="currentRow.todayApprovalStatus === 'pending'" class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <svg v-else class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <div>
                      <p>{{ currentRow.todayApprovalStatus === 'pending' ? 'Laporan terkirim, menunggu approval' : 'Sudah dicek hari ini' }}</p>
                      <p class="text-[10px] text-slate-400 font-normal">({{ formatDateTime(currentRow.todayCheckedAt) }})</p>
                    </div>
                  </div>
                  <button v-if="isManagerOrAdmin" @click="$emit('start-edit', currentRow)" class="px-4 py-2 rounded-xl bg-white border border-blue-300 hover:bg-blue-100 hover:border-blue-400 text-blue-600 text-xs font-bold transition-all cursor-pointer flex items-center gap-1">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    Edit
                  </button>
                </div>
                <!-- Rejected Today -->
                <div v-else-if="currentRow.rejectedToday && !currentRow.editing" class="space-y-3">
                  <div class="flex items-center gap-3 bg-red-50 border border-red-100 p-4 rounded-2xl">
                    <svg class="w-5 h-5 shrink-0 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <div class="text-xs font-semibold text-red-700">
                      <p>Laporan sebelumnya ditolak, silahkan buat laporan kembali</p>
                      <p v-if="currentRow.rejectedNotes" class="text-[10px] text-slate-400 font-normal">({{ currentRow.rejectedNotes }})</p>
                    </div>
                  </div>
                  <button @click="$emit('start-edit', currentRow)" class="w-full py-3.5 rounded-2xl bg-red-600 hover:bg-red-700 text-white shadow-sm border-2 border-red-600 font-bold text-sm transition-all cursor-pointer flex items-center justify-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    Buat Laporan Baru
                  </button>
                </div>
                <!-- Normal / Edit Mode -->
                <div v-else class="flex gap-2">
                  <button v-if="currentRow.editing" @click="$emit('cancel-edit', currentRow)" class="shrink-0 py-3.5 px-4 rounded-2xl border-2 border-red-200 bg-red-50 text-red-500 hover:bg-red-100 hover:border-red-300 font-bold text-sm transition-all cursor-pointer flex items-center justify-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                    Batal
                  </button>
                  <button
                    @click="handleWizardCheck(currentRow)"
                    :disabled="!canCheck(currentRow)"
                    :class="currentRow.checked
                      ? 'bg-green-600 hover:bg-green-700 text-white shadow-sm border-green-600'
                      : canCheck(currentRow)
                        ? 'bg-indigo-600 hover:bg-indigo-700 text-white shadow-sm border-indigo-600'
                        : 'bg-slate-100 text-slate-400 border-slate-200 cursor-not-allowed'"
                    class="flex-1 py-3.5 rounded-2xl border-2 font-bold text-sm transition-all cursor-pointer flex items-center justify-center gap-2 disabled:cursor-not-allowed"
                  >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    {{ currentRow.checked ? 'Batal Konfirmasi' : 'Konfirmasi Sudah Dicek' }}
                  </button>
                </div>
              </div>
            </div>

            <!-- Navigation Buttons -->
            <div class="flex items-center justify-between gap-3 pt-2">
              <button @click="wizardIndex--" :disabled="wizardIndex === 0" class="flex-1 py-3 bg-white border border-slate-200 hover:border-slate-300 disabled:opacity-50 text-slate-600 disabled:cursor-not-allowed rounded-2xl font-bold text-xs shadow-sm transition-all cursor-pointer flex items-center justify-center gap-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                Sebelumnya
              </button>
              <button @click="wizardIndex++" :disabled="wizardIndex === filteredRows.length - 1" class="flex-1 py-3 bg-white border border-slate-200 hover:border-slate-300 disabled:opacity-50 text-slate-600 disabled:cursor-not-allowed rounded-2xl font-bold text-xs shadow-sm transition-all cursor-pointer flex items-center justify-center gap-1">
                Selanjutnya
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
              </button>
            </div>
          </div>
        </template>

        <p class="text-xs text-slate-400 mt-4 flex items-center gap-1.5">
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
          Isi kondisi (%) terlebih dahulu, lalu klik checklist untuk konfirmasi. Komponen yang sudah dicek hari ini dapat diedit ulang.
        </p>
      </template>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import SearchInput from './SearchInput.vue';

const props = defineProps({
  rows: { type: Array, default: () => [] },
  hasComponents: { type: Boolean, default: false },
  isReportBlocked: { type: Boolean, default: false },
  forceReport: { type: Boolean, default: false },
  forceReportLoading: { type: Boolean, default: false },
  isManagerOrAdmin: { type: Boolean, default: false },
  activeFilter: { type: String, default: 'unchecked_today' },
  filterOptions: { type: Array, default: () => [] },
  nextScheduleDateFormatted: { type: String, default: '' },
  maintenanceDaysLabel: { type: String, default: '' },
  getLastReplacement: { type: Function, required: true },
  getPrevCheck: { type: Function, required: true },
  formatDate: { type: Function, required: true },
  formatDateTime: { type: Function, required: true },
  colorTheme: { type: Function, required: true },
  conditionLabel: { type: Function, required: true },
  rowClass: { type: Function, required: true },
  isInputDisabled: { type: Function, required: true },
  canCheck: { type: Function, required: true },
  isConditionValid: { type: Function, required: true },
  startTime: { type: String, default: '' },
  endTime: { type: String, default: '' },
  durationLabel: { type: String, default: '' },
  allDone: { type: Boolean, default: false },
});

const emit = defineEmits([
  'force-report',
  'update:filter',
  'condition-change',
  'toggle-check',
  'wizard-check',
  'wizard-preset',
  'start-edit',
  'cancel-edit',
  'update:startTime',
  'update:endTime',
]);

const viewMode = ref('wizard');
const search = ref('');
const wizardIndex = ref(0);

const handleWizardCheck = (row) => {
  const wasChecked = row.checked;
  emit('wizard-check', row);
  // Auto-advance after a short delay so parent can update row.checked first
  if (!wasChecked && wizardIndex.value < filteredRows.value.length - 1) {
    setTimeout(() => {
      if (wizardIndex.value < filteredRows.value.length - 1) {
        wizardIndex.value++;
      }
    }, 350);
  }
};

const filteredRows = computed(() => {
  if (!search.value) return props.rows;
  const q = search.value.toLowerCase();
  return props.rows.filter(r => r.name.toLowerCase().includes(q));
});

const currentRow = computed(() => {
  if (!filteredRows.value.length) return null;
  const idx = Math.min(Math.max(0, wizardIndex.value), filteredRows.value.length - 1);
  return filteredRows.value[idx];
});

const isWorkTimeReadonly = computed(() => props.activeFilter === 'sudah_teknisi');

const savedWorkTime = computed(() => {
  const row = filteredRows.value.find(r => r.startTime && r.endTime);
  if (!row) return { start: '', end: '' };
  return {
    start: formatTimeForInput(row.startTime),
    end: formatTimeForInput(row.endTime),
  };
});

const displayStartTime = computed(() => isWorkTimeReadonly.value ? savedWorkTime.value.start : props.startTime);
const displayEndTime = computed(() => isWorkTimeReadonly.value ? savedWorkTime.value.end : props.endTime);
const isWorkTimeDisabled = computed(() => isWorkTimeReadonly.value || (props.isReportBlocked && !props.forceReport));

const hasIndicators = (row) => Array.isArray(row.indicators) && row.indicators.length > 0;

const getIndicatorValue = (row, indicatorId) => row.indicatorValues?.[indicatorId] ?? false;

const toggleIndicator = (row, indicatorId) => {
  if (!row.indicatorValues) row.indicatorValues = {};
  row.indicatorValues[indicatorId] = !row.indicatorValues[indicatorId];
  recalculateConditionFromIndicators(row);
  emit('condition-change', row);
};

const recalculateConditionFromIndicators = (row) => {
  if (!hasIndicators(row)) return;
  const indicators = row.indicators;
  const trueCount = indicators.filter(i => row.indicatorValues?.[i.id]).length;
  row.conditionPct = indicators.length > 0 ? Math.round((trueCount / indicators.length) * 100) : 0;
};

const formatTimeForInput = (timeStr) => {
  if (!timeStr) return '';
  // Handle HH:MM:SS format by stripping seconds
  return timeStr.split(':').slice(0, 2).join(':');
};

watch(() => props.activeFilter, () => { wizardIndex.value = 0; });
watch(search, () => { wizardIndex.value = 0; });

// Helper functions for difficulty badge
const getDifficultyBadgeClass = (difficulty) => {
  switch (difficulty) {
    case 'berat': return 'bg-red-100 text-red-700';
    case 'sedang': return 'bg-amber-100 text-amber-700';
    case 'ringan': return 'bg-green-100 text-green-700';
    default: return 'bg-slate-100 text-slate-500';
  }
};

const getDifficultyLabel = (difficulty) => {
  switch (difficulty) {
    case 'berat': return 'Berat';
    case 'sedang': return 'Sedang';
    case 'ringan': return 'Ringan';
    default: return 'Tanpa Kategori';
  }
};
watch(() => filteredRows.value.length, (newLen) => {
  if (newLen === 0) wizardIndex.value = 0;
  else if (wizardIndex.value >= newLen) wizardIndex.value = newLen - 1;
});

</script>
