# PRD: Notes Modal, Dashboard Improvements, Maintenance Alert & Machine Detail Fixes

**Version:** 1.0  
**Date:** 3 Agustus 2026  
**Status:** Draft  

---

## 1. Latar Belakang

Setelah implementasi fitur unlock approval dan report lock, ditemukan beberapa issue yang perlu diperbaiki:
1. Proses approve/reject unlock masih menggunakan `prompt()` sederhana, perlu modal yang proper dengan konfirmasi
2. Catatan penolakan wajib, catatan persetujuan opsional — perlu validasi di backend
3. Catatan approval/rejection belum muncul di page Report (untuk approved) dan page Approval (untuk rejected)
4. Tag di Maintenance Alerts tidak center vertikal
5. Progress laporan bulan ini di page Machines tidak uptodate, berbeda dengan progress di page Report
6. Kotak informasi di Dashboard belum berfungsi sebagai filter, dan data Tepat Waktu/Terlambat mengambil dari laporan bukan dari mesin
7. Modal Ganti Komponen menampilkan data kosong di kolom komponen, teknisi, dan catatan
8. Count Ganti Komponen menghitung mesin, bukan jumlah komponen yang diganti
9. Belum ada kotak informasi untuk mesin yang terkunci
10. Modal Detail mesin perlu improve: kondisi komponen bisa show/hide, hapus bagian laporan terbaru, riwayat ganti komponen perlu info pelapor, riwayat laporan bisa show/hide per laporan

---

## 2. Requirements

### 2.1 Modal Catatan untuk Approve/Reject Unlock

**Prioritas:** High

**Deskripsi:**  
Ganti `prompt()` sederhana dengan modal proper yang berisi:
- Konfirmasi pilihan (Approve/Reject)
- Input catatan (textarea)
- Validasi: catatan wajib jika reject, opsional jika approve
- Tombol konfirmasi dan batal

**Acceptance Criteria:**
- [ ] Modal muncul saat klik tombol Approve atau Reject di `ApprovalUnlockPanel.vue`
- [ ] Modal menampilkan: judul ("Setujui Pengajuan" / "Tolak Pengajuan"), nama mesin, nama pemohon, alasan pengajuan
- [ ] Textarea untuk catatan dengan placeholder sesuai konteks
- [ ] Jika Reject: catatan wajib diisi (tombol konfirmasi disabled jika kosong)
- [ ] Jika Approve: catatan opsional
- [ ] Tombol "Konfirmasi" dan "Batal"
- [ ] Backend validasi: reject tanpa notes → return 422 error
- [ ] Backend: field `unlock_approval_notes` sudah ada (sudah diimplementasi)

**Files:**
- `resources/js/components/ApprovalUnlockPanel.vue` — ganti `handleAction` dengan modal
- `app/Http/Controllers/MachineController.php` — validasi notes wajib untuk reject

---

### 2.2 Catatan Approval Muncul di Report & Approval Page

**Prioritas:** High

**Deskripsi:**  
Catatan dari approver perlu muncul:
- **Page Report** (untuk approved): sudah ada banner, pastikan catatan muncul
- **Page Approval** tab "Pengajuan Buka Kunci" (untuk rejected): user bisa lihat alasan ditolak

**Acceptance Criteria:**
- [ ] Page Report: banner "Disetujui" menampilkan catatan approver (jika ada) — sudah ada, verifikasi
- [ ] Page Approval tab "Pengajuan Buka Kunci": untuk status rejected, tampilkan catatan approver
- [ ] `MyUnlockRequestsPanel.vue`: untuk rejected, tampilkan catatan dengan styling yang jelas (merah)

**Files:**
- `resources/js/components/MyUnlockRequestsPanel.vue` — tambah catatan untuk rejected
- `resources/js/views/Report.vue` — verifikasi catatan muncul di banner approved

---

### 2.3 Maintenance Alert Tag Tidak Center Vertikal

**Prioritas:** Low

**Deskripsi:**  
Tag/badge di Maintenance Alerts (yang menampilkan "Hari Ini", "3 hari lagi", dll) tidak center vertikal terhadap konten di sebelahnya.

**Acceptance Criteria:**
- [ ] Badge waktu di Maintenance Alerts center vertikal dengan title mesin
- [ ] Layout menggunakan `items-center` atau flex alignment yang proper

**Files:**
- `resources/js/components/MaintenanceAlerts.vue` — fix alignment badge

---

### 2.4 Progress Laporan Bulan Ini di Page Machines Tidak Uptodate

**Prioritas:** High

**Deskripsi:**  
Progress pengisian laporan di page Machines (`getMachineProgress`) menghitung berdasarkan `periodStart` (awal bulan jadwal) hingga hari ini, tetapi tidak memperhitungkan periode jadwal yang spesifik (Week 1 / Week 2). Berbeda dengan page Report yang menggunakan `scheduled_period_date` untuk mencocokkan record dengan periode yang tepat.

**Root Cause:**  
`getMachineProgress` di `Machines.vue` mengambil semua record dari awal bulan hingga hari ini, tanpa filter `scheduled_period_date`. Sehingga jika ada 2x sebulan (Week 1 & Week 2), progress Week 1 ikut dihitung di Week 2.

**Acceptance Criteria:**
- [ ] Progress di page Machines sama dengan progress di page Report untuk periode yang sama
- [ ] Gunakan `getCurrentPeriod` untuk mendapatkan periode aktif
- [ ] Filter record berdasarkan `scheduled_period_date` yang cocok dengan periode aktif
- [ ] Jika tidak ada periode aktif, tampilkan progress 0

**Files:**
- `resources/js/views/Machines.vue` — perbaiki `getMachineProgress`

---

### 2.5 Kotak Informasi Dashboard sebagai Filter & Perbaikan Data

**Prioritas:** High

**Deskripsi:**  
Saat ini kotak informasi di Dashboard (Total Mesin, Mesin Dicek Semua, Tepat Waktu, Terlambat, dll) hanya membuka modal. Perubahan yang dibutuhkan:

1. **Klik kotak = filter tabel mesin di bawah** (bukan modal lagi), kecuali kotak "Ganti Komponen" tetap modal
2. **Kotak Tepat Waktu & Terlambat**: saat ini mengambil data dari laporan (record-level), harusnya mengambil dari mesin yang sudah dicek — apakah pengecekan mesin tersebut tepat waktu atau terlambat terhadap jadwal
3. **Kotak Ganti Komponen**: tetap modal, tetapi perbaiki data yang muncul (komponen, teknisi, catatan tertampil kosong)
4. **Count Ganti Komponen**: hitung jumlah komponen yang diganti (length of replacement actions), bukan jumlah mesin
5. **Kotak baru: Mesin Terkunci** — menampilkan jumlah mesin yang sedang terkunci (di luar window maintenance dan belum dicek)

**Acceptance Criteria:**
- [ ] Klik kotak "Total Mesin", "Dicek Semua", "Dicek Sebagian", "Tidak Dicek", "Tepat Waktu", "Terlambat", "Diluar Jadwal" → filter tabel mesin di bawah
- [ ] Kotak yang aktif sebagai filter memiliki highlight/indicator
- [ ] Klik kotak yang sama lagi → reset filter
- [ ] Kotak "Ganti Komponen" tetap membuka modal
- [ ] Modal Ganti Komponen: kolom komponen, teknisi, dan catatan menampilkan data yang benar
- [ ] Count "Ganti Komponen" = jumlah total komponen yang diganti (bukan jumlah mesin)
- [ ] Kotak "Tepat Waktu" = mesin yang dicek tepat waktu (completed on or before due date, not late, not unscheduled)
- [ ] Kotak "Terlambat" = mesin yang dicek terlambat (is_late = true)
- [ ] Kotak baru "Mesin Terkunci" = mesin yang di luar window maintenance dan belum dicek sama sekali
- [ ] Filter kotak bekerja kombinasi dengan filter issue yang sudah ada

**Files:**
- `resources/js/components/DashboardReportPanel.vue` — ubah `openStat` menjadi filter, tambah kotak terkunci, perbaiki data
- `resources/js/components/ReportStatModal.vue` — perbaiki data replacement modal

---

### 2.6 Modal Detail Mesin — Improvements

**Prioritas:** Medium

**Deskripsi:**  
Modal detail mesin (`MachineSummaryModal.vue`) perlu beberapa perbaikan:

1. **Kondisi Komponen**: bisa show/hide isinya (collapsible)
2. **Hapus bagian "Laporan Terbaru"** — tidak diperlukan
3. **Riwayat Ganti Komponen**: tambahkan info siapa yang report (technician name)
4. **Riwayat Laporan**: bisa show/hide per laporan, saat show muncul detail laporan (isi apa saja rangkuman reportnya)

**Acceptance Criteria:**
- [ ] Bagian "Kondisi Komponen" punya toggle show/hide
- [ ] Bagian "Laporan Terbaru" dihapus dari modal
- [ ] Riwayat Ganti Komponen: setiap item menampilkan nama komponen, tanggal, dan nama technician yang report
- [ ] Riwayat Laporan: setiap laporan bisa di-expand/collapse
- [ ] Saat expand laporan: tampilkan detail (tanggal, teknisi, status approval, late/unscheduled, jumlah ganti, jumlah inspeksi, catatan, dan list komponen yang dicek dengan kondisi before/after)
- [ ] Default state: collapsed

**Files:**
- `resources/js/components/MachineSummaryModal.vue` — collapsible sections, hapus laporan terbaru, expand/collapse riwayat

---

## 3. Urutan Implementasi

1. **2.1** Modal catatan untuk approve/reject unlock (High)
2. **2.2** Catatan approval muncul di Report & Approval page (High)
3. **2.4** Progress laporan di page Machines uptodate (High)
4. **2.5** Kotak informasi dashboard sebagai filter & perbaikan data (High)
5. **2.6** Modal detail mesin improvements (Medium)
6. **2.3** Maintenance alert tag center vertikal (Low)

---

## 4. Tabel Perubahan

| Area | File | Perubahan |
|------|------|-----------|
| Unlock Approval | `ApprovalUnlockPanel.vue` | Modal proper untuk approve/reject dengan validasi |
| Unlock Approval | `MachineController.php` | Validasi notes wajib untuk reject |
| Unlock Info | `MyUnlockRequestsPanel.vue` | Tampilkan catatan untuk rejected |
| Maintenance Alert | `MaintenanceAlerts.vue` | Fix alignment badge |
| Machines Progress | `Machines.vue` | Perbaiki `getMachineProgress` gunakan `scheduled_period_date` |
| Dashboard | `DashboardReportPanel.vue` | Kotak sebagai filter, perbaiki data, tambah kotak terkunci |
| Dashboard Modal | `ReportStatModal.vue` | Perbaiki data replacement modal |
| Machine Detail | `MachineSummaryModal.vue` | Collapsible, hapus laporan terbaru, expand/collapse riwayat |
