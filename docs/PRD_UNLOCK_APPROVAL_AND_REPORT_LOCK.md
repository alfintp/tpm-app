# PRD: Unlock Approval & Report Lock Improvements

**Version:** 1.0  
**Date:** 3 Agustus 2026  
**Status:** Draft  

---

## 1. Latar Belakang

Saat ini sistem memiliki fitur pengajuan buka kunci (unlock request) untuk mesin yang terkunci, namun ada beberapa kekurangan:
- Informasi pengajuan unlock tidak tampil di page Report
- Aturan unlock setelah disetujui belum jelas (durasi, kategori terlambat, prioritas alert)
- Pesan lock di page Report tidak akurat untuk mesin yang belum masuk periode maintenance
- Tab approval kunci mesin di page Approval hanya menampilkan data untuk role admin, bukan untuk role lain yang diberi izin `can_approve_unlock`
- User pengaju unlock tidak memiliki tab khusus untuk melihat status pengajuannya

---

## 2. Requirements

### 2.1 Informasi Pengajuan Unlock di Page Report

**Prioritas:** High

**Deskripsi:**  
Saat user membuka page Report untuk mesin yang sedang dalam pengajuan unlock (status pending), tampilkan banner/keterangan berisi:
- Tanggal pengajuan
- Nama user yang mengajukan
- Keterangan/alasan pengajuan

**Acceptance Criteria:**
- [ ] Jika mesin memiliki unlock request dengan status `pending`, tampilkan banner info di atas form laporan
- [ ] Banner menampilkan: tanggal pengajuan, nama pengaju, keterangan
- [ ] Banner hilang otomatis jika request di-approve atau di-reject
- [ ] Style banner: warna amber/orange (sesuai status pending)

---

### 2.2 Aturan Unlock Disetujui - Akses Terbuka Sebulan & Prioritas

**Prioritas:** High

**Deskripsi:**  
Setelah unlock request di-approve:
1. Mesin tetap terbuka (unlocked) selama bulan tersebut
2. Mesin masuk kategori **Terlambat** karena lewat jadwal
3. Mesin menjadi **prioritas** di Maintenance Alerts
4. User mendapat notifikasi bahwa unlock sudah di-approve
5. Di page Report tampilkan keterangan bahwa unlock sudah di-acc beserta catatan/approver

**Acceptance Criteria:**
- [ ] Setelah approve, `is_locked` mesin = `false` hingga akhir bulan (auto re-lock di bulan berikutnya)
- [ ] Mesin yang di-unlock masuk kategori "Terlambat" di kalender dan alerts
- [ ] Maintenance Alerts: mesin yang baru di-unlock muncul di urutan teratas / prioritas
- [ ] Notifikasi terkirim ke user pengaju bahwa unlock disetujui
- [ ] Di page Report, tampilkan banner "Pengajuan buka kunci disetujui" dengan: tanggal approve, nama approver, catatan (jika ada)
- [ ] Banner approve berwarna hijau
- [ ] Auto re-lock: pada awal bulan baru, mesin kembali terkunci jika jadwal maintenance berikutnya belum tiba

**Catatan Teknis:**
- Perlu mekanisme cron/scheduled job untuk auto re-lock pada awal bulan
- Atau cek saat load machine: jika bulan unlock != bulan saat ini, kunci ulang

---

### 2.3 Perbaikan Pesan Lock di Page Report

**Prioritas:** High

**Deskripsi:**  
Saat ini mesin yang belum masuk periode maintenance menampilkan pesan "terkunci" yang tidak tepat. Pesan harus disesuaikan:

| Kondisi | Pesan Saat Ini | Pesan Yang Diinginkan |
|---------|---------------|----------------------|
| Belum masuk periode maintenance | "Terkunci" / "Form laporan dikunci" | "Belum Jadwal Maintenance" - jadwal: [tanggal] |
| Sudah lewat periode (overdue) | "Terkunci" | "Terlambat" - lewat [X] hari dari jadwal |
| Dalam periode maintenance | (normal, tidak terkunci) | (tidak berubah) |
| Pengajuan unlock pending | "Terkunci" | "Menunggu Persetujuan Buka Kunci" + info pengajuan |

**Acceptance Criteria:**
- [ ] Mesin yang belum masuk periode: tampilkan pesan "Belum waktunya pengecekan" (sudah ada, pastikan jalan)
- [ ] Mesin overdue: tampilkan pesan "Terlambat" dengan jumlah hari terlambat
- [ ] Tombol "Tetap Maintenance di Luar Jadwal" tetap tersedia untuk kondisi belum jadwal

---

### 2.4 Tab Khusus Pengajuan Unlock di Page Approval

**Prioritas:** Medium

**Deskripsi:**  
User yang mengajukan unlock request perlu bisa melihat status pengajuannya. Tambahkan tab di page Approval khusus untuk menampilkan list pengajuan unlock dari user tersebut.

**Acceptance Criteria:**
- [ ] Tab baru "Pengajuan Buka Kunci" di page Approval
- [ ] Tab ini menampilkan list unlock request yang diajukan oleh user yang sedang login
- [ ] Kolom: tanggal pengajuan, nama mesin, keterangan, status (pending/approved/rejected), tanggal approve, nama approver
- [ ] Filter status (semua/pending/approved/rejected)
- [ ] Tab ini terlihat oleh semua user (karena ini adalah pengajuan milik user sendiri)
- [ ] Tab "Approval Kunci Mesin" (untuk approver) dan tab "Pengajuan Buka Kunci" (untuk pengaju) adalah tab terpisah

---

### 2.5 Fix: Data Approval Kunci Mesin Tidak Muncul untuk Non-Admin

**Prioritas:** High

**Deskripsi:**  
Saat ini role selain admin yang diberi izin `can_approve_unlock` bisa melihat tab "Approval Kunci Mesin" tetapi data pengajuan tidak muncul. Hanya admin yang bisa melihat data.

**Root Cause (diduga):**  
Backend endpoint untuk unlock requests kemungkinan masih memfilter berdasarkan role admin/manager, bukan berdasarkan permission `can_approve_unlock`.

**Acceptance Criteria:**
- [ ] User dengan role yang memiliki `can_approve_unlock = true` dapat melihat list unlock requests di tab "Approval Kunci Mesin"
- [ ] Data yang muncul adalah semua unlock request dengan status pending (tidak difilter per user)
- [ ] User bisa approve/reject unlock request (sesuai permission)
- [ ] Admin tetap bisa melihat dan approve semua unlock requests
- [ ] User tanpa permission `can_approve_unlock` tidak melihat tab "Approval Kunci Mesin"

**Investigasi yang Diperlukan:**
- Cek endpoint API yang mengambil unlock requests (kemungkinan di `MachineController@unlockHistory`)
- Pastikan query tidak memfilter berdasarkan role admin/manager
- Pastikan response includes semua pending unlock requests

---

## 3. Alur Unlock Approval

```
User buka Report → Mesin terkunci (belum jadwal/terlambat)
  ↓
User klik "Ajukan Buka Kunci" → isi keterangan
  ↓
Unlock request dibuat (status: pending)
  ↓
Approver (admin / role dengan can_approve_unlock) lihat di tab "Approval Kunci Mesin"
  ↓
Approver approve/reject + catatan
  ↓
Jika approved:
  - Mesin unlocked selama bulan tersebut
  - Masuk kategori "Terlambat"
  - Muncul sebagai prioritas di Maintenance Alerts
  - Notifikasi ke user pengaju
  - User lihat banner "Disetujui" di page Report
  ↓
Awal bulan berikutnya: auto re-lock
```

---

## 4. Tabel Permission

| Permission | Admin | Manager | Custom Role |
|-----------|-------|---------|-------------|
| `can_approve` | ✅ | ✅ | Configurable |
| `can_approve_unlock` | ✅ | ✅ (default) | Configurable |
| Lihat tab "Pengajuan Buka Kunci" | ✅ | ✅ | ✅ (all users) |
| Lihat tab "Approval Kunci Mesin" | ✅ | ✅ | Hanya jika `can_approve_unlock = true` |

---

## 5. Files yang Perlu Dimodifikasi

### Backend
- `app/Http/Controllers/MachineController.php` - fix unlockHistory query, add unlock request info to machine data
- `app/Http/Controllers/ApprovalController.php` (jika ada) - endpoint untuk user's own unlock requests
- `app/Models/Machine.php` - relationship/method untuk active unlock request
- `app/Models/UnlockRequest.php` (jika ada) - model untuk unlock requests
- Scheduled job untuk auto re-lock

### Frontend
- `resources/js/views/Report.vue` - banner info unlock pending/approved, fix lock messages
- `resources/js/views/Approval.vue` - tab "Pengajuan Buka Kunci", fix data loading untuk non-admin
- `resources/js/components/ReportComponentTable.vue` - pass unlock status info
- `resources/js/components/MaintenanceAlerts.vue` - prioritas untuk mesin yang di-unlock

---

## 6. Urutan Implementasi

1. **2.5** Fix data approval kunci mesin untuk non-admin (bug fix, paling urgent)
2. **2.3** Perbaikan pesan lock di page Report
3. **2.1** Informasi pengajuan unlock di page Report
4. **2.2** Aturan unlock disetujui (auto re-lock, prioritas alert, notifikasi)
5. **2.4** Tab pengajuan unlock di page Approval
6. **2.6** Fix: Informasi unlock disetujui tidak muncul di Report & Maintenance Alert

---

### 2.6 Fix: Informasi Unlock Disetujui Tidak Muncul di Report & Maintenance Alert

**Prioritas:** High

**Deskripsi:**  
Setelah unlock request di-approve, informasi bahwa mesin tersebut sudah dibuka kuncinya tidak muncul di page Report dan di Maintenance Alerts. User tidak tahu bahwa mesin sudah bisa diinput laporannya.

**Root Cause:**  
1. Backend `isOpenForMaintenance()` dan frontend lock logic hanya mengecek `unlock_approved_at` (field baru dari migration). Untuk approval lama yang belum punya field ini, unlock tidak terdeteksi.
2. Banner di Report.vue hanya muncul jika `unlock_approved_at` ada, tidak ada fallback ke `unlock_expires_at`.
3. `isUnlockPriority` di Maintenance Alerts dan backend notifications juga hanya cek `unlock_approved_at`.

**Acceptance Criteria:**
- [ ] Banner "Pengajuan Buka Kunci Disetujui" muncul di page Report untuk mesin dengan `unlock_status = 'approved'`
- [ ] Banner muncul meskipun `unlock_approved_at` belum terisi (fallback ke `unlock_expires_at`)
- [ ] Maintenance Alerts menampilkan mesin yang di-unlock dengan badge "UNLOCKED" dan styling emerald
- [ ] Mesin yang di-unlock muncul di Maintenance Alerts meskipun overdue (melewati jadwal)
- [ ] User bisa input report di page Report untuk mesin yang sudah di-approve unlock-nya
- [ ] Tombol "Ajukan Buka Kunci" tidak muncul ketika "Belum waktunya pengecekan" (diffDays > 0)
- [ ] Tombol "Ajukan Buka Kunci" hanya muncul ketika overdue (diffDays < 0) dan status belum pending/approved

**Catatan Teknis:**
- Fallback: jika `unlock_approved_at` null, cek `unlock_expires_at` > now()
- Backend `isOpenForMaintenance()`, frontend `schedulePeriods` lock logic, `isUnlockPriority` di Machines.vue dan backend notifications harus konsisten
- Tombol unlock hanya untuk kondisi overdue, bukan "belum waktunya"
