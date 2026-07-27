<template>
  <div class="w-full max-w-5xl mx-auto bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
    <div class="px-5 py-3.5 border-b border-slate-100 flex items-center justify-between">
      <div class="flex items-center gap-2">
        <svg class="w-4 h-4 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
        <h2 class="text-sm font-bold text-slate-700">Daftar Komponen & Indikator</h2>
      </div>
      <div class="flex items-center gap-3">
        <div class="relative">
          <svg class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
          <input
            :value="search"
            @input="$emit('update:search', $event.target.value)"
            type="text"
            placeholder="Cari komponen..."
            class="w-40 sm:w-56 text-xs border border-slate-200 rounded-lg pl-9 pr-3 py-1.5 focus:outline-none focus:ring-1 focus:ring-brand-brown text-slate-700"
          />
        </div>
        <span class="text-xs text-slate-400">{{ components.length }} komponen ditampilkan</span>
      </div>
    </div>

    <div class="overflow-x-auto">
      <table class="w-full min-w-0 sm:w-auto text-sm">
        <thead>
          <tr class="bg-slate-50 border-b border-slate-100">
            <th class="px-4 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wide">Komponen</th>
            <th class="px-4 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wide hidden sm:table-cell">Deskripsi</th>
            <th class="py-3 px-1 text-center text-xs font-bold text-slate-500 uppercase tracking-wide sm:hidden" style="width:100px">Status</th>
            <th class="py-3 px-2 text-center text-xs font-bold text-slate-500 uppercase tracking-wide hidden sm:table-cell w-40">Status</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <tr v-if="components.length === 0" class="bg-white">
            <td colspan="4" class="px-6 py-20 text-center">
              <div class="flex flex-col items-center justify-center gap-2">
                <svg class="w-12 h-12 text-slate-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.172 9.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <div class="space-y-1">
                  <p class="text-sm font-bold text-slate-500">
                    <template v-if="search">
                      Tidak ada komponen yang cocok dengan "{{ search }}"
                    </template>
                    <template v-else-if="difficultyFilter === 'ringan'">
                      Mesin ini tidak memiliki komponen kategori ringan
                    </template>
                    <template v-else-if="difficultyFilter === 'berat'">
                      Mesin ini tidak memiliki komponen kategori berat / sedang
                    </template>
                    <template v-else>
                      Tidak ada komponen yang ditemukan
                    </template>
                  </p>
                  <p class="text-xs text-slate-400">Silakan cek kembali filter atau daftar komponen mesin ini.</p>
                </div>
              </div>
            </td>
          </tr>
          <template v-for="comp in components" :key="comp.id">

            <tr :id="`comp-${comp.id}`"
              :class="[
                comp.hasError ? 'border-l-2 border-l-red-400 bg-red-50/40' : (comp.isLocked ? 'bg-slate-300/60' : 'bg-slate-200/50'),
                comp.isLocked ? 'border-l-4 border-indigo-300' : ''
              ]"
              class="border-t border-slate-100"
            >
              <td colspan="4" class="pl-2 pr-2 sm:px-4 py-2.5">
                <div class="flex items-start justify-between gap-2">
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
                      <span v-if="comp.difficulty" :class="getDifficultyBadgeClass(comp.difficulty)" class="text-[10px] px-1.5 py-0.5 rounded font-semibold capitalize">{{ comp.difficulty }}</span>
                      <span v-if="isCompDone(comp)" class="text-[10px] px-1.5 py-0.5 rounded bg-emerald-100 text-emerald-600 font-semibold">{{ comp.conditionPct }}% OK</span>
                      <span v-else-if="comp.inProgress" class="text-[10px] px-1.5 py-0.5 rounded bg-amber-100 text-amber-600 font-semibold">{{ comp.conditionPct }}%</span>
                      <span v-if="comp.hasError" class="text-[10px] px-1.5 py-0.5 rounded bg-red-100 text-red-600 font-semibold">Ada indikator belum diisi</span>
                      <span v-if="comp.isLocked" class="text-[10px] px-1.5 py-0.5 rounded bg-indigo-100 text-indigo-600 font-semibold">{{ comp.lockStatus }}</span>
                    </div>
                  </div>
                  </div>
                  <div class="flex items-center gap-1.5 shrink-0 self-center">
                  <button
                    @click="comp.is_component_replacement = !comp.is_component_replacement"
                    :disabled="comp.isLocked"
                    :class="comp.is_component_replacement ? 'bg-amber-500 border-amber-400 text-white' : 'bg-white border-amber-200 text-slate-500 hover:border-amber-500'"
                    class="hidden sm:flex items-center gap-1.5 px-2.5 py-2 rounded-lg border text-[11px] font-semibold transition-all cursor-pointer"
                  >
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                    {{ comp.is_component_replacement ? 'Diganti' : 'Ganti?' }}
                  </button>
                  <button
                    @click="comp.is_component_replacement = !comp.is_component_replacement"
                    :disabled="comp.isLocked"
                    :class="comp.is_component_replacement ? 'bg-amber-500 border-amber-400 text-white' : 'bg-white border-amber-200 text-slate-500'"
                    class="sm:hidden flex items-center gap-1 px-2 py-2 rounded border text-[10px] font-semibold transition-all cursor-pointer"
                  >
                    <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                    {{ comp.is_component_replacement ? 'Diganti' : 'Ganti?' }}
                  </button>
                  <button
                    @click="comp.showNote = !comp.showNote"
                    :disabled="comp.isLocked"
                    :class="comp.showNote || comp.description ? 'bg-indigo-50 border-indigo-200 text-indigo-600' : 'bg-white border-slate-200 text-slate-500 hover:border-slate-300'"
                    class="hidden sm:flex items-center gap-1.5 px-2.5 py-2 rounded-lg border text-[11px] font-semibold transition-all cursor-pointer"
                  >
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/></svg>
                    Catatan
                  </button>
                  <button
                    @click="comp.showNote = !comp.showNote"
                    :disabled="comp.isLocked"
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
                    :disabled="comp.isLocked"
                    class="w-full text-xs border border-slate-200 rounded-xl px-3 py-2 focus:outline-none focus:ring-1 focus:ring-indigo-400 text-slate-700 placeholder-slate-400 resize-none"
                  ></textarea>
                </div>
              </td>
            </tr>

            <template v-if="comp.indicators.length > 0">
              <tr
                v-for="(ind, iIdx) in comp.indicators" :key="ind.id"
                :class="[
                  comp.isLocked ? 'bg-slate-200/50' : (iIdx % 2 === 0 ? 'bg-white' : 'bg-slate-50/40'),
                  comp.isLocked ? 'border-l-4 border-indigo-300' : '',
                  !comp.isLocked && comp.indicatorValues[ind.id] === null && comp.hasError ? 'outline outline-red-200' : ''
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
                  <div class="flex flex-col items-center justify-center gap-1.5">
                    <button
                      @click="setIndicator(comp, ind.id, true)"
                      :disabled="comp.isLocked"
                      :class="comp.indicatorValues[ind.id] === true ? 'bg-emerald-500 text-white border-emerald-400 shadow-sm' : 'bg-white text-slate-500 border-emerald-200'"
                      class="w-14 py-4 rounded border text-[10px] font-bold transition-all cursor-pointer text-center"
                    >OK</button>
                    <button
                      @click="setIndicator(comp, ind.id, false)"
                      :disabled="comp.isLocked"
                      :class="comp.indicatorValues[ind.id] === false ? 'bg-red-500 text-white border-red-400 shadow-sm' : 'bg-white text-slate-500 border-red-200'"
                      class="w-14 py-4 rounded border text-[10px] font-bold transition-all cursor-pointer text-center"
                    >Not OK</button>
                    <span v-if="comp.indicatorValues[ind.id] === null && comp.hasError" class="text-[10px] text-red-500 font-semibold">!</span>
                  </div>
                </td>
                <td class="py-2.5 px-2 text-center hidden sm:table-cell">
                  <div class="flex flex-nowrap items-center justify-center gap-2">
                    <button
                      @click="setIndicator(comp, ind.id, true)"
                      :disabled="comp.isLocked"
                      :class="comp.indicatorValues[ind.id] === true ? 'bg-emerald-500 text-white border-emerald-400 shadow-sm' : 'bg-white text-slate-500 border-emerald-200 hover:border-emerald-300 hover:text-emerald-600'"
                      class="px-4 py-2 rounded-lg border text-[11px] font-bold transition-all cursor-pointer"
                    >OK</button>
                    <button
                      @click="setIndicator(comp, ind.id, false)"
                      :disabled="comp.isLocked"
                      :class="comp.indicatorValues[ind.id] === false ? 'bg-red-500 text-white border-red-400 shadow-sm' : 'bg-white text-slate-500 border-red-200 hover:border-red-300 hover:text-red-500'"
                      class="px-4 py-2 rounded-lg border text-[11px] font-bold transition-all cursor-pointer"
                    >Not OK</button>
                    <span v-if="comp.indicatorValues[ind.id] === null && comp.hasError" class="text-[10px] text-red-500 font-semibold">!</span>
                  </div>
                </td>
              </tr>
            </template>

            <tr v-else :class="[comp.isLocked ? 'bg-slate-300/40 border-l-4 border-indigo-300' : 'bg-white']" class="border-t border-slate-100/60">
              <td class="px-4 py-3">
                <span class="text-xs text-slate-400 pl-8 italic">Input manual</span>
              </td>
              <td class="px-4 py-3 hidden sm:table-cell">
                <div class="flex items-center gap-3">
                  <input type="range" min="0" max="100" step="5" v-model.number="comp.conditionPct"
                    :disabled="comp.isLocked"
                    @input="comp.inProgress = true"
                    class="flex-1 h-1.5 accent-brand-brown cursor-pointer"/>
                  <div class="flex items-center gap-1">
                    <input type="number" min="0" max="100" step="1" v-model.number="comp.conditionPct"
                      :disabled="comp.isLocked"
                      @input="comp.inProgress = true"
                      class="w-14 text-right text-xs font-black font-mono border border-slate-200 rounded-lg px-2 py-1 focus:outline-none focus:ring-1 focus:ring-brand-brown" :class="condClass(comp.conditionPct)"/>
                    <span class="text-xs font-black font-mono text-slate-400">%</span>
                  </div>
                </div>
                <div class="flex gap-1.5 mt-2">
                  <button v-for="p in presets" :key="p.val" @click="applyPreset(comp, p.val)" :disabled="comp.isLocked"
                    :class="comp.conditionPct === p.val ? 'bg-brand-brown text-white border-brand-brown' : 'bg-slate-50 text-slate-600 border-slate-200 hover:bg-slate-100'"
                    class="px-2 py-0.5 rounded-lg border text-[10px] font-bold cursor-pointer transition-colors"
                  >{{ p.label }}</button>
                </div>
              </td>
              <td class="py-2 pl-1 pr-2 text-center sm:hidden" style="width:100px">
                <div class="flex items-center justify-center gap-1">
                  <input type="number" min="0" max="100" step="5" v-model.number="comp.conditionPct"
                    :disabled="comp.isLocked"
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

<script setup>
defineProps({
  components: { type: Array, default: () => [] },
  search: { type: String, default: '' },
  difficultyFilter: { type: String, default: 'semua' },
  presets: { type: Array, default: () => [{ val: 100, label: 'OK' }, { val: 80, label: 'Minor' }, { val: 50, label: 'Medium' }, { val: 0, label: 'Broken' }] },
  setIndicator: { type: Function, required: true },
  applyPreset: { type: Function, required: true },
  isCompDone: { type: Function, required: true },
  condClass: { type: Function, required: true },
  getDifficultyBadgeClass: { type: Function, required: true },
});

defineEmits(['update:search']);
</script>
