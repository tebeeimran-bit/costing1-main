# Deployment On-Premise Windows (5–20 Pengguna)

Dokumen ini adalah panduan serah-terima kepada IT untuk menjalankan Costing System secara rutin di jaringan internal. Aplikasi dan database tetap berada di kantor dan tidak perlu dibuka ke internet.

## Arsitektur

```text
Browser pengguna -- LAN/Wi-Fi internal --> Apache :80 --> Laravel --> MySQL localhost :3306
```

- Siapkan satu PC server khusus: CPU 4 core, RAM minimal 8 GB, SSD, LAN, dan UPS.
- Gunakan Windows 10/11 Pro atau Windows Server yang masih didukung.
- Server tidak boleh sleep/hibernate dan harus mempunyai IP statis atau DHCP reservation.
- MySQL hanya mendengarkan localhost. Jangan membuka port `3306` di firewall.
- Firewall web hanya mengizinkan TCP `80`/`443` dari subnet internal perusahaan.

## Software server

Pasang Apache 2.4, PHP 8.2+ 64-bit, Composer, dan MySQL 8/MariaDB yang kompatibel. Aktifkan ekstensi PHP: `ctype`, `curl`, `dom`, `fileinfo`, `filter`, `gd`, `intl`, `mbstring`, `openssl`, `pdo_mysql`, `session`, `simplexml`, `tokenizer`, `xml`, `xmlreader`, `xmlwriter`, dan `zip`.

Node.js tidak perlu terpasang di server apabila folder `public/build` sudah dibuat sebelum deployment.

## Apache VirtualHost

Document root wajib menunjuk ke folder `public`, bukan root repository. Contoh (sesuaikan path):

```apache
<VirtualHost *:80>
    ServerName costing.internal
    DocumentRoot "C:/apps/costing/public"

    <Directory "C:/apps/costing/public">
        AllowOverride All
        Options -Indexes +FollowSymLinks
        Require ip 192.168.144.0/24
    </Directory>

    ErrorLog "logs/costing-error.log"
    CustomLog "logs/costing-access.log" combined
</VirtualHost>
```

Aktifkan `mod_rewrite`. Ganti subnet contoh dengan subnet kantor yang diberikan IT. Jalankan Apache dan MySQL sebagai Windows Service agar otomatis hidup setelah restart.

## Database dan aplikasi

1. Buat database dan user MySQL khusus sesuai README.
2. Salin `.env.production.example` menjadi `.env`, lalu isi IP/hostname, database, password kuat, dan akun admin awal.
3. Jangan mengganti `APP_KEY` setelah sistem mulai digunakan.
4. Jalankan dari folder aplikasi:

```powershell
composer install --no-dev --optimize-autoloader
npm ci
npm run build
php artisan key:generate
php artisan storage:link
php artisan migrate --seed --force
php artisan optimize
php artisan app:deployment-check
```

Setelah admin pertama berhasil dibuat, kosongkan `ADMIN_EMAIL` dan `ADMIN_PASSWORD` dari `.env`, kemudian jalankan `php artisan config:cache`.

## Queue dan scheduler

Buat dua Windows Scheduled Task dengan akun service yang memiliki hak baca aplikasi dan hak tulis `storage` serta `bootstrap/cache`:

1. Scheduler, dijalankan setiap 1 menit:

```powershell
php C:\apps\costing\artisan schedule:run
```

2. Queue worker, dijalankan saat startup dan otomatis restart jika gagal:

```powershell
php C:\apps\costing\artisan queue:work --sleep=3 --tries=3 --timeout=300 --max-time=3600
```

Setelah setiap pembaruan aplikasi, jalankan `php artisan queue:restart`.

## Backup wajib

Backup setiap malam harus mencakup:

- dump MySQL dengan `mysqldump --single-transaction`;
- seluruh folder `storage/app`;
- file `.env`, disimpan terenkripsi dan dibatasi untuk administrator.

Simpan backup pada media internal yang berbeda dari SSD server. Terapkan retensi harian dan uji restore sekurangnya setiap tiga bulan. Backup yang belum pernah diuji restore belum dapat dianggap valid.

## Pemeriksaan sebelum go-live

- `php artisan app:deployment-check` selesai tanpa error.
- `php artisan test` lulus menggunakan database test, bukan database production.
- Dua pengguna dapat login dan menyimpan data secara bersamaan.
- Upload/download Excel dan PDF berhasil.
- Restart server: Apache, MySQL, queue, dan scheduler kembali aktif otomatis.
- PC LAN dan Wi-Fi internal dapat membuka aplikasi.
- PC di luar subnet internal tidak dapat membuka aplikasi.
- Backup dapat dibuat dan dipulihkan pada mesin uji.

## Operasional

Untuk maintenance terencana:

```powershell
php artisan down --secret="TOKEN-RAHASIA-SEMENTARA"
php artisan migrate --force
php artisan optimize
php artisan queue:restart
php artisan up
```

Pantau `storage/logs/laravel.log`, kapasitas SSD, status Windows Service, hasil backup, dan Windows Event Viewer. Jangan menghapus database atau menjalankan `migrate:fresh` pada server production.
