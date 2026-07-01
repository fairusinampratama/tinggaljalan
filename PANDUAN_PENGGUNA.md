# Panduan Pengguna (User Guide) - Tinggal Jalan Admin Panel

Selamat datang di Panduan Pengguna untuk panel administrasi **Tinggal Jalan**. Dokumen ini akan membantu Anda memahami cara mengelola konten, pesanan, dan pengaturan website menggunakan Filament Admin Panel.

---

## 1. Akses Login & Dashboard

Untuk masuk ke panel admin, buka URL berikut di browser Anda:
```txt
URL: http://localhost:8000/admin (atau domain utama saat di-hosting)
```
Gunakan Email dan Password yang telah diberikan oleh tim developer (contoh lokal: `admin@tinggaljalan.test`).

Setelah login, Anda akan diarahkan ke **Dashboard**. Di sini, Anda dapat melihat ringkasan (widget) metrik penting seperti total pesanan bulan ini, jumlah paket wisata aktif, dan lainnya.

---

## 2. Manajemen Paket Wisata (Tour Packages)

Modul ini adalah pusat utama untuk layanan Anda. Pengisian data paket wisata dibagi menjadi beberapa tahapan (Wizard):

1. **Detail Dasar:** Nama paket, deskripsi singkat, kategori (Open Trip / Private Trip), dan harga dasar.
2. **Media & Galeri:** Mengunggah foto utama (thumbnail) dan foto-foto galeri tambahan.
3. **Itinerary (Rencana Perjalanan):** Mengatur jadwal hari per hari atau jam per jam untuk paket tersebut.
4. **Ketersediaan (Jadwal Keberangkatan):** Menentukan tanggal berapa saja paket wisata ini tersedia.
5. **Add-ons (Layanan Tambahan):** Menambahkan opsi ekstra seperti dokumentasi drone, penjemputan khusus, atau sewa alat yang dapat dibeli saat pemesanan.

> **Tips:** Pastikan Anda mengisi kolom yang bertanda bintang merah (*) karena itu adalah data wajib (Required).

---

## 3. Manajemen Pemesanan (Bookings)

Setiap pesanan yang masuk dari pelanggan (baik dari website maupun WhatsApp) akan dicatat di menu **Bookings**.

- **Status Pesanan:** Pesanan memiliki siklus hidup seperti `New` (Baru), `Confirmed` (Terkonfirmasi), `Completed` (Selesai), dan `Cancelled` (Dibatalkan).
- **Detail Harga & Add-ons:** Anda dapat melihat rincian tamu, tanggal perjalanan, dan layanan tambahan yang dipilih oleh pelanggan.
- **Pembayaran (Booking Payments):** Setiap pesanan terhubung ke menu pembayaran yang mencatat status Midtrans (Pending/Paid/Expired/Failed) serta catatan log notifikasi WhatsApp/Email ke pelanggan.

---

## 4. Manajemen Berita & Artikel (News Articles)

Menu ini digunakan untuk membuat blog, artikel promosi, atau pengumuman.
Pembuatan artikel kini dibagi dalam **4 langkah (Wizard)**:

1. **Informasi Utama:** Mengisi judul artikel dan memilih Kategori.
2. **Konten Artikel:** Menulis isi artikel menggunakan Rich Text Editor (Trix/Quill) agar mudah diformat (tebal, miring, list).
3. **Gambar Sampul (Media):** Mengunggah gambar thumbnail yang akan tampil di halaman depan website.
4. **Penerbitan & SEO (Publishing & Meta):** Menentukan status aktif (Is Active), fitur highlight (Is Featured), mengatur urutan (Sort Order), dan menulis SEO Meta Title/Description agar artikel mudah dicari di Google.

> **Catatan:** Jangan lupa mencentang `Is Active` agar artikel langsung tayang di website umum.

---

## 5. Mengelola Pengaturan Situs (Site Settings)

Segala informasi global perusahaan Anda dikelola melalui menu **Site Settings**.

- **Informasi Kontak:** Nomor WhatsApp, Email Utama, dan Alamat Bisnis.
- **Tautan Eksternal:** Link Google Maps untuk memudahkan pelanggan mencari lokasi kantor.
- **Jam Operasional & Area Layanan:** Detail waktu buka kantor dan wilayah cakupan wisata.
- **Logo & Trust Badges:** Mengunggah logo perusahaan dan lencana keamanan.

---

## 6. Fitur Pendukung Lainnya

- **Destinations (Destinasi):** Mengelola daftar kota/lokasi wisata. Paket wisata akan dihubungkan ke lokasi-lokasi ini.
- **FAQs (Tanya Jawab):** Mengatur pertanyaan yang sering diajukan oleh pelanggan agar tampil di halaman informasi website.
- **Reviews (Ulasan):** Anda dapat menampilkan ulasan terbaik dari pelanggan. Centang `Is Featured` agar ulasan tersebut muncul di halaman utama website.
- **Why Choose Us:** Mengelola poin-poin alasan mengapa pelanggan harus memilih Tinggal Jalan, lengkap dengan ikon dan penjelasannya.

---

## Panduan Pengisian Data (Helper Text)
Pada setiap kolom isian (form) di panel admin, perhatikan teks kecil berwarna abu-abu (Helper Text) di bawah kolom tersebut. Teks itu akan memandu Anda mengenai:
- Format penulisan yang benar.
- Batasan jumlah karakter (contoh: maksimal 255 karakter).
- Kapan data tersebut akan ditampilkan ke pelanggan.

Jika Anda mengalami kesulitan, hubungi tim developer melalui dokumentasi teknis atau issue tracker repositori.
