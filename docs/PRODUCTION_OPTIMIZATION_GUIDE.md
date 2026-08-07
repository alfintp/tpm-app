# 🚀 Panduan Optimasi Production TPM-App

> Dokumen ini berisi: cara mengecek performa, masalah yang ada sekarang, dan langkah optimasi yang perlu dilakukan.

---

## 📋 Cara Cek Performa (Testing Tools)

### 1. Google PageSpeed Insights
- Buka: https://pagespeed.web.dev/
- Masukkan URL website Anda (mis. `https://tpm.ladanglima.com`)
- Tool ini menilai: loading speed, Core Web Vitals, best practices, SEO
- Target: skor **90+** untuk performa

### 2. GTmetrix
- Buka: https://gtmetrix.com/
- Lebih detail dari PageSpeed — menampilkan waterfall request, ukuran file, waktu load per elemen

### 3. Chrome DevTools (Lighthouse)
- Buka website Anda di Chrome
- Tekan `F12` → tab **Lighthouse** → klik **Analyze page load**
- Hasil: skor Performance, Accessibility, Best Practices, SEO

### 4. Chrome DevTools (Network Tab)
- `F12` → tab **Network** → refresh halaman
- Lihat: ukuran file JS/CSS, waktu response API, jumlah request
- Target: total transfer < 500KB, API response < 300ms

### 5. Laravel Debugbar (Development Only)
```bash
composer require barryvdh/laravel-debugbar --dev
```
- Menampilkan: query SQL count, waktu query, memory usage, timeline
- **Hanya untuk development** — jangan di production

---

## 🔴 Masalah Performa yang Ada Sekarang

### Masalah 1: N+1 Query di `MachineController::index()`

**Lokasi:** `app/Http/Controllers/MachineController.php:18`

```php
$query = Machine::with([
    'schedules.occurrences',
    'components.indicators',
    'picMesin',
    'records.actions.indicatorValues',
    'records.actions.component',
    'records.latestApproval',
    'records.technician'
]);
return response()->json($query->get());
```

**Masalah:** Eager loading `records.actions.indicatorValues` untuk SEMUA mesin sekaligus. Jika ada 50 mesin × 10 records × 5 actions × 3 indicators = **7.500 baris** dimuat sekaligus. Response JSON bisa mencapai **5-10MB**.

**Solusi:** Lihat bagian "Optimasi yang Perlu Dilakukan" di bawah.

### Masalah 2: Tidak Ada Pagination
Endpoint `/api/machines` mengembalikan **semua mesin** sekaligus tanpa pagination. Semakin banyak mesin, semakin lambat.

### Masalah 3: Tidak Ada API Rate Limiting
Tidak ada `throttle` middleware di `routes/api.php`. Rentan terhadap abuse/DDoS.

### Masalah 4: Cache Driver = File
`CACHE_DRIVER` default-nya `file` — paling lambat. Production sebaiknya pakai Redis atau database.

### Masalah 5: Tidak Ada Response Caching
Tidak ada `Cache::remember()` di controller manapun. Setiap request hit database dari nol.

### Masalah 6: Tidak Ada Gzip Compression
Tidak terlihat konfigurasi gzip di level server. JSON response besar bisa dikompres 70-80%.

### Masalah 7: Frontend Tidak Code-Split
Vue components di-load sekaligus (bundle utama). Tidak ada `defineAsyncComponent` atau lazy loading per route.

---

## ✅ Optimasi yang Perlu Dilakukan

### A. Backend (Prioritas Tinggi)

#### 1. Tambah Pagination di `MachineController::index()`

**File:** `app/Http/Controllers/MachineController.php`

Ganti:
```php
return response()->json($query->get());
```
Menjadi:
```php
$perPage = $request->get('per_page', 15);
return response()->json($query->paginate($perPage));
```

Atau jika frontend butuh semua data, pisahkan endpoint:
- `/api/machines` → list ringan (id, kode, name, kota, status, condition_pct)
- `/api/machines/{id}` → detail lengkap (sudah ada, tinggal pakai)

#### 2. Tambah API Rate Limiting

**File:** `routes/api.php`

```php
Route::middleware(['auth:sanctum', 'throttle:60,1'])->group(function () {
    // semua route authenticated ada di sini
});
```

Artinya: max 60 request per menit per user. Cukup untuk aplikasi normal, mencegah abuse.

#### 3. Tambah Response Caching untuk Data Statis

**File:** `app/Http/Controllers/MachineController.php`

```php
public function index(Request $request)
{
    $cacheKey = 'machines_index_' . ($authUser->city ?? 'all');
    
    return Cache::remember($cacheKey, 300, function () use ($authUser) {
        $query = Machine::with(['schedules.occurrences', 'components', 'picMesin'])
            ->select(['id', 'kode', 'name', 'condition_pct', 'kota', 'status', 'import_order']);
        
        if ($authUser && isset($authUser->city) && $authUser->city !== 'both') {
            $query->where('kota', $authUser->city);
        }
        
        return $query->get();
    });
}
```

Cache 5 menit — setiap create/update machine, panggil `Cache::forget('machines_index_*')`.

#### 4. Ganti Cache Driver ke Database atau Redis

**File:** `.env` (production)

```env
CACHE_DRIVER=database
```

Atau jika Redis tersedia:
```env
CACHE_DRIVER=redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

Jika pakai database, jalankan:
```bash
php artisan cache:table
php artisan migrate
```

#### 5. Optimasi Eager Loading di `index()`

Kurangi relasi yang dimuat di list view. Pindahkan ke `show()`:

```php
// index() — ringan, untuk list
$query = Machine::with(['picMesin'])
    ->select(['id', 'kode', 'name', 'condition_pct', 'kota', 'status', 'import_order']);

// show() — lengkap, untuk detail 1 mesin (sudah ada)
```

#### 6. Jalankan Laravel Optimization Commands

Setiap kali setelah deploy ke production, jalankan:

```bash
php artisan config:cache     # Cache semua config
php artisan route:cache      # Cache semua route
php artisan view:cache       # Cache semua view
php artisan event:cache      # Cache event listeners
php artisan optimize         # Gabungan semua cache di atas
```

> **Penting:** Setiap kali ada perubahan code, jalankan `php artisan optimize:clear` dulu sebelum `optimize`.

---

### B. Server / cPanel (Prioritas Tinggi)

#### 7. Enable Gzip Compression

**Apache (`.htaccess` di folder public):**
```apache
<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE text/html text/plain text/css application/json application/javascript text/javascript application/xml
</IfModule>
```

**Nginx (jika pakai Nginx):**
```nginx
gzip on;
gzip_types text/css application/javascript application/json text/xml;
gzip_min_length 1000;
```

Efek: JSON response 1MB → ~200KB (80% reduction).

#### 8. Enable Browser Caching (Static Assets)

**Apache (`.htaccess`):**
```apache
<IfModule mod_expires.c>
    ExpiresActive On
    ExpiresByType text/css "access plus 1 year"
    ExpiresByType application/javascript "access plus 1 year"
    ExpiresByType image/png "access plus 1 year"
    ExpiresByType image/jpeg "access plus 1 year"
    ExpiresByType image/svg+xml "access plus 1 year"
</IfModule>
```

File di `public/build/` sudah hashed (nama file berubah saat build), jadi aman di-cache 1 tahun.

#### 9. Set OPcache di PHP

**`php.ini` (cPanel → MultiPHP INI Editor):**
```ini
opcache.enable=1
opcache.memory_consumption=128
opcache.max_accelerated_files=10000
opcache.revalidate_freq=300
```

Efek: PHP tidak perlu compile ulang setiap request — 30-50% lebih cepat.

---

### C. Frontend (Prioritas Sedang)

#### 10. Code Splitting — Lazy Load Vue Views

**File:** `resources/js/App.vue` (atau router)

Saat ini semua view di-import sekaligus. Ubah ke lazy load:

```js
// Sebelum (semua di-load sekaligus)
import Machines from './views/Machines.vue'
import Components from './views/Components.vue'
import Users from './views/Users.vue'

// Sesudah (hanya di-load saat dibuka)
const Machines = defineAsyncComponent(() => import('./views/Machines.vue'))
const Components = defineAsyncComponent(() => import('./views/Components.vue'))
const Users = defineAsyncComponent(() => import('./views/Users.vue'))
```

Efek: halaman login hanya load login.js (~50KB), bukan seluruh app (~500KB).

#### 11. Vite Build Optimization

**File:** `vite.config.js`

```js
export default defineConfig({
    plugins: [/* ... */],
    build: {
        rollupOptions: {
            output: {
                manualChunks: {
                    'vendor-vue': ['vue', 'vue-router'],
                    'vendor-axios': ['axios'],
                    'vendor-xlsx': ['xlsx'],
                },
            },
        },
        chunkSizeWarningLimit: 500,
    },
});
```

Efek: library besar (XLSX, Vue) di-pisah ke file terpisah, bisa di-cache browser.

#### 12. Lazy Load SheetJS (XLSX)

SheetJS (~400KB) hanya dipakai saat import Excel. Jangan load di halaman utama.

```js
// Sudah benar — loadSheetJS pakai dynamic import
const loadSheetJS = async () => {
    const XLSX = await import('xlsx');
    return XLSX.default || XLSX;
};
```

Pastikan tidak ada `import XLSX from 'xlsx'` di top-level file apapun.

---

### D. Database (Prioritas Sedang)

#### 13. Tambah Index untuk Query yang Sering Dipakai

```bash
php artisan make:migration add_indexes_to_frequently_queried_tables
```

```php
public function up()
{
    Schema::table('machines', function (Blueprint $table) {
        $table->index('kota');
        $table->index('status');
        $table->index(['kota', 'import_order']);
    });
    
    Schema::table('schedule_occurrences', function (Blueprint $table) {
        $table->index(['machine_id', 'due_date']);
        $table->index(['period_year', 'period_month']);
    });
    
    Schema::table('maintenance_records', function (Blueprint $table) {
        $table->index(['machine_id', 'maintenance_date']);
        $table->index('status');
    });
}
```

#### 14. Backup Database Otomatis

Buat cron job di cPanel untuk backup harian:
```bash
mysqldump -u USER -pPASS DB_NAME > /home/user/backups/tpm_$(date +\%Y\%m\%d).sql
```

Atau pakai package:
```bash
composer require spatie/laravel-backup
```

---

## 📊 Checklist Optimasi (Urutkan Prioritas)

| # | Optimasi | Tingkat | Effort | Dampak |
|---|---|---|---|---|
| 1 | Gzip compression (.htaccess) | 🔴 Tinggi | 5 menit | ⭐⭐⭐⭐⭐ |
| 2 | `php artisan optimize` | 🔴 Tinggi | 1 menit | ⭐⭐⭐⭐ |
| 3 | OPcache PHP | 🔴 Tinggi | 5 menit | ⭐⭐⭐⭐ |
| 4 | Browser caching (.htaccess) | 🔴 Tinggi | 5 menit | ⭐⭐⭐ |
| 5 | Kurangi eager loading di index() | 🔴 Tinggi | 30 menit | ⭐⭐⭐⭐⭐ |
| 6 | API rate limiting (throttle) | 🟡 Sedang | 5 menit | ⭐⭐⭐ |
| 7 | Ganti cache driver ke database | 🟡 Sedang | 10 menit | ⭐⭐⭐ |
| 8 | Response caching (Cache::remember) | 🟡 Sedang | 30 menit | ⭐⭐⭐⭐ |
| 9 | Database indexes | 🟡 Sedang | 15 menit | ⭐⭐⭐ |
| 10 | Code splitting frontend | 🟢 Rendah | 1 jam | ⭐⭐⭐ |
| 11 | Vite manualChunks | 🟢 Rendah | 15 menit | ⭐⭐ |
| 12 | Pagination API | 🟢 Rendah | 1 jam | ⭐⭐⭐⭐ |
| 13 | Backup otomatis | 🟢 Rendah | 30 menit | ⭐⭐ (safety) |

---

## 🧪 Cara Test Setelah Optimasi

1. **Sebelum optimasi:** Catat skor PageSpeed + waktu load di GTmetrix
2. **Lakukan optimasi** sesuai checklist di atas (mulai dari prioritas tinggi)
3. **Sesudah optimasi:** Test ulang dengan tools yang sama
4. **Bandingkan** — seharusnya ada peningkatan signifikan

### Target Performa Ideal
| Metrik | Target |
|---|---|
| First Contentful Paint (FCP) | < 1.5 detik |
| Largest Contentful Paint (LCP) | < 2.5 detik |
| Time to Interactive (TTI) | < 3.5 detik |
| Total page weight | < 500 KB |
| API response time | < 300 ms |
| PageSpeed score | 90+ |

---

## ⚡ Quick Wins (Bisa Langsung Sekarang, < 30 menit)

Ini 3 hal yang paling mudah dan dampaknya terbesar:

### 1. Enable Gzip (5 menit)
Edit `.htaccess` di folder `public/`, tambahkan:
```apache
<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE text/html text/plain text/css application/json application/javascript text/javascript
</IfModule>
```

### 2. Jalankan Laravel Optimize (1 menit)
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### 3. Enable OPcache (5 menit)
Di cPanel → MultiPHP INI Editor → set `opcache.enable=On`

Ketiganya tidak perlu ubah code, hanya konfigurasi server. Dampak: loading **30-50% lebih cepat**.
