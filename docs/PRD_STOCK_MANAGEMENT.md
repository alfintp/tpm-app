# PRD: Stock Management & Component Replacement Integration

## 1. Overview

Sistem manajemen stok suku cadang untuk mesin TPM. Admin dapat mengelola data stok (CRUD + import), dan teknisi saat melakukan report dapat memilih item stok yang dipakai saat mengganti komponen. Stok akan otomatis berkurang. Tersedia laporan pemakaian stok dan peringatan restock berdasarkan `limit_qty`.

## 2. Data Model

### Table: `stocks`
| Field | Type | Description |
|-------|------|-------------|
| `id` | uuid (PK) | Primary key |
| `code` | varchar(50) | Kode unik stok, contoh: `STK-001` |
| `category` | varchar(100) | Kategori, contoh: `Mechanical`, `Electrical` |
| `name` | varchar(255) | Nama item stok |
| `quantity` | integer | Jumlah stok saat ini |
| `unit` | varchar(50) | Satuan, contoh: `pcs`, `set`, `meter` |
| `limit_qty` | integer, default 1 | Batas bawah stok untuk peringatan restock |
| `is_active` | boolean, default true | Status aktif |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |

### Table: `stock_usages` (laporan pemakaian stok)
| Field | Type | Description |
|-------|------|-------------|
| `id` | uuid (PK) | Primary key |
| `stock_id` | uuid (FK) | References `stocks.id` |
| `maintenance_action_id` | uuid (FK, nullable) | References `maintenance_actions.id` |
| `maintenance_record_id` | uuid (FK) | References `maintenance_records.id` |
| `machine_id` | uuid (FK) | References `machines.id` (denormalized untuk laporan cepat) |
| `machine_component_id` | uuid (FK, nullable) | Komponen yang diganti |
| `quantity_used` | integer | Jumlah stok yang dipakai |
| `used_at` | date | Tanggal pemakaian |
| `technician_id` | uuid (FK, nullable) | User yang melakukan penggantian |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |

### Alter Table: `maintenance_actions`
- Add `stock_id` uuid nullable (FK ke `stocks.id`) — stok yang dipilih saat ganti komponen
- Add `stock_qty_used` integer nullable — jumlah stok yang dipakai

## 3. Backend

### Controller: `StockController.php`

#### API Endpoints
| Method | Route | Description | Access |
|--------|-------|-------------|--------|
| GET | `/api/stocks` | List semua stok + filter + search | auth |
| GET | `/api/stocks/low` | List stok dengan quantity <= limit_qty | auth |
| POST | `/api/stocks` | Tambah stok baru | admin |
| PUT | `/api/stocks/{id}` | Edit stok | admin |
| DELETE | `/api/stocks/{id}` | Hapus stok | admin |
| POST | `/api/stocks/import` | Bulk import stok | admin |
| PUT | `/api/stocks/bulk-limit` | Bulk update limit_qty untuk multiple stok | admin |
| POST | `/api/stocks/{id}/restock` | Tambah quantity stok (restock) | admin |
| GET | `/api/stocks/usages` | Laporan pemakaian stok (filter: date range, machine, stock) | auth |
| GET | `/api/stocks/{id}/usages` | Riwayat pemakaian per item stok | auth |

#### `index()` — List stok
- Query params: `search`, `category`, `low_stock` (boolean), `sort`
- Return: array of stock objects dengan field tambaran `is_low_stock` (quantity <= limit_qty)

#### `store()` — Tambah stok
- Validate: `code` (unique), `category`, `name` (required), `quantity` (integer >= 0), `unit`, `limit_qty` (integer >= 0, default 1)
- Log activity: "Tambah Stok"

#### `update()` — Edit stok
- Validate sama seperti store, code unique except self
- Log activity: "Edit Stok"

#### `bulkLimit()` — Bulk update limit_qty
- Input: `{ items: [{ id, limit_qty }] }`
- Update multiple stocks sekaligus
- Log activity: "Bulk Update Limit Stok"
- **Tujuan**: Memudahkan admin set limit_qty untuk beberapa stok sekaligus

#### `restock()` — Tambah quantity
- Input: `{ added_quantity: integer }`
- Update: `quantity += added_quantity`
- Log activity: "Restock Stok"

#### `import()` — Bulk import
- Input: `{ stocks: [{ code, category, name, quantity, unit, limit_qty }] }`
- Upsert berdasarkan `code` (jika code sudah ada, update quantity/fields)
- Log activity: "Import Stok"

#### `usages()` — Laporan pemakaian
- Query params: `from_date`, `to_date`, `machine_id`, `stock_id`
- Return: array of stock_usage objects dengan relasi stock, machine, technician

### Model: `Stock.php`
- `fillable`: code, category, name, quantity, unit, limit_qty, is_active
- `casts`: quantity → integer, limit_qty → integer, is_active → boolean
- Relations: `usages()` → hasMany StockUsage

### Model: `StockUsage.php`
- `fillable`: stock_id, maintenance_action_id, maintenance_record_id, machine_id, machine_component_id, quantity_used, used_at, technician_id
- Relations: `stock()`, `machine()`, `technician()`, `maintenanceRecord()`

### Modify: `MaintenanceRecordController::store()`
Saat menyimpan maintenance action dengan `action_type === 'replace'`:
1. Cek apakah `stock_id` dan `stock_qty_used` ada di action payload
2. Jika ada, validasi stok tersedia dan `quantity >= stock_qty_used`
3. Decrement `stock.quantity -= stock_qty_used`
4. Buat record `StockUsage` dengan relasi ke action, record, machine, technician
5. Jika `stock.quantity <= stock.limit_qty`, log warning untuk peringatan restock

## 4. Frontend

### A. Page: `Stock.vue` (page baru)

#### Struktur — mengikuti template page Components.vue:
- **PageHeader**: "Manajemen Stok" / "Kelola stok suku cadang penggantian komponen"
- **Stats Cards** (grid 4):
  - Total Item Stok
  - Stok Menipis (quantity <= limit_qty) — klik untuk filter
  - Kategori Stok (jumlah kategori unik)
  - Pemakaian Bulan Ini (total quantity_used bulan ini)
- **Search & Filter Bar**:
  - SearchInput (cari code, name, category)
  - Filter kategori (dropdown)
  - Filter stok menipis (toggle)
  - Sort (nama A-Z, nama Z-A, quantity terendah, quantity tertinggi)
  - Tombol "Import" (admin only)
  - Tombol "Tambah Stok" (admin only)
- **DataTable**:
  - Columns: Code, Category, Name, Quantity, Unit, Limit Qty, Status
  - Status: badge "Normal" (hijau) / "Menipis" (merah, qty <= limit)
  - Actions: Edit, Restock (tambah qty), Riwayat Pemakaian, Hapus
- **Tabs** (di bawah table atau di atas):
  - Tab 1: "Data Stok" — table di atas
  - Tab 2: "Laporan Pemakaian" — table stock_usages dengan filter tanggal, mesin, stok

#### Tab: Laporan Pemakaian
- Filter: date range (from/to), mesin (dropdown), stok (dropdown)
- DataTable columns: Tanggal, Nama Stok, Mesin, Komponen, Jumlah Dipakai, Teknisi
- Export/Print button (opsional)

#### Modal: StockForm (tambah/edit)
- Fields: code, category, name, quantity, unit, limit_qty
- Pattern sama seperti ComponentForm.vue

#### Modal: BulkSetLimit (edit limit_qty untuk multiple stok)
- Tabel/list stok dengan input limit_qty per row
- Admin pilih beberapa stok → set limit_qty sekaligus
- Tombol "Simpan Semua"
- **Tujuan**: Admin belum punya data limit_qty, jadi UI ini memudahkan set beberapa sekaligus

#### Modal: Restock
- Input: `added_quantity` (jumlah yang ditambahkan)
- Show: current quantity, new quantity (preview)
- Submit: POST `/api/stocks/{id}/restock`

#### Modal: Import
- Upload Excel/CSV atau paste table
- Columns: code, category, name, quantity, unit, limit_qty
- Preview sebelum submit
- Pattern sama seperti component import

#### Modal: StockUsageHistory
- Show riwayat pemakaian untuk 1 item stok
- DataTable: tanggal, mesin, komponen, jumlah, teknisi

### B. Sidebar: `App.vue`
- Tambah menu item "Stock" setelah "Components"
- Icon: package/box icon
- Route: `/stock`
- Visible untuk semua role (admin, manager, technician)
- Manager/Admin: full CRUD
- Technician: view only + laporan

### C. Route: `web.php`
```php
Route::get('/stock', function () {
    return Inertia::render('Stock');
});
```

### D. Route: `api.php`
```php
// Stock Routes
Route::get('/stocks', [StockController::class, 'index']);
Route::get('/stocks/low', [StockController::class, 'lowStock']);
Route::post('/stocks', [StockController::class, 'store']);
Route::put('/stocks/{id}', [StockController::class, 'update']);
Route::delete('/stocks/{id}', [StockController::class, 'destroy']);
Route::post('/stocks/import', [StockController::class, 'import']);
Route::put('/stocks/bulk-limit', [StockController::class, 'bulkLimit']);
Route::post('/stocks/{id}/restock', [StockController::class, 'restock']);
Route::get('/stocks/usages', [StockController::class, 'usages']);
Route::get('/stocks/{id}/usages', [StockController::class, 'usageHistory']);
```

### E. Modify: `MachineReportTab.vue` — Ganti Komponen Enhancement

Saat checkbox "Ganti komponen" dicentang:
1. Tampilkan dropdown/search untuk pilih item stok (fetch dari `/api/stocks`)
2. Tampilkan input jumlah qty yang dipakai
3. Validasi: qty_used > 0 dan qty_used <= stock.quantity
4. Tampilkan info: "Stok tersedia: X unit"
5. Jika stok menipis, tampilkan warning badge

**Layout perubahan di kolom "Catatan & Ganti Komponen":**
```
[textarea catatan]
[x] Ganti komponen
  ↓ (muncul saat dicentang)
  [Dropdown: Pilih stok...] (searchable, show code + name + qty)
  [Input: Jumlah] [unit]
  [Info: Stok tersedia: 10 pcs]
  [Warning: Stok menipis! (qty <= limit)]
```

### F. Modify: `Report.vue` — Submit dengan stock data

Saat submit report, untuk action dengan `is_component_replacement === true`:
- Include `stock_id` dan `stock_qty_used` di action payload
- Backend akan decrement stock dan create StockUsage record

### G. Modify: `MachineDetail.vue` — Tampilkan stok yang dipakai

Di history/riwayat penggantian komponen:
- Tampilkan nama stok + qty yang dipakai
- Link ke page Stock untuk detail

## 5. UI/UX Guidelines

- **Design template**: Mengikuti page Components.vue (PageHeader, stats cards, search bar, DataTable, modal)
- **Color theme**: Konsisten dengan tema existing (brand-brown, brand-cream, slate)
- **Badge stok menipis**: Merah (`bg-red-50 text-red-700 border-red-200`)
- **Badge normal**: Hijau (`bg-green-50 text-green-700 border-green-200`)
- **Responsive**: Mobile-friendly, table scroll horizontal
- **Empty state**: Sama pattern dengan Components.vue

## 6. Implementation Order

1. **Database**: Migration `stocks` + `stock_usages` + alter `maintenance_actions`
2. **Backend**: StockController + Model + Routes (api.php)
3. **Frontend Stock page**: Stock.vue + modals (StockForm, Restock, Import, BulkSetLimit, UsageHistory)
4. **Sidebar**: Tambah menu Stock di App.vue
5. **Route**: Tambah `/stock` di web.php
6. **Report integration**: Modify MachineReportTab.vue + Report.vue + MaintenanceRecordController
7. **Laporan pemakaian**: Tab di Stock page
8. **Build & test**

## 7. Permissions

| Role | View Stock | CRUD Stock | Import | Restock | Set Limit | Use Stock in Report |
|------|-----------|------------|--------|---------|-----------|---------------------|
| admin | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| manager | ✅ | ❌ | ❌ | ✅ | ✅ | ✅ |
| technician | ✅ (view only) | ❌ | ❌ | ❌ | ❌ | ✅ |

## 8. Edge Cases

- Stok habis (quantity = 0): dropdown di report tetap show tapi disabled + badge "Habis"
- Stok dihapus saat masih ada stock_usages: soft delete atau prevent delete
- Quantity tidak boleh negatif
- Bulk import: jika code sudah ada, update (upsert) bukan skip
- Restock tidak menghapus history pemakaian sebelumnya
