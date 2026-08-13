# Costing System

Aplikasi web internal berbasis Laravel untuk mengelola proses costing manufaktur, dokumen project, approval COGM, master data, serta laporan operasional. Aplikasi dirancang untuk digunakan oleh beberapa role melalui jaringan internal perusahaan.

## Fitur Utama

- Dashboard project dan potensial cost berdasarkan status A00, A04, dan A05.
- Control Project A00 dan tracking revisi dokumen.
- Inbox Drawing, Breakdown, Costing, New Part Request, dan Marketing.
- Form costing untuk material, process, overhead, scrap, serta COGM.
- Workflow approval coordinator dan pengiriman hasil ke marketing.
- Master data material, wire, tube, customer, plant, PIC, business category, kurs, dan cycle time.
- Import/export Excel untuk partlist, UMH, material, COGM, dan master data.
- Laporan resume COGM, analisis tren, dan unpriced parts.
- Manajemen user, role, serta permission menu.

## Teknologi

- PHP 8.2 atau lebih baru
- Laravel 12
- MySQL 8 atau versi kompatibel
- Composer
- Vite/Node.js hanya diperlukan jika asset frontend akan dibangun ulang

## Environment Aplikasi

Aplikasi memiliki dua environment yang terpisah:

- **Development** digunakan untuk pengembangan fitur, debugging, migrasi percobaan, dan pengujian.
- **Production** digunakan untuk aktivitas operasional dan data aktual pengguna.

```text
Developer --> Server Development --> Database Development
Pengguna  --> Server Production  --> Database Production
```

Kode, konfigurasi, database, dan file unggahan setiap environment harus dipisahkan. Perubahan selalu diverifikasi di development sebelum diterapkan ke production.

### Memilih Environment yang Benar

Gunakan panduan berikut agar data tidak tertukar:

| Kegiatan | Folder yang dijalankan | Database |
| --- | --- | --- |
| Membuat atau memperbaiki fitur | `C:\Users\tsz\Costing-System\Development` | `db_costing_dev` |
| Menguji fitur dan memasukkan data percobaan | `C:\Users\tsz\Costing-System\Development` | `db_costing_dev` |
| Memasukkan data operasional sebenarnya | `C:\Users\tsz\Costing-System\Production` | `db_costing` |
| Memberikan akses kepada pengguna | `C:\Users\tsz\Costing-System\Production` | `db_costing` |

Nama folder tidak menentukan database. Database yang digunakan ditentukan oleh nilai `DB_DATABASE` di file `.env` dalam masing-masing folder. Selalu periksa folder aktif dan `.env` sebelum menjalankan migration atau memasukkan data penting.

## Konfigurasi Environment

Gunakan file environment sesuai server. Salin `.env.example` untuk development atau `.env.production.example` untuk production menjadi `.env`, lalu sesuaikan nilainya.

Contoh development:

```env
APP_ENV=local
APP_MODE=demo
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=db_costing_dev
DB_USERNAME=costing_dev
DB_PASSWORD=PASSWORD_DATABASE_DEVELOPMENT
```

`APP_MODE=demo` merupakan mode non-production yang digunakan aplikasi pada server development.

Contoh production:

```env
APP_ENV=production
APP_MODE=production
APP_DEBUG=false
APP_URL=http://IP-SERVER

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=db_costing
DB_USERNAME=costing_app
DB_PASSWORD=PASSWORD_DATABASE
```

Jangan menyimpan `.env` di repository. Gunakan kredensial database yang berbeda untuk development dan production.

## Menyiapkan Database Development

Login ke MySQL menggunakan akun administrator:

```powershell
mysql -u root -p
```

Buat database dan akun khusus Development. Ganti contoh password berikut dengan password yang kuat:

```sql
CREATE DATABASE db_costing_dev
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

CREATE USER 'costing_dev'@'localhost'
IDENTIFIED BY 'PASSWORD_DATABASE_DEVELOPMENT';

GRANT ALL PRIVILEGES ON db_costing_dev.*
TO 'costing_dev'@'localhost';

FLUSH PRIVILEGES;
```

Pastikan `.env` di folder Development menggunakan `DB_DATABASE=db_costing_dev`, lalu jalankan:

```powershell
cd C:\Users\tsz\Costing-System\Development
php artisan key:generate
php artisan config:clear
php artisan migrate --seed
php artisan migrate:status
```

Data yang dimasukkan melalui aplikasi Development hanya tersimpan di `db_costing_dev` dan tidak otomatis masuk ke Production.

## Menyiapkan Database Production

Buat database dan akun MySQL khusus Production:

```sql
CREATE DATABASE db_costing
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

CREATE USER 'costing_app'@'localhost' IDENTIFIED BY 'PASSWORD_DATABASE';
GRANT ALL PRIVILEGES ON db_costing.* TO 'costing_app'@'localhost';
FLUSH PRIVILEGES;
```

Untuk database Production yang masih kosong, jalankan:

```powershell
cd C:\Users\tsz\Costing-System\Production
php artisan migrate --seed --force
```

Jika tersedia dump awal `database/db_costing.sql`, restore data dengan:

```powershell
mysql -u costing_app -p db_costing < database/db_costing.sql
```

Jangan menjalankan `migrate:fresh` pada Production karena perintah tersebut menghapus seluruh tabel dan data.

## Menyiapkan Aplikasi Production

Jika folder `vendor` belum tersedia:

```powershell
composer install --no-dev --optimize-autoloader
```

Kemudian jalankan:

```powershell
php artisan key:generate
php artisan storage:link
php artisan migrate --force
php artisan optimize
php artisan app:deployment-check
```

Jangan mengganti `APP_KEY` setelah aplikasi mulai menyimpan data/session production.

## Menjalankan Environment Development

Pastikan MySQL aktif dan `.env` mengarah ke database development. Buka PowerShell, masuk ke folder aplikasi, lalu jalankan server:

```powershell
cd C:\Users\tsz\Costing-System\Development
php artisan serve
```

Aplikasi development dapat dibuka melalui `http://127.0.0.1:8000`. Jalankan test suite sebelum menyiapkan rilis ke production.

Development hanya digunakan untuk pengembangan dan pengujian. Jangan membagikan alamat development kepada pengguna operasional.

## Menjalankan Environment Production Secara Manual

Gunakan bagian ini untuk memasukkan data operasional sebenarnya dan memberikan akses kepada pengguna. Pastikan MySQL aktif dan `.env` pada folder Production menggunakan `DB_DATABASE=db_costing`. Buka PowerShell, lalu jalankan:

```powershell
cd C:\Users\tsz\Costing-System\Production
php artisan serve --host=0.0.0.0 --port=8000 --no-reload
```
http://127.0.0.1:8000
Cari alamat IPv4 server dengan `ipconfig`, lalu bagikan alamat berikut kepada pengguna:

```text
http://IP-SERVER-PRODUCTION:8000
```

Contoh: `http://192.168.1.50:8000`. Izinkan koneksi masuk TCP port `8000` pada Windows Firewall hanya dari jaringan internal. PowerShell harus tetap terbuka selama aplikasi digunakan. Tekan `Ctrl+C` untuk mematikan aplikasi.

## Menjalankan Environment Production sebagai Service

Untuk layanan production yang berjalan otomatis, gunakan Apache atau Nginx dan arahkan document root ke folder `public`. Aktifkan HTTPS dan gunakan `APP_DEBUG=false`. Cara ini lebih sesuai untuk pemakaian rutin daripada menjalankan server secara manual melalui PowerShell.

Apache/Nginx dan MySQL sebaiknya dijalankan sebagai Windows Service dengan tipe startup **Automatic**, sehingga aplikasi kembali tersedia setelah server dinyalakan atau direstart. Untuk memeriksa dan menyalakannya secara manual:

1. Buka **Services** melalui menu Start atau jalankan `services.msc`.
2. Pastikan service Apache/Nginx dan MySQL berstatus **Running**.
3. Jika belum aktif, pilih service tersebut lalu klik **Start**.
4. Buka alamat aplikasi production dari browser server untuk memastikan halaman login tampil.

### Membagikan Akses Production

Pastikan server memiliki alamat IP tetap atau hostname internal. Izinkan port `8000` untuk server manual, atau port `80` (HTTP)/`443` (HTTPS) untuk Apache/Nginx, hanya dari jaringan internal. Sesuaikan `APP_URL` di `.env` dengan cara menjalankan server, misalnya:

```env
APP_URL=http://192.168.1.50:8000
```

Kemudian jalankan:

```powershell
php artisan optimize
php artisan app:deployment-check
```

Bagikan URL dari `APP_URL` kepada pengguna. Perangkat pengguna harus terhubung ke jaringan yang dapat menjangkau server. Port database `3306` tidak perlu dibuka atau dibagikan karena pengguna mengakses aplikasi melalui browser.

Panduan konfigurasi lengkap tersedia di [Deployment On-Premise Windows](docs/DEPLOYMENT-ON-PREMISE-WINDOWS.md).

## Backup dan Restore

Contoh backup MySQL:

```powershell
mysqldump -u costing_app -p --single-transaction --routines --triggers `
  --set-gtid-purged=OFF db_costing > db_costing-backup.sql
```

Contoh restore:

```powershell
mysql -u costing_app -p db_costing < db_costing-backup.sql
```

Backup juga folder berikut karena dapat berisi dokumen project:

```text
storage/app
```

Simpan backup di media internal terpisah dan uji proses restore secara berkala.

## Deployment Development ke Production

Data Development tidak perlu dan tidak boleh disalin ke Production. Yang dipindahkan hanya perubahan source code, migration baru, dependency, dan asset aplikasi.

Urutan pembaruan yang aman:

1. Selesaikan fitur dan pengujian di folder Development.
2. Jalankan `php artisan test` di Development.
3. Backup database Production dan folder `storage/app`.
4. Matikan aplikasi Production dengan `Ctrl+C` jika dijalankan secara manual.
5. Terapkan perubahan source code dari Development ke Production. Jangan menimpa `.env`, database, `storage/app`, atau file unggahan Production.
6. Buka PowerShell di folder Production dan jalankan:

```powershell
cd C:\Users\tsz\Costing-System\Production
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan optimize
php artisan app:deployment-check
```

7. Nyalakan kembali aplikasi Production dan periksa fitur baru serta data lama.

`php artisan migrate --force` hanya menerapkan perubahan struktur database dari migration baru. Perintah tersebut tidak menyalin data percobaan dari `db_costing_dev` ke `db_costing`.

## Pemeriksaan dan Pengujian

Pemeriksaan konfigurasi production:

```powershell
php artisan app:deployment-check
```

Menjalankan test suite:

```powershell
php artisan test
```

Test menggunakan SQLite sementara melalui `phpunit.xml`, sehingga tidak mengubah database MySQL production.

## Struktur Folder

```text
app/          Controller, model, service, middleware, dan request
bootstrap/    Bootstrap dan cache framework
config/       Konfigurasi Laravel
database/     Migration, seeder, factory, dan dump SQL handoff
public/       Document root, entry point, dan asset publik
resources/    Blade view serta source frontend
routes/       Definisi route aplikasi
storage/      Upload, template, cache, session, dan log
tests/        Test unit dan feature
vendor/       Dependency PHP
```

Document root web server harus diarahkan ke folder `public`, bukan root project.

## Route Utama

- `/login` — autentikasi
- `/` — dashboard
- `/project` — daftar dan progress project
- `/tracking-documents` — tracking dokumen
- `/form` — form costing
- `/database` — master data
- `/resume-cogm` — resume COGM
- `/analisis-tren` — analisis tren
- `/laporan` — laporan
- `/marketing/cogm-inbox` — inbox marketing

## Keamanan

- Aplikasi dan database hanya untuk jaringan internal perusahaan.
- Jangan mengirim `.env`, dump SQL, atau backup melalui kanal publik.
- Gunakan akun MySQL khusus aplikasi, bukan root.
- Ganti password yang pernah terlihat atau dibagikan setelah deployment.
- Pastikan `APP_DEBUG=false` pada production.
- Jangan membuka port MySQL ke internet.
