# Panduan Pengguna (User Guide) - Tinggal Jalan Admin Panel

Selamat datang di Panduan Pengguna untuk panel administrasi **Tinggal Jalan**. Platform ini dibangun khusus untuk memudahkan Anda mengatur operasional agen perjalanan Anda, mulai dari pembuatan paket wisata (seperti *Bromo Sunrise* atau *Tumpak Sewu*), manajemen pesanan, hingga integrasi pembayaran otomatis.

---

## 1. Akses Login & Dashboard

Untuk masuk ke panel admin, buka URL berikut di browser Anda:
```txt
URL: http://localhost:8000/admin (Ganti localhost dengan domain Anda jika sudah online)
```
Gunakan kredensial default dari developer untuk login pertama kali (Email: `admin@tinggaljalan.test`).

Setelah login, **Dashboard** akan menampilkan widget analitik ringkas. Anda bisa melihat statistik berapa pesanan bulan ini, jumlah destinasi aktif, dan lain sebagainya.

---

## 2. Manajemen Paket Wisata (Tour Packages)

Ini adalah fitur utama website Anda. Pembuatan paket wisata menggunakan sistem tahapan (Wizard) agar Anda tidak pusing melihat form yang terlalu panjang.

1. **Detail Dasar (Basic Details):** Tulis nama paket wisata Anda (misal: *Jogja 3 Hari 2 Malam*). Atur harga dasar dalam IDR maupun USD.
2. **Media & Galeri:** Unggah foto utama (thumbnail) dan tambahkan foto-foto pendukung ke dalam galeri.
3. **Itinerary (Rencana Perjalanan):** Di sini Anda bisa membuat jadwal terperinci dari Hari 1 hingga Hari terakhir, atau jadwal per jam (misal jam 03:00 menuju titik kumpul Jeep Bromo).
4. **Ketersediaan (Availabilities):** Kapan paket ini tersedia? Anda bisa menambahkan tanggal keberangkatan khusus.
5. **Add-ons (Layanan Tambahan):** Anda dapat membuat opsi layanan ekstra, seperti:
   - *Drone Documentation*
   - *Airport Pickup (Penjemputan Bandara)*
   - *Sewa Jaket Tebal*
   Pelanggan dapat menambahkan (dan membayar) opsi ini saat melakukan checkout.

---

## 3. Destinasi (Destinations)

Sebelum membuat paket wisata, pastikan Anda telah mendaftarkan **Destinasi** (misal: *Bromo*, *Jogja*, *Medan*, *Tumpak Sewu*).
Setiap destinasi memiliki kolom gambar, wilayah (Region), provinsi, dan deskripsi singkat. Seluruh data wisata akan dihubungkan (di-tag) ke destinasi-destinasi ini.

---

## 4. Manajemen Pemesanan & Pembayaran (Bookings & Payments)

Setiap pesanan pelanggan yang masuk akan muncul di menu **Bookings**.

- **Workflow Status:** Anda dapat memantau pesanan mulai dari berstatus `New`, `Confirmed`, hingga `Completed`.
- **Riwayat Kontak & Konfirmasi:** Panel ini mencatat waktu persis (timestamp) kapan pesanan dikonfirmasi (`confirmed_at`) atau dibatalkan (`cancelled_at`).
- **Integrasi Midtrans:** Jika pesanan dibuat melalui website, link pembayaran (Snap URL Midtrans) otomatis di-generate. Status pembayaran (`Pending`, `Paid`, `Expired`) akan berubah secara otomatis saat pelanggan membayar (melalui webhook).
- **Log Notifikasi:** Terdapat pelacakan pesan otomatis. Anda bisa melihat apakah notifikasi receipt/invoice melalui Email dan WhatsApp sudah sukses dikirim (`sent_at`), dibuka pelanggan (`opened_at`), atau gagal (`failed_at`).

---

## 5. Berita & Artikel Promosi (News Articles)

Buat artikel blog, tips wisata (seperti *Tips Menikmati Sunrise di Bromo*), atau pengumuman melalui fitur berita ini.
Sama seperti Tour Packages, pembuatan artikel dibagi dalam 4 tahapan (Wizard) meliputi: Informasi Dasar, Konten Editor (Rich Text), Media Gambar Sampul, dan Pengaturan SEO (Meta Description untuk Google).

---

## 6. Mengelola Pengaturan Situs (Site Settings & Landing Page)

Anda tidak perlu menghubungi programmer untuk mengganti informasi dasar website Anda. Akses menu di bagian pengaturan untuk:

- **Site Settings:** Mengubah Nomor WhatsApp admin, Email Kontak, Alamat Kantor, Jam Layanan, Link Google Maps, dan Logo Perusahaan.
- **Why Choose Us:** Mengelola 3 atau 4 poin keunggulan Tinggal Jalan yang muncul di halaman depan (Home).
- **Trust Stats:** Mengubah metrik reputasi Anda (misalnya: *100+ Happy Customers*, *50+ Destinations*).
- **Reviews (Ulasan):** Masukkan ulasan terbaik dari pelanggan Anda. Centang opsi `Is Featured` agar ulasan tersebut tampil menonjol di halaman depan.
- **FAQs:** Kelola daftar pertanyaan umum (Tanya Jawab) untuk membantu pelanggan yang masih bingung.

---

## 💡 Tips Penting: Perhatikan "Helper Text" (Teks Abu-Abu)

Saat Anda mengisi form apapun di panel admin (mulai dari membuat paket hingga mengisi pengaturan web), selalu baca teks kecil berwarna abu-abu yang ada di bawah setiap kolom isian.

Teks ini dibuat khusus untuk memandu Anda:
- Batasan karakter (misal: "Maksimal 255 karakter").
- Format gambar yang disarankan.
- Peringatan agar format SEO tetap rapi.
- Cara mengisi harga agar konversi mata uang sesuai.

Ikuti petunjuk tersebut agar data yang ditampilkan ke publik di website Tinggal Jalan terlihat sempurna!
