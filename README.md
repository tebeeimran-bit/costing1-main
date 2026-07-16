# Costing System

Aplikasi web internal berbasis Laravel untuk mengelola proses costing manufaktur, mulai dari penerimaan dokumen project, input dan validasi data costing, pengelolaan master data, approval COGM, sampai laporan operasional.

## Ringkasan Fitur

- Dashboard monitoring costing dan dokumen project.
- Tracking dokumen engineering dan revisi project.
- Form costing dengan komponen material, process, overhead, administrasi, dan COGM.
- Database master untuk parts/material, wires, tubes, customers, plants, PIC, business category, rate kurs, dan cycle time.
- Workflow approval costing ke coordinator dan pengiriman ke marketing.
- Laporan resume COGM, analisis tren, unpriced parts, dan inbox marketing.
- Import template Excel untuk parts, wires, part list, UMH, dan COGM.
- Operations Center, System Center, role-based workspace, dan Export Center.
- Release readiness, business-day SLA, monitoring, announcement, digital sign-off, delegation, dan backup/restore.

## Teknologi

- PHP 8.3
- Laravel 12
- MySQL 8 untuk konfigurasi lokal aktif
- Vite untuk asset frontend
- Composer dan npm sebagai dependency manager

## Database Lokal

Konfigurasi aktif dibaca dari `.env`. Pada kondisi repository ini, localhost memakai MySQL:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=db_costing
DB_USERNAME=root
DB_PASSWORD=
```

Catatan:

- `config/database.php` memiliki fallback SQLite jika `DB_CONNECTION` tidak diset.
- File `database/database.sqlite` masih tersedia sebagai opsi lokal/backup, tetapi bukan database aktif selama `.env` memakai MySQL.
- Dump SQL dan file backup lama sudah dipindahkan ke `Dump/` agar root project tetap bersih.

## Menjalankan di Localhost

1. Install dependency PHP dan JavaScript:

```bash
composer install
npm install
```

2. Siapkan environment:

```bash
cp .env.example .env
php artisan key:generate
```

3. Pastikan `.env` mengarah ke database yang benar. Untuk setup saat ini gunakan MySQL `db_costing` seperti bagian Database Lokal.

4. Jalankan migrasi jika database masih kosong:

```bash
php artisan migrate --seed
```

5. Jalankan aplikasi:

```bash
php artisan serve --host=0.0.0.0 --port=8000
npm run dev
```

Aplikasi dapat dibuka melalui `http://localhost:8000`.

## Quality Gate dan Staging

Workflow `.github/workflows/ci.yml` otomatis menjalankan validasi dependency, audit production, migration, seluruh PHPUnit test, Blade cache, Vite build, serta Playwright browser/mobile test pada push dan pull request.

Deployment staging menggunakan `.github/workflows/staging.yml` dan GitHub Environment bernama `staging`. Konfigurasikan:

- environment variable `STAGING_URL`;
- secret `STAGING_DEPLOY_WEBHOOK` bila server mempunyai deployment hook;
- required reviewer pada environment untuk approval sebelum deployment.

Gunakan `.env.staging.example` sebagai template tanpa memasukkan credential ke repository.

Scheduler production wajib berjalan setiap menit:

```cron
* * * * * php /path/to/artisan schedule:run
```

Scheduler menjalankan backup terverifikasi, snapshot SLA harian, scheduled export, dan pembersihan telemetry lama. Backup mendukung SQLite serta MySQL/MariaDB melalui `mysqldump`; atur `MYSQLDUMP_PATH` dan `MYSQL_PATH` bila executable tidak tersedia di PATH. Restore selalu memverifikasi checksum dan membuat safety backup terlebih dahulu.

Browser quality gate menggunakan database E2E terpisah untuk menguji login, pembuatan project, upload Partlist/UMH, submit approval, digital sign-off, pengiriman ke Marketing, viewport mobile, dan performance budget tanpa menyentuh data staging/production.

## Struktur Folder Penting

```text
app/                 Logic aplikasi: controller, model, service, middleware, request
bootstrap/           Bootstrap Laravel
config/              Konfigurasi aplikasi
database/            Migration, factory, seeder, dan SQLite optional
public/              Entry point web dan asset publik
resources/           Blade view, CSS, dan JavaScript source
routes/              Route web dan console command
storage/             Cache, compiled view, log runtime, dan file storage Laravel
tests/               Test aplikasi
Dump/                Arsip file non-runtime hasil cleanup
```

## Folder Dump

`Dump/` adalah folder arsip untuk file yang tidak diperlukan langsung oleh runtime aplikasi, tetapi sengaja tidak dihapus. Isinya dapat berupa:

- dokumen ringkasan lama,
- dump SQL dan backup database lama,
- arsip `.zip` atau `.tar.gz`,
- file log/error lokal,
- cache sementara,
- file eksperimen atau backup route/view/controller.

Folder ini sudah masuk `.gitignore`, sehingga tidak ikut masuk tracking baru. Jika perlu restore salah satu file, pindahkan kembali file terkait dari `Dump/` ke lokasi asalnya secara manual.

## Route Utama

- `/login` untuk autentikasi.
- `/` untuk dashboard.
- `/project` dan `/tracking-documents` untuk daftar project dan dokumen.
- `/form` untuk form costing.
- `/database` untuk master data.
- `/resume-cogm`, `/analisis-tren`, dan `/laporan` untuk laporan.
- `/marketing/cogm-inbox` untuk inbox COGM marketing.

Route debug `/test` dan halaman preview `/approval-flow-preview` sudah dilepas dari routing aktif.

## Costing Assistant

Aplikasi menyediakan Costing Assistant lokal tanpa AI cloud/API eksternal. Assistant ini bekerja dengan knowledge base, rule checklist, dan validasi file lokal sehingga data kantor tidak dikirim keluar aplikasi.

Fitur utama:

- panel assistant responsif untuk role `admin` dan `admin_costing`,
- jawaban panduan berdasarkan topic/FAQ dan keyword,
- rule dinamis seperti unpriced parts aktif, waiting approval, dan rate kurs bulan berjalan,
- menu admin `Assistant Training` untuk menambah topic, rule, dan template file,
- validasi upload Excel/PDF lokal melalui tab `File Check`,
- workflow `Preview New Project` dan `Buat New Project` dari partlist Excel yang memiliki metadata project.

Data training disimpan di tabel berikut:

```text
assistant_topics
assistant_rules
assistant_file_templates
```

Admin dapat membuka `Assistant Training` dari sidebar Administrasi. Panel assistant hanya dirender untuk `admin` dan `admin_costing`; role lain tidak melihat panel dan endpoint assistant akan menolak akses.

Validasi file saat ini mendukung:

- Excel `.xlsx`, `.xls`, `.csv`: baca sheet aktif, header, jumlah baris, kolom wajib, cell wajib kosong, duplikasi sederhana berdasarkan template, serta membuat New Project dari partlist jika kolom `customer`, `model`, `part_no`, dan `part_name` terbaca.
- PDF `.pdf`: cek format, MIME type, dan ukuran dasar. Isi PDF tidak diproses OCR dan tidak dikirim ke layanan luar.

## Maintenance

Backup SQLite manual tersedia jika aplikasi dijalankan dengan koneksi SQLite:

```bash
php artisan db:backup-sqlite --keep=14
```

Scheduler Laravel dapat dijalankan di development dengan:

```bash
php artisan schedule:work
```

Untuk production, gunakan cron yang menjalankan `php artisan schedule:run` setiap menit.
