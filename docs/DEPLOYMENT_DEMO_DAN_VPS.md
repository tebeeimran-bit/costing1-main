# Instalasi Demo SQLite dan Production MySQL

Project ini memakai satu codebase dan dua database yang sengaja terpisah:

- PC kantor: `APP_MODE=demo` dan `DB_CONNECTION=sqlite`.
- VPS: `APP_MODE=production` dan `DB_CONNECTION=mysql`.

Data demo tidak otomatis tersinkron ke VPS. Untuk pemakaian 9 user, seluruh user harus membuka alamat VPS agar bekerja pada database production yang sama.

## Demo di PC kantor

1. Salin `.env.example` menjadi `.env`.
2. Pastikan `APP_MODE=demo` dan `DB_CONNECTION=sqlite`.
3. Buat file kosong `database/database.sqlite` jika belum ada.
4. Jalankan:

```powershell
php artisan key:generate
php artisan migrate --seed
php artisan app:deployment-check
php artisan serve
```

Halaman aplikasi akan menampilkan banner `MODE DEMO` agar pengguna tidak mengira data lokal adalah data production.

## Production di VPS

Persyaratan VPS: PHP 8.2+, ekstensi PHP yang diperlukan Laravel, Composer, MySQL 8/MariaDB yang kompatibel, serta Nginx atau Apache. Document root web server harus diarahkan ke folder `public`, bukan root project.

1. Clone project ke VPS dan jalankan `composer install --no-dev --optimize-autoloader`.
2. Salin `.env.production.example` menjadi `.env`.
3. Isi URL, kredensial MySQL, email admin awal, dan password kuat. Jangan commit `.env`.
4. Jalankan:

```bash
php artisan key:generate
php artisan migrate --seed --force
php artisan storage:link
php artisan optimize
php artisan app:deployment-check
```

5. Atur permission tulis web server hanya untuk `storage` dan `bootstrap/cache`.
6. Jalankan queue worker melalui Supervisor/systemd dan scheduler Laravel melalui cron bila fitur tersebut digunakan.
7. Setelah admin awal berhasil dibuat, kosongkan `ADMIN_PASSWORD` dari `.env`.

## Deploy pembaruan

Sebelum deploy, backup database dan folder `storage/app`. Setelah mengambil kode terbaru, jalankan:

```bash
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan optimize
php artisan app:deployment-check
```

Jangan menjalankan `migrate:fresh`, `db:wipe`, atau mengganti `APP_KEY` pada production. Ketiganya dapat menghilangkan data atau membuat session/data terenkripsi tidak dapat dibaca.

## Backup minimum production

Backup harus disimpan di lokasi lain, bukan hanya pada VPS yang sama:

```bash
mysqldump --single-transaction --routines --triggers -u costing_app -p costing_production > costing-YYYYMMDD.sql
tar -czf costing-storage-YYYYMMDD.tar.gz storage/app
```

Lakukan backup harian, simpan beberapa versi, dan uji proses restore secara berkala.

## Template Excel

Template yang sudah ada di repository ikut ter-deploy bersama source code. Template tidak perlu dikirim ulang kecuali isi atau formatnya berubah. Template Bulk COGM tetap perlu ditambahkan ketika file finalnya sudah tersedia.
