<template>
  <div class="space-y-6">
    <!-- Header -->
    <PageHeader
    title="Approval Laporan"
      :subtitle="isApproverUser ? 'Review dan approve laporan maintenance dari teknisi' : 'Status laporan maintenance yang sudah kamu kirimkan'"
    />

    <!-- Admin Tabs -->
    <div v-if="isAdmin" class="border-b border-slate-200">
      <nav class="flex gap-6 -mb-px">
        <button
          @click="currentTab = 'list'"
          :class="currentTab === 'list' ? 'border-indigo-600 text-indigo-600 font-bold' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300'"
          class="pb-3 border-b-2 text-sm font-semibold transition-all cursor-pointer bg-transparent border-0"
        >
          Daftar Approval
        </button>
        <button
          @click="currentTab = 'flow'"
          :class="currentTab === 'flow' ? 'border-indigo-600 text-indigo-600 font-bold' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300'"
          class="pb-3 border-b-2 text-sm font-semibold transition-all cursor-pointer bg-transparent border-0"
        >
          Alur Approval
        </button>
        <button
          @click="currentTab = 'roles'"
          :class="currentTab === 'roles' ? 'border-indigo-600 text-indigo-600 font-bold' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300'"
          class="pb-3 border-b-2 text-sm font-semibold transition-all cursor-pointer bg-transparent border-0"
        >
          Kelola Role
        </button>
      </nav>
    </div>

    <template v-if="currentTab === 'flow'">
      <!-- Inline Flow Config Layout -->
      <div class="bg-white rounded-3xl border border-slate-100 p-6 shadow-sm space-y-6">
        <div class="flex items-center justify-between">
          <div>
            <h3 class="text-lg font-bold text-slate-800">Alur Approval</h3>
            <p class="text-xs text-slate-500 mt-1">Alur approval disesuaikan berdasarkan role yang membuat laporan maintenance.</p>
          </div>
          <button
            v-if="!flowEditMode"
            @click="flowEditMode = true"
            class="px-4 py-2 bg-indigo-50 border border-indigo-200 text-indigo-600 hover:bg-indigo-100 rounded-xl text-xs font-bold cursor-pointer transition-all flex items-center gap-1.5"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
            Edit Alur
          </button>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
          <!-- Sidebar: Reporter Roles -->
          <div class="space-y-2">
            <label class="text-xs font-bold text-slate-500 uppercase tracking-wider block mb-3">Role Pembuat Laporan</label>
            <button
              v-for="role in reporterRoles"
              :key="role.name"
              @click="activeReporterRole = role.name"
              :class="activeReporterRole === role.name 
                ? 'bg-indigo-50 border-indigo-200 text-indigo-700 font-bold' 
                : 'bg-slate-50 border-slate-100 hover:bg-slate-100 text-slate-600'"
              class="w-full text-left px-4 py-3 rounded-xl border text-sm font-semibold transition-all cursor-pointer flex items-center justify-between"
            >
              <span>{{ role.display_name || role.name }}</span>
              <span class="text-[10px] px-2.5 py-0.5 rounded-full bg-slate-200 text-slate-600 font-extrabold">
                {{ getFlowStepsForReporter(role.name).length }} Tahap
              </span>
            </button>
          </div>

          <!-- Content: Flow Steps of the selected reporter role -->
          <div class="md:col-span-3 space-y-6">
            <h4 class="text-sm font-bold text-slate-700">Alur Approval untuk role: <span class="text-indigo-600 capitalize font-extrabold">{{ activeReporterDisplayName }}</span></h4>

            <!-- Steps List -->
            <div v-if="getFlowStepsForReporter(activeReporterRole).length === 0" class="border-2 border-dashed border-slate-200 rounded-2xl p-8 text-center text-slate-500">
              <p class="text-sm">Belum ada alur approval yang diatur untuk role ini.</p>
              <p class="text-xs text-slate-400 mt-1">Laporan dari role ini akan menggunakan alur fallback dari role <strong>technician</strong>.</p>
              <button
                v-if="flowEditMode"
                @click="initializeWithDefaultSteps(activeReporterRole)"
                class="mt-4 px-4 py-2 bg-indigo-50 border border-indigo-200 text-indigo-600 hover:bg-indigo-100 rounded-xl text-xs font-bold transition-all cursor-pointer"
              >
                Buat Alur Baru
              </button>
            </div>

            <div v-else class="space-y-4">
              <div
                v-for="(step, index) in getFlowStepsForReporter(activeReporterRole)"
                :key="index"
                class="flex items-center gap-4 bg-slate-50 p-4 rounded-2xl border border-slate-100 shadow-xs"
              >
                <div class="w-8 h-8 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center text-sm font-bold shrink-0">
                  {{ index + 1 }}
                </div>
                <div class="flex-1">
                  <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Role Approver</label>
                  <select
                    v-model="step.role"
                    :disabled="!flowEditMode"
                    :class="flowEditMode ? 'bg-white cursor-pointer' : 'bg-slate-100 cursor-not-allowed opacity-70'"
                    class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 mt-1"
                  >
                    <option value="" disabled>Pilih role approver</option>
                    <option v-for="r in approvableRoles" :key="r.name" :value="r.name">
                      {{ r.display_name || r.name }}
                    </option>
                  </select>
                </div>
                <button
                  v-if="flowEditMode"
                  @click="removeStepForReporter(activeReporterRole, index)"
                  class="p-2.5 text-red-500 hover:bg-red-50 rounded-xl transition-all cursor-pointer mt-4"
                  title="Hapus tahap"
                >
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                </button>
              </div>

              <button
                v-if="flowEditMode && getFlowStepsForReporter(activeReporterRole).length < 10"
                @click="addStepForReporter(activeReporterRole)"
                class="w-full py-3 border-2 border-dashed border-slate-200 hover:border-indigo-400 text-slate-500 hover:text-indigo-600 rounded-xl text-xs font-bold transition-all cursor-pointer flex items-center justify-center gap-1.5 bg-slate-50/50 hover:bg-white"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Tambah Tahap Approval
              </button>
            </div>

            <!-- Bottom Action buttons (edit mode only) -->
            <div v-if="flowEditMode" class="flex items-center justify-end gap-3 pt-6 border-t border-slate-100">
              <button
                @click="cancelFlowEdit"
                class="px-5 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-xl text-xs font-bold cursor-pointer transition-all"
              >
                Batal
              </button>
              <button
                @click="saveFlowConfig"
                :disabled="savingFlowConfig || !isFlowConfigValid"
                class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 text-white rounded-xl text-xs font-bold flex items-center gap-1.5 cursor-pointer transition-all shadow-sm"
              >
                <svg v-if="savingFlowConfig" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                <span>Simpan Alur (Semua Role)</span>
              </button>
            </div>
          </div>
        </div>
      </div>
    </template>

    <template v-else-if="currentTab === 'roles'">
      <!-- Inline Role Management -->
      <div class="bg-white rounded-3xl border border-slate-100 p-6 shadow-sm space-y-6">
        <div class="flex items-center justify-between">
          <div>
            <h3 class="text-lg font-bold text-slate-800 flex items-center gap-2">
              <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
              </svg>
              Kelola Role
            </h3>
            <p class="text-xs text-slate-500 mt-1">Tambah, ubah, atau hapus role. Role dengan akses report dapat dikonfigurasi kategori komponen wajibnya.</p>
          </div>
          <button
            v-if="!roleEditMode"
            @click="roleEditMode = true"
            class="px-4 py-2 bg-indigo-50 border border-indigo-200 text-indigo-600 hover:bg-indigo-100 rounded-xl text-xs font-bold cursor-pointer transition-all flex items-center gap-1.5"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
            Edit Role
          </button>
        </div>

        <!-- New Role Form (edit mode only) -->
        <div v-if="roleEditMode" class="bg-slate-50 p-4 rounded-2xl border border-slate-100 space-y-3">
          <h4 class="text-xs font-bold text-slate-600 uppercase tracking-wider">Tambah Role Baru</h4>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            <div>
              <label class="text-[10px] font-semibold text-slate-500 uppercase tracking-wider">Nama Role (slug)</label>
              <input
                v-model="newRole.name"
                type="text"
                placeholder="contoh: supervisor"
                class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 bg-white"
              />
            </div>
            <div>
              <label class="text-[10px] font-semibold text-slate-500 uppercase tracking-wider">Nama Tampilan</label>
              <input
                v-model="newRole.display_name"
                type="text"
                placeholder="contoh: Supervisor"
                class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 bg-white"
              />
            </div>
          </div>
          <div class="flex items-center gap-4 flex-wrap">
            <div class="flex items-center gap-2">
              <input id="newRoleCanApprove" v-model="newRole.can_approve" type="checkbox" class="w-4 h-4 text-indigo-600 rounded border-slate-300 focus:ring-indigo-500" />
              <label for="newRoleCanApprove" class="text-xs text-slate-600">Dapat Approval</label>
            </div>
            <div class="flex items-center gap-2">
              <input id="newRoleCanReport" v-model="newRole.can_report" type="checkbox" class="w-4 h-4 text-indigo-600 rounded border-slate-300 focus:ring-indigo-500" />
              <label for="newRoleCanReport" class="text-xs text-slate-600">Dapat Report</label>
            </div>
          </div>
          <button
            @click="addNewRole"
            :disabled="!isNewRoleValid || roleConfigLoading"
            class="w-full md:w-auto px-4 py-2 bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 text-white rounded-xl text-xs font-bold cursor-pointer transition-all flex items-center justify-center gap-1.5"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Tambah Role
          </button>
        </div>

        <!-- Roles Table -->
        <div class="overflow-x-auto">
          <table class="w-full text-xs">
            <thead class="bg-slate-50 text-slate-500 uppercase tracking-wider">
              <tr>
                <th class="px-3 py-2 text-left font-semibold">Nama</th>
                <th class="px-3 py-2 text-left font-semibold">Tampilan</th>
                <th class="px-3 py-2 text-center font-semibold">Approve</th>
                <th class="px-3 py-2 text-center font-semibold">Report</th>
                <th class="px-3 py-2 text-left font-semibold">Kategori Wajib</th>
                <th v-if="roleEditMode" class="px-3 py-2 text-right font-semibold">Aksi</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-for="role in localRoles" :key="role.id" class="hover:bg-slate-50">
                <td class="px-3 py-2">
                  <input
                    v-if="roleEditMode && !isCoreSystemRole(role)"
                    v-model="role.name"
                    type="text"
                    class="w-full rounded-lg border border-slate-200 px-2 py-1 text-xs text-slate-700 focus:outline-none focus:ring-1 focus:ring-indigo-500 bg-white"
                  />
                  <span v-else class="text-xs text-slate-700 font-medium">{{ role.name }}</span>
                </td>
                <td class="px-3 py-2">
                  <input
                    v-if="roleEditMode"
                    v-model="role.display_name"
                    type="text"
                    class="w-full rounded-lg border border-slate-200 px-2 py-1 text-xs text-slate-700 focus:outline-none focus:ring-1 focus:ring-indigo-500 bg-white"
                  />
                  <span v-else class="text-xs text-slate-700">{{ role.display_name }}</span>
                </td>
                <td class="px-3 py-2 text-center">
                  <input v-model="role.can_approve" type="checkbox" :disabled="!roleEditMode" class="w-4 h-4 text-indigo-600 rounded border-slate-300 focus:ring-indigo-500" />
                </td>
                <td class="px-3 py-2 text-center">
                  <input v-model="role.can_report" type="checkbox" :disabled="!roleEditMode" class="w-4 h-4 text-indigo-600 rounded border-slate-300 focus:ring-indigo-500" />
                </td>
                <td class="px-3 py-2">
                  <div v-if="role.can_report" class="flex items-center gap-2 flex-wrap">
                    <label v-for="diff in difficultyOptions" :key="diff.value" class="flex items-center gap-1 text-[11px] text-slate-600" :class="roleEditMode ? 'cursor-pointer' : 'cursor-not-allowed opacity-70'">
                      <input
                        type="checkbox"
                        :checked="(role.required_difficulties || []).includes(diff.value)"
                        @change="toggleDifficulty(role, diff.value)"
                        :disabled="!roleEditMode"
                        class="w-3.5 h-3.5 text-indigo-600 rounded border-slate-300 focus:ring-indigo-500"
                      />
                      {{ diff.label }}
                    </label>
                  </div>
                  <span v-else class="text-[10px] text-slate-400 italic">—</span>
                </td>
                <td v-if="roleEditMode" class="px-3 py-2 text-right">
                  <button
                    v-if="!isSystemRole(role)"
                    @click="confirmDeleteRole(role)"
                    :disabled="roleConfigLoading"
                    class="p-1.5 text-red-500 hover:bg-red-50 rounded-lg transition-all cursor-pointer"
                    title="Hapus"
                  >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Bottom Action buttons (edit mode only) -->
        <div v-if="roleEditMode" class="flex items-center justify-end gap-3 pt-6 border-t border-slate-100">
          <button
            @click="cancelRoleEdit"
            class="px-5 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-xl text-xs font-bold cursor-pointer transition-all"
          >
            Batal
          </button>
          <button
            @click="saveAllRoles"
            :disabled="roleConfigLoading || !isRoleNamesValid"
            class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 text-white rounded-xl text-xs font-bold flex items-center gap-1.5 cursor-pointer transition-all shadow-sm"
          >
            <svg v-if="roleConfigLoading" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
            <span>Simpan Semua Role</span>
          </button>
        </div>
      </div>
    </template>

    <template v-else>
      <!-- Stats (Approver only) -->
      <div v-if="isApproverUser" class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <StatCard :value="pendingCount"  label="Menunggu Approval" color="amber" />
        <StatCard :value="approvedCount" label="Disetujui"         color="green" />
        <StatCard :value="rejectedCount" label="Ditolak"           color="red" />
        <StatCard :value="items.length"  label="Total Report"      color="slate" />
      </div>

      <!-- Search & Filter -->
      <div class="flex flex-col md:flex-row gap-4 items-center justify-between">
        <SearchInput
          v-model="searchQuery"
          placeholder="Cari mesin, teknisi..."
        />
        <div class="flex items-center gap-3">
          <FilterTabs v-model="activeFilter" :tabs="filterTabsWithCount" />
        </div>
      </div>

    <!-- Main Table -->
    <DataTable
      :columns="approvalColumns"
      :rows="paginatedItems"
      :loading="loading"
      loading-text="Memuat data approval..."
      loading-subtext="Mengambil laporan dari server"
      empty-title="Tidak ada laporan ditemukan"
      empty-subtext="Coba sesuaikan filter atau pencarian."
      min-width="min-w-[800px]"
      :paginate="false"
      actions-align="right"
      actions-width="w-[12%]"
    >
      <!-- Kolom: Mesin & Waktu -->
      <template #cell-machine_name="{ row }">
        <div class="flex items-center gap-1.5 flex-wrap">
          <p class="font-semibold text-slate-800 text-sm cursor-pointer hover:text-indigo-600 transition-colors" @click="goToMachine(row.machine_id)">{{ row.machine_name }}</p>
          <span v-if="row.is_unscheduled" class="text-[9px] font-extrabold px-1.5 py-0.5 rounded bg-amber-100 text-amber-800 border border-amber-200 tracking-wider uppercase shrink-0">Luar Jadwal</span>
          <span v-else-if="row.is_late" class="text-[9px] font-extrabold px-1.5 py-0.5 rounded bg-rose-50 text-rose-700 border border-rose-100 tracking-wider uppercase shrink-0">Terlambat</span>
          <span v-else class="text-[9px] font-extrabold px-1.5 py-0.5 rounded bg-indigo-50 text-indigo-700 border border-indigo-100 tracking-wider uppercase shrink-0">Sesuai Jadwal</span>
        </div>
        <div class="flex items-center gap-1.5 mt-0.5">
          <span v-if="row.machine_kota" class="text-[9px] font-bold px-1.5 py-0.5 rounded bg-slate-100 text-slate-500 border border-slate-200 uppercase tracking-wider">
            {{ row.machine_kota === 'sby' ? 'Surabaya' : row.machine_kota === 'pasuruan' ? 'Pasuruan' : row.machine_kota }}
          </span>
          <p class="text-xs text-slate-400">{{ formatDateTime(row.maintenance_date) }}</p>
        </div>
        <p v-if="row.duration_minutes" class="text-xs text-brand-gradation font-semibold mt-0.5">
          Durasi: {{ formatDuration(row.duration_minutes) }}
          <span v-if="row.start_time || row.end_time" class="text-slate-400 font-normal">({{ formatTime(row.start_time) }} - {{ formatTime(row.end_time) }})</span>
        </p>
      </template>

      <!-- Kolom: Teknisi -->
      <template #cell-technician_name="{ row }">
        <div class="flex items-center gap-2">
          <div class="w-7 h-7 rounded-full bg-linear-to-tr from-brand-brown to-brand-gradation flex items-center justify-center text-white text-[10px] font-bold shrink-0">
            {{ initials(row.technician_name) }}
          </div>
          <span class="text-sm text-slate-700">{{ row.technician_name ?? '-' }}</span>
        </div>
      </template>

      <!-- Kolom: Detail Tindakan -->
      <template #cell-actions_summary="{ row }">
        <div class="space-y-1.5">
          <div class="flex items-center gap-1.5 flex-wrap">
            <span class="text-xs font-semibold px-2 py-0.5 bg-slate-100 text-slate-700 rounded-md">{{ row.actions.length }} Tindakan</span>
            <span v-if="getReplaceCount(row.actions) > 0" class="text-xs font-semibold px-2 py-0.5 bg-red-50 text-red-700 border border-red-100 rounded-md">
              {{ getReplaceCount(row.actions) }} Ganti
            </span>
            <span v-if="getInspectCount(row.actions) > 0" class="text-xs font-semibold px-2 py-0.5 bg-blue-50 text-blue-700 border border-blue-100 rounded-md">
              {{ getInspectCount(row.actions) }} Inspeksi
            </span>
          </div>
          <button
            @click="openDetails(row)"
            class="text-indigo-600 hover:text-indigo-800 text-xs font-bold flex items-center gap-1 transition-colors cursor-pointer pt-0.5"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
            Lihat Laporan ({{ row.actions.length }})
          </button>
        </div>
      </template>

      <!-- Kolom: Status -->
      <template #cell-approval_status="{ row }">
        <div class="space-y-2">
          <span :class="statusBadgeClass(row.approval_status)" class="inline-flex items-center gap-1.5 text-xs font-semibold px-3 py-1 rounded-full">
            <span class="w-1.5 h-1.5 rounded-full" :class="statusDotClass(row.approval_status)"></span>
            {{ statusLabel(row.approval_status) }}
          </span>
          <ApprovalProgress
            :flow-steps="row.flow_steps || []"
            :current-step="row.current_step || 1"
            :completed-steps="row.completed_steps || 0"
            :total-steps="row.total_steps || 0"
            :status="row.approval_status"
            :pending-role="row.pending_role"
          />
          <p v-if="row.approval_status === 'approved' && row.approved_by" class="text-[10px] text-slate-400">oleh {{ row.approved_by }} • {{ formatDateTime(row.decided_at) }}</p>
          <p v-if="row.approval_status === 'rejected' && row.approved_by" class="text-[10px] text-red-400">oleh {{ row.approved_by }} • {{ formatDateTime(row.decided_at) }}</p>
          <div v-if="row.approval_notes && row.approval_status === 'rejected'" class="mt-1 bg-red-50 border border-red-100 rounded-lg px-2 py-1.5">
            <p class="text-[10px] font-semibold text-red-600">Alasan:</p>
            <p class="text-[10px] text-red-700 italic line-clamp-3">{{ row.approval_notes }}</p>
          </div>
          <p v-else-if="row.approval_notes" class="text-[10px] text-amber-600 italic line-clamp-2">{{ row.approval_notes }}</p>
        </div>
      </template>

      <!-- Slot Aksi -->
      <template #actions="{ row }">
        <div v-if="canDecide(row)" class="flex items-center justify-end gap-1.5">
          <Button
            size="sm"
            @click="openDecide(row, 'approved')"
            class="rounded-lg bg-green-500 hover:bg-green-600 text-white text-xs font-semibold h-auto py-1.5 px-3"
          >Setujui</Button>
          <Button
            size="sm"
            @click="openDecide(row, 'rejected')"
            class="rounded-lg bg-red-500 hover:bg-red-600 text-white text-xs font-semibold h-auto py-1.5 px-3"
          >Tolak</Button>
        </div>
        <Button
          v-else-if="row.approval_status === 'rejected' && row.technician_id === currentUser?.id"
          size="sm"
          @click="goToMachine(row.machine_id)"
          class="rounded-lg bg-amber-500 hover:bg-amber-600 text-white text-xs font-semibold h-auto py-1.5 px-3 flex items-center gap-1.5"
        >
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
          Cek Ulang
        </Button>
      </template>
    </DataTable>

    <!-- Pagination -->
    <TablePagination
      v-if="!loading && currentTab === 'list'"
      v-model="currentPage"
      :total="filteredItems.length"
      :per-page="perPage"
    />
    </template>
  </div>

  <!-- Decide Modal -->
  <ApprovalDecideModal
    :show="decideModal.show"
    :item="decideModal.item"
    :decision="decideModal.decision"
    :loading="deciding"
    @close="decideModal.show = false"
    @submit="submitDecision"
  />

  <!-- Detail Actions Modal -->
  <ApprovalDetailModal
    :show="detailModal.show"
    :item="detailModal.item"
    :can-decide="detailCanDecide"
    @close="detailModal.show = false"
    @decide="openDecideFromDetail"
  />

</template>

<script setup>
import { ref, computed, onMounted, watch, watchEffect } from 'vue';
import axios from 'axios';
import { router } from '@inertiajs/vue3';
import { useAuth } from '../composables/useAuth.js';
import PageHeader from '../components/PageHeader.vue';
import SearchInput from '../components/SearchInput.vue';
import FilterTabs from '../components/FilterTabs.vue';
import DataTable from '../components/DataTable.vue';
import TablePagination from '../components/TablePagination.vue';
import StatCard from '../components/StatCard.vue';
import Button from '../../views/components/ui/button/Button.vue';
import ApprovalDecideModal from '../components/ApprovalDecideModal.vue';
import ApprovalDetailModal from '../components/ApprovalDetailModal.vue';
import ApprovalProgress from '../components/ApprovalProgress.vue';
import { showAlert, showConfirm } from '../composables/useAlert.js';

const loading = ref(true);
const deciding = ref(false);
const items = ref([]);
const activeFilter = ref('all');
const searchQuery = ref('');
const currentPage = ref(1);
const perPage = 10;

const currentTab = ref('list'); // 'list', 'flow', or 'roles'
const activeReporterRole = ref('technician');
const localFlowSteps = ref([]);
const savingFlowConfig = ref(false);
const flowEditMode = ref(false);
const flowStepsBackup = ref([]);

const localRoles = ref([]);
const rolesBackup = ref([]);
const newRole = ref({ name: '', display_name: '', can_approve: true, can_report: false, is_active: true });
const roleConfigLoading = ref(false);
const roleEditMode = ref(false);

const difficultyOptions = [
  { value: 'berat', label: 'Berat' },
  { value: 'sedang', label: 'Sedang' },
  { value: 'ringan', label: 'Ringan' },
  { value: 'none', label: 'Tanpa Kategori' },
];

const approvalColumns = [
  { key: 'machine_name',     label: 'Mesin & Waktu',    width: 'w-[22%]', cellClass: 'align-top' },
  { key: 'technician_name',  label: 'Teknisi',           width: 'w-[18%]', cellClass: 'align-top' },
  { key: 'actions_summary',  label: 'Detail Tindakan',   width: 'w-[30%]', cellClass: 'align-top' },
  { key: 'approval_status',  label: 'Status',            width: 'w-[20%]', cellClass: 'align-top' },
];

const { isAdmin, user: currentUser, authReady } = useAuth();

const decideModal = ref({ show: false, item: null, decision: 'approved', notes: '' });
const detailModal = ref({ show: false, item: null });
const roles = ref([]);

const getReplaceCount = (actions) => {
  if (!actions) return 0;
  return actions.filter(a => a.action_type === 'replace').length;
};

const getInspectCount = (actions) => {
  if (!actions) return 0;
  return actions.filter(a => a.action_type !== 'replace').length;
};

const openDetails = (item) => {
  detailModal.value = { show: true, item };
};

const openDecideFromDetail = (decision) => {
  if (!detailModal.value.item) return;
  openDecide(detailModal.value.item, decision);
};

const loadData = async () => {
  loading.value = true;
  try {
    const [approvalsRes, rolesRes, flowConfigRes] = await Promise.all([
      axios.get('/api/approvals'),
      axios.get('/api/roles'),
      axios.get('/api/approval-flow'),
    ]);
    items.value = Array.isArray(approvalsRes.data) ? approvalsRes.data : [];
    roles.value = Array.isArray(rolesRes.data) ? rolesRes.data : [];
    localRoles.value = roles.value.map(r => ({ ...r, required_difficulties: r.required_difficulties || [] }));
    rolesBackup.value = roles.value.map(r => ({ ...r, required_difficulties: [...(r.required_difficulties || [])] }));
    localFlowSteps.value = Array.isArray(flowConfigRes.data?.steps) ? flowConfigRes.data.steps.map(s => ({ ...s })) : [];
    flowStepsBackup.value = localFlowSteps.value.map(s => ({ ...s }));
  } catch (e) {
    console.error(e);
    showAlert('error', 'Gagal Memuat', e.response?.data?.message || 'Terjadi kesalahan saat memuat data.');
    items.value = [];
    roles.value = [];
    localRoles.value = [];
    localFlowSteps.value = [];
  } finally {
    loading.value = false;
  }
};

onMounted(() => {
  if (authReady.value) loadData();
});

watch(authReady, (ready) => {
  if (ready && items.value.length === 0 && !loading.value) loadData();
});

const approvingRoleNames = computed(() => roles.value.filter(r => r.can_approve).map(r => r.name));
const approvableRoles = computed(() => roles.value.filter(r => r.can_approve && r.is_active));
const isApproverUser = computed(() => {
  const role = currentUser.value?.role;
  return role === 'admin' || approvingRoleNames.value.includes(role);
});

const myPendingItems = computed(() => {
  const role = currentUser.value?.role;
  if (role === 'admin') return items.value.filter(i => i.approval_status === 'pending');
  if (!isApproverUser.value) return items.value.filter(i => i.approval_status === 'pending');
  return items.value.filter(i => i.approval_status === 'pending' && i.pending_role === role);
});
const pendingCount  = computed(() => myPendingItems.value.length);
const approvedCount = computed(() => items.value.filter(i => i.approval_status === 'approved').length);
const rejectedCount = computed(() => items.value.filter(i => i.approval_status === 'rejected').length);

const detailCanDecide = computed(() => canDecide(detailModal.value.item));

const filterTabsWithCount = computed(() => [
  { value: 'all',      label: 'Semua',    count: items.value.length },
  { value: 'pending',  label: isApproverUser.value ? 'Menunggu Anda' : 'Menunggu', count: pendingCount.value },
  { value: 'approved', label: 'Disetujui', count: approvedCount.value },
  { value: 'rejected', label: 'Ditolak',  count: rejectedCount.value },
]);

const filteredItems = computed(() => {
  let list = items.value;
  if (activeFilter.value === 'pending') {
    list = myPendingItems.value;
  } else if (activeFilter.value !== 'all') {
    list = list.filter(i => i.approval_status === activeFilter.value);
  }
  if (searchQuery.value.trim()) {
    const q = searchQuery.value.toLowerCase();
    list = list.filter(i =>
      (i.machine_name && i.machine_name.toLowerCase().includes(q)) ||
      (i.technician_name && i.technician_name.toLowerCase().includes(q))
    );
  }
  return list;
});

const paginatedItems = computed(() => {
  const start = (currentPage.value - 1) * perPage;
  return filteredItems.value.slice(start, start + perPage);
});

watch([activeFilter, searchQuery], () => { currentPage.value = 1; });

const openDecide = (item, decision) => {
  decideModal.value = { show: true, item, decision };
};

const submitDecision = async (notes) => {
  deciding.value = true;
  try {
    await axios.post(`/api/approvals/${decideModal.value.item.record_id}/decide`, {
      decision: decideModal.value.decision,
      notes: notes || null,
    });
    decideModal.value.show = false;
    detailModal.value.show = false;
    await loadData();
  } catch (e) {
    showAlert('error', 'Gagal Memproses', e.response?.data?.message || e.message);
  } finally {
    deciding.value = false;
  }
};

const canDecide = (item) => {
  if (!item || !item.approval_status || !item.pending_role) return false;
  const role = currentUser.value?.role;
  if (role === 'admin') return true;
  if (!approvingRoleNames.value.includes(role)) return false;
  return item.approval_status === 'pending' && role === item.pending_role;
};

const reporterRoles = computed(() => {
  const list = roles.value.filter(r => r.is_active && r.can_report);
  if (!list.some(r => r.name === 'technician')) {
    const tech = roles.value.find(r => r.name === 'technician');
    if (tech) list.unshift(tech);
  }
  return list;
});

const getFlowStepsForReporter = (reporterRole) => {
  return localFlowSteps.value.filter(s => s.reporter_role === reporterRole);
};

const addStepForReporter = (reporterRole) => {
  const steps = getFlowStepsForReporter(reporterRole);
  if (steps.length < 10) {
    localFlowSteps.value.push({
      reporter_role: reporterRole,
      role: '',
      step_order: steps.length + 1,
      is_active: true
    });
  }
};

const removeStepForReporter = (reporterRole, indexInGroup) => {
  let count = 0;
  const globalIndex = localFlowSteps.value.findIndex(s => {
    if (s.reporter_role === reporterRole) {
      if (count === indexInGroup) return true;
      count++;
    }
    return false;
  });
  
  if (globalIndex !== -1) {
    localFlowSteps.value.splice(globalIndex, 1);
    let newOrder = 1;
    localFlowSteps.value.forEach(s => {
      if (s.reporter_role === reporterRole) {
        s.step_order = newOrder++;
      }
    });
  }
};

const initializeWithDefaultSteps = (reporterRole) => {
  localFlowSteps.value.push({
    reporter_role: reporterRole,
    role: '',
    step_order: 1,
    is_active: true
  });
};

const activeReporterDisplayName = computed(() => {
  const role = roles.value.find(r => r.name === activeReporterRole.value);
  return role ? (role.display_name || role.name) : activeReporterRole.value;
});

const isFlowConfigValid = computed(() => {
  return localFlowSteps.value.length > 0 && localFlowSteps.value.every(s => s.role);
});

const cancelFlowEdit = () => {
  localFlowSteps.value = flowStepsBackup.value.map(s => ({ ...s }));
  flowEditMode.value = false;
};

const saveFlowConfig = async () => {
  if (!isFlowConfigValid.value) return;
  savingFlowConfig.value = true;
  try {
    await axios.put('/api/approval-flow', {
      steps: localFlowSteps.value.map(s => ({
        reporter_role: s.reporter_role,
        role: s.role
      }))
    });
    showAlert('success', 'Berhasil', 'Alur approval berhasil disimpan.');
    flowEditMode.value = false;
    await loadData();
  } catch (e) {
    showAlert('error', 'Gagal Menyimpan', e.response?.data?.message || e.message);
  } finally {
    savingFlowConfig.value = false;
  }
};

const isNewRoleValid = computed(() => {
  return roles.value.every(r => r.name !== newRole.value.name) &&
    /^[a-z0-9_]+$/.test(newRole.value.name) &&
    newRole.value.display_name.trim().length > 0;
});

const isCoreSystemRole = (role) => {
  return ['admin', 'technician'].includes(role.name);
};

const isSystemRole = (role) => {
  return isCoreSystemRole(role) || role.is_manager;
};

const isRoleNamesValid = computed(() => {
  const names = localRoles.value.map(r => r.name.trim().toLowerCase());
  if (new Set(names).size !== names.length) return false;
  return localRoles.value.every(r => {
    const name = r.name.trim();
    if (!name) return false;
    if (!/^[a-z0-9_]+$/.test(name)) return false;
    const originalName = rolesBackup.value.find(rb => rb.id === r.id)?.name;
    if (isCoreSystemRole(r) && name !== originalName) return false;
    return true;
  });
});

const toggleDifficulty = (role, diffValue) => {
  if (!role.required_difficulties) role.required_difficulties = [];
  const idx = role.required_difficulties.indexOf(diffValue);
  if (idx === -1) {
    role.required_difficulties.push(diffValue);
  } else {
    role.required_difficulties.splice(idx, 1);
  }
};

const addNewRole = async () => {
  if (!isNewRoleValid.value) return;
  roleConfigLoading.value = true;
  try {
    await axios.post('/api/roles', { ...newRole.value });
    newRole.value = { name: '', display_name: '', can_approve: true, can_report: false, is_active: true };
    await loadData();
    showAlert('success', 'Berhasil', 'Role baru berhasil ditambahkan.');
  } catch (e) {
    showAlert('error', 'Gagal Menyimpan', e.response?.data?.message || e.message);
  } finally {
    roleConfigLoading.value = false;
  }
};

const cancelRoleEdit = () => {
  localRoles.value = rolesBackup.value.map(r => ({ ...r, required_difficulties: [...(r.required_difficulties || [])] }));
  roleEditMode.value = false;
};

const saveAllRoles = async () => {
  roleConfigLoading.value = true;
  try {
    await axios.put('/api/roles/bulk', {
      roles: localRoles.value.map(r => ({
        id: r.id,
        name: r.name,
        display_name: r.display_name,
        can_approve: r.can_approve,
        can_report: r.can_report,
        required_difficulties: r.required_difficulties || [],
      }))
    });
    showAlert('success', 'Berhasil', 'Semua role berhasil disimpan.');
    roleEditMode.value = false;
    await loadData();
  } catch (e) {
    showAlert('error', 'Gagal Menyimpan', e.response?.data?.message || e.message);
  } finally {
    roleConfigLoading.value = false;
  }
};

const confirmDeleteRole = async (role) => {
  const confirmed = await showConfirm('Hapus Role?', `Yakin ingin menghapus role ${role.display_name}?`);
  if (!confirmed) return;
  roleConfigLoading.value = true;
  try {
    await axios.delete(`/api/roles/${role.id}`);
    await loadData();
    showAlert('success', 'Berhasil', `Role ${role.display_name} berhasil dihapus.`);
  } catch (e) {
    showAlert('error', 'Gagal Menghapus', e.response?.data?.message || e.message);
  } finally {
    roleConfigLoading.value = false;
  }
};

const formatDuration = (minutes) => {
  if (minutes === null || minutes === undefined) return '-';
  if (minutes < 60) return `${minutes} menit`;
  const h = Math.floor(minutes / 60);
  const rem = minutes % 60;
  return rem ? `${h} jam ${rem} menit` : `${h} jam`;
};

const formatTime = (timeStr) => {
  if (!timeStr) return '-';
  const parts = timeStr.split(':');
  if (parts.length >= 2) {
    return `${parts[0].padStart(2, '0')}:${parts[1].padStart(2, '0')}`;
  }
  return timeStr;
};

const formatDateTime = (d) => {
  if (!d) return '-';
  return new Date(d).toLocaleString('id-ID', { day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' });
};

const goToMachine = (machineId) => {
  if (machineId) router.visit(`/machine/${machineId}`);
};

const initials = (name) => {
  if (!name) return '?';
  return name.split(' ').slice(0, 2).map(w => w[0]).join('').toUpperCase();
};

const statusLabel = (s) => ({ pending: 'Menunggu', approved: 'Disetujui', rejected: 'Ditolak' }[s] ?? s);

const statusBadgeClass = (s) => ({
  pending:  'bg-amber-100 text-amber-700',
  approved: 'bg-green-100 text-green-700',
  rejected: 'bg-red-100 text-red-700',
}[s] ?? 'bg-slate-100 text-slate-600');

const statusDotClass = (s) => ({
  pending:  'bg-amber-500',
  approved: 'bg-green-500',
  rejected: 'bg-red-500',
}[s] ?? 'bg-slate-400');
</script>
