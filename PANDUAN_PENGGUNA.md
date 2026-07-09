# Panduan Pengguna Tinggal Jalan

Panduan ini ditujukan untuk admin yang mengelola konten, paket wisata, booking, pembayaran, dan komunikasi pelanggan melalui panel Tinggal Jalan.

## 1. Masuk ke Admin

Buka:

```text
http://127.0.0.1:8000/admin
```

Akun lokal bawaan:

```text
Email: admin@tinggaljalan.test
Password: password
```

Ganti password sebelum aplikasi digunakan di staging atau production.

Dashboard menampilkan ringkasan operasional seperti booking yang perlu ditangani, perjalanan yang akan datang, serta kesiapan paket wisata.

## 2. Prinsip Kerja Admin

Gunakan urutan berikut saat menyiapkan produk:

1. Buat Destinasi.
2. Siapkan Route Filters jika dibutuhkan.
3. Buat Tour Package dan lengkapi seluruh kontennya.
4. Atur Availability untuk tanggal khusus.
5. Periksa tampilan paket di website publik.
6. Kelola voucher jika ada promosi.

Untuk booking pelanggan:

1. Pelanggan mengirim permintaan booking.
2. Admin memeriksa detail dan mengonfirmasi ketersediaan.
3. Admin membuat payment request.
4. Admin mengirim invoice melalui email dan/atau WhatsApp.
5. Pelanggan membayar melalui Midtrans.
6. Sistem mencatat pembayaran dan mengirim receipt.
7. Admin menyiapkan perjalanan.
8. Setelah layanan selesai, admin menandai perjalanan selesai.

## 3. Tour Packages

Menu **Tour Packages** adalah pusat pengelolaan produk wisata.

### Membuat paket

Isi bagian utama berikut:

- Judul paket dalam English, Indonesian, dan Chinese.
- Slug yang singkat dan unik.
- Destinasi.
- Ringkasan, deskripsi, kategori, dan informasi traveler.
- Harga dasar IDR dan USD.
- Durasi, pickup, cakupan layanan, serta catatan penting.
- Cover image dan galeri.
- Highlights, includes, excludes, policies, dan good-to-know.
- Itinerary.
- Add-ons.
- Status aktif dan featured.

English menjadi konten utama. Indonesian dan Chinese digunakan ketika pelanggan memilih bahasa tersebut. Pastikan minimal konten English lengkap sebelum mengaktifkan paket.

### Itinerary dan add-ons

Itinerary dikelola dari dalam Tour Package. Menu Itinerary Items tidak digunakan sebagai alur kerja utama.

Untuk setiap add-on, tentukan:

- Nama dan deskripsi.
- Harga IDR dan USD.
- Perhitungan per booking atau per orang.
- Status aktif.

### Readiness

Kolom readiness menunjukkan apakah paket sudah cukup lengkap untuk dijual. Paket yang masih memiliki data penting kosong sebaiknya tidak diaktifkan atau dijadikan featured.

### Traveler Price Tiers (Tier Harga Berdasarkan Jumlah Peserta)

Paket dapat dikonfigurasi menggunakan harga berjenjang (tiered pricing) berdasarkan jumlah peserta (Guests):
- **Rentang Berkelanjutan (Contiguous Ranges):** Rentang peserta harus dimulai dari 1 dan berlanjut secara berurutan (misal: Tier 1: 1-2 orang, Tier 2: 3-4 orang).
- **Batas Terbuka (Open-Ended Tiers):** Untuk membuat batas atas terbuka (misalnya 5+ orang), kosongkan kolom **To travelers** pada jenjang terakhir. Sistem akan secara otomatis menganggapnya sebagai range tanpa batas atas (misal: 5+).
- **Triger Kuotasi (Quote Trigger):** Jika jenjang terakhir memiliki batas atas tetap (misalnya berakhir di 6 orang), pemesanan yang melebihi jumlah tersebut (misalnya 7+ orang) akan otomatis dianggap sebagai **"Quote Required" (Butuh Pengaturan Grup Khusus)**. Sistem akan memblokir alur checkout langsung dan mengarahkan pelanggan untuk menghubungi admin via WhatsApp.

## 4. Destinations dan Route Filters

### Destinations

Destinasi mengelompokkan paket berdasarkan wilayah wisata. Isi nama, slug, lokasi, deskripsi, gambar, urutan, status aktif, dan featured.

- **Active** menentukan apakah destinasi tersedia di website.
- **Featured** menentukan apakah destinasi dipromosikan di homepage.

Destinasi yang aktif tetapi tidak featured masih dapat dipakai oleh paket, tetapi tidak muncul sebagai destinasi unggulan di homepage.

### Route Filters

Route Filters mengatur pilihan penyaringan pada halaman daftar paket, misalnya gaya perjalanan atau kategori tertentu.

Jangan menghapus filter yang masih dipakai paket. Nonaktifkan terlebih dahulu jika filter tidak ingin ditampilkan kepada pelanggan.

## 5. Availability

Menu **Availability** mengelola aturan tanggal khusus.

Terdapat dua cakupan:

- **Destination-wide**: berlaku untuk semua paket dalam satu destinasi.
- **Specific package**: berlaku hanya untuk satu paket dan mengalahkan aturan destinasi pada tanggal yang sama.

Status yang tersedia:

- **Available**: tanggal dapat dipilih.
- **Limited**: jumlah kursi terbatas; pelanggan masih dapat mengirim permintaan dan admin harus mengonfirmasi.
- **Booked**: tanggal tidak tersedia.
- **Blocked**: tanggal ditutup dengan alasan tertentu.

Untuk status Limited, isi jumlah kursi yang tersisa. Jika jumlah tamu melebihi kapasitas, website menampilkan peringatan tetapi tetap mengizinkan permintaan booking.

## 6. Vouchers

Voucher digunakan saat pelanggan menghitung booking.

Tentukan:

- Kode voucher.
- Jenis diskon: persen atau nominal tetap.
- Nilai diskon.
- Mata uang yang diizinkan.
- Periode berlaku.
- Status aktif.

Pastikan nominal voucher sesuai mata uang. Uji voucher dari halaman booking setelah menyimpan perubahan.

## 7. Booking Workflow

Menu **Bookings** adalah inbox operasional. Booking dibuat oleh pelanggan melalui website; admin tidak membuat booking secara manual.

### Tab booking

- **Needs action**: perlu diperiksa atau membutuhkan tindakan admin.
- **Awaiting payment**: payment request sudah dikirim tetapi belum dibayar.
- **Confirmed trips**: pembayaran sudah diterima dan perjalanan perlu disiapkan.
- **Closed**: booking selesai atau dibatalkan.
- **All**: seluruh booking.

Kolom **Workflow** menjelaskan tindakan berikutnya. Gunakan panduan ini sebagai prioritas kerja.

### Langkah 1: Periksa booking baru

Periksa:

- Nama, email, dan WhatsApp.
- Bahasa komunikasi.
- Paket dan tanggal perjalanan.
- Jumlah tamu.
- Pickup dan catatan pelanggan.
- Harga, add-ons, voucher, dan total.

Jika kontak atau detail perjalanan salah, gunakan **Correct details**. Tindakan ini hanya memperbaiki informasi pelanggan dan logistik; snapshot harga dan pembayaran tidak berubah.

### Langkah 2: Konfirmasi ketersediaan

Jika jadwal tersedia, buka **Booking status** lalu pilih **Confirm availability**.

Status booking berubah dari `new` menjadi `confirmed`. Konfirmasi ini belum berarti pelanggan sudah membayar.

Jika jadwal tidak dapat dipenuhi, hubungi pelanggan terlebih dahulu lalu gunakan **Cancel booking** bila diperlukan.

### Langkah 3: Buat payment request

Pada booking yang sudah confirmed:

- **Alur Standar:**
  1. Buka menu tindakan **Payment**.
  2. Pilih **Create payment request**.
  3. Untuk IDR, nominal charge mengikuti total booking.
  4. Untuk USD, sistem mengambil kurs USD-IDR, menambahkan buffer, lalu menampilkan nominal charge IDR.
  5. Periksa kurs dan nominal sebelum menyimpan.

- **Alur Pemesanan Grup Khusus (Quote Required):**
  Jika pemesanan melebihi tier harga (status pricing `quote_required`), Anda tidak dapat langsung membuat request pembayaran. Alurnya adalah:
  1. Hubungi pelanggan terlebih dahulu via WhatsApp dengan menekan tombol **Open customer WhatsApp**. Diskusikan kebutuhan armada/pemandu tambahan dan sepakati harga per orang.
  2. Klik tindakan **Set final group quote** pada tabel booking.
  3. Masukkan harga per orang yang disepakati, lalu simpan. Sistem akan menghitung ulang total harga secara otomatis dan mengubah status pricing menjadi `quoted`.
  4. Lanjutkan dengan menekan tombol **Create payment request** untuk mengirim tagihan akhir.

Midtrans menerima pembayaran dalam IDR. Quote USD asli tetap disimpan dan ditampilkan kepada pelanggan.

### Langkah 4: Kirim invoice

Setelah payment request berhasil dibuat, kirim salah satu atau kedua kanal:

- **Send payment request email**.
- **Send payment request WhatsApp**.

Invoice adalah permintaan pembayaran sebelum pelanggan membayar. Status handoff menunjukkan apakah email berhasil dikirim, WhatsApp berhasil dikirim, atau fallback WhatsApp manual dibuka.

Jika WhatsApp API gagal, gunakan **Open manual payment request WhatsApp**.

### Langkah 5: Pantau pembayaran

Pelanggan membuka halaman payment status dan membayar melalui Midtrans.

Status dapat diperbarui melalui:

- Webhook Midtrans di production.
- Pemeriksaan otomatis dari halaman pelanggan.
- **Sync Midtrans status** sebagai tindakan manual admin.

Jangan menandai perjalanan selesai hanya karena pembayaran sudah diterima.

Jika payment request expired, failed, atau cancelled, periksa penyebabnya. Admin dapat membuat payment request baru atau membatalkan booking.

### Langkah 6: Receipt setelah pembayaran

Setelah Midtrans menandai pembayaran `paid`:

- Booking tetap `confirmed`.
- Sistem mencoba mengirim receipt email dan konfirmasi WhatsApp.
- Receipt adalah bukti pembayaran diterima, bukan invoice dan bukan faktur pajak.

Jika salah satu kanal gagal, gunakan:

- **Send/Resend payment receipt email**.
- **Send/Resend payment receipt WhatsApp**.
- **Open manual receipt WhatsApp**.

Mengirim ulang receipt tidak mengubah harga, status pembayaran, atau status booking.

### Langkah 7: Selesaikan perjalanan

Booking paid masuk ke **Confirmed trips**. Tim menyiapkan pickup dan detail perjalanan.

Setelah perjalanan benar-benar selesai, buka **Booking status** lalu pilih **Mark trip completed**. Booking kemudian masuk ke tab Closed.

## 8. Perbedaan Status Penting

### Status booking

- `new`: permintaan baru, belum dikonfirmasi.
- `confirmed`: ketersediaan telah dikonfirmasi.
- `completed`: layanan/perjalanan telah selesai.
- `cancelled`: booking dibatalkan.

### Status pembayaran

- `pending`: payment request dibuat.
- `invoice_sent`: invoice telah dikirim.
- `paid`: pembayaran diterima.
- `expired`: link pembayaran kedaluwarsa.
- `failed`: pembayaran atau pembuatan request gagal.
- `cancelled`: payment request dibatalkan.

Status booking dan pembayaran adalah dua hal berbeda. Booking confirmed belum tentu paid, dan booking paid belum berarti perjalanan completed.

## 9. Konten Website

### News Articles dan Article Categories

Gunakan News Articles untuk panduan destinasi, tips perjalanan, dan konten promosi.

Lengkapi judul, slug, kategori, excerpt, isi artikel, cover image, tanggal publikasi, serta metadata SEO. Artikel hanya tampil jika status dan tanggal publikasinya valid.

### FAQs

FAQ bersifat global. Semua FAQ aktif dapat tampil pada homepage dan halaman paket. Atur pertanyaan, jawaban tiga bahasa, urutan, dan status aktif.

### Reviews

Reviews digunakan sebagai social proof di homepage. Maksimal tiga review dapat aktif sekaligus sebagai featured.

### Why Choose Us

Kelola tiga alasan utama memilih Tinggal Jalan. Isi title dan description dalam tiga bahasa, pilih ikon yang tersedia, atur urutan, dan aktifkan maksimal tiga item.

### Trust Stats

Trust Stats menampilkan angka atau bukti kepercayaan, misalnya jumlah traveler atau rating. Maksimal empat item aktif.

### Platform Links

Platform Links menampilkan tautan platform eksternal beserta logo. Maksimal empat item aktif. Gunakan logo yang jelas dan URL lengkap dengan `https://`.

## 10. Site Details

Menu **Site Details** mengatur identitas dan kontak publik:

- Logo.
- Nomor WhatsApp dalam format internasional, misalnya `+6281234567890`.
- Email.
- Alamat bisnis.
- Google Maps URL.
- Jam layanan dalam tiga bahasa.
- Area layanan.
- Footer trust badges.

Simpan perubahan lalu periksa header, footer, dan tombol WhatsApp pada website publik.

## 11. Gateway Settings

### Payment Settings

Gunakan untuk mengatur Midtrans sandbox atau production serta Client Key dan Server Key.

- Gunakan sandbox selama pengembangan.
- Jangan mencampur key sandbox dan production.
- Jalankan test atau buat payment request uji sebelum production.

### Email Gateway Settings

Gunakan provider log untuk pengembangan atau SMTP untuk pengiriman nyata. Untuk Brevo, isi host, port, login, SMTP key, sender email, dan sender name.

Gunakan **Send test email** setelah mengubah konfigurasi.

### WhatsApp Gateway Settings

Pilihan provider:

- **Manual**: membuka `wa.me`; admin tetap menekan tombol kirim.
- **Whatspie**: mengirim pesan melalui session/device Whatspie.

Gunakan **Send test WhatsApp** untuk memeriksa token dan device. Jika Whatspie gagal dan fallback aktif, booking tetap aman dan admin dapat membuka pesan manual.

Mengubah provider memengaruhi pengiriman berikutnya, bukan pesan yang sudah tercatat.

## 12. Users

Menu Users mengelola akun admin.

- Buat akun hanya untuk staf yang memang membutuhkan akses.
- Gunakan password yang kuat.
- Jangan menghapus atau menonaktifkan satu-satunya admin aktif.
- Hapus akun staf segera setelah aksesnya tidak lagi diperlukan.

## 13. Bahasa Pelanggan

Website dan komunikasi pelanggan mendukung:

- Bahasa Indonesia.
- English.
- Simplified Chinese.

Booking menyimpan bahasa komunikasi saat pelanggan mengirim permintaan. Admin dapat mengoreksinya melalui **Correct details**.

Perubahan bahasa hanya berlaku untuk halaman dan pesan yang dibuat atau dikirim setelah perubahan. Pesan lama tidak berubah.

## 14. Upload Gambar

Gunakan gambar yang relevan, terang, dan jelas.

- Cover package harus menunjukkan pengalaman wisata yang dijual.
- Cover destination harus menunjukkan lokasi sebenarnya.
- Logo platform sebaiknya memiliki latar transparan.
- Gunakan ukuran file yang wajar agar website tetap cepat.
- Setelah upload, periksa hasilnya pada desktop dan mobile.

Jika gambar upload tidak tampil, pastikan storage link tersedia:

```bash
docker compose exec app php artisan storage:link
```

## 15. Pemeriksaan Sebelum Publish

Sebelum mengaktifkan konten atau paket:

1. Pastikan konten English utama lengkap.
2. Periksa terjemahan Indonesian dan Chinese.
3. Pastikan slug unik dan mudah dibaca.
4. Periksa gambar dan alt text.
5. Pastikan harga IDR dan USD benar.
6. Pastikan itinerary, includes, excludes, dan pickup jelas.
7. Periksa availability dan voucher.
8. Simpan lalu buka halaman publik terkait.
9. Uji tampilan mobile.
10. Untuk perubahan gateway, selalu jalankan test pengiriman.

## 16. Troubleshooting Singkat

### Website tidak dapat dibuka

```bash
docker compose up -d
docker compose ps
```

Buka `http://127.0.0.1:8000`, bukan HTTPS.

### Database tidak terhubung

Pastikan app dan MySQL berada pada stack yang sama:

```bash
docker compose up -d --force-recreate mysql app
docker compose exec app php artisan optimize:clear
```

### Data lokal kosong

Untuk instalasi lokal baru:

```bash
docker compose exec app php artisan migrate:fresh --seed
```

Perintah tersebut menghapus seluruh data database lokal. Jangan jalankan pada production.

### Styling tidak muncul

```bash
npm run build
docker compose exec app php artisan optimize:clear
```

### Email atau WhatsApp gagal

1. Periksa gateway aktif.
2. Gunakan test action pada halaman gateway.
3. Periksa alamat email atau nomor WhatsApp pelanggan.
4. Lihat pesan error di admin.
5. Gunakan fallback manual jika WhatsApp API tidak tersedia.

### Pembayaran sandbox tidak berubah menjadi paid

Kembali ke halaman payment status agar pemeriksaan otomatis berjalan, atau gunakan **Sync Midtrans status** pada booking.

## 17. Bantuan Teknis

Saat melaporkan masalah kepada developer, sertakan:

- Halaman atau menu tempat masalah terjadi.
- Kode booking jika terkait pelanggan.
- Tindakan terakhir yang dilakukan.
- Pesan error lengkap.
- Screenshot.
- Waktu kejadian.
- Apakah masalah terjadi di local, sandbox, atau production.

Jangan mengirim API key, password SMTP, OTP, PIN, atau data kartu melalui screenshot maupun chat.
