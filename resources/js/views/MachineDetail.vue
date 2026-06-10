<template>
  <div v-if="loading" class="animate-pulse">
    <div class="h-8 bg-slate-200 rounded w-1/4 mb-4"></div>
    <div class="h-4 bg-slate-200 rounded w-1/2 mb-8"></div>
    <div class="h-64 bg-slate-200 rounded-xl mb-8"></div>
  </div>

  <div v-else-if="machine" class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between flex-wrap gap-4">
      <div class="flex items-center space-x-4">
        <button @click="handleNavigateBack" class="p-2 rounded-full hover:bg-slate-200 text-slate-500 transition-colors cursor-pointer">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
        </button>
        <div>
          <h2 class="text-2xl font-bold text-slate-800">{{ machine.name }}</h2>
          <p class="text-slate-500 text-sm mt-0.5">Maintenance Report &bull; {{ machine.description }} &bull; <span class="font-medium">{{ machine.location }}</span></p>
        </div>
      </div>
      <div class="flex items-center gap-3">
        <button
          @click="openEditMachine"
          class="px-4 py-2 bg-slate-100 text-slate-700 rounded-xl font-medium hover:bg-slate-200 transition-colors shadow-sm cursor-pointer flex items-center gap-2"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
          Edit Mesin
        </button>
        <button
          @click="submitReport()"
          :disabled="submitting || pendingCount === 0"
          class="px-4 py-2 bg-indigo-600 text-white rounded-xl font-medium hover:bg-indigo-700 transition-colors shadow-sm cursor-pointer flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed"
        >
          <svg v-if="submitting" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
          <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
          Simpan Report ({{ pendingCount }})
        </button>
      </div>
    </div>

    <!-- Machine Condition Card -->
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6">
      <div class="flex flex-wrap items-center gap-6">
        <div class="flex items-center gap-4">
          <div class="relative">
            <svg class="w-24 h-24" viewBox="0 0 100 100" style="transform: rotate(-90deg)">
              <circle class="text-slate-100 stroke-current" stroke-width="10" cx="50" cy="50" r="42" fill="transparent"/>
              <circle :class="getColorTheme(machine.condition_pct).textClass" class="stroke-current transition-all duration-1000 ease-out" stroke-width="10" stroke-linecap="round" cx="50" cy="50" r="42" fill="transparent"
                :stroke-dasharray="264" :stroke-dashoffset="264 - (machine.condition_pct / 100) * 264"/>
            </svg>
            <div class="absolute inset-0 flex flex-col items-center justify-center">
              <span :class="getColorTheme(machine.condition_pct).textClass" class="text-xl font-bold">{{ machine.condition_pct }}%</span>
            </div>
          </div>
          <div>
            <p class="text-sm text-slate-500 font-medium">Kondisi Mesin (Avg Komponen)</p>
            <h3 :class="getColorTheme(machine.condition_pct).textClass" class="text-2xl font-bold">{{ getConditionLabel(machine.condition_pct) }}</h3>
            <p class="text-xs text-slate-400 mt-1">Dihitung dari {{ machine.components?.length ?? 0 }} komponen</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Tabs -->
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
      <div class="flex border-b border-slate-100">
        <button
          @click="switchTab('report')"
          :class="activeTab === 'report' ? 'border-indigo-600 text-indigo-600 bg-indigo-50/50' : 'border-transparent text-slate-500 hover:text-slate-700 hover:bg-slate-50'"
          class="flex-1 sm:flex-none px-6 py-4 text-sm font-semibold border-b-2 transition-colors cursor-pointer flex items-center justify-center gap-2"
        >
         <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
          Laporan
          <span v-if="uncheckedTodayCount > 0" class="text-[10px] bg-amber-100 text-amber-700 px-1.5 py-0.5 rounded-full font-bold">{{ uncheckedTodayCount }} belum</span>
        </button>
        <button
          @click="switchTab('history')"
          :class="activeTab === 'history' ? 'border-indigo-600 text-indigo-600 bg-indigo-50/50' : 'border-transparent text-slate-500 hover:text-slate-700 hover:bg-slate-50'"
          class="flex-1 sm:flex-none px-6 py-4 text-sm font-semibold border-b-2 transition-colors cursor-pointer flex items-center justify-center gap-2"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
          Riwayat Maintenance
        </button>
        <button
          @click="switchTab('component')"
          :class="activeTab === 'component' ? 'border-indigo-600 text-indigo-600 bg-indigo-50/50' : 'border-transparent text-slate-500 hover:text-slate-700 hover:bg-slate-50'"
          class="flex-1 sm:flex-none px-6 py-4 text-sm font-semibold border-b-2 transition-colors cursor-pointer flex items-center justify-center gap-2"
        >
            <svg class="w-5 h-5 " fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
          Daftar Komponen
        </button>
      </div>

      <!-- Tab 1: Report Table -->
      <div v-show="activeTab === 'report'" class="p-6">
        <div v-if="!machine.components?.length" class="text-center py-12 text-slate-400 text-sm">
          Belum ada komponen pada mesin ini.
        </div>
        <template v-else>
          <!-- Filter Bar -->
          <div class="flex flex-wrap items-center gap-3 mb-4 pb-4 border-b border-slate-100">
            <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Filter:</span>
            <button
              v-for="opt in filterOptions"
              :key="opt.value"
              @click="componentFilter = opt.value"
              :class="componentFilter === opt.value
                ? 'bg-indigo-600 text-white border-indigo-600'
                : 'bg-white text-slate-600 border-slate-200 hover:border-indigo-300'"
              class="px-3 py-1.5 rounded-lg text-xs font-semibold border transition-colors cursor-pointer"
            >
              {{ opt.label }}
              <span class="ml-1 opacity-80">({{ opt.count }})</span>
            </button>
            <div class="ml-auto text-xs text-slate-400">
              Menampilkan {{ filteredComponentRows.length }} dari {{ componentRows.length }} komponen
            </div>
          </div>

          <div v-if="filteredComponentRows.length === 0" class="text-center py-10 text-slate-400 text-sm">
            Tidak ada komponen untuk filter ini.
          </div>
          <div v-else class="overflow-x-auto -mx-6 px-6">
            <table class="w-full min-w-[900px] border-collapse">
              <thead>
                <tr class="bg-slate-50 border-y border-slate-100">
                  <th class="text-left text-xs font-semibold text-slate-500 uppercase tracking-wider px-4 py-3 w-[28%]">Nama & Spesifikasi</th>
                  <th class="text-left text-xs font-semibold text-slate-500 uppercase tracking-wider px-4 py-3 w-[14%]">Penggantian Terakhir</th>
                  <th class="text-left text-xs font-semibold text-slate-500 uppercase tracking-wider px-4 py-3 w-[16%]">Kondisi Sebelumnya</th>
                   <th class="text-left text-xs font-semibold text-slate-500 uppercase tracking-wider px-4 py-3 w-[14%]">Deskripsi</th>
                  <th class="text-left text-xs font-semibold text-slate-500 uppercase tracking-wider px-4 py-3 w-[42%]">Kondisi & Konfirmasi</th>
                  
                  
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100">
                <tr
                  v-for="row in filteredComponentRows"
                  :key="row.id"
                  :class="rowRowClass(row)"
                  class="transition-colors"
                >
                  <!-- Nama & Spesifikasi -->
                  <td class="px-4 py-3 align-top">
                    <div class="flex gap-2 mb-1">
                      <span class="text-[10px] font-semibold text-indigo-600 bg-indigo-50 px-2 py-0.5 rounded-full">{{ row.category }}</span>
                      <span v-if="row.maintenance_schedule" class="text-[10px] font-semibold text-slate-500 bg-slate-100 px-2 py-0.5 rounded-full">{{ row.maintenance_schedule }}</span>
                    </div>
                    <p class="font-semibold text-slate-800 text-sm">{{ row.name }}</p>
                    <p class="text-xs text-slate-500 mt-0.5 leading-relaxed">{{ row.specification || '-' }}</p>
                    <p class="text-[10px] text-slate-400 mt-1">Qty: {{ row.qty }} {{ row.unit }}</p>
                  </td>

                  <!-- Penggantian Terakhir -->
                  <td class="px-4 py-3 align-top">
                    <p class="text-sm text-slate-700 font-medium">{{ formatDate(getLastReplacementDate(row.id)) }}</p>
                    <p v-if="getLastReplacementDate(row.id)" class="text-[10px] text-slate-400 mt-0.5">dari riwayat maintenance</p>
                    <p v-else class="text-[10px] text-slate-400 mt-0.5">Belum pernah diganti</p>
                  </td>

                  <!-- Kondisi Sebelumnya -->
                  <td class="px-4 py-3 align-top">
                    <template v-if="getPreviousCheck(row.id)">
                      <p :class="getColorTheme(getPreviousCheck(row.id).condition).textClass" class="text-sm font-bold">
                        {{ getPreviousCheck(row.id).condition }}%
                      </p>
                      <p v-if="getPreviousCheck(row.id).date" class="text-[10px] text-slate-400 mt-0.5">
                        {{ formatDateTime(getPreviousCheck(row.id).date) }}
                      </p>
                      <p v-else class="text-[10px] text-slate-400 mt-0.5">belum ada riwayat pengecekan</p>
                    </template>
                    <p v-else class="text-sm text-slate-400">-</p>
                  </td>

                  <!-- buat tombol dulu, jika diklik muncul form untuk mengisi Deskripsi dan ada pertanyaan apakah pergantian komponen -->
                  <td class="px-4 py-3 align-top">
                    <div class="space-y-2">
                      <button
                        @click="row.showForm = !row.showForm"
                        class="px-3 py-2 rounded-lg text-xs font-semibold border transition-colors cursor-pointer"
                      >
                        {{ row.showForm ? 'Tutup Form' : 'Isi Form' }}
                      </button>
                      <div v-if="row.showForm">
                        <textarea
                          v-model="row.description"
                          class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500"
                          placeholder="Masukkan deskripsi"
                        ></textarea>
                        <!-- pilihan hanya centang saja, Ganti komponen, kalo ga dicentang yasudah, input ganti komponen juga ada di kolom sebelah, jadi gausah pake kolom is_component_replacement -->
                        <label class="flex items-center gap-2">
                          <input type="checkbox" v-model="row.is_component_replacement" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                          <span class="text-sm text-slate-700">Ganti komponen</span>
                        </label>
                      </div>
                    </div>
                  </td>

                  <!-- Kondisi & Konfirmasi -->
                  <td class="px-4 py-3 align-top">
                    <div class="flex items-start gap-3">
                      <div class="flex-1 space-y-2">
                        <div class="flex items-center gap-2">
                          <input
                            type="number"
                            v-model.number="row.conditionPct"
                            @input="onConditionChange(row)"
                            min="0"
                            max="100"
                            placeholder="0–100"
                            :disabled="isInputDisabled(row)"
                            class="w-20 rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 disabled:bg-slate-100 disabled:cursor-not-allowed"
                          />
                          <span class="text-sm text-slate-500">%</span>
                          <span
                            v-if="isConditionValid(row.conditionPct)"
                            :class="getColorTheme(row.conditionPct).textClass"
                            class="text-xs font-semibold"
                          >{{ getConditionLabel(row.conditionPct) }}</span>
                        </div>

                        <!-- Sudah dicek hari ini (belum edit) -->
                        <p v-if="row.checkedToday && !row.editing" class="text-[11px] text-blue-600 font-medium flex items-center gap-1">
                          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                          Komponen sudah dicek hari ini
                          <span class="text-slate-400 font-normal">({{ formatDateTime(row.todayCheckedAt) }})</span>
                        </p>

                        <!-- Baru dikonfirmasi (belum disimpan) -->
                        <p v-else-if="row.checked && row.checkedAt" class="text-[11px] text-green-600 font-medium flex items-center gap-1">
                          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                          Dikonfirmasi: {{ formatDateTime(row.checkedAt) }}
                        </p>
                      </div>

                      <!-- Tombol Edit (sudah dicek hari ini) -->
                      <button
                        v-if="row.checkedToday && !row.editing"
                        @click="startEdit(row)"
                        title="Edit kondisi"
                        class="flex-shrink-0 px-3 h-10 rounded-xl border-2 border-blue-200 bg-blue-50 text-blue-600 hover:bg-blue-100 hover:border-blue-300 flex items-center justify-center gap-1.5 transition-all cursor-pointer text-xs font-semibold"
                      >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                        Edit
                      </button>

                      <!-- Tombol Checklist -->
                      <button
                        v-else
                        @click="toggleCheck(row)"
                        :disabled="!canCheck(row)"
                        :title="row.checked ? 'Batalkan konfirmasi' : 'Konfirmasi sudah dicek'"
                        :class="row.checked
                          ? 'bg-green-500 text-white border-green-500 hover:bg-green-600'
                          : canCheck(row)
                            ? 'bg-white text-slate-600 border-slate-300 hover:border-indigo-400 hover:text-indigo-600'
                            : 'bg-slate-50 text-slate-300 border-slate-200 cursor-not-allowed'"
                        class="flex-shrink-0 w-10 h-10 rounded-xl border-2 flex items-center justify-center transition-all cursor-pointer disabled:cursor-not-allowed"
                      >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <p class="text-xs text-slate-400 mt-4 flex items-center gap-1.5">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            Isi kondisi (%) terlebih dahulu, lalu klik checklist untuk konfirmasi. Komponen yang sudah dicek hari ini dapat diedit ulang.
          </p>
        </template>
      </div>

      <!-- Tab 2: Maintenance History -->
      <div v-show="activeTab === 'history'" class="p-6">
        <div v-if="sortedRecords.length === 0" class="text-center py-12 text-slate-400 text-sm">
          Belum ada riwayat pengerjaan.
        </div>

        <div v-else class="relative border-l-2 border-indigo-100 ml-3 space-y-6 pb-4">
          <div v-for="record in sortedRecords" :key="record.id" class="relative pl-6">
            <div class="absolute w-4 h-4 rounded-full bg-indigo-500 border-4 border-white left-[-9px] top-1.5 shadow-sm"></div>
            <div class="bg-slate-50 rounded-xl p-4 border border-slate-100 hover:shadow-md transition-shadow">
              <div class="flex justify-between items-start mb-2">
                <div>
                  <span class="text-sm font-bold text-slate-800">{{ formatDateTime(record.maintenance_date) }}</span>
                  <p class="text-xs text-slate-400 mt-0.5">Teknisi: {{ record.technician?.full_name ?? '-' }}</p>
                </div>
                <span class="text-xs font-semibold px-2 py-1 rounded-lg" :class="{
                  'bg-green-100 text-green-700': record.status === 'completed',
                  'bg-amber-100 text-amber-700': record.status === 'in_progress',
                  'bg-slate-200 text-slate-700': record.status === 'planned'
                }">{{ record.status.toUpperCase() }}</span>
              </div>

              <p class="text-sm text-slate-600 italic mb-3">{{ record.notes || 'Tidak ada catatan.' }}</p>

              <div v-if="record.actions?.length > 0" class="space-y-2">
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Tindakan:</p>
                <div v-for="action in record.actions" :key="action.id" class="flex items-start gap-2 bg-white border border-slate-100 p-2 rounded-lg">
                  <span :class="getActionTypeClass(action.action_type)" class="text-xs font-semibold px-2 py-0.5 rounded-full flex-shrink-0">{{ action.action_type }}</span>
                  <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-slate-800 truncate">{{ action.component?.name ?? '-' }}</p>
                    <p v-if="action.description" class="text-xs text-slate-500 mt-0.5">{{ action.description }}</p>
                  </div>
                  <div class="text-xs text-slate-400 flex-shrink-0 text-right">
                    <div>{{ action.condition_before_pct ?? '-' }}% → <span class="text-indigo-600 font-medium">{{ action.condition_after_pct ?? '-' }}%</span></div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
      <!-- Tab 3: Component -->
      <div v-show="activeTab === 'component'" class="p-6">
        <!-- Filter Bar -->
        <div class="flex flex-wrap items-center gap-3 mb-4 pb-4 border-b border-slate-100">
          <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Filter:</span>
          <button
            v-for="opt in componentCategoryFilterOptions"
            :key="opt.value"
            @click="componentCategoryFilter = opt.value"
            :class="componentCategoryFilter === opt.value
              ? 'bg-indigo-600 text-white border-indigo-600'
              : 'bg-white text-slate-600 border-slate-200 hover:border-indigo-300'"
            class="px-3 py-1.5 rounded-lg text-xs font-semibold border transition-colors cursor-pointer"
          >
            {{ opt.label }}
            <span class="ml-1 opacity-80">({{ opt.count }})</span>
          </button>
          <div class="ml-auto text-xs text-slate-400">
            Menampilkan {{ filteredComponents.length }} dari {{ machine.components?.length ?? 0 }} komponen
          </div>
        </div>

        <div v-if="machine.components?.length === 0" class="text-center py-12 text-slate-400 text-sm">
          Belum ada komponen pada mesin ini.
        </div>

        <div v-else-if="filteredComponents.length === 0" class="text-center py-10 text-slate-400 text-sm">
          Tidak ada komponen untuk filter ini.
        </div>

        <div v-else class="grid grid-cols-1 lg:grid-cols-2 gap-4">
          <!-- Header with Add button -->
          <div class="lg:col-span-2 flex justify-between items-center mb-2">
            <h3 class="text-lg font-semibold text-slate-800 flex items-center gap-2">
              <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
              Daftar Komponen
            </h3>
            <button @click="openAddComponent" class="flex items-center gap-1.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium px-4 py-2 rounded-xl transition-colors shadow-sm cursor-pointer">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
              Tambah Komponen
            </button>
          </div>

          <!-- Component Cards -->
          <div v-for="comp in filteredComponents" :key="comp.id"
            class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5 hover:shadow-md hover:border-indigo-100 transition-all group">
            <div class="flex justify-between items-start">
              <div class="flex-1 cursor-pointer" @click="openComponentHistory(comp)">
                <div class="flex gap-2 mb-1.5">
                  <span class="text-xs font-semibold text-indigo-600 bg-indigo-50 px-2 py-0.5 rounded-full">{{ comp.category }}</span>
                  <span v-if="comp.maintenance_schedule" class="text-xs font-semibold text-slate-500 bg-slate-100 px-2 py-0.5 rounded-full">{{ comp.maintenance_schedule }}</span>
                </div>
                <h4 class="font-bold text-slate-800 hover:text-indigo-600 transition-colors">{{ comp.name }}</h4>
                <p class="text-xs text-slate-500 mt-0.5 line-clamp-1">{{ comp.specification }}</p>
              </div>

              <!-- Condition circle -->
              <div class="flex items-center gap-3 ml-3">
                <div class="relative flex-shrink-0">
                  <svg class="w-14 h-14" viewBox="0 0 50 50" style="transform: rotate(-90deg)">
                    <circle class="text-slate-100 stroke-current" stroke-width="5" cx="25" cy="25" r="20" fill="transparent"/>
                    <circle :class="getColorTheme(comp.last_condition_pct).textClass" class="stroke-current" stroke-width="5" stroke-linecap="round" cx="25" cy="25" r="20" fill="transparent"
                      :stroke-dasharray="125.7" :stroke-dashoffset="125.7 - (comp.last_condition_pct / 100) * 125.7"/>
                  </svg>
                  <div class="absolute inset-0 flex items-center justify-center">
                    <span :class="getColorTheme(comp.last_condition_pct).textClass" class="text-[10px] font-bold">{{ comp.last_condition_pct }}%</span>
                  </div>
                </div>

                <!-- Edit/Delete actions -->
                <div class="flex flex-col gap-1">
                  <button @click.stop="openEditComponent(comp)" class="p-1.5 text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 rounded-lg cursor-pointer transition-colors" title="Edit">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                  </button>
                  <button @click.stop="deleteComponent(comp)" class="p-1.5 text-slate-400 hover:text-red-500 hover:bg-red-50 rounded-lg cursor-pointer transition-colors" title="Hapus">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                  </button>
                </div>
              </div>
            </div>

            <div class="flex justify-between text-xs text-slate-400 border-t border-slate-100 pt-2 mt-3">
              <span>Qty: {{ comp.qty }} {{ comp.unit }}</span>
              <span>Penggantian: {{ formatDate(comp.last_replaced_at) }}</span>
            </div>
            <p class="text-xs text-indigo-500 mt-2 cursor-pointer hover:underline" @click="openComponentHistory(comp)">Lihat riwayat →</p>
          </div>
        </div>
      </div>

      <!-- Modals -->
      <ComponentForm
        v-if="showComponentForm"
        :machineId="route.params.id"
        :component="editingComponent"
        @close="showComponentForm = false"
        @saved="onComponentSaved"
      />
      <ComponentHistory
        v-if="showComponentHistory"
        :component="historyComponent"
        @close="showComponentHistory = false"
      />
      <MachineEditModal
        v-if="showMachineEdit"
        :machine="machine"
        @close="showMachineEdit = false"
        @saved="onMachineSaved"
      />
    </div>
    </div>

</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRoute, useRouter, onBeforeRouteLeave } from 'vue-router';
import axios from 'axios';
import { showAlert, showUnsavedConfirm, showConfirm } from '../composables/useAlert.js';
import ComponentForm from '../components/ComponentForm.vue';
import ComponentHistory from '../components/ComponentHistory.vue';
import MachineEditModal from '../components/MachineEditModal.vue';

const route = useRoute();
const router = useRouter();
const machine = ref(null);
const loading = ref(true);
const activeTab = ref('report');
const componentFilter = ref('unchecked_today');
const submitting = ref(false);
const componentRows = ref([]);
const skipLeaveGuard = ref(false);

// Modal states for component CRUD
const showComponentForm = ref(false);
const editingComponent = ref(null);
const showComponentHistory = ref(false);
const historyComponent = ref(null);

// Modal states for machine edit
const showMachineEdit = ref(false);

// Filter for component tab
const componentCategoryFilter = ref('all');

const isSameDay = (d1, d2) => {
  const a = new Date(d1);
  const b = new Date(d2);
  return a.getFullYear() === b.getFullYear() && a.getMonth() === b.getMonth() && a.getDate() === b.getDate();
};

const loadData = async () => {
  try {
    const res = await axios.get(`/api/machines/${route.params.id}`);
    machine.value = res.data;
    initComponentRows();
  } catch (e) {
    console.error(e);
  } finally {
    loading.value = false;
  }
};

const todayChecks = computed(() => {
  const map = {};
  const today = new Date();
  if (!machine.value?.records) return map;

  for (const record of machine.value.records) {
    if (!isSameDay(record.maintenance_date, today)) continue;
    for (const action of record.actions ?? []) {
      const id = action.machine_component_id;
      if (!id) continue;
      if (!map[id] || new Date(record.maintenance_date) > new Date(map[id].date)) {
        map[id] = {
          condition: action.condition_after_pct,
          date: record.maintenance_date,
          conditionBefore: action.condition_before_pct,
        };
      }
    }
  }
  return map;
});

const getComponentActions = (componentId) => {
  const actions = [];
  for (const record of machine.value?.records ?? []) {
    for (const action of record.actions ?? []) {
      if (action.machine_component_id === componentId) {
        actions.push({ ...action, recordDate: record.maintenance_date });
      }
    }
  }
  return actions.sort((a, b) => new Date(b.recordDate) - new Date(a.recordDate));
};

const getPreviousCheck = (componentId) => {
  const all = getComponentActions(componentId);
  const today = new Date();
  const nonToday = all.filter(a => !isSameDay(a.recordDate, today));

  if (nonToday.length > 0) {
    const prev = nonToday[0];
    return { condition: prev.condition_after_pct, date: prev.recordDate };
  }

  const todayAction = all.find(a => isSameDay(a.recordDate, today));
  if (todayAction?.condition_before_pct != null) {
    return { condition: todayAction.condition_before_pct, date: null };
  }

  const comp = machine.value?.components?.find(c => c.id === componentId);
  if (comp?.last_condition_pct != null) {
    return { condition: comp.last_condition_pct, date: null };
  }

  return null;
};

const initComponentRows = () => {
  componentRows.value = (machine.value?.components ?? []).map(comp => {
    const todayCheck = todayChecks.value[comp.id];
    return {
      id: comp.id,
      category: comp.category,
      name: comp.name,
      specification: comp.specification,
      qty: comp.qty,
      unit: comp.unit,
      maintenance_schedule: comp.maintenance_schedule,
      lastConditionPct: comp.last_condition_pct,
      checkedToday: !!todayCheck,
      todayCondition: todayCheck?.condition ?? null,
      todayCheckedAt: todayCheck?.date ?? null,
      conditionPct: todayCheck?.condition ?? comp.last_condition_pct ?? null,
      editing: false,
      checked: false,
      checkedAt: null,
    };
  });
};

onMounted(loadData);

const replacementDates = computed(() => {
  const map = {};
  if (!machine.value?.records) return map;

  for (const record of machine.value.records) {
    for (const action of record.actions ?? []) {
      if (action.action_type !== 'replace' || !action.machine_component_id) continue;
      const date = record.maintenance_date;
      if (!map[action.machine_component_id] || new Date(date) > new Date(map[action.machine_component_id])) {
        map[action.machine_component_id] = date;
      }
    }
  }
  return map;
});

const getLastReplacementDate = (componentId) => {
  const fromHistory = replacementDates.value[componentId];
  if (fromHistory) return fromHistory;

  const comp = machine.value?.components?.find(c => c.id === componentId);
  return comp?.last_replaced_at ?? null;
};

const sortedRecords = computed(() => {
  if (!machine.value?.records) return [];
  return [...machine.value.records].sort((a, b) => new Date(b.maintenance_date) - new Date(a.maintenance_date));
});

const uncheckedTodayCount = computed(() => componentRows.value.filter(r => !r.checkedToday).length);
const checkedTodayCount = computed(() => componentRows.value.filter(r => r.checkedToday).length);
const pendingCount = computed(() => componentRows.value.filter(r => r.checked).length);
const hasUnsavedChanges = computed(() => pendingCount.value > 0);

const filterOptions = computed(() => [
  { value: 'unchecked_today', label: 'Belum Dicek Hari Ini', count: uncheckedTodayCount.value },
  { value: 'checked_today', label: 'Sudah Dicek Hari Ini', count: checkedTodayCount.value },
  { value: 'all', label: 'Semua', count: componentRows.value.length },
]);

const filteredComponentRows = computed(() => {
  if (componentFilter.value === 'unchecked_today') {
    return componentRows.value.filter(r => !r.checkedToday);
  }
  if (componentFilter.value === 'checked_today') {
    return componentRows.value.filter(r => r.checkedToday);
  }
  return componentRows.value;
});

const isConditionValid = (pct) => pct !== null && pct !== '' && !isNaN(pct) && pct >= 0 && pct <= 100;

const isInputDisabled = (row) => (row.checkedToday && !row.editing) || (row.checked && !row.editing);

const canCheck = (row) => isConditionValid(row.conditionPct) && (!row.checkedToday || row.editing);

const rowRowClass = (row) => {
  if (row.checked) return 'bg-green-50/40';
  if (row.checkedToday && !row.editing) return 'bg-blue-50/30';
  return 'hover:bg-slate-50/50';
};

const onConditionChange = (row) => {
  if (row.checked) {
    row.checked = false;
    row.checkedAt = null;
  }
};

const startEdit = (row) => {
  row.editing = true;
  row.checked = false;
  row.checkedAt = null;
};

const toggleCheck = (row) => {
  if (!canCheck(row) && !row.checked) return;

  if (row.checked) {
    row.checked = false;
    row.checkedAt = null;
  } else {
    row.checked = true;
    row.checkedAt = new Date().toISOString();
  }
};

const confirmUnsaved = async () => {
  return showUnsavedConfirm(
    'Report Belum Disimpan',
    `${pendingCount.value} komponen sudah dikonfirmasi tetapi belum disimpan. Simpan perubahan sebelum melanjutkan?`
  );
};

const handleUnsavedAction = async () => {
  if (!hasUnsavedChanges.value) return true;

  const choice = await confirmUnsaved();
  if (choice === 'cancel') return false;
  if (choice === 'save') {
    return await submitReport(false);
  }
  // discard
  initComponentRows();
  return true;
};

const switchTab = async (tab) => {
  if (tab === activeTab.value) return;
  const ok = await handleUnsavedAction();
  if (!ok) return;
  activeTab.value = tab;
};

const handleNavigateBack = async () => {
  const ok = await handleUnsavedAction();
  if (!ok) return;
  router.push(`/machine/${route.params.id}`);
};

onBeforeRouteLeave(async (to, from, next) => {
  if (skipLeaveGuard.value) {
    next();
    return;
  }
  if (!hasUnsavedChanges.value) {
    next();
    return;
  }
  const choice = await confirmUnsaved();
  if (choice === 'cancel') {
    next(false);
  } else if (choice === 'save') {
    const saved = await submitReport(false);
    if (saved) skipLeaveGuard.value = true;
    next(saved);
  } else {
    next();
  }
});

const submitReport = async (redirect = true) => {
  const checkedRows = componentRows.value.filter(r => r.checked);
  if (checkedRows.length === 0) {
    showAlert('warning', 'Perhatian', 'Konfirmasi minimal satu komponen terlebih dahulu.');
    return false;
  }

  submitting.value = true;
  try {
    const uRes = await axios.get('/api/dummy-user').catch(() => null);
    const now = new Date();
    const localNow = new Date(now.getTime() - now.getTimezoneOffset() * 60000).toISOString().slice(0, 16);

    const actions = checkedRows.map(row => {
      const prev = getPreviousCheck(row.id);
      return {
        machine_component_id: row.id,
        action_type: 'inspect',
        condition_before_pct: prev?.condition ?? row.lastConditionPct,
        condition_after_pct: row.conditionPct,
        description: `Inspeksi report - dikonfirmasi ${formatDateTime(row.checkedAt)}`,
      };
    });

    await axios.post('/api/records', {
      machine_id: route.params.id,
      technician_id: uRes?.data?.id,
      maintenance_date: localNow,
      status: 'completed',
      notes: `Maintenance report - ${checkedRows.length} komponen diperiksa`,
      actions,
    });

    if (redirect) {
      skipLeaveGuard.value = true;
      showAlert('success', 'Berhasil!', 'Report berhasil disimpan.');
      // REVISI
      router.push(`/machine/${route.params.id}`)
      
    } else {
      await loadData();
      showAlert('success', 'Berhasil!', 'Report berhasil disimpan.');
      window.dispatchEvent(new CustomEvent('refresh-data'));
    }
    return true;
  } catch (e) {
    showAlert('error', 'Gagal!', 'Gagal menyimpan report: ' + (e.response?.data?.error || e.message));
    return false;
  } finally {
    submitting.value = false;
  }
};

const formatDateTime = (d) => {
  if (!d) return '-';
  return new Date(d).toLocaleString('id-ID', { day: 'numeric', month: 'long', year: 'numeric', hour: '2-digit', minute: '2-digit' });
};

const formatDate = (d) => {
  if (!d) return '-';
  return new Date(d).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
};

const getColorTheme = (pct) => {
  if (!pct && pct !== 0) return { textClass: 'text-slate-400' };
  if (pct < 50) return { textClass: 'text-red-500' };
  if (pct < 80) return { textClass: 'text-amber-500' };
  return { textClass: 'text-green-500' };
};

const getConditionLabel = (pct) => {
  if (pct < 50) return 'Perlu Perhatian';
  if (pct < 80) return 'Kondisi Sedang';
  return 'Kondisi Baik';
};

const getActionTypeClass = (type) => {
  const map = {
    replace: 'bg-red-100 text-red-700',
    repair: 'bg-orange-100 text-orange-700',
    inspect: 'bg-blue-100 text-blue-700',
    clean: 'bg-teal-100 text-teal-700',
    lubricate: 'bg-purple-100 text-purple-700',
  };
  return map[type] || 'bg-slate-100 text-slate-700';
};

// Component CRUD functions
const openAddComponent = () => {
  editingComponent.value = null;
  showComponentForm.value = true;
};

const openEditComponent = (comp) => {
  editingComponent.value = comp;
  showComponentForm.value = true;
};

const openComponentHistory = (comp) => {
  historyComponent.value = comp;
  showComponentHistory.value = true;
};

const onComponentSaved = async () => {
  showComponentForm.value = false;
  await loadData();
  showAlert('success', 'Berhasil!', 'Komponen berhasil disimpan.');
};

const deleteComponent = async (comp) => {
  const ok = await showConfirm('Hapus Komponen Mesin', `Apakah Anda yakin ingin menghapus "${comp.name}"? Semua data terkait (komponen, jadwal, riwayat) akan ikut terhapus.`);
  if (!ok) return;
  try {
    await axios.delete(`/api/components/${comp.id}`);
    await loadData();
    showAlert('success', 'Dihapus!', `Komponen "${comp.name}" berhasil dihapus.`);
  } catch (e) {
    showAlert('error', 'Gagal!', 'Gagal menghapus komponen: ' + (e.response?.data?.message || e.message));
  }
};

// Filter logic for component tab
const componentCategoryFilterOptions = computed(() => {
  const categories = new Set(machine.value?.components?.map(c => c.category) || []);
  const options = [{ value: 'all', label: 'Semua', count: machine.value?.components?.length || 0 }];
  
  categories.forEach(cat => {
    const count = machine.value?.components?.filter(c => c.category === cat).length || 0;
    options.push({ value: cat, label: cat, count });
  });
  
  return options;
});

const filteredComponents = computed(() => {
  if (componentCategoryFilter.value === 'all') {
    return machine.value?.components || [];
  }
  return machine.value?.components?.filter(c => c.category === componentCategoryFilter.value) || [];
});

// Machine edit functions
const openEditMachine = () => {
  showMachineEdit.value = true;
};

const onMachineSaved = async () => {
  showMachineEdit.value = false;
  await loadData();
  showAlert('success', 'Berhasil!', 'Data mesin berhasil diperbarui.');
};
</script>
