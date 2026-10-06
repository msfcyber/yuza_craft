# Product Requirements Document (PRD)

## 1. Ringkasan produk

Webapp penjualan 3D print yang responsif untuk desktop dan mobile. Admin mengelola model, file 3D, harga, warna, stok ready, dan waktu produksi pre-order (PO). Pelanggan dapat melihat katalog dan pratinjau model 3D, memilih warna, lalu memesan tanpa membuat akun.

Pelanggan menerima kode transaksi untuk melacak pesanan. Untuk melihat seluruh riwayat pesanan yang terkait dengan nomor HP, pelanggan harus memverifikasi kepemilikan nomor tersebut melalui OTP WhatsApp.

## 2. Tujuan dan indikator keberhasilan

### Tujuan

- Memudahkan admin mengelola produk dan ketersediaan berdasarkan model dan warna.
- Memudahkan pelanggan memilih produk, memahami perbedaan ready dan PO, serta melakukan pemesanan tanpa akun.
- Menyediakan pencatatan transaksi yang akurat berdasarkan model, warna, harga, dan jumlah yang dipilih.
- Memberikan tracking transaksi dan akses aman ke riwayat pesanan pelanggan.

### Indikator keberhasilan MVP

- Admin dapat membuat dan memperbarui model, harga, file, warna, stok, dan estimasi PO.
- Pelanggan dapat menyelesaikan pemesanan dari perangkat desktop maupun mobile.
- Setiap pesanan memiliki kode transaksi unik dan dapat dilacak.
- Riwayat pesanan berdasarkan nomor HP hanya dapat dilihat setelah OTP berhasil diverifikasi.
- Stok ready tidak dapat dipesan melebihi jumlah yang tersedia.

## 3. Pengguna dan hak akses

### Admin

Admin masuk menggunakan akun terautentikasi dan dapat mengelola katalog, ketersediaan, pesanan, serta status pembayaran.

### Pelanggan

Pelanggan tidak perlu membuat akun untuk melihat katalog atau membuat pesanan. Kode transaksi digunakan untuk tracking pesanan tertentu. OTP WhatsApp diperlukan untuk melihat seluruh pesanan berdasarkan nomor HP.

## 4. Platform dan batasan teknis

- Backend: Laravel.
- Database: MySQL.
- Antarmuka: responsif untuk desktop dan mobile.
- Format unggahan model 3D: STL dan 3MF.
- Model 3D dapat ditinjau secara interaktif di halaman produk, minimal dengan kontrol rotasi dan zoom.
- Pembayaran dilakukan di luar aplikasi pada MVP; aplikasi hanya mencatat status pembayaran yang dikonfirmasi admin.
- Pengiriman diatur manual pada MVP kecuali ditetapkan berbeda pada tahap implementasi.
- Penyedia OTP WhatsApp dan layanan hosting/storage belum ditentukan.

## 5. Ruang lingkup MVP

### Termasuk

- Login dan dashboard admin.
- CRUD model produk dan pengelolaan unggahan STL/3MF.
- Katalog produk publik dan halaman detail dengan pratinjau 3D.
- Pengelolaan warna serta stok per kombinasi model dan warna.
- Konfigurasi harga dan estimasi waktu PO.
- Checkout tanpa login.
- Kode transaksi dan halaman tracking.
- Verifikasi OTP WhatsApp untuk akses riwayat pesanan per nomor HP.
- Pencatatan pembayaran manual dan pembaruan status pesanan.
- Antarmuka responsif dan validasi input dasar.

### Tidak termasuk pada MVP

- Pembayaran online/payment gateway.
- Akun pelanggan dan password pelanggan.
- Integrasi otomatis kurir atau kalkulasi ongkos kirim.
- Multi-vendor, program loyalitas, kupon, atau ulasan produk.
- Pelacakan proses produksi secara otomatis dari mesin 3D printer.

## 6. Kebutuhan fungsional

### 6.1 Katalog dan detail produk

- Pengunjung dapat melihat daftar model aktif, gambar, nama, harga mulai, serta ringkasan ketersediaan.
- Pengunjung dapat membuka halaman detail yang menampilkan deskripsi, gambar, harga, pilihan warna, informasi ready/PO, dan pratinjau 3D.
- Pratinjau mendukung interaksi rotasi dan zoom, serta tetap dapat digunakan pada layar mobile.
- Pemilihan warna memperbarui ketersediaan dan informasi waktu produksi yang ditampilkan.
- Produk nonaktif atau yang belum siap dijual tidak dapat dipesan.

### 6.2 Pengelolaan produk dan file 3D oleh admin

- Admin dapat membuat, mengubah, mengaktifkan, dan menonaktifkan model.
- Data model mencakup nama, deskripsi, harga dasar, gambar, status aktif, dan file 3D.
- Sistem menerima unggahan STL dan 3MF serta memvalidasi format dan batas ukuran file.
- Admin dapat mengganti file model dan gambar produk.
- Sistem menyimpan file secara aman dan mengaitkannya dengan model yang benar.
- Kegagalan unggah atau file yang tidak valid ditampilkan dengan pesan yang jelas.

### 6.3 Warna, stok, dan PO

- Admin dapat mengelola pilihan warna yang tersedia.
- Ketersediaan disimpan untuk setiap kombinasi model dan warna.
- Untuk status ready, admin dapat menetapkan jumlah stok.
- Untuk status PO, admin dapat menetapkan estimasi waktu produksi/pemenuhan.
- Sistem mencegah pemesanan jumlah ready yang melebihi stok tersedia.
- Perubahan stok dan status ketersediaan hanya berlaku pada pesanan baru; rincian pesanan terdahulu tetap tersimpan.

### 6.4 Pemesanan pelanggan

- Pelanggan memilih model, warna, dan jumlah, lalu memasukkan nama, nomor HP, serta informasi pengiriman yang diperlukan.
- Sebelum konfirmasi, sistem menampilkan ringkasan produk, warna, jumlah, harga, status ready/PO, dan estimasi waktu.
- Setelah pesanan berhasil dibuat, sistem membuat kode transaksi unik dan menampilkan instruksi pembayaran/pengiriman yang dikonfigurasi admin.
- Sistem menyimpan snapshot nama model, warna, jumlah, harga satuan, subtotal, dan estimasi waktu pada saat transaksi.
- Pesanan ready mengurangi atau mencadangkan stok secara konsisten agar stok tidak terjual ganda.
- Pesanan PO tidak mengurangi stok ready.

### 6.5 Tracking dan riwayat pesanan

- Pelanggan dapat memasukkan kode transaksi untuk melihat status pesanan tersebut tanpa login.
- Halaman tracking hanya menampilkan informasi yang diperlukan untuk mengenali dan mengikuti transaksi, bukan seluruh riwayat nomor HP.
- Pelanggan dapat meminta OTP WhatsApp ke nomor HP yang digunakan saat pemesanan.
- Setelah OTP valid, pelanggan dapat melihat daftar pesanan yang terkait dengan nomor HP terverifikasi.
- OTP memiliki masa berlaku dan pembatasan percobaan/kiriman ulang.
- Jika kode transaksi atau OTP salah/kedaluwarsa, sistem menampilkan pesan yang tidak membocorkan data pesanan.

### 6.6 Dashboard transaksi admin

- Admin dapat melihat dan mencari pesanan berdasarkan kode transaksi, nomor HP, tanggal, serta status.
- Detail pesanan menampilkan item, model, warna, jumlah, harga snapshot, kontak, pengiriman, dan catatan waktu.
- Admin dapat memperbarui status pesanan dan status pembayaran.
- Riwayat perubahan status dan waktu perubahan dicatat.
- Admin dapat menandai pembayaran sebagai belum dibayar atau sudah dikonfirmasi.

### 6.7 Kustomisasi clicker

- Admin dapat menandai produk sebagai clicker dan menetapkan batas karakter nama (1–24 karakter).
- Admin mengatur pilihan warna, stok ready, dan estimasi PO secara terpisah untuk komponen base, tombol, dan tulisan nama.
- Pelanggan dapat memilih warna base, warna tombol, dan warna tulisan nama secara independen.
- Pelanggan memasukkan nama yang akan diembos; nama dibatasi sesuai konfigurasi produk dan hanya menerima huruf, angka, spasi, titik, apostrof, dan tanda hubung.
- Halaman produk menampilkan penghitung karakter, pratinjau nama, dan pratinjau warna komponen bila file model mendukung bagian yang dapat dibedakan.
- Pesanan menyimpan snapshot teks, panjang karakter, warna setiap komponen, status ready/PO, serta referensi stok komponen.
- Harga MVP clicker tetap memakai harga dasar produk; belum ada biaya per karakter.
- Jika kombinasi mengandung komponen PO, estimasi pesanan mengikuti waktu PO terlama. Stok komponen ready yang dipilih dicadangkan/dikurangi saat checkout dan dikembalikan bila pesanan dibatalkan.

## 7. Alur utama

### Alur pelanggan membuat pesanan

1. Pelanggan membuka katalog dan memilih model.
2. Pelanggan meninjau pratinjau 3D, memilih warna dan jumlah.
3. Sistem menampilkan stok ready atau estimasi PO beserta harga.
4. Pelanggan mengisi nama, nomor HP, dan informasi pengiriman.
5. Pelanggan meninjau ringkasan dan mengirim pesanan.
6. Sistem menyimpan pesanan, mengelola stok ready, serta menampilkan kode transaksi dan instruksi pembayaran.

### Alur pelanggan melihat riwayat

1. Pelanggan memasukkan nomor HP pada halaman riwayat.
2. Sistem mengirim OTP melalui WhatsApp.
3. Pelanggan memasukkan OTP yang valid.
4. Sistem menampilkan pesanan yang terhubung dengan nomor HP terverifikasi.

### Alur admin mengelola ready/PO

1. Admin masuk ke dashboard.
2. Admin memilih model dan warna.
3. Admin menetapkan status ready beserta stok, atau PO beserta estimasi waktu.
4. Perubahan tampil pada halaman produk dan berlaku untuk pesanan baru.

## 8. Status pesanan dan pembayaran

Status pesanan awal yang disarankan:

- `Menunggu pembayaran`
- `Pembayaran dikonfirmasi`
- `Diproses`
- `Siap dikirim/diambil`
- `Selesai`
- `Dibatalkan`

Status pembayaran dicatat terpisah dari status pemenuhan, minimal `Belum dibayar` dan `Dikonfirmasi`. Aturan pelepasan stok saat pesanan dibatalkan atau tidak dibayar perlu ditetapkan sebelum implementasi.

## 9. Data utama

- **Admin:** identitas login dan status akun.
- **Model:** nama, deskripsi, harga, status, gambar, dan referensi file 3D.
- **Warna:** nama dan nilai warna/tampilan.
- **Varian/ketersediaan:** model, warna, status ready/PO, jumlah stok, dan estimasi PO. Untuk clicker, ketersediaan dicatat terpisah per komponen base, tombol, dan tulisan.
- **Pesanan:** kode transaksi, nama dan nomor HP pelanggan, informasi pengiriman, status pesanan, status pembayaran, dan waktu dibuat.
- **Item pesanan:** snapshot nama model, warna, jumlah, harga satuan, subtotal, estimasi pemenuhan, serta konfigurasi clicker bila berlaku.
- **Riwayat status:** status sebelumnya/baru, waktu, dan admin yang melakukan perubahan.
- **Verifikasi OTP:** nomor tujuan, hash/kode OTP, masa berlaku, jumlah percobaan, dan status verifikasi.

## 10. Persyaratan nonfungsional

- Antarmuka dapat digunakan pada ukuran layar desktop dan mobile.
- Validasi diterapkan di server untuk seluruh data pesanan, file unggahan, dan perubahan admin.
- Akses admin dilindungi autentikasi dan otorisasi.
- OTP tidak disimpan atau dicatat dalam bentuk yang dapat dibaca setelah proses pengiriman; percobaan dibatasi.
- Kode transaksi harus unik dan sulit ditebak.
- File unggahan divalidasi berdasarkan format dan ukuran, serta tidak boleh dieksekusi sebagai aplikasi.
- Pesanan menyimpan snapshot data produk agar riwayat transaksi tetap konsisten setelah perubahan katalog.
- Kegagalan proses penyimpanan pesanan tidak boleh menyebabkan stok berubah sebagian.

## 11. Kriteria penerimaan MVP

1. Admin dapat mengunggah file STL dan 3MF yang valid dan mengaitkannya dengan model.
2. Pelanggan dapat membuka pratinjau 3D serta berinteraksi dengan model pada halaman produk.
3. Pilihan warna menampilkan stok ready atau estimasi PO yang benar untuk model tersebut.
4. Pesanan ready tidak dapat melebihi stok yang tersedia, termasuk jika ada pemesanan bersamaan.
5. Pesanan berhasil menghasilkan kode transaksi dan dapat dilacak.
6. Riwayat pesanan hanya muncul setelah OTP WhatsApp valid untuk nomor yang bersangkutan.
7. Admin dapat mengubah status pesanan dan status pembayaran, dan perubahan tercatat.
8. Alur katalog, pemesanan, tracking, dan dashboard dapat digunakan di desktop maupun mobile.
9. Admin dapat mengaktifkan tipe clicker, mengatur stok/PO tiap warna komponen, dan batas karakter nama.
10. Checkout clicker menyimpan nama serta warna base, tombol, dan tulisan; stok ready per komponen terpilih berkurang dan dipulihkan saat pembatalan.

## 12. Keputusan yang masih perlu ditetapkan

- Penyedia OTP WhatsApp dan konfigurasi template/pengirim.
- Layanan hosting database dan penyimpanan file.
- Batas ukuran unggahan STL/3MF.
- Metode, biaya, dan informasi pengiriman yang diminta saat checkout.
- Aturan pembatalan, batas waktu pembayaran, dan kapan stok ready dilepas kembali.
- Apakah harga berbeda per warna/varian atau hanya per model.
- Apakah perlu bukti pembayaran diunggah ke aplikasi meski pembayaran dilakukan di luar aplikasi.

## 13. Risiko teknis yang perlu divalidasi

- Pratinjau STL dan 3MF di browser dapat memerlukan penanganan format yang berbeda. Implementasi perlu memvalidasi dukungan file nyata, performa pada perangkat mobile, serta opsi konversi bila format tertentu tidak dapat ditampilkan langsung.
- Pewarnaan base dan tombol per bagian memerlukan model 3MF multi-part dengan mesh yang dapat dikenali (misalnya bernama `base` dan `button`). STL satu bagian tidak menyediakan pemisahan material yang andal.
- Nama dan warna tulisan disimpan untuk produksi dan ditampilkan sebagai pratinjau teks; pembuatan geometri emboss manufaktur tetap bergantung pada model clicker beserta area/posisi tulisan yang sesuai.
- File 3D dapat berukuran besar; batas ukuran, validasi, penyimpanan, dan waktu pemuatan pratinjau perlu diuji.
- OTP WhatsApp bergantung pada penyedia eksternal, kredensial, biaya, dan konfigurasi nomor pengirim.
