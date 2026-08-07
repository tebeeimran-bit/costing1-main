# Implementation Plan Bulky COGM

## 1. Tujuan dan Batasan

Bulky COGM digunakan ketika satu dokumen A00 memiliki beberapa item project. A00 menjadi induk proses, sedangkan data project, revision, costing, material, labor, overhead, approval, dan histori tetap tersimpan per item.

Prinsip yang tidak boleh dilanggar:

- Satu A00 memiliki satu Costing Group.
- Satu item A00 tetap terhubung ke satu project dan satu revision aktif.
- Data costing antar-item tidak boleh digabung atau saling menimpa.
- Draft dan submission final adalah dua artefak yang berbeda.
- File dan snapshot yang sudah pernah dikirim tidak boleh ditimpa.
- Perubahan setelah submission wajib menghasilkan versi baru dan pemberitahuan kepada PIC terkait.
- Alur normal satu item harus tetap berjalan selama fitur Bulky dikembangkan.

## 2. Keputusan Produk yang Sudah Disepakati

1. Bulky berlaku pada level A00.
2. Export Bulky menggunakan template Excel yang akan diberikan kemudian.
3. Draft boleh dibuat dan dibagikan walaupun harga belum lengkap, tetapi tidak boleh dianggap final.
4. A00 mempunyai PIC Engineering dan PIC Marketing utama.
5. Setiap item Bulky boleh mempunyai PIC sendiri. Jika kosong, PIC item mengikuti PIC A00.
6. Ringkasan menampilkan COGM per unit, quantity/volume, dan extended COGM.
7. Perubahan satu item membuat versi group baru, tetapi hanya item tersebut yang mempunyai revision snapshot baru.
8. Item boleh ditambahkan setelah submission dengan alasan wajib, versioning, perubahan status, audit log, dan pemberitahuan kepada seluruh PIC.

## 3. Definisi Perhitungan

Untuk menghindari total yang ambigu, sistem menyimpan dan menampilkan dua nilai:

- `unit_cogm`: COGM untuk satu unit item.
- `extended_cogm`: `unit_cogm x quantity`.

Aturan total group:

- `total_unit_cogm` adalah penjumlahan COGM per unit seluruh item dan hanya digunakan sebagai informasi.
- `total_extended_cogm` adalah penjumlahan extended COGM seluruh item dan menjadi nilai total komersial group.
- Quantity harus memiliki nilai dan satuan. Jika quantity belum tersedia, extended COGM item dan total extended group ditandai belum lengkap, bukan dianggap nol.
- Nilai snapshot submission dihitung di server dari data costing tersimpan, bukan dari nilai pada browser atau formula workbook.

## 4. Status dan State Machine

### Status Costing Group

- `draft`: group dibuat dan belum siap diajukan.
- `in_progress`: minimal satu item sedang dikerjakan.
- `waiting_approval`: seluruh item yang diwajibkan sudah diajukan ke Coordinator.
- `approved`: seluruh item yang diwajibkan sudah disetujui Coordinator.
- `submitted`: versi final sudah dikirim ke Marketing.
- `under_revision`: ada perubahan item atau komposisi setelah final submission.
- `cancelled`: group dibatalkan dengan alasan dan audit trail.

### Status Item Group

- `pending`: costing belum tersedia.
- `in_progress`: costing sedang dikerjakan.
- `waiting_price`: masih ada harga wajib yang kosong.
- `waiting_approval`: menunggu Coordinator.
- `rejected`: dikembalikan oleh Coordinator.
- `approved`: item disetujui.
- `submitted`: item termasuk dalam versi final yang dikirim.
- `changed`: item berubah setelah submission terakhir.
- `added_after_submission`: item baru ditambahkan setelah submission terakhir.
- `removed`: item dikeluarkan dari versi berikutnya, tanpa menghapus histori versi lama.

### Aturan Transisi Penting

- Draft export boleh dibuat dari status `draft`, `in_progress`, `waiting_approval`, atau `approved`.
- Final export hanya boleh dibuat jika seluruh item aktif memiliki costing, quantity valid, tidak mempunyai harga wajib kosong, dan sudah approved.
- Final submission hanya memakai final export yang sesuai dengan snapshot data terbaru.
- Perubahan costing, revision, quantity, PIC item, atau susunan item setelah `submitted` mengubah group menjadi `under_revision`.
- Group kembali menjadi `submitted` hanya setelah versi final baru selesai di-approve dan dikirim.

## 5. Rancangan Database

Nama akhir kolom harus disesuaikan dengan konvensi migration yang ada, tetapi kebutuhan datanya sebagai berikut.

### `costing_groups`

- `id`
- `project_a00_form_id`, unique
- `mode`: `normal` atau `bulky`
- `status`
- `pic_engineering`, PIC default A00
- `pic_marketing`, PIC default A00
- `current_version_number`, mulai dari 0
- `last_submitted_version_id`, nullable
- `created_by_id`, `updated_by_id`
- timestamps

### `costing_group_items`

- `id`
- `costing_group_id`
- `project_a00_item_id`
- `document_project_id`
- `active_document_revision_id`
- `costing_data_id`, nullable sampai form costing tersedia
- `sequence`
- `status`
- `pic_engineering`, nullable sebagai override
- `pic_marketing`, nullable sebagai override
- `quantity`, nullable
- `quantity_uom`, nullable
- `added_after_submission`, boolean
- `change_reason`, nullable
- `removed_at`, `removed_by_id`, `removal_reason`, nullable
- timestamps

Constraint wajib:

- Unique `project_a00_item_id` dalam satu group.
- Unique pasangan `costing_group_id` dan `document_project_id` untuk item aktif.
- Foreign key tidak boleh melakukan cascade delete terhadap histori submission/version.
- Sequence harus konsisten dan tidak boleh ganda dalam satu group aktif.

### `costing_group_versions`

Tabel ini menyimpan satu record untuk setiap draft atau final export.

- `id`
- `costing_group_id`
- `version_number`
- `type`: `draft` atau `final`
- `status`: `generated`, `submitted`, `superseded`, atau `failed`
- `file_path`, `original_name`, dan checksum file
- `total_unit_cogm`
- `total_extended_cogm`, nullable jika quantity belum lengkap
- `has_incomplete_price`
- `has_incomplete_quantity`
- `change_summary`
- `generated_by_id`, `generated_at`
- `submitted_by_id`, `submitted_at`, nullable
- timestamps

Constraint wajib:

- Unique pasangan `costing_group_id`, `version_number`, dan `type` sesuai kebijakan nomor versi.
- File lama tidak boleh diperbarui secara in-place.

### `costing_group_version_items`

Tabel immutable untuk snapshot item dalam setiap versi.

- `id`
- `costing_group_version_id`
- `costing_group_item_id`
- `document_revision_id`
- `costing_data_id`
- `item_revision_number`
- snapshot nomor assy, nama item, project, customer, dan model
- snapshot PIC efektif Engineering dan Marketing
- snapshot quantity dan satuan
- snapshot material, labor, overhead, unit COGM, dan extended COGM
- snapshot jumlah part tanpa harga
- `change_type`: `unchanged`, `changed`, `added`, atau `removed`
- `change_reason`, nullable
- timestamps

Snapshot tidak boleh ikut berubah saat master project atau costing diedit setelah export.

### `costing_group_events`

Audit trail untuk perubahan penting:

- `costing_group_id`
- `costing_group_item_id`, nullable
- `costing_group_version_id`, nullable
- `event_type`
- `actor_id`
- `reason`, nullable
- `metadata` JSON
- `created_at`

Event minimal: group dibuat, item ditambahkan/dikeluarkan, PIC berubah, draft dibuat, approval diajukan/disetujui/ditolak, final dibuat, submission dikirim, dan revision dibuka kembali.

### Notifikasi

Gunakan Laravel database notifications agar notifikasi dapat dibaca di dalam aplikasi. Email dapat ditambahkan kemudian tanpa mengubah event domain. Payload minimal berisi group, A00, item terkait, versi, jenis perubahan, pelaku, waktu, dan tautan tujuan.

## 6. Tahapan Implementasi

### Fase 0 — Baseline dan Pengamanan

1. Jalankan seluruh test suite dan catat baseline.
2. Buat fixture A00 satu item dan A00 multi-item untuk regression test.
3. Dokumentasikan route, permission, status revision, approval, dan submission yang sedang aktif.
4. Pastikan migration baru bersifat additive dan tidak mengubah data lama.
5. Tambahkan feature flag/config `bulky_cogm_enabled` bila deployment perlu dilakukan bertahap.

Selesai jika test existing tetap lulus dan fixture dapat merepresentasikan alur normal serta bulky.

### Fase 1 — Domain Model dan Migration

1. Buat migration empat tabel group, group items, versions, version items, dan events.
2. Tambahkan model, casts, constants status, dan relationship.
3. Tambahkan relationship dari `ProjectA00Form`, `ProjectA00Item`, `DocumentRevision`, `CostingData`, dan `User` bila diperlukan.
4. Buat service untuk membuat/sinkronisasi Costing Group dari A00.
5. Saat A00 diterbitkan, buat satu group dan seluruh group item dalam transaksi yang sama.
6. Untuk A00 lama, buat command backfill idempotent; jangan langsung mengubah data produksi di migration.
7. Tentukan mode: satu item `normal`, lebih dari satu item `bulky`. Mode berubah menjadi bulky ketika item kedua ditambahkan dan tidak kembali otomatis ke normal agar histori konsisten.

Selesai jika group dapat dibuat, diulang tanpa duplikasi, dan foreign key/unique constraint terbukti lewat test.

### Fase 2 — PIC A00 dan Override per Item

1. Pertahankan PIC pada A00/revision sebagai default.
2. Tambahkan input override PIC pada item Bulky.
3. Buat satu resolver PIC efektif: override item jika terisi, selain itu PIC A00.
4. Semua query inbox, authorization, snapshot, dan notifikasi wajib memakai resolver yang sama.
5. Perubahan PIC setelah submission dicatat sebagai event dan memicu `under_revision` bila memengaruhi penerima versi berikutnya.
6. Pengguna lama tetap dapat membuka item yang menjadi tanggung jawabnya sesuai aturan PIC existing.

Selesai jika kombinasi default, override Engineering saja, override Marketing saja, dan keduanya sudah diuji.

### Fase 3 — Workspace Bulky A00

1. Tambahkan ringkasan group pada halaman detail A00.
2. Tampilkan jumlah item, progress keseluruhan, status group, versi terakhir, dan kelengkapan harga/quantity.
3. Tampilkan tabel item dengan nomor assy, project, PIC efektif, revision, status workflow, unit COGM, quantity, extended COGM, dan jumlah unpriced part.
4. Sediakan aksi per item untuk membuka workspace costing existing.
5. Jangan membuat form costing gabungan; setiap item tetap memakai form existing.
6. Tampilkan alasan yang spesifik ketika draft/final/submit belum dapat dilakukan.
7. Pastikan akses mengikuti permission menu dan ownership PIC yang sudah ada.

Selesai jika satu A00 multi-item dapat dipantau dari satu halaman tanpa mengubah cara menghitung costing masing-masing item.

### Fase 4 — Sinkronisasi Status dan Kalkulasi Group

1. Buat `CostingGroupStatusService` sebagai satu-satunya sumber agregasi status.
2. Jalankan sinkronisasi setelah costing disimpan, COGM dihitung ulang, harga dilengkapi, approval berubah, item ditambah/dikeluarkan, quantity berubah, dan revision berubah.
3. Hitung unit COGM dan extended COGM dengan decimal yang konsisten dengan `CostingData`.
4. Jangan membulatkan nilai sumber sebelum agregasi; pembulatan hanya untuk tampilan/export sesuai template.
5. Item tanpa quantity membuat extended total incomplete.
6. Item dengan unpriced part membuat final readiness false.
7. Simpan hasil snapshot saat export, bukan mengandalkan kalkulasi live untuk histori.

Selesai jika hasil agregasi identik antara UI, service, snapshot, dan test untuk nilai decimal serta quantity.

### Fase 5 — Draft Export Tanpa Template Final

1. Bangun `BulkyCogmSnapshotService` terlebih dahulu agar format data tidak bergantung pada Excel.
2. Validasi semua item dan hasilkan daftar masalah per item.
3. Izinkan pembuatan snapshot bertipe `draft` saat harga atau quantity belum lengkap.
4. Draft wajib memiliki penanda `DRAFT`, waktu dibuat, pembuat, dan daftar data yang belum lengkap.
5. Draft tidak mengubah status item menjadi submitted dan tidak memenuhi syarat final submission.
6. Selama template belum tersedia, sediakan preview HTML/JSON internal atau workbook sementara hanya untuk QA; jangan nyatakan sebagai format resmi.

Selesai jika snapshot draft reproducible dan tidak mengubah histori final.

### Fase 6 — Approval Group

1. Pertahankan approval per item karena Coordinator harus dapat menerima atau menolak costing tertentu.
2. Tambahkan tampilan group pada Inbox Coordinator dengan drill-down item.
3. Aksi submit group mengajukan hanya item yang eligible dan belum approved; hasil harus menjelaskan item yang gagal.
4. Final group berstatus approved hanya jika semua item aktif sudah approved dan snapshot approval cocok dengan revision/costing terbaru.
5. Perubahan costing setelah approval membatalkan readiness item tersebut dan mewajibkan approval ulang.
6. Penolakan satu item tidak menghapus approval item lainnya, tetapi memblokir final group.

Selesai jika partial approval, rejection, resubmit, dan perubahan pasca-approval sudah teruji.

### Fase 7 — Export Excel Resmi

Fase ini dimulai setelah template Excel diterima.

1. Simpan template sebagai asset yang versioned dan catat versi template pada export.
2. Petakan setiap field snapshot ke cell/range/named range template.
3. Tentukan apakah detail menggunakan satu sheet per item atau layout lain berdasarkan template aktual.
4. Sanitasi dan deduplikasi nama sheet; batasi sesuai aturan Excel.
5. Pertahankan formula, style, merged cells, print area, gambar, dan proteksi template yang diperlukan.
6. Isi summary dari snapshot server.
7. Tambahkan metadata tersembunyi/terkontrol: ID A00, group ID, group version, revision ID setiap item, timestamp, template version, dan checksum.
8. Nama file mengikuti pola baku yang diputuskan setelah template diterima.
9. Simpan file ke storage private dan unduh melalui route berizin.
10. Verifikasi workbook dapat dibuka oleh Excel dan LibreOffice tanpa repair warning.

Selesai jika golden-file test, pemeriksaan formula/style, dan user acceptance terhadap template lulus.

### Fase 8 — Final Submission dan Inbox Marketing

1. Buat final snapshot tepat sebelum export dalam transaksi/logical lock untuk mencegah perubahan data di tengah proses.
2. Tolak final jika ada item belum approved, harga wajib kosong, quantity invalid, revision berubah, atau file tidak cocok dengan snapshot terbaru.
3. Buat versi final baru; jangan overwrite versi sebelumnya.
4. Kirim satu submission group ke Marketing dengan daftar PIC efektif seluruh item.
5. Inbox Marketing menampilkan satu baris per A00/group dan dapat dibuka untuk melihat setiap item.
6. Tampilkan total unit, total extended, kelengkapan, versi, waktu submission, perubahan sejak versi sebelumnya, dan tombol download.
7. Komentar Marketing ditautkan ke group version; komentar item-spesifik juga menyimpan group item ID.
8. Pertahankan pembacaan submission per revision lama untuk kompatibilitas sampai migrasi selesai.

Selesai jika Marketing hanya melihat group yang menjadi tanggung jawabnya dan dapat mengakses snapshot historis yang tepat.

### Fase 9 — Revisi Satu Item

1. Deteksi perubahan dengan membandingkan revision ID, costing ID/update marker, quantity, PIC, dan checksum data costing terhadap final snapshot terakhir.
2. Ubah item terkait menjadi `changed` dan group menjadi `under_revision`.
3. Item lain tetap menunjuk snapshot approved sebelumnya dan tidak wajib dihitung ulang.
4. Item yang berubah menjalani costing dan approval ulang.
5. Saat final baru dibuat, copy snapshot item yang unchanged dan buat snapshot baru untuk item changed.
6. Tandai `change_type` setiap item pada versi baru.
7. Buat ringkasan perubahan yang dapat dibaca manusia.
8. Beri notifikasi kepada PIC item berubah, PIC utama A00, seluruh PIC Marketing yang terlibat, Admin Costing, dan Coordinator.

Selesai jika histori menunjukkan contoh: group Rev 1 berisi Item A Rev 0, Item B Rev 1, dan Item C Rev 0 tanpa mengubah group Rev 0.

### Fase 10 — Tambah atau Keluarkan Item Setelah Submission

1. Batasi aksi kepada role yang ditetapkan, minimal Admin/role pemilik A00.
2. Wajibkan alasan perubahan.
3. Penambahan membuat group `under_revision` dan item `added_after_submission`.
4. Item baru wajib menjalani workflow lengkap dan approval sebelum final berikutnya.
5. Pengeluaran item dilakukan secara soft remove; dilarang menghapus histori item dari versi lama.
6. Preview perubahan menampilkan dampak jumlah item dan total sebelum pengguna mengonfirmasi.
7. Catat actor, waktu, alasan, item, snapshot sebelum/sesudah, dan versi terakhir.
8. Kirim notifikasi ke PIC utama A00, seluruh PIC item lama dan baru, Admin Costing, Coordinator, dan Marketing penerima versi terakhir.
9. Submission lama tetap dapat dilihat dan diunduh.

Selesai jika add/remove tidak mengubah snapshot versi sebelumnya dan semua penerima mendapat notifikasi yang dapat ditandai sudah dibaca.

### Fase 11 — Notifikasi dan Audit UI

1. Buat notification class per kategori event, bukan pesan bebas dari controller.
2. Hindari notifikasi duplikat kepada user yang sama meskipun ia menjadi PIC pada beberapa item.
3. Tambahkan notification center, unread count, mark-as-read, dan deep link ke group/version/item.
4. Tambahkan tab riwayat pada halaman A00 berisi event timeline.
5. Informasi sensitif dan file hanya dapat dibuka oleh role/PIC berizin meskipun memiliki URL langsung.
6. Siapkan retry/logging jika kelak email diaktifkan.

Selesai jika matriks penerima untuk setiap event lulus feature test.

### Fase 12 — Backfill, Rollout, dan Observability

1. Jalankan command dry-run untuk menghitung A00 yang akan dibuatkan group.
2. Backfill satu Costing Group untuk setiap A00 lama secara batch dan idempotent.
3. Cocokkan item A00 ke revision/costing; kasus ambigu masuk laporan manual, bukan ditebak.
4. Jangan membuat submission group historis palsu. Jika submission lama dipetakan, beri sumber `legacy` dan simpan referensinya.
5. Aktifkan fitur untuk admin/internal QA terlebih dahulu.
6. Catat kegagalan snapshot/export/submission dengan group ID dan correlation ID tanpa membocorkan data sensitif.
7. Pantau jumlah group incomplete, export gagal, mismatch snapshot, dan notifikasi gagal.
8. Setelah stabil, aktifkan untuk seluruh role dan pertahankan jalur rollback lewat feature flag.

Selesai jika hasil dry-run sama dengan hasil backfill, tidak ada duplikasi, dan alur normal lama tetap bekerja.

## 7. Matriks Notifikasi Minimum

| Event | Penerima minimum |
|---|---|
| Item ditambahkan/dikeluarkan | PIC utama A00, semua PIC item, Admin Costing, Coordinator, Marketing penerima terakhir |
| PIC item berubah | PIC lama, PIC baru, PIC utama A00 |
| Item menunggu harga | PIC item Engineering, Admin Costing |
| Group diajukan approval | Coordinator, Admin Costing |
| Item ditolak | PIC item, Admin Costing, PIC utama A00 |
| Group approved | PIC utama A00, semua PIC item, Admin Costing |
| Draft dibagikan | Semua penerima draft |
| Final dikirim | Seluruh PIC Marketing efektif, PIC utama A00, Admin Costing, Coordinator |
| Perubahan setelah final | Seluruh PIC group dan penerima final terakhir |

Jika satu user muncul melalui beberapa aturan, kirim satu notifikasi dengan daftar item yang relevan.

## 8. Matriks Pengujian Wajib

### Unit Test

- Resolver PIC default dan override.
- Agregasi unit/extended COGM dan decimal precision.
- Readiness draft/final.
- State transition group dan item.
- Perbandingan snapshot untuk changed/unchanged/added/removed.
- Penentuan penerima notifikasi tanpa duplikasi.

### Feature Test

- Membuat A00 satu item dan multi-item.
- Sinkronisasi group idempotent.
- Akses halaman berdasarkan role dan PIC.
- Draft dengan harga/quantity belum lengkap.
- Final ditolak saat syarat belum lengkap.
- Partial approval dan rejection.
- Final submission berhasil.
- Revisi hanya satu item.
- Tambah dan keluarkan item setelah submission.
- Download versi lama dan versi terbaru.
- Marketing tidak dapat membuka group milik PIC lain.
- Concurrent submit menghasilkan satu versi, bukan duplikasi.

### Export Test

- Template dan formula tidak rusak.
- Nama sheet valid dan unik.
- Nilai summary sama dengan snapshot database.
- Workbook besar tetap dapat dibuat dalam batas memori/waktu yang disepakati.
- File gagal tidak menghasilkan submission setengah jadi.

### Regression Test

- Alur A00 satu item.
- Costing form per revision.
- Unpriced parts.
- Approval Coordinator existing.
- Marketing Inbox existing.
- Download COGM existing.
- Dashboard, project progress, dan permission menu.

## 9. Urutan Pull Request yang Disarankan

1. PR-1: migration, model, relationship, constraint, dan backfill dry-run.
2. PR-2: group synchronization, PIC resolver, status calculator, dan unit test.
3. PR-3: workspace A00 Bulky dan permission.
4. PR-4: snapshot draft, kalkulasi total, dan audit event.
5. PR-5: approval group dan regression approval per item.
6. PR-6: notification center dan recipient rules.
7. PR-7: integrasi template Excel resmi.
8. PR-8: final submission dan Marketing Inbox group.
9. PR-9: revisi satu item serta add/remove setelah submission.
10. PR-10: backfill produksi, observability, performance, dan rollout.

Setiap PR harus dapat di-deploy sendiri, migration harus backward-compatible, dan seluruh test yang relevan harus lulus sebelum lanjut ke PR berikutnya.

## 10. Hal yang Menunggu Input Template

Implementasi tidak perlu berhenti seluruhnya. Hanya detail berikut yang ditunda sampai template diterima:

- Struktur sheet final.
- Cell/range mapping.
- Formula dan aturan pembulatan tampilan.
- Branding, print area, proteksi, dan signature.
- Nama file final.
- Batas maksimum item per workbook jika template mempunyai batas layout.

Database, status, PIC, snapshot, draft, approval, revision, notification, dan UI group dapat dikerjakan lebih dahulu.

## 11. Definition of Done Keseluruhan

Bulky COGM dinyatakan selesai hanya jika:

- Satu A00 multi-item dapat diproses dari pembuatan sampai diterima Marketing.
- Costing setiap item tetap terisolasi.
- Draft incomplete dapat dibedakan secara jelas dari final.
- Final incomplete selalu ditolak dari sisi server.
- PIC default dan override bekerja pada UI, authorization, inbox, snapshot, dan notifikasi.
- Total unit dan extended dapat ditelusuri ke data item.
- Revisi satu item tidak menimpa item atau file versi lama.
- Add/remove setelah submission memiliki alasan, audit trail, versi baru, dan notifikasi.
- Seluruh versi file dapat diunduh sesuai hak akses.
- Backfill tidak menggandakan data.
- Test unit, feature, export, authorization, concurrency, dan regression lulus.

