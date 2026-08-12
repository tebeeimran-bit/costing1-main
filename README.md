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

## Arsitektur Internal

Satu PC atau VM internal menjalankan Laravel dan MySQL. PC pengguna hanya mengakses aplikasi melalui browser.

```text
PC pengguna ── jaringan LAN ──> PC/VM server Laravel ──> MySQL localhost
```

- Port web internal: `8000`
- Port MySQL: `3306`, hanya untuk localhost/server
- MySQL tidak perlu dibuka langsung kepada PC pengguna
- Data dan file aplikasi harus tetap berada di jaringan internal perusahaan

## Konfigurasi Environment

Salin `.env.production.example` menjadi `.env`, lalu sesuaikan nilainya:

```env
APP_ENV=production
APP_MODE=production
APP_DEBUG=false
APP_URL=http://IP-SERVER:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=db_costing
DB_USERNAME=costing_app
DB_PASSWORD=PASSWORD_DATABASE
```

Jangan menyimpan `.env` di repository publik. Batasi akses file hanya untuk administrator server.

## Instalasi Database Baru

Buat database dan user MySQL khusus aplikasi:

```sql
CREATE DATABASE db_costing
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

CREATE USER 'costing_app'@'localhost' IDENTIFIED BY 'PASSWORD_DATABASE';
GRANT ALL PRIVILEGES ON db_costing.* TO 'costing_app'@'localhost';
FLUSH PRIVILEGES;
```

Untuk database kosong, jalankan:

```powershell
php artisan migrate --seed --force
```

Jika paket serah-terima berisi `database/db_costing.sql`, restore data dengan:

```powershell
mysql -u costing_app -p db_costing < database/db_costing.sql
```

Jangan menjalankan `migrate:fresh` pada database yang sudah berisi data.

## Menyiapkan Aplikasi

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

## Menjalankan pada PC Server

MySQL harus aktif terlebih dahulu. Untuk MySQL portable yang digunakan pada PC pengembangan saat ini:

```powershell
Start-Process `
  -FilePath "C:\Users\tsz\scoop\apps\mysql\current\bin\mysqld.exe" `
  -ArgumentList "--defaults-file=C:\Users\tsz\scoop\persist\mysql\my.ini", "--bind-address=127.0.0.1" `
  -WindowStyle Hidden
```

Jalankan Laravel agar dapat diakses dari jaringan internal:

```powershell
php -S 0.0.0.0:8000 -t public public/router.php
```

`public/router.php` wajib digunakan pada PHP built-in server agar CSS, JavaScript, dan gambar dilayani sebagai file statis.

Cari IP server menggunakan:

```powershell
ipconfig
```

Jika IP server adalah `192.168.1.50`, pengguna membuka:

```text
http://192.168.1.50:8000
```

`127.0.0.1:8000` hanya dapat dibuka dari PC server. Setiap PC memiliki IP berbeda, tetapi harus berada dalam jaringan internal yang dapat saling terhubung.

## Kebutuhan Tim IT

- Tetapkan IP statis atau DHCP reservation untuk server.
- Izinkan inbound TCP port `8000` hanya dari jaringan internal/domain.
- Jangan mengekspos MySQL port `3306` ke internet atau PC pengguna biasa.
- Jalankan MySQL dan Laravel otomatis setelah restart.
- Nonaktifkan sleep otomatis pada PC server selama jam operasional.
- Batasi akses folder aplikasi, `.env`, backup, dan dump SQL.
- Gunakan HTTPS/reverse proxy internal jika tersedia.
- Siapkan backup database dan `storage/app` secara berkala.

Untuk operasional jangka panjang, gunakan PC atau VM internal khusus daripada workstation pengguna.

## Backup dan Restore

Contoh backup MySQL:

```powershell
mysqldump -u root -p --single-transaction --routines --triggers `
  --set-gtid-purged=OFF db_costing > db_costing-backup.sql
```

Contoh restore:

```powershell
mysql -u root -p db_costing < db_costing-backup.sql
```

Backup juga folder berikut karena dapat berisi dokumen project:

```text
storage/app
```

Simpan backup di media internal terpisah dan uji proses restore secara berkala.

## Deployment Pembaruan

Sebelum pembaruan, backup database dan `storage/app`. Setelah source diperbarui:

```powershell
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan optimize
php artisan app:deployment-check
```

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
