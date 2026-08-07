# 📖 Panduan Database TPM-App

> Dokumen ini menjelaskan struktur database, relasi antar tabel, dan alur kerja sistem secara lengkap. Dibuat untuk yang baru mengenal backend.

---

## 🗺️ Diagram Relasi (Text-Based)

```
┌─────────────┐       ┌──────────────────┐       ┌─────────────────────────┐
│   users     │       │     roles        │       │   activity_logs         │
│─────────────│       │──────────────────│       │─────────────────────────│
│ id (UUID)   │◄──┐   │ id (UUID)        │       │ id (UUID)               │
│ full_name   │   │   │ name             │       │ user_id ──────────────► │ users
│ email       │   │   │ display_name     │       │ user_fullname           │
│ password    │   │   │ can_approve      │       │ activity                │
│ role ───────┼───┼──►│ can_report       │       │ details                 │
│ city        │   │   │ is_manager       │       │ ip_address              │
└──────┬──────┘   │   │ can_approve_unlock│      └─────────────────────────┘
       │          │   │ required_diff... │
       │          │   │ is_active        │
       │          │   └──────────────────┘
       │          │
       │ pic_mesin_id
       ▼          │
┌──────────────────────────────────────────┐
│                 machines                 │
│──────────────────────────────────────────│
│ id (UUID)                                │
│ kode            ← kode unik mesin         │
│ name            ← nama mesin              │
│ description                               │
│ condition_pct   ← rata-rata kondisi (%)   │
│ location        ← lokasi area             │
│ kota            ← pasuruan / sby          │
│ import_order    ← urutan round-robin (*)  │
│ status          ← active/inactive/maint.. │
│ is_locked       ← kunci manual             │
│ maintenance_duration ← frekuensi (hari)   │
│ unlock_status   ← pending/approved/reject │
│ unlock_requested_by_id ──► users          │
│ unlock_approved_by_id ──► users           │
│ pic_mesin_id ─────────────► users         │
└──────────┬───────────┬───────────┬────────┘
           │           │           │
     hasMany      hasMany     hasMany
           │           │           │
           ▼           ▼           ▼
┌─────────────────┐  ┌──────────────────┐  ┌─────────────────────┐
│machine_components│  │maintenance_      │  │schedule_occurrences │
│─────────────────│  │schedules         │  │─────────────────────│
│ id (UUID)       │  │──────────────────│  │ id (UUID)           │
│ machine_id ────►│  │ id (UUID)        │  │ schedule_id ───────►│ schedules
│ category        │  │ machine_id ─────►│  │ machine_id ────────►│ machines
│ name            │  │ interval_days    │  │ period_year         │
│ specification   │  │ schedule_type    │  │ period_month        │
│ qty             │  │ next_due_date    │  │ due_date            │
│ unit            │  │ is_active        │  │ original_date       │
│ difficulty      │  └────────┬─────────│  │ is_shifted          │
│last_condition_% │           │           │  └─────────────────────┘
│last_replaced_at │     hasMany          │
└────────┬────────│    occurrences      │
         │          │                    │
    hasMany         │                    │
         ▼          │                    │
┌──────────────────────┐                 │
│component_indicators  │                 │
│──────────────────────│                 │
│ id (UUID)            │                 │
│ machine_component_id │                 │
│ name                 │                 │
│ description          │                 │
│ sort_order           │                 │
└──────────────────────┘                 │
                                         │
┌──────────────────────────────────────────────────────┐
│               maintenance_records                    │
│──────────────────────────────────────────────────────│
│ id (UUID)                                            │
│ machine_id ──────────────────────────────────────► │ machines
│ technician_id ────────────────────────────────────► │ users
│ schedule_id ──────────────────────────────────────► │ schedules
│ scheduled_period_date                                │
│ is_unscheduled                                       │
│ is_late                                              │
│ maintenance_date                                     │
│ start_time / end_time                                │
│ duration_minutes                                     │
│ condition_before_pct / condition_after_pct           │
│ notes                                                │
│ status ← draft/completed/pending_approval/approved   │
│ approval_flow_snapshot (JSON)                        │
└──────────┬───────────────────────────┬───────────────┘
           │                           │
     hasMany                      hasMany
           ▼                           ▼
┌──────────────────────┐    ┌─────────────────────┐
│maintenance_actions   │    │    approvals        │
│──────────────────────│    │─────────────────────│
│ id (UUID)            │    │ id (UUID)           │
│ record_id ──────────►│    │ record_id ─────────►│ records
│ machine_component_id │    │ approver_id ───────►│ users
│ action_type          │    │ step_order          │
│condition_before_%    │    │ decision ← approve/ │
│condition_after_%     │    │   reject/pending    │
│ description          │    │ notes               │
└──────────┬───────────│    │ decided_at          │
           │            │    └─────────────────────┘
    hasMany            │
           ▼            │
┌──────────────────────────────┐
│maintenance_action_indicators │
│──────────────────────────────│
│ id (UUID)                    │
│ maintenance_action_id ─────► │ actions
│ component_indicator_id ────► │ indicators
│ value (boolean)              │
└──────────────────────────────┘

┌──────────────────────────┐    ┌──────────────────────────┐
│ approval_flow_steps      │    │maintenance_window_       │
│──────────────────────────│    │settings                  │
│ id (UUID)                │    │──────────────────────────│
│ reporter_role            │    │ id (UUID)                │
│ role                     │    │ days_before              │
│ step_order               │    │ days_after               │
│ is_active                │    │ alert_days_before        │
└──────────────────────────┘    └──────────────────────────┘
```

---

## 📊 Tabel-Tabel Database (Penjelasan Per Tabel)

### 1. `users` — Pengguna Sistem
| Kolom | Kegunaan |
|---|---|
| `id` | UUID unik |
| `full_name` | Nama lengkap |
| `email` | Email login |
| `password` | Password (ter-hash) |
| `role` | Nama role, relasi ke tabel `roles` |
| `city` | Kota penempatan (pasuruan/sby) |

### 2. `roles` — Daftar Role & Hak Akses
| Kolom | Kegunaan |
|---|---|
| `name` | Nama role (admin, supervisor, teknisi, dll) |
| `can_approve` | Boleh approve maintenance record? |
| `can_report` | Boleh buat laporan maintenance? |
| `is_manager` | Apakah level manajer? |
| `can_approve_unlock` | Boleh approve unlock mesin? |
| `required_difficulties` | Tingkat kesulitan yang wajib dicek (JSON array) |

### 3. `machines` — Data Mesin
| Kolom | Kegunaan |
|---|---|
| `kode` | Kode unik mesin (mis. LL-BLR-01) |
| `name` | Nama mesin |
| `condition_pct` | Kondisi rata-rata (%) — otomatis dihitung dari komponen |
| `kota` | Pasuruan atau Surabaya |
| `import_order` | Urutan mesin per kota untuk pembagian jadwal round-robin |
| `status` | active / inactive / maintenance |
| `is_locked` | Kunci manual — jika true, mesin tidak bisa dilaporkan |
| `maintenance_duration` | Frekuensi maintenance dalam hari (7/14/28/56/84) |
| `unlock_status` | Status unlock: pending / approved / rejected |
| `pic_mesin_id` | User yang ditunjuk sebagai PIC mesin |

### 4. `machine_components` — Komponen Mesin
| Kolom | Kegunaan |
|---|---|
| `machine_id` | Relasi ke mesin |
| `category` | Kategori komponen |
| `name` | Nama komponen |
| `specification` | Spesifikasi |
| `qty` | Jumlah |
| `unit` | Satuan (Pcs, Unit, dll) |
| `difficulty` | ringan / sedang / berat |
| `last_condition_pct` | Kondisi terakhir (%) — diupdate saat maintenance |
| `last_replaced_at` | Tanggal terakhir diganti |

### 5. `component_indicators` — Indikator Pengecekan Komponen
| Kolom | Kegunaan |
|---|---|
| `machine_component_id` | Relasi ke komponen |
| `name` | Nama indikator (mis. "Visual", "Kelistrikan") |
| `description` | Penjelasan indikator |
| `sort_order` | Urutan tampil |

### 6. `maintenance_schedules` — Jadwal Maintenance
| Kolom | Kegunaan |
|---|---|
| `machine_id` | Relasi ke mesin |
| `interval_days` | Interval maintenance dalam hari (dari `maintenance_duration`) |
| `schedule_type` | Tipe jadwal (preventive) |
| `next_due_date` | Tanggal due date berikutnya (placeholder, dihitung ulang oleh generator) |
| `is_active` | Jadwal aktif atau tidak |

### 7. `schedule_occurrences` — Hasil Generate Jadwal
| Kolom | Kegunaan |
|---|---|
| `schedule_id` | Relasi ke jadwal |
| `machine_id` | Relasi ke mesin |
| `period_year` | Tahun periode |
| `period_month` | Bulan periode (1-12) |
| `due_date` | Tanggal jatuh tempo aktual (sudah di-shift dari hari Minggu/libur) |
| `original_date` | Tanggal asli sebelum shift |
| `is_shifted` | Apakah tanggal digeser dari aslinya? |

### 8. `maintenance_records` — Laporan Hasil Maintenance
| Kolom | Kegunaan |
|---|---|
| `machine_id` | Mesin yang di-maintenance |
| `technician_id` | User yang melakukan maintenance |
| `schedule_id` | Jadwal yang terkait (boleh null untuk unscheduled) |
| `scheduled_period_date` | Periode jadwal yang dituju |
| `is_unscheduled` | Maintenance di luar jadwal? |
| `is_late` | Terlambat? |
| `maintenance_date` | Tanggal maintenance dilakukan |
| `start_time` / `end_time` | Jam mulai & selesai |
| `duration_minutes` | Durasi (menit) |
| `condition_before_pct` / `condition_after_pct` | Kondisi sebelum & sesudah |
| `status` | draft / completed / pending_approval / approved |
| `approval_flow_snapshot` | Snapshot alur approval saat record dibuat (JSON) |

### 9. `maintenance_actions` — Aksi Per Komponen Dalam Maintenance
| Kolom | Kegunaan |
|---|---|
| `record_id` | Relasi ke maintenance record |
| `machine_component_id` | Komponen yang diperiksa |
| `action_type` | Tipe aksi (check, replace, repair) |
| `condition_before_pct` / `condition_after_pct` | Kondisi komponen sebelum & sesudah |
| `description` | Catatan aksi |

### 10. `maintenance_action_indicators` — Hasil Pengecekan Indikator
| Kolom | Kegunaan |
|---|---|
| `maintenance_action_id` | Relasi ke aksi |
| `component_indicator_id` | Relasi ke indikator komponen |
| `value` | Hasil cek: true (OK) / false (tidak OK) |

### 11. `approvals` — Persetujuan Maintenance Record
| Kolom | Kegunaan |
|---|---|
| `record_id` | Relasi ke maintenance record |
| `approver_id` | User yang approve/reject |
| `step_order` | Urutan step approval (1, 2, 3...) |
| `decision` | approve / reject / pending |
| `notes` | Catatan approver |
| `decided_at` | Waktu keputusan |

### 12. `approval_flow_steps` — Konfigurasi Alur Approval
| Kolom | Kegunaan |
|---|---|
| `reporter_role` | Role yang membuat laporan |
| `role` | Role yang harus approve |
| `step_order` | Urutan approval |
| `is_active` | Langkah ini aktif? |

### 13. `maintenance_window_settings` — Jendela Waktu Maintenance
| Kolom | Kegunaan |
|---|---|
| `days_before` | Berapa hari sebelum due date mesin dibuka (H-x) |
| `days_after` | Berapa hari setelah due date mesin ditutup (H+y) |
| `alert_days_before` | Berapa hari sebelum due date muncul alert |

### 14. `activity_logs` — Log Aktivitas Sistem
| Kolom | Kegunaan |
|---|---|
| `user_id` | User yang melakukan aktivitas |
| `user_fullname` | Nama user (disimpan untuk history) |
| `activity` | Jenis aktivitas (mis. "Tambah Mesin", "Import Komponen") |
| `details` | Detail aktivitas |
| `ip_address` | IP address user |

---

## 🔄 Alur Kerja Sistem (End-to-End)

### Alur 1: Pembuatan Mesin → Generate Jadwal

```
[Admin buat/import mesin]
        │
        ▼
┌─────────────────────────────────┐
│ 1. Simpan data mesin            │
│    - kode, name, kota, dll      │
│    - import_order auto-set      │
│    - maintenance_duration diisi │
└──────────────┬──────────────────┘
               │
               ▼
┌─────────────────────────────────┐
│ 2. Buat maintenance_schedule    │
│    - interval_days = duration   │
│    - next_due_date = now()      │
│    - is_active = true           │
└──────────────┬──────────────────┘
               │
               ▼
┌─────────────────────────────────┐
│ 3. ScheduleOccurrenceGenerator  │
│    generateUpcomingForCity()    │
│                                 │
│    Untuk setiap mesin di kota:  │
│    a. Ambil semua mesin aktif   │
│    b. Bagi ke hari kerja        │
│       (skip Minggu & libur)     │
│    c. Urutkan by import_order   │
│    d. Buat schedule_occurrence  │
│       per bulan                 │
└─────────────────────────────────┘
```

### Alur 2: Maintenance Report → Approval

```
[Mesin terbuka untuk maintenance]
        │
        ▼
┌──────────────────────────────────────┐
│ 1. Teknisi buat maintenance_record   │
│    - Pilih mesin yang terbuka        │
│    - Isi tanggal, jam mulai/selesai  │
│    - Status: "draft"                 │
└──────────────┬───────────────────────┘
               │
               ▼
┌──────────────────────────────────────┐
│ 2. Isi maintenance_actions           │
│    - Pilih komponen yang diperiksa   │
│    - Isi kondisi sebelum & sesudah   │
│    - Pilih action_type (check/dll)   │
└──────────────┬───────────────────────┘
               │
               ▼
┌──────────────────────────────────────┐
│ 3. Isi maintenance_action_indicators │
│    - Untuk setiap indikator komponen │
│    - Centang OK / tidak OK           │
└──────────────┬───────────────────────┘
               │
               ▼
┌──────────────────────────────────────┐
│ 4. Submit record                     │
│    - Status → "pending_approval"     │
│    - Buat approval rows berdasarkan  │
│      approval_flow_steps             │
│    - Update condition_pct komponen   │
│    - Update condition_pct mesin      │
│      (auto rata-rata dari komponen)  │
└──────────────┬───────────────────────┘
               │
               ▼
┌──────────────────────────────────────┐
│ 5. Approver review                   │
│    - Approver lihat record           │
│    - Approve / Reject per step       │
│    - Jika semua step approve:        │
│      Status → "approved"             │
│    - Jika ada yang reject:           │
│      Status tetap, perlu revisi      │
└──────────────────────────────────────┘
```

### Alur 3: Unlock Mesin (Khusus Mesin Terkunci)

```
[Mesin terkunci / sudah lewat due date]
        │
        ▼
┌──────────────────────────────────────┐
│ 1. User request unlock               │
│    - unlock_status → "pending"       │
│    - unlock_requested_by_id = user   │
│    - unlock_reason diisi             │
│    - unlock_requested_period diisi   │
└──────────────┬───────────────────────┘
               │
               ▼
┌──────────────────────────────────────┐
│ 2. Admin/Manager approve             │
│    - unlock_status → "approved"      │
│    - unlock_approved_by_id = admin   │
│    - unlock_approved_at = now()      │
│    - Mesin terbuka untuk bulan itu   │
└──────────────────────────────────────┘
```

---

## 🔑 Konsep Penting

### `import_order` — Pembagian Jadwal Round-Robin
Setiap mesin punya nomor urut per kota. Generator memakai nomor ini untuk membagi mesin ke hari kerja secara merata. Misal: 30 mesin di Pasuruan → dibagi ke ~22 hari kerja per bulan → beberapa mesin dijadwalkan di hari yang sama, tapi tidak menumpuk semua di satu hari.

### `ScheduleOccurrenceGenerator` — Otomatisasi Jadwal
- **Cron harian** (`schedules:generate-occurrences`) — generate occurrence untuk bulan depan
- **Saat create/import mesin** — langsung generate untuk kota tersebut
- **Saat update frekuensi** — regenerate occurrence untuk mesin tersebut
- **Aturan shift**: jika due_date jatuh di hari Minggu atau libur nasional, digeser ke hari kerja berikutnya

### `condition_pct` — Kondisi Otomatis
- `machine_components.last_condition_pct` diupdate saat maintenance record disubmit
- `machines.condition_pct` dihitung otomatis sebagai **rata-rata** dari semua komponen
- Tidak perlu input manual — sistem yang hitung

### `maintenance_window_settings` — Jendela Buka/Tutup
- **H-x**: mesin dibuka untuk dilaporkan x hari sebelum due date
- **H+y**: mesin ditutup y hari setelah due date
- Default: H-2 (buka 2 hari sebelum) sampai H+0 (tutup tepat di due date)
- Di luar jendela ini, mesin "terkunci" kecuali ada unlock approval

### `approval_flow_steps` — Alur Approval Berjenjang
- Setiap role reporter punya alur approval berbeda
- Misal: teknisi → supervisor → manager
- Snapshot alur disimpan di `maintenance_records.approval_flow_snapshot` agar tidak berubah jika konfigurasi diubah di kemudian hari

---

## 📁 Struktur File Backend Penting

| File | Kegunaan |
|---|---|
| `app/Models/` | Definisi tabel & relasi (14 model) |
| `app/Http/Controllers/` | Logic API endpoint |
| `app/Services/ScheduleOccurrenceGenerator.php` | Generator jadwal otomatis |
| `database/migrations/` | Skema database (DDL) |
| `routes/api.php` | Daftar API endpoint |
| `app/Console/Kernel.php` | Cron job (generate jadwal harian) |

---

## 🚀 Saat Deploy / Migration

| Aksi | Efek |
|---|---|
| `php artisan migrate` | Jalankan migration baru (mis. drop kolom) — **tidak hapus data** |
| `php artisan migrate:rollback` | Batalkan migration terakhir |
| `php artisan migrate:fresh` | **HATI-HATI**: hapus SEMUA tabel & data, lalu re-create |
| `php artisan config:cache` | Cache config (wajib setelah deploy di production) |
| `php artisan config:clear` | Hapus cache config |

> **Penting:** Jangan pernah jalankan `migrate:fresh` di production. Itu akan menghapus semua data.
