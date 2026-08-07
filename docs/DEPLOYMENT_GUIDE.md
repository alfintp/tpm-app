# Panduan Deployment ke cPanel (Incremental Update)

> **Konteks:** Production sudah berjalan. Ini panduan untuk upload **perubahan/update fitur baru** saja, bukan deploy dari nol.

---

## 1. File yang Perlu Di-upload

### A. File Backend yang Berubah (Modified)

| File | Perubahan |
|------|-----------|
| `app/Http/Controllers/MachineController.php` | Lite mode include schedules + records |
| `app/Http/Controllers/MaintenanceRecordController.php` | Perubahan submit report |
| `app/Http/Controllers/MaintenanceScheduleController.php` | Perubahan schedule |
| `app/Http/Controllers/ApprovalController.php` | Perubahan approval |
| `app/Http/Controllers/MachineComponentController.php` | Perubahan component |
| `app/Http/Controllers/UserController.php` | Perubahan user |
| `app/Models/MaintenanceAction.php` | Tambah field stock |
| `routes/api.php` | Tambah route stock + notification |
| `routes/web.php` | Tambah route Inertia /stock, /notifications |
| `public/.htaccess` | Konfigurasi Apache |
| `resources/views/app.blade.php` | Perubahan blade template |
| `resources/views/components/ui/input/Input.vue` | Perubahan UI input |
| `resources/css/app.css` | Perubahan CSS |
| `vite.config.js` | Perubahan config Vite |
| `package.json` / `package-lock.json` | Update dependency |

### B. File Backend Baru (New)

| File | Keterangan |
|------|-----------|
| `app/Http/Controllers/StockController.php` | Controller stok |
| `app/Http/Controllers/NotificationController.php` | Controller notifikasi |
| `app/Models/Stock.php` | Model stok |
| `app/Models/StockUsage.php` | Model pemakaian stok |
| `app/Models/AppNotification.php` | Model notifikasi |
| `app/Models/NotificationRead.php` | Model read tracking |

### C. File Frontend yang Berubah (Modified)

| File | Perubahan |
|------|-----------|
| `resources/js/App.vue` | Tambah bell icon + sidebar menu notifikasi |
| `resources/js/views/Report.vue` | Stock selector + toggle OK/NOT OK + validasi |
| `resources/js/views/Dashboard.vue` | Perubahan dashboard |
| `resources/js/views/Login.vue` | Perubahan login |
| `resources/js/views/Machines.vue` | Perubahan machines |
| `resources/js/views/MachineDetail.vue` | Perubahan machine detail |
| `resources/js/views/Components.vue` | Perubahan components |
| `resources/js/views/Approval.vue` | Perubahan approval |
| `resources/js/views/Users.vue` | Perubahan users |
| `resources/js/components/ReportComponentTable.vue` | Stock selector UI + qty stepper |
| `resources/js/components/ReportMachineSelector.vue` | Sort by urgency + schedule status |
| `resources/js/components/ReportSchedulePanel.vue` | Perubahan schedule panel |
| `resources/js/components/MachineReportTab.vue` | Perubahan report tab |
| `resources/js/components/MaintenanceAlerts.vue` | Perubahan alerts |
| `resources/js/components/DashboardAlertPanel.vue` | Perubahan dashboard alerts |
| `resources/js/components/DashboardReportPanel.vue` | Perubahan dashboard report |
| `resources/js/components/ApprovalFlowPanel.vue` | Perubahan approval flow |
| `resources/js/components/MachineCalendarModal.vue` | Perubahan calendar modal |
| `resources/js/components/MachineDetailConditionCard.vue` | Perubahan condition card |
| `resources/js/components/MachineSummaryModal.vue` | Perubahan summary modal |

### D. File Frontend Baru (New)

| File | Keterangan |
|------|-----------|
| `resources/js/views/Stock.vue` | Halaman manajemen stok |
| `resources/js/views/Notifications.vue` | Halaman admin notifikasi |
| `resources/js/components/NotificationBell.vue` | Komponen bell icon |
| `resources/js/components/StockForm.vue` | Form CRUD stok |
| `resources/js/components/StockImport.vue` | Import stok Excel/CSV |
| `resources/js/components/RestockModal.vue` | Modal restock |
| `resources/js/components/StockUsageHistory.vue` | History pemakaian stok |
| `resources/js/components/BulkSetLimit.vue` | Bulk set limit qty |

### E. Migration Baru (New)

| File | Keterangan |
|------|-----------|
| `2026_08_05_021134_add_indexes_to_frequently_queried_tables.php` | Tambah index untuk performance |
| `2026_08_07_090000_create_stocks_table.php` | Buat tabel stocks |
| `2026_08_07_090001_create_stock_usages_table.php` | Buat tabel stock_usages |
| `2026_08_07_090002_add_stock_fields_to_maintenance_actions_table.php` | Tambah field stock ke maintenance_actions |
| `2026_08_07_100000_create_notifications_table.php` | Buat tabel notifications + notification_reads |

### F. Asset Baru

| File | Keterangan |
|------|-----------|
| `public/build/` | **Wajib** — hasil build frontend terbaru |
| `public/images/logo-ladang-lima-64.png` | Logo baru |
| `public/images/cookie-32.png` | Icon cookie |
| `public/images/cookie-opt.png` | Icon cookie opt |

---

## 2. File yang TIDAK Perlu Di-upload

| Folder/File | Alasan |
|-------------|--------|
| `node_modules/` | Tidak dibutuhkan di production |
| `.git/` | Tidak perlu |
| `storage/logs/*` | Jangan timpa log production |
| `storage/framework/*` | Jangan timpa cache/sessions production |
| `vendor/` | Tidak berubah, skip kecuali ada dependency baru |
| `docs/` | Dokumentasi internal |
| `e2e/` | Folder testing |
| `playwright.config.ts` | File testing |
| `vite.config.js.timestamp-*.mjs` | File sementara, hapus |

---

## 3. Langkah Deployment

### Langkah 1: Build Frontend (di local)
```bash
npm run build
```

### Langkah 2: Zip File yang Berubah
Zip hanya file di Section 1 di atas. Atau jika lebih mudah, zip folder berikut:
- `app/` (controller + model baru/berubah)
- `database/migrations/` (5 file migration baru)
- `public/build/` (hasil build)
- `public/images/` (gambar baru)
- `public/.htaccess`
- `resources/` (Vue files + CSS + blade)
- `routes/` (api.php + web.php)
- `package.json` + `package-lock.json`
- `vite.config.js`

### Langkah 3: Upload & Extract di cPanel
Upload zip ke cPanel, extract dengan **overwrite** file existing.

### Langkah 4: Jalankan Migration
```bash
php artisan route:clear
php artisan config:clear
php artisan view:clear
php artisan migrate --force
```

`migrate --force` hanya menjalankan migration yang **belum ran** di production. Migration yang sudah ada tidak diulang.

### Langkah 5: Generate Jadwal (Opsional - Aman)
```bash
php artisan schedules:generate-occurrences
```

**Ini AMAN** — hanya mengisi bulan yang belum punya occurrences. Jadwal yang sudah ada **tidak berubah, tidak dihapus**.

### Langkah 6: Set Permission (jika perlu)
```bash
chmod -R 775 storage bootstrap/cache
```

**Selesai.**

---

## 4. Keamanan Jadwal (Schedule Occurrences)

### TIDAK akan terjadi:
- Jadwal yang sudah ada **tidak berubah tanggal**
- Data `maintenance_records` (laporan, status terlambat) **tidak terpengaruh**
- Urutan mesin **tidak acak** — algoritma deterministik berdasarkan `import_order`

### Cara kerja generate-occurrences:
Function `generateUpcoming()` hanya mengisi bulan yang **belum punya data**. Yang sudah ada **dilewati**:

```php
$pending = $schedules->filter(function ($s) use ($year, $month) {
    return !ScheduleOccurrence::where('schedule_id', $s->id)
        ->where('period_year', $year)
        ->where('period_month', $month)
        ->exists();
});
```

### JANGAN jalankan:
```bash
php artisan schedules:generate-occurrences --fresh
```
Ini akan **menghapus SEMUA jadwal** dan regenerate dari awal. Bisa mengubah tanggal.

---

## 5. Troubleshooting

### Migration Duplicate Error
```bash
php artisan migrate:status
```
Cek migration mana yang sudah ran. Jika ada duplicate, hapus file migration duplikat lalu jalankan `php artisan migrate --force` lagi.

### Frontend Tidak Update
```bash
php artisan view:clear
```
Hard refresh browser (Ctrl+Shift+R).

### Error 500 Setelah Deploy
```bash
php artisan route:clear
php artisan config:clear
php artisan view:clear
chmod -R 775 storage bootstrap/cache
```

---

## 6. Fitur Baru yang Ditambahkan

### 1. Manajemen Stok Suku Cadang
- CRUD stok: code, category, name, quantity, unit, limit_qty
- Import stok via Excel/CSV (upsert berdasarkan code)
- Restock stok dengan history
- Set limit qty untuk peringatan restock
- Laporan pemakaian stok dengan filter mesin

**Permission:**
| Role | View | CRUD | Import | Restock | Set Limit | Use in Report |
|------|------|------|--------|---------|-----------|---------------|
| admin | Ya | Ya | Ya | Ya | Ya | Ya |
| manager | Ya | Tidak | Tidak | Ya | Ya | Ya |
| technician | Ya (view only) | Tidak | Tidak | Tidak | Tidak | Ya |

### 2. Pemilihan Stok di Laporan (Report Page)
- Checkbox "Ganti komponen" → muncul dropdown stok pengganti
- Searchable dropdown (ketik teks, muncul saran, tombol X untuk clear)
- Input qty dengan tombol + dan - (mobile-friendly)
- Layout 1 baris 2 field (stok + qty)
- Info "Stok tersedia: X unit" + warning "Stok menipis!"
- Validasi: wajib pilih stok + isi qty saat "Ganti" aktif
- Stok otomatis berkurang saat laporan dikirim

### 3. Tombol OK / NOT OK Toggle
- Klik OK → terisi, klik lagi → kosong (clear)
- Klik NOT OK → terisi, klik lagi → kosong (clear)
- Semua indikator di-clear → status inProgress di-reset

### 4. Notifikasi (Bell Icon)
- Lonceng di kanan atas, tampil di semua halaman
- Badge merah dengan jumlah belum dibaca
- PC: dropdown card | Mobile: modal centered
- Klik notifikasi → mark as read
- "Tandai semua dibaca" button
- Auto-poll setiap 60 detik
- Tanggal relatif ("Baru saja", "5 menit lalu", "Kemarin")

**Admin Management:**
- Sidebar menu "Notifikasi" (admin only)
- CRUD notifikasi (judul + keterangan + toggle aktif)
- Stats: total, aktif, nonaktif

### 5. Filter Mesin di Report Page
- Keterangan status mesin (Terlambat, Hari ini, Besok, X hari lagi, Sudah dicek)
- Urutkan mesin berdasarkan urgensi
- API lite mode include schedules + records untuk status

### 6. Performance Optimization
- Lazy loading Vue components
- Database indexes untuk frequently queried tables
- API lite mode untuk faster machine list loading
