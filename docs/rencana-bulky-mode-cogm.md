# Rencana Bulky Mode COGM

## Latar Belakang

Saat ini proses costing cenderung menggunakan pola satu A00 untuk satu item project. Pada kebutuhan **Bulky Mode**, satu dokumen A00 dapat berisi beberapa item project dan seluruh hasil costing perlu dikirim dalam satu file COGM yang sama.

Tujuan utama rancangan ini adalah menggabungkan proses export dan submission tanpa mencampur data material, labor, overhead, maupun histori setiap item project.

## Konsep Utama

A00 menjadi induk atau batch costing, sedangkan setiap item di dalam A00 tetap disimpan sebagai project tersendiri.

```text
A00
└── Costing Group / Bulky Costing
    ├── Item Project W40295
    ├── Item Project W40296
    └── Item Project W40297
```

Hubungan yang direkomendasikan:

```text
Satu A00 = Satu Costing Group = Satu File COGM
```

Perhitungan dan detail costing tetap dipisahkan untuk setiap item project.

## Pilihan Mode A00

Pada saat membuat A00, sistem menyediakan dua pilihan:

- **Normal Mode**: satu A00 berisi satu item project.
- **Bulky Mode**: satu A00 berisi beberapa item project.

Setiap item tetap mempunyai data berikut:

- Nomor assy.
- Customer dan model.
- Partlist.
- UMH.
- Material.
- Labor.
- Overhead.
- Nilai COGM item.
- Status harga part.
- PIC Engineering dan PIC Marketing.
- Progress workflow.

Seluruh item dalam Bulky Mode dihubungkan menggunakan `costing_group_id` atau `a00_id` yang sama.

## Rancangan File COGM Gabungan

Satu workbook Excel dapat memiliki susunan sheet berikut:

- `SUMMARY COGM`
- `W40295`
- `W40296`
- `W40297`
- Sheet material atau referensi pendukung jika diperlukan.

Sheet `SUMMARY COGM` berisi ringkasan seluruh item:

| No. Assy | Project | Material | Labor | Overhead | Total COGM | Status |
|---|---|---:|---:|---:|---:|---|
| W40295 | Wiring Harness A | ... | ... | ... | ... | Complete |
| W40296 | Wiring Harness B | ... | ... | ... | ... | Complete |
| W40297 | Wiring Harness C | ... | ... | ... | ... | Waiting Price |

Dengan format ini, Marketing menerima satu file tetapi tetap dapat melihat rincian setiap item secara terpisah.

## Rancangan Alur Halaman

Pada halaman A00 Bulky Mode:

1. Tampilkan seluruh item project dalam satu tabel.
2. Sediakan tombol **Buka Form Costing** pada setiap item.
3. Tampilkan progress setiap item, misalnya:
   - Belum drawing.
   - Breakdown.
   - Costing.
   - Menunggu harga.
   - Selesai.
4. Tampilkan progress keseluruhan, misalnya `2 dari 3 item selesai`.
5. Aktifkan tombol **Export COGM Gabungan** setelah data yang diwajibkan tersedia.
6. Lakukan submit ke Marketing satu kali untuk seluruh A00.

Jika masih terdapat item dengan harga kosong, sistem dapat mengizinkan export berstatus draft. Namun, submission final sebaiknya diblokir sampai seluruh item selesai.

## Rancangan Database Awal

Data material dari beberapa item tidak boleh digabungkan ke dalam satu record. Tambahkan entitas penghubung, misalnya:

### Tabel `costing_groups`

| Kolom | Keterangan |
|---|---|
| `id` | Primary key. |
| `project_a00_form_id` | Relasi ke dokumen A00. |
| `mode` | Nilai `normal` atau `bulky`. |
| `status` | Status keseluruhan costing group. |
| `submitted_at` | Waktu submission final. |
| `submitted_by` | User yang melakukan submission. |

### Tabel `costing_group_items`

| Kolom | Keterangan |
|---|---|
| `id` | Primary key. |
| `costing_group_id` | Relasi ke costing group. |
| `document_project_id` | Relasi ke project item. |
| `document_revision_id` | Revisi project yang digunakan. |
| `costing_data_id` | Data costing item. |
| `sequence` | Urutan item dalam A00 dan workbook. |
| `status` | Status pengerjaan item. |

Data `costing_data`, material, labor, overhead, dan data pendukung tetap disimpan per item. Proses export dan submission saja yang digabungkan.

## Inbox dan History

### Inbox Costing

Tampilkan satu baris per A00, misalnya:

```text
A00 0101/MKT-PROJECT/A00/VIII/2026
3 item · 2 selesai · 1 menunggu harga
```

Ketika baris dibuka, sistem menampilkan daftar seluruh item di dalam A00 tersebut.

### Inbox Marketing

Tampilkan satu baris per A00 dengan informasi:

- Jumlah item.
- Total COGM gabungan.
- PIC Engineering.
- PIC Marketing.
- Versi file.
- Tombol download COGM gabungan.

## Revisi dan Versioning

Jika salah satu item diperbarui setelah submission:

1. Jangan menimpa file submission sebelumnya.
2. Buat versi file gabungan baru.
3. Tandai item yang mengalami perubahan.
4. Tampilkan pemberitahuan pembaruan pada Inbox Marketing.

Contoh nama file:

```text
COGM A00 0101-MKT-PROJECT-A00-VIII-2026 - Rev 0.xlsx
COGM A00 0101-MKT-PROJECT-A00-VIII-2026 - Rev 1.xlsx
```

## Aturan Validasi yang Disarankan

- Satu item hanya boleh terhubung satu kali pada Costing Group yang sama.
- Revision ID yang digunakan harus tercatat pada saat export dan submission.
- Submission final diblokir jika masih ada item dengan harga wajib yang kosong.
- Total pada sheet Summary harus berasal dari data server, bukan dihitung ulang dari nilai tampilan browser.
- File export harus menyimpan identitas A00, Costing Group, revisi, dan waktu pembuatan.
- Perubahan setelah submission harus menghasilkan revision history baru.

## Keputusan yang Perlu Dibahas

Pembahasan berikut perlu diputuskan sebelum implementasi:

1. Apakah Bulky Mode dipilih ketika membuat A00 atau otomatis aktif ketika item A00 lebih dari satu?
2. Apakah satu workbook menggunakan satu sheet per item atau satu template panjang dalam satu sheet?
3. Apakah draft boleh dikirim ke Marketing ketika masih ada harga kosong?
4. Apakah PIC Engineering dan PIC Marketing berlaku per A00 atau dapat berbeda pada setiap item?
5. Apakah total COGM gabungan hanya berupa penjumlahan atau membutuhkan pembobotan quantity/volume?
6. Bagaimana aturan revisi jika hanya satu dari beberapa item yang berubah?
7. Apakah item baru boleh ditambahkan setelah Costing Group pernah disubmit?
8. Format nama file final yang akan digunakan.

## Rekomendasi Awal

Gunakan model **satu A00 sebagai satu Costing Group dan satu file COGM**, tetapi pertahankan seluruh perhitungan serta data detail per item. Pendekatan ini lebih aman untuk audit, revisi, pelacakan progress, dan pencegahan data antarproject saling tertimpa.

