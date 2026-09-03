# PRD: Production Readiness — Error Prevention & Security Hardening

## Tujuan
Dokumen ini menilai setiap potensi error production yang dilaporkan, mengecek apakah sudah/sangat perlu diimplementasi di project TPM-APP, dan memberikan solusi konkret. Tujuannya agar Anda paham dan bisa konfirmasi mana yang perlu dikerjakan.

---

## Status: SANGAT PERLU (Wajib sebelum production)

### 1. CORS Terbuka Lebar (SECURITY)
**Apa itu:** CORS (Cross-Origin Resource Sharing) mengatur domain mana yang boleh akses API Anda. Jika terbuka (`*`), siapapun dari domain manapun bisa call API Anda.

**Status project:** `config/cors.php` line 22 → `'allowed_origins' => ['*']`. Ini **berbahaya di production**. Siapapun bisa buat script di domain lain yang call API Anda.

**Solusi:** Set `allowed_origins` ke domain production yang spesifik, misal: `['https://tpm.ladanglima.com', 'https://tpm-app.netlify.app']`. Untuk development, bisa pakai env variable.

---

### 2. APP Debug Mode (SECURITY)
**Apa itu:** Jika `APP_DEBUG=true`, error menampilkan stack trace lengkap, query SQL, variabel, dll. Ini **bocor informasi sensitif** ke publik.

**Status project:** `config/app.php` line 45 → `'debug' => (bool) env('APP_DEBUG', false)`. Default false sudah aman, tapi pastikan di `.env` production TIDAK ada `APP_DEBUG=true`.

**Solusi:** Pastikan `.env` production: `APP_DEBUG=false`. Tidak perlu code change, hanya konfirmasi config.

---

### 3. Throttle / Rate Limiting Login (SECURITY)
**Apa itu:** Tanpa rate limiting, attacker bisa brute-force password login sebanyak yang mereka mau.

**Status project:** Route `POST /api/login` di `routes/api.php` line 24 **TIDAK punya throttle**. Semua route lain di dalam group `auth:sanctum` juga tidak ada throttle tambahan (hanya default `throttle:api` di group level).

**Solusi:** Tambah `throttle:5,1` (5 request per menit) untuk route login. Bisa juga tambah throttle untuk route write lainnya.

---

### 4. Sanctum Token Expiration (SECURITY)
**Apa itu:** Token yang tidak pernah expired bisa dipakai selamanya. Jika token dicuri, attacker punya akses abadi.

**Status project:** `config/sanctum.php` line 49 → `'expiration' => null`. Token tidak pernah expired.

**Solusi:** Set expiration, misal 60 menit (1 jam) atau 1440 (24 jam) tergantung kebutuhan. Atau implement refresh token mechanism.

---

### 5. DB Transaction Tanpa Deadlock Retry
**Apa itu:** Saat dua request concurrent meng-lock row yang sama, terjadi deadlock. Tanpa retry, user dapat error 500.

**Status project:** Hanya 1 `DB::transaction` di `RoleController.php`. `ApprovalController::decide` pakai `DB::beginTransaction` manual. **Tidak ada retry logic** untuk deadlock.

**Solusi:** Tambah retry wrapper untuk operasi critical (approval, role update). Laravel sudah punya `DB::transaction(fn() => ..., attempts: 3)` yang auto-retry deadlock.

---

### 6. N+1 Query di MaintenanceRecordController::index
**Apa itu:** N+1 query = query database N+1 kali (1 untuk parent + N untuk setiap child) padahal bisa 1 query saja dengan eager loading.

**Status project:** `MaintenanceRecordController.php` line 26 → `MaintenanceRecord::with(['machine', 'technician', 'actions.component', 'actions.stock', 'approvals'])->get()`. Sudah pakai eager loading. **TAPI** `ApprovalController::index` line 151 → `->get()->map()` memproses semua record di memory sekaligus tanpa pagination. Untuk 215+ records ini masih OK, tapi bisa jadi masalah jika data tumbuh.

**Solusi:** Tambah pagination di `ApprovalController::index` atau chunk processing. Untuk sekarang tidak urgent tapi perlu diwaspadai.

---

### 7. Force HTTPS di Production (SECURITY)
**Apa itu:** Tanpa HTTPS, data (termasuk token dan password) dikirim plain-text dan bisa di-intercept.

**Status project:** `AppServiceProvider::boot()` kosong — tidak ada `URL::forceScheme('https')`. `TrustHosts` juga di-comment out di `Kernel.php` line 17.

**Solusi:** Uncomment `TrustHosts` dan tambah `URL::forceScheme('https')` di production environment. Atau handle di web server (Nginx/Apache) level.

---

### 8. Session Cookie Secure Flag (SECURITY)
**Apa itu:** Cookie dengan `secure=true` hanya dikirim via HTTPS, mencegah intercept via HTTP.

**Status project:** `config/session.php` line 171 → `'secure' => env('SESSION_SECURE_COOKIE')`. Default null = tidak secure. Tapi karena app pakai Sanctum token (Bearer), bukan session cookie untuk API, ini **kurang relevan**. Frontend store token di localStorage.

**Solusi:** Set `SESSION_SECURE_COOKIE=true` di `.env` production. Prioritas rendah karena auth pakai Bearer token, bukan session cookie.

---

## Status: PERLU (Penting tapi tidak blocking)

### 9. Cron Job / Scheduler Tidak Ada Worker
**Apa itu:** Laravel scheduler butuh `cron` entry di server: `* * * * * cd /project && php artisan schedule:run`. Tanpa ini, scheduled command tidak jalan.

**Status project:** `Console\Kernel.php` ada schedule: `schedules:generate-occurrences` monthly. **Tapi jika server tidak setup cron, ini tidak akan jalan.**

**Solusi:** Setup cron di server production. Tidak perlu code change, hanya server config. Jika pakai shared hosting, pastikan cron tersedia.

---

### 10. Queue Worker / Stuck Queue
**Apa itu:** Jika queue connection = `sync`, job dijalankan synchronous (blocking). Jika = `database`/`redis`, butuh worker (`php artisan queue:work`) yang harus selalu running.

**Status project:** `config/queue.php` line 16 → `'default' => env('QUEUE_CONNECTION', 'sync')`. Default sync = **tidak ada queue worker yang perlu dijalankan**. Aman untuk sekarang.

**Solusi:** Tidak perlu untuk sekarang. Jika nanti ada heavy process (email, export), baru switch ke `database` dan setup supervisor untuk worker.

---

### 11. External API Timeout (Holiday API)
**Apa itu:** Jika external API lambat/down, request user ikut lambat/hang.

**Status project:** `routes/api.php` line 133 → `Http::timeout(10)` untuk holiday API. Sudah ada timeout 10 detik + cache 24 jam + try-catch fallback ke `[]`. **Sudah baik.**

**Solusi:** Sudah terimplementasi. Tidak perlu perubahan.

---

### 12. Migration Safety
**Apa itu:** Migration yang lupa di-run bisa cause error (table/column tidak ada). Rollback gagal bisa leave DB in inconsistent state.

**Status project:** Ada migration files di `database/migrations/`. Tidak ada auto-check apakah migration sudah di-run. Praktik standar: jalankan `php artisan migrate` saat deploy.

**Solusi:** Tambah `php artisan migrate --force` di deployment script. Pastikan migration selalu di-run sebelum app live.

---

## Status: TIDAK PERLU (Tidak relevan untuk project ini)

### 13. Webhook Error
**Apa itu:** Webhook = callback HTTP dari external service ke app Anda. Jika endpoint Anda error, external service retry/lewat.
**Status project:** **Tidak ada webhook endpoint.** Tidak perlu.

### 14. Push Notif Gagal
**Apa itu:** Push notification ke device gagal (FCM/APNS token expired, device offline).
**Status project:** **Tidak ada push notification.** Notifikasi di app pakai polling API (`/api/notifications/dynamic`). Tidak perlu.

### 15. Redis Down
**Apa itu:** Jika Redis dipakai untuk cache/queue/session dan Redis crash, app error.
**Status project:** Cache default = `file` (`CACHE_STORE=file` implied), queue = `sync`, session = `file`. **Redis tidak dipakai.** Tidak perlu.

### 16. Cache Basi
**Apa itu:** Cache lama yang isinya sudah tidak valid tapi masih dipakai.
**Status project:** Hanya cache holiday API (24 jam, auto-expire). **Tidak ada cache lain.** Tidak perlu.

### 17. Worker Mati
**Apa itu:** Queue worker process berhenti/crash dan job menumpuk.
**Status project:** Queue = sync, **tidak ada worker.** Tidak perlu.

### 18. Cookie Gak Ke-Set
**Apa itu:** Cookie tidak ter-set di browser karena domain mismatch, SameSite policy, atau Secure flag.
**Status project:** App pakai Bearer token di localStorage, **bukan cookie-based auth**. Tidak perlu.

### 19. Session Logout Sendiri
**Apa itu:** User tiba-tiba logout karena session expired atau session driver bermasalah.
**Status project:** Auth pakai Sanctum Bearer token, bukan session. Token disimpan di localStorage. **Tidak relevan** (kecuali token dihapus manual). Tidak perlu.

### 20. JWT Invalid
**Apa itu:** JWT (JSON Web Token) invalid/expired causing 401 errors.
**Status project:** App pakai **Sanctum** (plain token, bukan JWT). Tidak ada JWT. Tidak perlu.

### 21. Page Render Salah Implementation
**Apa itu:** Frontend render page yang salah karena routing/middleware error.
**Status project:** Inertia.js + Vue 3, routing via Laravel web routes. Catch-all route sudah handle 404. **Sudah OK.** Tidak perlu.

### 22. Duplicate Data
**Apa itu:** Data tersimpan dua kali karena double-submit atau race condition.
**Status project:** Tidak ada unique constraint check di beberapa endpoint (misal: maintenance record bisa di-submit dua kali untuk mesin+tanggal yang sama). **Risiko rendah** karena UI tidak memungkinkan double submit (button disable saat loading). Tidak perlu untuk sekarang.

### 23. Memory Leak
**Apa itu:** Memory PHP process terus tumbuh sampai crash. Biasanya di long-running process (Octane, Swoole).
**Status project:** App pakai PHP-FPM tradisional (request = process baru, memory di-reset per request). **Tidak mungkin memory leak.** Tidak perlu.

### 24. Null Pointer
**Apa itu:** Akses property dari object null (NullPointerException).
**Status project:** PHP 8+ handle ini dengan null-safe operator `?->`. Code sudah pakai `?->` di banyak tempat (misal: `$record->machine?->name`). **Sudah baik.** Tidak perlu.

### 25. Infinite Loop
**Apa itu:** Loop yang tidak pernah berhenti, causing timeout/memory exhaustion.
**Status project:** Tidak ada loop rekursif yang berisiko. **Tidak perlu.**

### 26. Rollback Gagal
**Apa itu:** `php artisan migrate:rollback` gagal dan DB jadi inconsistent.
**Status project:** Migration `migrate_existing_unlock_requests` punya `down()` yang empty (by design). Lainnya standar. **Risiko rendah.** Tidak perlu.

### 27. DNS Drama
**Apa itu:** DNS resolve gagal causing API call ke external service error.
**Status project:** Hanya 1 external API (holiday API) yang sudah ada timeout + fallback. **Tidak perlu.**

### 28. SSL Expired
**Apa itu:** SSL certificate domain expired causing browser warning/error.
**Status project:** **Bukan tanggung jawab code**, tapi server/infra. Pastikan SSL certificate auto-renew (Let's Encrypt + certbot). Tidak perlu code change.

### 29. Redirect Loop
**Apa itu:** Page A redirect ke B, B redirect ke A, browser error "too many redirects".
**Status project:** Tidak ada redirect complex. Auth redirect ke `/login` jika tidak authenticated. **Tidak perlu.**

### 30. CDN Tidak Update
**Apa itu:** CDN cache asset lama, user lihat versi lama setelah deploy.
**Status project:** Asset di-serve langsung dari server (Vite build). **Tidak pakai CDN.** Tidak perlu.

---

## Tambahan: Yang Tidak Ada di List Tapi Perlu Diperhatikan

### A. APP_KEY Tidak Boleh Kosong (SECURITY)
Jika `APP_KEY` kosong, encryption tidak berfungsi (session, cookie, encrypted data tidak aman). Pastikan `.env` production punya `APP_KEY` yang valid (32 char random). Cek dengan `php artisan key:generate`.

### B. .env Tidak Boleh Commit ke Git (SECURITY)
Pastikan `.env` ada di `.gitignore` (sudah ada). Tapi pastikan juga tidak ada secret yang hard-coded di code (API key, password, dll).

### C. SQL Injection Protection
Laravel Eloquent sudah otomatis parameterized query. **Tidak ada raw query** yang terdeteksi. Sudah aman.

### D. XSS Protection
Vue 3 auto-escape `{{ }}` interpolation. **Sudah aman** selang tidak pakai `v-html` dengan user input. Cek: tidak ada `v-html` yang terdeteksi di komponen.

### E. Mass Assignment Protection
Model punya `$fillable` / `$guarded`. Cek: `User`, `MaintenanceRecord`, dll sudah punya `$fillable`. **Sudah aman.**

---

## Ringkasan Prioritas

| # | Item | Status | Prioritas | Penanganan |
|---|------|--------|-----------|------------|
| 1 | CORS `*` terbuka | Belum | SANGAT PERLU | Ikut web utama |
| 2 | APP_DEBUG=true | Sudah aman | OK | Sudah aman |
| 3 | Throttle login | Belum | SANGAT PERLU | Ikut web utama |
| 4 | Token expiration | Belum (null) | SANGAT PERLU | Ikut web utama |
| 5 | Deadlock retry | Belum | PERLU | Implementasi sekarang |
| 6 | N+1 / pagination approval | Sebagian | PERLU | Implementasi sekarang |
| 7 | Force HTTPS | Belum | SANGAT PERLU | Ikut web utama |
| 8 | Session secure cookie | N/A | TIDAK PERLU | Ikut web utama |
| 9 | Cron scheduler setup | Server config | PERLU | Lihat catatan cPanel/VPS |
| 10 | Queue worker | Tidak perlu (sync) | TIDAK PERLU | - |
| 11 | External API timeout | Sudah OK | OK | - |
| 12 | Migration safety | Deployment step | PERLU | Ikut deployment web utama |
| 13-30 | Lainnya | Tidak relevan | TIDAK PERLU | - |

---

## Catatan Migrasi ke Web Utama

### Konteks
Aplikasi TPM-APP saat ini berjalan standalone (cPanel). Selanjutnya akan di-migrate dan digabung ke web utama kantor menjadi satu kesatuan di VPS. Beberapa aturan security/infra akan mengikuti konfigurasi web utama kantor.

### Akan Ikut Aturan Web Utama (Tidak perlu diimplementasi sekarang)

Item-item ini bergantung pada konfigurasi server/domain web utama. Tidak ada gunanya di-set sekarang karena akan ditimpa/diatur ulang saat migrasi:

| # | Item | Alasan Ikut Web Utama |
|---|------|----------------------|
| 1 | CORS | Domain web utama belum diketahui. Akan di-set sesuai domain final. |
| 3 | Throttle login | Web utama mungkin sudah punya rate limiting global (Nginx/Cloudflare). Sinkronkan setelah gabung. |
| 4 | Token expiration | Tergantung kebijakan auth web utama. Mungkin akan pakai SSO/auth terpusat. |
| 7 | Force HTTPS | VPS web utama sudah pasti pakai HTTPS (Nginx config + Let's Encrypt). Di-handle di server level. |
| 8 | Session secure cookie | Auth pakai Bearer token, bukan session. Web utama yang atur cookie policy. |
| 12 | Migration safety | Akan mengikuti deployment pipeline web utama. |

### Bisa Diimplementasi Sekarang (Sebelum Migrasi)

Item-item ini adalah code-level fix yang tidak bergantung pada server/domain. Aman diimplementasi sekarang dan akan tetap relevan setelah migrasi:

| # | Item | Alasan Bisa Sekarang |
|---|------|----------------------|
| 5 | Deadlock retry | Code-level fix di controller. Tidak bergantung server. Tetap relevan setelah migrasi. |
| 6 | N+1 / pagination approval | Code-level fix di controller. Mencegah memory issue saat data tumbuh. Tetap relevan. |
| 9 | Cron → On-demand | Diganti dengan on-demand generation + cache 1 hari. Tidak perlu cron setup di server mana pun. |

### Kasus Khusus: Cron Scheduler (Poin 9) — SELESAI (On-Demand)

**Perubahan:** Cron job diganti dengan on-demand generation. Schedule sekarang di-generate saat user membuka Dashboard (endpoint `/api/schedules/notifications`).

**Cara kerja:**
1. User buka Dashboard → frontend panggil `/api/schedules/notifications`
2. Backend panggil `ScheduleOccurrenceGenerator::ensureGenerated()`
3. Method cek cache key `schedule_generation_checked` (TTL 1 hari)
4. Jika cache masih ada → skip (return immediately, no DB query)
5. Jika cache expired → jalankan `generateUpcoming()` (idempotent, skip month yang sudah ada) → set cache baru
6. Maksimal 1x generate per hari, tidak per request

**Keuntungan:**
- Tidak perlu setup cron di cPanel/VPS
- Tidak perlu reconfigure saat migrasi server
- Tetap idempotent (jadwal yang sudah ada tidak berubah)
- Cache 1 hari memastikan tidak ada redundant DB query di setiap page load

**File yang diubah:**
- `app/Services/ScheduleOccurrenceGenerator.php` — tambah method `ensureGenerated()` dengan cache
- `app/Http/Controllers/MaintenanceScheduleController.php` — trigger `ensureGenerated()` di `notifications()`
- `app/Console/Kernel.php` — cron job dikomentari (artisan command masih available manual)

**Catatan:** Jika tidak ada user yang buka app dalam waktu lama, jadwal tidak ter-generate. Tapi karena jadwal di-generate 6 bulan ke depan, butuh 6 bulan tanpa ada user buka app baru terjadi masalah. Command manual `php artisan schedules:generate-occurrences` masih available sebagai backup.

### Tambahan: Yang Tidak Ada di List Tapi Perlu Diperhatikan Saat Migrasi

- **APP_KEY** — Pastikan `.env` di VPS punya `APP_KEY` baru yang berbeda dari development. Jika digabung dengan web utama, pastikan tidak konflik.
- **Database connection** — Saat migrasi, pastikan DB credential di `.env` VPS sudah benar dan DB sudah di-migrate (`php artisan migrate`).
- **Storage permission** — Pastikan `storage/` dan `bootstrap/cache/` writable di VPS (`chmod -R 775 storage bootstrap/cache`).
- **Composer dependencies** — Jalankan `composer install --no-dev --optimize-autoloader` di VPS untuk production.
- **NPM build** — Jalankan `npm run build` untuk asset production (sudah dilakukan).

---

## Next Step
Poin 5, 6, dan 9 sudah selesai diimplementasi. Poin lain (1, 3, 4, 7, 8, 12) menunggu migrasi ke web utama.

---

## Log Implementasi

### Poin 5: Deadlock Retry — SELESAI (26 Aug 2026)

**Yang diubah:** Semua `DB::beginTransaction()` / `DB::commit()` / `DB::rollBack()` manual diganti dengan `DB::transaction(fn() => ..., 3)` yang auto-retry 3x pada deadlock.

**File yang diubah:**
- `app/Http/Controllers/ApprovalController.php` — method `updateFlowConfig` dan `decide`
- `app/Http/Controllers/RoleController.php` — method `bulkUpdate`
- `app/Http/Controllers/MaintenanceRecordController.php` — method `store`
- `app/Http/Controllers/MachineController.php` — method `bulkStore`
- `app/Http/Controllers/StockController.php` — method `import` dan `bulkLimit`
- `app/Http/Controllers/MachineComponentController.php` — method `bulkStore`, `bulkStoreGlobal`, `bulkImportIndicators`

**Cara kerja:** Jika terjadi deadlock (MySQL error 1213), Laravel otomatis retry hingga 3x dengan delay kecil antara retry. Jika masih gagal setelah 3x, exception dilempar dan user dapat error 500.

### Poin 6: N+1 / Pagination Approval — SELESAI (26 Aug 2026)

**Yang diubah:**
1. `app/Http/Controllers/ApprovalController.php` — method `index`: ditambah `->select([...])` untuk hanya load kolom yang dibutuhkan, mengurangi memory per record.
2. `database/migrations/2026_08_26_110000_add_status_date_index_to_maintenance_records.php` — composite index `(status, maintenance_date)` untuk mempercepat query approval list.

**Kenapa tidak pakai server-side pagination?** Frontend (`Approval.vue`) melakukan semua filtering client-side (city, month, search, status tabs, sorting). Server-side pagination akan membreak filtering ini. Sebagai gantinya, optimasi dilakukan dengan:
- Select hanya kolom yang dibutuhkan (reduce memory per record)
- Composite index untuk query yang lebih cepat
- Eager loading sudah ada (tidak ada N+1)

**Catatan untuk masa depan:** Jika data tumbuh > 1000 records, pertimbangkan untuk:
- Limit query ke 12-24 bulan terakhir
- Implement server-side pagination dengan frontend rewrite
- Atau switch ke server-side filtering

### Poin 9: Cron → On-Demand Generation — SELESAI (26 Aug 2026)

**Yang diubah:**
1. `app/Services/ScheduleOccurrenceGenerator.php` — tambah method `ensureGenerated()` dengan cache 1 hari
2. `app/Http/Controllers/MaintenanceScheduleController.php` — panggil `ensureGenerated()` di `notifications()`
3. `app/Console/Kernel.php` — cron job dikomentari (artisan command masih available)

**Cara kerja:** Saat user buka Dashboard, backend cek cache `schedule_generation_checked`. Jika masih ada (dalam 1 hari), skip. Jika expired, jalankan `generateUpcoming()` (idempotent) dan set cache baru. Maksimal 1x generate per hari.

**Jadwal yang sudah ada tidak berubah** — `generateUpcoming()` skip month yang sudah punya occurrence.
