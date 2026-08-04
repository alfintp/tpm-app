# PRD: Revisi 3 — Role Display, Dashboard Cards, Approval Stats

## 1. Role Name di Alur Approval Tidak Update setelah Ganti Nama

### Problem
Saat user mengganti `display_name` sebuah role di page Approval > tab Roles, nama lama masih tampil di alur approval (flow steps) pada laporan yang sudah ada. Aturan sebelumnya: snapshot alur approval dibekukan hanya jika **struktur alur** berubah (tambah/hapus/edit step). Ganti nama role seharusnya **tidak** membekukan nama lama.

### Root Cause
`approval_flow_snapshot` pada `MaintenanceRecord` menyimpan field `role` (yaitu `name` dari tabel `roles`). Saat menampilkan, backend memetakan `Role::pluck('display_name', 'name')` untuk mendapatkan `role_display`. Jika `name` role tidak berubah, pemetaan ini seharusnya mengambil `display_name` terbaru. Kemungkinan masalah:
- Frontend masih ada yang menampilkan `step.role` (name) bukan `step.role_display`
- Atau ada path kode yang belum lewat mapping `role_display`

### Solusi yang Diajukan
1. **Audit semua tempat yang menampilkan role di alur approval** — pastikan semua menggunakan `role_display` (display_name), bukan `role` (name)
2. **Backend**: Pastikan `recordApprovalState()` selalu menambahkan `role_display` ke setiap step, baik dari snapshot maupun dari flow aktif
3. **Backend**: Pastikan `mapApprovals()` juga mengembalikan `role_display` untuk setiap approval decision
4. **Frontend**: Pastikan `ApprovalProgress.vue`, `ComponentHistory.vue`, dan modal detail approval semua menggunakan `role_display`

### Files Terkait
- `app/Http/Controllers/ApprovalController.php` — `recordApprovalState()`, `mapApprovals()`
- `app/Http/Controllers/MachineComponentController.php` — `recordApprovalState()` (duplikat)
- `resources/js/components/ApprovalProgress.vue`
- `resources/js/components/ComponentHistory.vue`
- `resources/js/components/ApprovalDetailModal.vue`

---

## 2. Revisi Kotak Statistik di Dashboard (Laporan Maintenance)

### Problem
Kotak-kotak statistik di `DashboardReportPanel.vue` perlu direvisi:

### Current State (8 kotak)
1. Total Mesin
2. Dicek Semua
3. Dicek Sebagian
4. Tidak Dicek
5. Tepat Waktu
6. Terlambat
7. Diluar Jadwal
8. Ganti Komponen
9. Mesin Terkunci

### Proposed Changes

#### 2.1 Kotak Pertama: "Total Mesin" → "Mesin Berprogres"
- **Sekarang**: Menampilkan total semua mesin
- **Revisi**: `x / total mesin`, di mana `x` = mesin yang sudah ada progres pengerjaan (dicek semua ATAU dicek sebagian). Mesin yang 0 progres tidak dihitung ke `x`.
- **Format**: Tampilkan sebagai `x / total` (misal: `7 / 10`)

#### 2.2 Hapus Kotak "Mesin Terkunci"
- Kotak "Mesin Terkunci" dihapus dari stat boxes

#### 2.3 Revisi Aturan "Tepat Waktu" dan "Terlambat"
- **Sekarang**: Timeliness dihitung dari `latestCompleted` record saja (record terakhir yang completed & tidak rejected)
- **Revisi**: Mesin dikategorikan "Tepat Waktu" atau "Terlambat" hanya **setelah semua progres pengisian selesai** (semua komponen sudah dicek = `checkState === 'full'`)
  - Jika **semua** laporan mesin tersebut tepat waktu → masuk "Tepat Waktu"
  - Jika **ada 1 saja** laporan yang telat → mesin masuk "Terlambat"
  - Jika belum selesai semua (partial/none) → tidak masuk kedua kategori

#### 2.4 Mesin dengan 2x Laporan per Bulan
- **Problem**: Beberapa mesin memiliki 2 period per bulan (interval 14 hari), sehingga punya 2 laporan. Bagaimana menentukan tepat waktu / terlambat?
- **Ide Solusi**:
  - **Opsi A**: Mesin dianggap "Tepat Waktu" hanya jika **kedua** laporan tepat waktu. Jika salah satu telat → mesin "Terlambat". (Sederhana, mudah dipahami user)
  - **Opsi B**: Tampilkan breakdown per laporan di tooltip/detail — "Laporan 1: Tepat Waktu, Laporan 2: Terlambat" sehingga user bisa lihat detail tanpa bingung di level ringkasan
  - **Rekomendasi**: **Opsi A** untuk kotak statistik (binary: tepat waktu / terlambat), dengan **Opsi B** sebagai info tambahan di tabel detail per mesin (kolom "Laporan" sudah ada, bisa tambah badge per laporan)

### Proposed Final Kotak (7 kotak)
1. **Mesin Berprogres** — `x / total` (mesin dengan progres > 0)
2. **Dicek Semua** — mesin dengan semua komponen dicek
3. **Dicek Sebagian** — mesin dengan sebagian komponen dicek
4. **Tidak Dicek** — mesin tanpa progres
5. **Tepat Waktu** — mesin yang semua laporannya tepat waktu (hanya jika progres selesai)
6. **Terlambat** — mesin dengan minimal 1 laporan telat (hanya jika progres selesai)
7. **Diluar Jadwal** — mesin dengan laporan di luar jadwal
8. **Ganti Komponen** — jumlah penggantian komponen

*(Mesin Terkunci dihapus)*

### Files Terkait
- `resources/js/components/DashboardReportPanel.vue` — `statBoxes`, `machineSummary`, `stats`

---

## 3. Stats Card di Page Approval Ikut Filter Bulan

### Problem
Saat user filter bulan (misal Juli) di page Approval, StatCard (Total Report, Disetujui, Ditolak) masih menampilkan count dari **semua data**, bukan data yang sudah difilter.

### Root Cause
- `approvedCount` dan `rejectedCount` di `Approval.vue` dihitung dari `items.value` (semua), bukan `filteredItems.value`
- `StatCard` di `ApprovalListPanel.vue` menggunakan `items.length` (prop dari parent), bukan `filteredItems.length`
- `filterTabsWithCount` juga menggunakan `items.value.length` untuk count "Semua"

### Solusi
1. **`Approval.vue`**: Ubah `approvedCount`, `rejectedCount`, dan count di `filterTabsWithCount` untuk menghitung dari `filteredItems.value` (yang sudah memperhitungkan `cityFilter`, `filterMonth`, `searchQuery`, dll)
2. **`ApprovalListPanel.vue`**: Ubah `StatCard` untuk Total Report menggunakan `filteredItems.length` (sudah ada sebagai prop), bukan `items.length`
3. **`pendingCount`**: Sudah dihitung dari `myPendingItems` yang juga perlu memperhitungkan filter bulan

### Files Terkait
- `resources/js/views/Approval.vue` — `approvedCount`, `rejectedCount`, `filterTabsWithCount`, `pendingCount`
- `resources/js/components/ApprovalListPanel.vue` — `StatCard` values

---

## Prioritas Eksekusi
1. **#3 — Approval stats filter bulan** (paling cepat, hanya computed property changes)
2. **#1 — Role display name di alur approval** (audit + fix backend/frontend)
3. **#2 — Dashboard cards revision** (logic change + UI change)
