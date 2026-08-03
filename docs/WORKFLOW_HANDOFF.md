# Handoff Pengembangan Workflow Project Costing

Terakhir diperbarui: 3 Agustus 2026 (Asia/Jakarta)

Dokumen ini merangkum pembahasan dan implementasi agar pengembangan dapat dilanjutkan dari perangkat atau sesi lain tanpa mengulang konteks dari awal.

## Tujuan Sistem

Membangun workflow project yang berkesinambungan dari project baru sampai COGM diterima Marketing:

```text
Admin Control Project
  A00 diterbitkan
        ↓
Admin Document Control
  Registrasi dan distribusi drawing
        ↓
Admin Costing
  Breakdown → Costing → Submit
        ↓
Admin Marketing
  Menerima COGM
```

Menu **Project** berfungsi sebagai monitoring bersama. Pekerjaan operasional dilakukan dari inbox masing-masing role.

## Pembagian Tanggung Jawab

| Tahap | Role | Tanggung jawab | Handoff |
|---|---|---|---|
| A00 | Admin Control Project | Membuat dan menerbitkan A00 | Membuat tugas Drawing untuk Document Control |
| Drawing | Admin Document Control | Registrasi dan distribusi drawing | Membuat tugas Breakdown untuk Admin Costing |
| Breakdown | Admin Costing | Input/import breakdown material atau BOM | Dilanjutkan ke Costing |
| Costing | Admin Costing | Menghitung dan melengkapi data costing | Siap disubmit |
| Submit | Admin Costing | Submit hasil costing/COGM | Masuk ke inbox Marketing |
| COGM | Admin Marketing | Menerima dan memproses COGM | Workflow selesai |

## Progress Project

Progress ditampilkan pada daftar Project:

```text
A00 → Drawing → Breakdown → Costing → Submit → COGM
```

- Hijau: selesai.
- Biru: tahap aktif/sedang diproses.
- Abu-abu: belum dimulai.
- Progress dapat diklik untuk melihat status, tanggal, dan PIC setiap tahap.

Sumber perhitungan saat ini:

- **A00**: revision memiliki A00 atau status A00 issued.
- **Drawing**: registrasi Document Control terhubung ke project/revision.
- **Breakdown**: material breakdown tersedia pada data costing.
- **Costing**: data costing tersedia.
- **Submit**: approval memiliki `submitted_at`.
- **COGM**: submission COGM ke Marketing memiliki `submitted_at`.

## Fitur yang Sudah Selesai

### A00 / Control Project

- Role `admin_control_project` dan permission `control_project`.
- Menu **Control Project — A00**.
- Form A00 tampil sebagai modal.
- Nomor dokumen masih diinput manual.
- Mendukung beberapa item/model/assy dalam satu A00.
- Setiap item menghasilkan satu Document Project dan revision V0.
- Format tampilan/cetak A00 dibuat menyerupai PDF referensi.
- Logo Dharma menggunakan `public/images/logo-dharma.svg`.
- Approval sementara memakai stempel teks `APPROVED`; file tanda tangan PNG akan diberikan kemudian.
- Setelah A00 diterbitkan, sistem otomatis membuat workflow task Drawing untuk setiap item.

### Document Control

- Satu menu sidebar: **Document Control**.
- Halaman utama berisi:
  - tugas Registrasi & Distribusi Drawing dari A00;
  - Daftar Registrasi Drawing yang terhubung ke Project.
- Tombol **Proses Drawing** membuka form modal pada halaman yang sama.
- Customer, model, part number, part name, revision, A00, dan kategori bisnis terisi otomatis.
- Penyimpanan task yang sama bersifat idempotent: submit ulang memperbarui registrasi, tidak membuat duplikasi.
- Tombol **Detail** membuka form edit lengkap sebagai modal tanpa header ganda.
- Registrasi terhubung langsung melalui:
  - `document_project_id`;
  - `document_revision_id`;
  - `workflow_task_id`.
- Registrasi legacy yang tidak memiliki pasangan Project sudah dibersihkan.

### Daftar Project

- Satu project ditampilkan dalam satu baris compact.
- PIC Engineering dan Marketing digabung dalam satu kolom.
- Aksi dipindahkan ke menu titik tiga.
- Kolom progress memiliki enam tahap dan panel detail.
- Modal detail progress sudah diposisikan di tengah layar.

## Data yang Dibersihkan

Sebanyak 44 registrasi Drawing legacy yang tidak ditemukan pada daftar Project telah dihapus. Sebelum penghapusan, data dicadangkan ke:

```text
storage/app/private/backups/document-control-orphans-20260803-165245.json
```

Tersisa satu registrasi aktif yang terhubung ke Project saat pembersihan dilakukan.

## Struktur Teknis Utama

### Model baru

- `ProjectA00Form`
- `ProjectA00Item`
- `ProjectWorkflowTask`
- `DocumentControlRegistration`
- `DocumentControlColumn`
- `DocumentControlCustomCell`

### Controller utama

- `ProjectA00Controller`
- `DocumentControlInboxController`
- `DocumentControlRegistrationController`
- `ProjectGroupController` untuk monitoring progress.

### Workflow Task

Tabel `project_workflow_tasks` menyimpan:

- project dan revision;
- tahap workflow;
- role tujuan;
- status `pending`, `in_progress`, atau `completed`;
- user yang menangani;
- waktu tersedia, mulai, dan selesai;
- metadata handoff.

Stage yang sudah didefinisikan:

- `drawing`
- `breakdown`
- `costing`
- `cogm`

## Status Git

- Repository: `https://github.com/tebeeimran-bit/costing1-main.git`
- Branch: `main`
- Commit fitur terakhir sebelum dokumen ini: `e63da63`
- Pesan commit: `feat: add A00 and document control workflow`
- `package-lock.json` memiliki perubahan lokal yang sengaja tidak dimasukkan karena hanya mengubah nama package.

## Pengujian

Pengujian terakhir untuk workflow A00/Document Control:

```text
2 tests passed
12 assertions
```

Full test suite sebelumnya:

```text
23 tests passed
71 assertions
3 deprecation notices dari PHP 8.5/reflection
```

## Pekerjaan Berikutnya: Breakdown

Tahap berikutnya adalah menyelesaikan handoff **Document Control → Admin Costing**.

Urutan implementasi yang disepakati:

1. Tambahkan aksi **Selesaikan Distribusi Drawing**.
2. Validasi persyaratan distribusi drawing sebelum selesai.
3. Tandai workflow task Drawing sebagai `completed`.
4. Catat user dan waktu penyelesaian.
5. Buat workflow task `breakdown` untuk role `admin_costing`.
6. Tambahkan menu/inbox Breakdown untuk Admin Costing.
7. Tampilkan data A00 dan Drawing sebagai referensi di workspace Breakdown.
8. Hubungkan input/import material breakdown ke project dan revision.
9. Setelah Breakdown selesai, aktifkan tahap Costing.
10. Setelah Costing disubmit, buat handoff ke COGM Inbox Marketing.

## Keputusan Penting untuk Tahap Breakdown

- Breakdown dikerjakan oleh **Admin Costing**, bukan Engineering.
- Project/revision harus menjadi identitas utama antar-modul; jangan hanya mencocokkan teks part number.
- Progress tidak boleh diubah manual. Status berubah melalui tindakan bisnis seperti menerbitkan A00, menyelesaikan distribusi, menyelesaikan breakdown, dan submit costing.
- Setiap handoff harus menghasilkan task pada inbox role berikutnya.
- Data tahap sebelumnya harus dapat dilihat sebagai referensi, tetapi tidak diedit oleh role berikutnya.

## Cara Melanjutkan di Sesi Baru

Gunakan instruksi berikut:

```text
Baca docs/WORKFLOW_HANDOFF.md lalu lanjutkan pekerjaan berikutnya pada bagian "Pekerjaan Berikutnya: Breakdown". Pertahankan workflow, role ownership, relasi project/revision, dan desain compact yang sudah disepakati.
```

