<?php

$output = dirname(__DIR__).'/Naskah Presentasi Costing System.docx';

$sections = [
    ['title', 'NASKAH PRESENTASI COSTING SYSTEM'],
    ['subtitle', 'Dharma Electrindo Manufacturing'],
    ['meta', 'Fokus: Otomatisasi Costing, COGM, Multi-Assy, dan Approval'],

    ['h1', '1. Pembukaan'],
    ['p', 'Selamat pagi/siang Bapak/Ibu. Pada kesempatan ini saya akan mempresentasikan Costing System Dharma Electrindo Manufacturing. Aplikasi ini dirancang untuk membantu proses perhitungan biaya produksi secara terintegrasi, mulai dari penerimaan data material sampai menghasilkan COGM yang siap diperiksa dan dikirim kepada Marketing.'],

    ['h1', '2. Latar Belakang'],
    ['p', 'Sebelumnya, proses costing melibatkan banyak file Excel, pencatatan manual, dan koordinasi antarbagian. Kondisi tersebut membuat data tersebar, progress sulit dipantau, serta meningkatkan risiko penggunaan file atau harga yang tidak terbaru. Costing System menyatukan data project, material, harga, kurs, UMH, perhitungan COGM, revisi, dan approval dalam satu alur.'],

    ['h1', '3. Tujuan Utama'],
    ['bullet', 'Mempercepat penyusunan costing dan COGM.'],
    ['bullet', 'Mengurangi input material secara manual.'],
    ['bullet', 'Memastikan material tanpa harga terdeteksi sebelum approval.'],
    ['bullet', 'Menjaga keterkaitan beberapa assy dalam satu A00.'],
    ['bullet', 'Menyimpan file, revisi, komentar, dan status secara terstruktur.'],

    ['h1', '4. Fitur Unggulan Costing'],
    ['h2', '4.1 Material Cost Terintegrasi'],
    ['p', 'Data material dimuat dari Partlist Engineering ke Form Costing. Informasi Part Number, ID Code, Part Name, Quantity Requirement, Unit, dan Process Code dapat digunakan tanpa memasukkan ulang satu per satu.'],
    ['h2', '4.2 Otomatisasi Harga Material'],
    ['p', 'Setiap material dilengkapi harga dasar, basis harga, currency, MOQ, klasifikasi N/C/E, supplier, dan import tax. Sistem menggunakan data tersebut bersama quantity requirement dan kurs untuk membentuk biaya material.'],
    ['h2', '4.3 Multi-Currency dan Rate'],
    ['p', 'Sistem mendukung IDR, USD, JPY, dan LME. Rate dapat dipilih dari database atau diisi manual. Perubahan kurs dapat digunakan untuk menghitung ulang costing tanpa memasukkan ulang seluruh material.'],
    ['h2', '4.4 UMH dan Cycle Time'],
    ['p', 'Biaya proses dihitung berdasarkan nama proses, quantity, waktu proses, waktu per quantity, cost per second, dan cost per unit. Hasilnya digunakan sebagai komponen labor dan manufacturing cost.'],
    ['h2', '4.5 Kalkulasi COGM'],
    ['p', 'Nilai COGM dibentuk dari Material Cost, Labor Cost, Overhead Cost, dan Scrap Cost. Secara sederhana: COGM = Material Cost + Labor Cost + Overhead Cost + Scrap Cost.'],

    ['h1', '5. Kontrol Material Tanpa Harga'],
    ['p', 'Material yang belum memiliki harga otomatis masuk ke Inbox New Part Request. Pengguna dapat memilih banyak material sekaligus, mengexport permintaan harga, lalu mengimport hasil update harga. Project tidak dapat disubmit untuk approval selama masih ada material tanpa harga.'],

    ['h1', '6. Costing Multi-Assy dalam Satu A00'],
    ['p', 'Apabila satu A00 memiliki beberapa assy, sistem menampilkan satu Form Costing dengan tab untuk setiap No. Assy. Setiap assy tetap memiliki material dan perhitungan masing-masing, tetapi hubungan dokumennya tetap terjaga sebagai satu kelompok A00.'],
    ['p', 'Contohnya, assy 32100-K4MA-0001 dan 32100-K4MA-0002 dikelola dalam satu form gabungan. Pengguna dapat berpindah tab tanpa membuka project secara terpisah.'],

    ['h1', '7. Export dan Import Excel'],
    ['h2', '7.1 Export Excel'],
    ['p', 'Export Excel menghasilkan file kerja yang dapat diedit dan masih memiliki formula.'],
    ['h2', '7.2 Export COGM'],
    ['p', 'Export COGM menghasilkan file final dengan nilai material yang sudah terisi. Hasil export diarsipkan sehingga dapat diunduh kembali melalui Inbox Costing.'],
    ['h2', '7.3 Workbook Multi-Sheet'],
    ['p', 'Untuk A00 gabungan, sistem menghasilkan satu workbook dengan sheet bernama sesuai No. Assy. Jika file hasil edit di-import dari tab mana pun, seluruh sheet dibaca dan seluruh assy diperbarui secara bersamaan. Import ditolak jika ada sheet yang hilang atau namanya tidak sesuai.'],

    ['h1', '8. Template Excel'],
    ['p', 'Database Template Excel menyediakan Template Costing, Partlist, UMH, dan A00. Template Costing dipilih otomatis berdasarkan jumlah assy, misalnya template satu assy, dua assy, atau tiga assy.'],

    ['h1', '9. Approval Costing'],
    ['p', 'Setelah costing lengkap, Admin Costing melakukan Submit Approval. Coordinator Costing dapat memeriksa nilai COGM, menyetujui, atau menolak dengan catatan revisi. Setelah disetujui, COGM dikirim ke Marketing. Seluruh perubahan status dan penanggung jawabnya tercatat dalam sistem.'],

    ['h1', '10. Monitoring dan Dashboard'],
    ['p', 'Inbox Costing menampilkan project, No. Assy, progress, status, nilai COGM, waktu update, Form Costing, file COGM, revisi, dan tindakan approval. Assy dalam satu A00 gabungan ditampilkan dalam satu baris. Dashboard menyajikan ringkasan A00, A04 atau Cancel, A05 atau Die Go, potential cost, dan customer utama.'],

    ['h1', '11. Nilai Tambah bagi Perusahaan'],
    ['bullet', 'Waktu penyusunan costing lebih singkat.'],
    ['bullet', 'Sumber data dan file lebih terkontrol.'],
    ['bullet', 'Kesalahan akibat input berulang dapat dikurangi.'],
    ['bullet', 'Material tanpa harga dapat diketahui lebih awal.'],
    ['bullet', 'Progress dan tanggung jawab setiap bagian dapat ditelusuri.'],
    ['bullet', 'Hasil COGM memiliki proses pemeriksaan dan approval yang jelas.'],

    ['h1', '12. Urutan Demonstrasi'],
    ['number', 'Buka Form Costing dan tunjukkan informasi project.'],
    ['number', 'Tunjukkan material yang dimuat dari Partlist.'],
    ['number', 'Jelaskan harga, currency, MOQ, supplier, dan import tax.'],
    ['number', 'Tunjukkan Rate, UMH, dan Cycle Time.'],
    ['number', 'Tunjukkan material tanpa harga pada New Part Request.'],
    ['number', 'Tunjukkan hasil perhitungan COGM.'],
    ['number', 'Pindah antar-tab pada A00 gabungan.'],
    ['number', 'Export workbook multi-sheet dan import hasil edit.'],
    ['number', 'Export serta download COGM final.'],
    ['number', 'Submit COGM ke Coordinator Costing untuk approval.'],

    ['h1', '13. Penutup'],
    ['p', 'Kesimpulannya, Costing System bukan hanya aplikasi monitoring project. Fitur utamanya adalah mengolah data material, harga, kurs, UMH, dan komponen biaya menjadi COGM yang lengkap, terkontrol, dapat direvisi, serta memiliki proses approval yang jelas.'],
    ['p', 'Dengan sistem ini, proses costing menjadi lebih cepat, transparan, mudah dipantau, dan memiliki riwayat yang dapat ditelusuri. Demikian presentasi dari saya. Terima kasih atas perhatian Bapak/Ibu. Selanjutnya saya akan melanjutkan dengan demonstrasi aplikasinya.'],
];

function xmlText(string $text): string
{
    return htmlspecialchars($text, ENT_XML1 | ENT_QUOTES, 'UTF-8');
}

function paragraph(string $type, string $text, int &$number): string
{
    $text = xmlText($text);
    if ($type === 'title') {
        return '<w:p><w:pPr><w:jc w:val="center"/><w:spacing w:after="180"/></w:pPr><w:r><w:rPr><w:b/><w:color w:val="173F8A"/><w:sz w:val="36"/></w:rPr><w:t>'.$text.'</w:t></w:r></w:p>';
    }
    if ($type === 'subtitle') {
        return '<w:p><w:pPr><w:jc w:val="center"/></w:pPr><w:r><w:rPr><w:b/><w:sz w:val="24"/></w:rPr><w:t>'.$text.'</w:t></w:r></w:p>';
    }
    if ($type === 'meta') {
        return '<w:p><w:pPr><w:jc w:val="center"/><w:spacing w:after="500"/></w:pPr><w:r><w:rPr><w:i/><w:color w:val="64748B"/><w:sz w:val="20"/></w:rPr><w:t>'.$text.'</w:t></w:r></w:p>';
    }
    if ($type === 'h1') {
        return '<w:p><w:pPr><w:spacing w:before="280" w:after="100"/><w:keepNext/></w:pPr><w:r><w:rPr><w:b/><w:color w:val="173F8A"/><w:sz w:val="28"/></w:rPr><w:t>'.$text.'</w:t></w:r></w:p>';
    }
    if ($type === 'h2') {
        return '<w:p><w:pPr><w:spacing w:before="160" w:after="70"/><w:keepNext/></w:pPr><w:r><w:rPr><w:b/><w:color w:val="2458A6"/><w:sz w:val="23"/></w:rPr><w:t>'.$text.'</w:t></w:r></w:p>';
    }
    if ($type === 'bullet') {
        return '<w:p><w:pPr><w:ind w:left="420" w:hanging="220"/><w:spacing w:after="65"/></w:pPr><w:r><w:t>•  '.$text.'</w:t></w:r></w:p>';
    }
    if ($type === 'number') {
        $number++;
        return '<w:p><w:pPr><w:ind w:left="420" w:hanging="220"/><w:spacing w:after="65"/></w:pPr><w:r><w:t>'.$number.'.  '.$text.'</w:t></w:r></w:p>';
    }
    return '<w:p><w:pPr><w:jc w:val="both"/><w:spacing w:after="120" w:line="300" w:lineRule="auto"/></w:pPr><w:r><w:t>'.$text.'</w:t></w:r></w:p>';
}

$body = '';
$number = 0;
foreach ($sections as [$type, $text]) {
    if ($type === 'h1' && str_starts_with($text, '13.')) {
        $number = 0;
    }
    $body .= paragraph($type, $text, $number);
}

$document = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
    .'<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:body>'
    .$body
    .'<w:sectPr><w:pgSz w:w="11906" w:h="16838"/><w:pgMar w:top="1134" w:right="1134" w:bottom="1134" w:left="1134"/><w:cols w:space="708"/></w:sectPr>'
    .'</w:body></w:document>';

$styles = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
    .'<w:styles xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">'
    .'<w:docDefaults><w:rPrDefault><w:rPr><w:rFonts w:ascii="Aptos" w:hAnsi="Aptos"/><w:sz w:val="21"/></w:rPr></w:rPrDefault>'
    .'<w:pPrDefault><w:pPr><w:spacing w:after="100"/></w:pPr></w:pPrDefault></w:docDefaults>'
    .'</w:styles>';

$zip = new ZipArchive();
if ($zip->open($output, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
    throw new RuntimeException('Tidak dapat membuat file Word.');
}
$zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/><Override PartName="/word/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.styles+xml"/><Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/><Override PartName="/docProps/app.xml" ContentType="application/vnd.openxmlformats-officedocument.extended-properties+xml"/></Types>');
$zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/><Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/extended-properties" Target="docProps/app.xml"/></Relationships>');
$zip->addFromString('word/document.xml', $document);
$zip->addFromString('word/styles.xml', $styles);
$zip->addFromString('word/_rels/document.xml.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>');
$zip->addFromString('docProps/core.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/"><dc:title>Naskah Presentasi Costing System</dc:title><dc:subject>Costing System Dharma Electrindo Manufacturing</dc:subject><dc:creator>Costing System</dc:creator><dc:description>Naskah presentasi terstruktur dengan fokus fitur unggulan costing.</dc:description></cp:coreProperties>');
$zip->addFromString('docProps/app.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties"><Application>Microsoft Office Word</Application><AppVersion>16.0000</AppVersion></Properties>');
$zip->close();

echo $output.PHP_EOL;
