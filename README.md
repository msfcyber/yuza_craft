# Printlab 3D

Webapp toko 3D print responsif berbasis Laravel 13, MySQL, Blade, Tailwind CSS, dan Three.js. Mendukung katalog, preview model STL/3MF, stok per warna, ready/PO, checkout tanpa akun, tracking, riwayat via OTP WhatsApp, serta dashboard admin.

## Menjalankan lokal

Persyaratan: PHP 8.3+, Composer, Node.js, npm, dan MySQL.

1. Salin `.env.example` menjadi `.env` (`Copy-Item .env.example .env` di PowerShell, atau `cp .env.example .env` di Bash).
2. Siapkan database MySQL, lalu sesuaikan `.env` (`DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`). Contoh:

   ```sql
   CREATE DATABASE printlab_3d CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```

3. Instal dependency dan siapkan database:

   ```bash
   composer install
   npm install
   php artisan key:generate
   php artisan migrate --seed
   php artisan storage:link
   npm run build
   ```

4. Jalankan aplikasi:

   ```bash
   php artisan serve
   ```

Database seed mengisi beberapa produk contoh dan warna. Untuk membuat akun admin pertama, isi variabel ini di `.env`, lalu jalankan `php artisan db:seed`:

```dotenv
ADMIN_NAME="Nama Admin"
ADMIN_EMAIL="admin@example.com"
ADMIN_PASSWORD="gunakan-password-kuat"
```

Login admin tersedia di `/admin/login`.

## WhatsApp OTP

Mode lokal memakai `WHATSAPP_MODE=log`; OTP development ditulis ke `storage/logs/laravel.log`. Untuk produksi, atur kredensial WhatsApp Cloud API berikut dan siapkan template OTP yang sudah disetujui Meta dengan parameter body `{{1}}`:

```dotenv
WHATSAPP_MODE=cloud
WHATSAPP_PHONE_NUMBER_ID=
WHATSAPP_TOKEN=
WHATSAPP_API_VERSION=v22.0
WHATSAPP_OTP_TEMPLATE=otp_verification
WHATSAPP_LANGUAGE=id
```

Jangan gunakan mode log di produksi.

## Upload model 3D

File STL/3MF disimpan pada storage privat dan disajikan melalui route produk. Gambar produk disimpan pada public disk. Batas aplikasi untuk file model adalah 50 MB; sesuaikan `upload_max_filesize` dan `post_max_size` PHP bila konfigurasi server lebih rendah.

## Pemeriksaan

```bash
php artisan test --compact
npm run build
vendor/bin/pint --format agent
```
