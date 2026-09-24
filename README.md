# POS Kasir — Coffee Shop

Sistem Point of Sale untuk coffee shop berbasis **Laravel 12** + **Blade** + **Alpine.js** + **MariaDB**, dengan autentikasi PIN dan kontrol shift kasir.

## Fitur

- **Login PIN** — autentikasi custom (tanpa email/password/Breeze), peran `CASHIER`, `MANAGER`, `ADMIN`
- **POS (Kasir)** — keranjang belanja, diskon & pajak, metode bayar (Tunai/QRIS/Debit/Kredit), cetak struk
- **Shift Kasir** — buka/tutup shift, kas awal, aktivitas shift, rekonsiliasi kas (kas masuk/keluar), selisih kas
- **Transaksi** — daftar, detail, cek ulang struk, **void transaksi dengan alasan**
- **Produk & Kategori** — CRUD lengkap (ADMIN/MANAGER), SKU unik, status aktif/nonaktif
- **Laporan** — laporan harian & penjualan per produk dengan rentang tanggal (ADMIN/MANAGER)
- **Dashboard** — ringkasan penjualan hari ini; kasir hanya melihat transaksinya sendiri

## Teknologi

- Laravel 12, Blade, Alpine.js (CDN/Vite), Tailwind CSS v4
- MariaDB/MySQL (XAMPP)
- Uang disimpan sebagai **integer** (rupiah, tanpa desimal)

## Instalasi

```bash
git clone https://github.com/rafli089/POS-KASIR.git
cd coffee-pos
composer install
npm install && npm run build
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

### Konfigurasi Database (XAMPP)

`.env`:

```
DB_CONNECTION=mysql
DB_HOST=localhost        # wajib 'localhost', bukan 127.0.0.1
DB_PORT=3306
DB_DATABASE=coffee_pos
DB_USERNAME=pos
DB_PASSWORD=pos123
```

Mulai MariaDB manual (jika belum ada service):

```powershell
Start-Process C:\xampp\mysql\bin\mysqld.exe -WorkingDirectory C:\xampp\mysql\bin
```

### Pengguna Bawaan (seeder)

| Nama | PIN | Peran |
|------|-----|-------|
| Admin | `123456` | ADMIN |
| Kasir 1–5 | `123451`–`123455` | CASHIER |

## Pengujian

```bash
php artisan test
```

21 fitur test / 85 assertions: alur POS, CRUD produk & kategori, void transaksi, laporan, kas masuk/keluar, hak akses per peran.