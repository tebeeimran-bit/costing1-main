# Cara Mengakses Aplikasi Costing

## Status Server
Server Laravel sedang berjalan dengan sempurna di port **8000**.

## ✅ Cara Akses Yang Benar

### Opsi 1: Lewat VS Code Terminal (DIREKOMENDASIKAN)
1. Buka VS Code Codespaces
2. Tekan **Ctrl + `** untuk membuka Terminal
3. Jalankan:
   ```bash
   curl http://localhost:8000/form
   ```
4. Atau buka browser dengan mengetik di address bar:
   ```
   http://localhost:8000/form
   ```

### Opsi 2: Lewat VS Code Remote Explorer (SIMPEL)
1. Klik icon **Remote Explorer** di sidebar VS Code (atau tekan Ctrl+Shift+E)
2. Di bagian "PORTS" lihat port 8000
3. Klik icon "Open in Browser" (globe icon)

### Opsi 3: Lewat Command Palette
1. Tekan **Ctrl+Shift+P** (atau Cmd+Shift+P di Mac)
2. Ketik: `Ports: Focus on Ports View`
3. Cari port 8000
4. Klik "Open in New Tab"

## 📋 Daftar Route Tersedia

| Path | Endpoint | Fungsi |
|------|----------|--------|
| `/form` | GET | Halaman form input costing (MAIN) |
| `/` | GET | Dashboard |
| `/test` | GET | Test endpoint (debug) |
| `/database` | GET | Database management |
| `/database/costing` | GET | Costing data |
| `/document-receipts` | GET | Document receipts |
| `/tracking-documents` | GET | Tracking documents |

## ✅ Verifikasi Server Berjalan

Server sudah verified working dengan beberapa test:
```
✓ HTTP/1.1 200 OK untuk /form
✓ HTTP/1.1 200 OK untuk /test
✓ HTTP/1.1 200 OK untuk /
✓ Semua 57 routes registered
✓ Database connection OK
```

## 🔧 Jika Masih Dapat 404

Jika masih mendapat 404:

1. **Clear browser cache**
   - Ctrl+Shift+Delete (Windows/Linux)
   - Cmd+Shift+Delete (Mac)
   - Clear cookies dan cache

2. **Re-build Codespaces** (jika perlu)
   - Klik ikon "Remote Window" di VS Code
   - Pilih "Codespaces: Rebuild Container"

3. **Restart Server**
   ```bash
   cd /workspaces/costing1
   pkill -f "php.*serve"
   sleep 2
   php artisan serve --host=0.0.0.0 --port=8000
   ```

4. **Test kesehatan server**
   ```bash
   curl http://localhost:8000/test
   ```
   Harus return JSON dengan status "ok"

## 📱 URL Codespaces Public

Jika ingin akses dari luar Codespaces, gunakan URL public yang disediakan Codespaces:
- Lihat di VS Code Codespaces Port Forwarding
- Format biasanya: `https://codespace-name-user-8000.app.github.dev`

**PENTING:** Jangan tambahkan `:8000` di akhir URL jika sudah ada port di subdomain.

## 📝 Verifikasi Fixes Yang Diterapkan

Semua fixes untuk Excel import sudah diterapkan:
- ✅ Header detection fleksibel (bisa di baris 7-15, bukan hanya baris 11)
- ✅ Intelligent scoring untuk header matching
- ✅ Display-only attributes tidak disimpan ke database (setRelation)
- ✅ Safe update untuk unpriced parts (query builder, bukan model->update)
- ✅ Error handling graceful (try-catch dengan user feedback)
- ✅ Route fallback untuk GET request

## 👉 NEXT STEP

1. Akses `/form` menggunakan salah satu metode di atas  
2. Coba import Excel partlist
3. Verifikasi kolom alignment sudah benar (SUPPLIER PART NO, ID CODE, PART NAME)
4. Lapor hasilnya!
