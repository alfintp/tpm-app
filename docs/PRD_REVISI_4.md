# PRD Revisi 4 — Penyesuaian Form & Import Mesin dengan Logic Schedule Baru

## Konteks

Sejak diperkenalkannya `ScheduleOccurrenceGenerator` (lihat `app/Services/ScheduleOccurrenceGenerator.php`), tanggal jatuh tempo maintenance (due date) setiap mesin **tidak lagi ditentukan oleh tanggal yang diinput user**, melainkan dihitung otomatis oleh sistem setiap bulan berdasarkan:

1. **`import_order`** — urutan baris mesin per kota (untuk round-robin pembagian hari kerja).
2. **`interval_days`** (diisi dari `maintenance_duration`) — frekuensi maintenance (mingguan/2 minggu/bulanan/dst).

Generator ini berjalan otomatis via cron harian (`schedules:generate-occurrences`, didaftarkan di `app/Console/Kernel.php`) dan langsung setelah import Excel (`generateUpcomingForCity`). Ia mendistribusikan mesin ke hari kerja (non-Minggu, non-libur nasional) secara merata dalam sebulan, lalu menyimpan hasilnya sebagai `ScheduleOccurrence` — bukan `MaintenanceSchedule.next_due_date` yang diinput manual.

## Problem

Field `maintenance_start_date` ("Tanggal Maintenance Pertama/Berikutnya" di form, "Tanggal Mulai Perawatan" di template Excel) masih ditampilkan dan diminta ke user, padahal:

- Nilai yang diinput user **langsung ditimpa** oleh generator begitu cron/generate berikutnya berjalan (untuk import massal, ini terjadi seketika setelah import).
- Untuk mesin yang dibuat satu-satu lewat form "Tambah Mesin", `import_order` tidak pernah diisi, sehingga mesin baru jatuh ke urutan "fallback" di generator (bukan urutan yang diharapkan), dan occurrence-nya baru muncul saat cron harian berikutnya jalan (bukan seketika seperti saat import Excel).
- User bisa salah paham bahwa tanggal yang mereka input adalah tanggal pasti maintenance akan berlangsung, padahal itu hanya nilai awal yang sementara.

## Solusi

### 1. Hapus field `maintenance_start_date` dari form Create/Edit Mesin

- `MachineCreateModal.vue`: hapus input "Tanggal Maintenance Pertama". Field yang tersisa untuk penjadwalan hanya "Frekuensi Maintenance" (`maintenance_duration`).
- `MachineEditModal.vue`: hapus input "Tanggal Maintenance Berikutnya" dengan alasan yang sama.

### 2. Hapus kolom "Tanggal Mulai Perawatan" dari template & parsing import Excel

- `Machines.vue` → `downloadMachineTemplate()`: hapus kolom ke-10 ("Tanggal Mulai Perawatan") dari header & contoh baris.
- `Machines.vue` → `importMachines()`: hapus mapping `row[9]` (`maintenance_start_date`).

### 3. Backend — hapus dependensi pada `maintenance_start_date`, pastikan occurrence ter-generate otomatis

- `MachineController::store()` / `bulkStore()` / `update()`: hapus validasi & penggunaan `maintenance_start_date`. Skedul (`MaintenanceSchedule`) tetap dibuat/diupdate/dihapus berdasarkan `maintenance_duration` saja, dengan `next_due_date` awal diisi otomatis (mis. `now()`) sebagai placeholder — nilai ini akan langsung disinkronkan ulang oleh generator.
- `MachineController::store()`: set `import_order` mesin baru = urutan terakhir + 1 untuk kota tersebut (sama seperti `bulkStore`), lalu panggil `ScheduleOccurrenceGenerator::generateUpcomingForCity()` seketika setelah membuat schedule — supaya occurrence langsung tersedia tanpa menunggu cron harian (konsisten dengan behavior import Excel).
- `MachineController::update()`: saat `maintenance_duration` berubah, panggil `ScheduleOccurrenceGenerator::regenerateForSchedule()` agar occurrence bulan ini/depan langsung dihitung ulang dengan interval baru.

### 4. Migration — hapus kolom `maintenance_start_date` dari tabel `machines`

Kolom ini sudah tidak dipakai untuk logic apa pun (hanya `maintenance_duration`/`interval_days` yang relevan). Migration baru untuk drop kolom demi kebersihan skema.

## Field yang **tetap dipertahankan** (masih diperlukan)

| Field | Alasan |
|---|---|
| `maintenance_duration` (Frekuensi Maintenance) | Sumber `interval_days` — input utama yang menentukan seberapa sering mesin dijadwalkan. |
| `kode`, `name`, `description`, `condition_pct`, `location`, `kota`, `status`, `pic_mesin_id` | Data dasar mesin, tidak terkait logic schedule, tetap dibutuhkan. |
| `import_order` | Menentukan urutan round-robin pembagian hari kerja per kota — kini akan diisi otomatis juga untuk create satuan (sebelumnya hanya diisi oleh `bulkStore`). |

## Field yang **dihapus**

| Field | Alasan |
|---|---|
| `maintenance_start_date` | Nilai yang diinput user tidak pernah dipakai secara permanen oleh `ScheduleOccurrenceGenerator` — selalu ditimpa oleh hasil kalkulasi round-robin (`import_order` + `interval_days`). Menyimpan/menampilkannya hanya membingungkan user. |

## Files Terkait

- `app/Http/Controllers/MachineController.php` — `store()`, `bulkStore()`, `update()`
- `app/Models/Machine.php` — fillable
- `app/Services/ScheduleOccurrenceGenerator.php` — dipanggil setelah create/update single machine
- `database/migrations/` — migration baru drop `maintenance_start_date`
- `resources/js/components/MachineCreateModal.vue`
- `resources/js/components/MachineEditModal.vue`
- `resources/js/views/Machines.vue` — `downloadMachineTemplate()`, `importMachines()`

## Testing & Verifikasi

- Build frontend berhasil (`npx vite build`).
- Form Tambah/Edit Mesin tidak lagi menampilkan field tanggal maintenance — hanya frekuensi.
- Template Excel yang didownload hanya berisi 9 kolom (tanpa "Tanggal Mulai Perawatan").
- Import Excel dengan format baru berhasil tanpa error, dan mesin baru langsung punya `ScheduleOccurrence` untuk bulan ini/depan.
- Membuat mesin satu-satu dengan frekuensi maintenance langsung menghasilkan occurrence (cek query `ScheduleOccurrence` atau tampilan kolom "Jadwal" di `Machines.vue`) tanpa menunggu cron.
