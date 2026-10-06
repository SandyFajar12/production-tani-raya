# TaniRaya ERP — Laravel 8.83.27

Project Laravel untuk ERP stok sparepart armada operasional PT TaniRaya. Database dibuat
manual lewat file `.sql` (bukan lewat `php artisan migrate`).

## 1. Install dependency

```
composer install
```

(File `vendor/` sengaja tidak disertakan — jalankan perintah di atas dulu, butuh koneksi internet ke Packagist/GitHub.)

## 2. Siapkan file .env

```
copy .env.example .env
php artisan key:generate
```

Buka `.env`, sesuaikan bagian database kalau perlu (default sudah diarahkan ke `taniraya_erp` / `root` / password kosong, cocok untuk setup Laragon default):

```
DB_DATABASE=taniraya_erp
DB_USERNAME=root
DB_PASSWORD=
```

## 3. Buat database & import schema

```
mysql -u root -p -e "CREATE DATABASE taniraya_erp"
mysql -u root -p taniraya_erp < database/taniraya_erp.sql
```

File `database/taniraya_erp.sql` sudah berisi seluruh tabel + data awal (role, permission,
5 user contoh, kategori sparepart, satuan, beberapa sparepart/armada/supplier contoh).

## 4. Jalankan server

```
php artisan serve
```

Buka `http://127.0.0.1:8000` — otomatis diarahkan ke halaman login.

## Login default

Semua user contoh di bawah pakai password yang sama: **`password`** (wajib diganti setelah login pertama, lewat menu Master User).

| Email | Role |
|---|---|
| dedi.k@taniraya.co.id | Admin |
| rina.h@taniraya.co.id | Approver |
| budi.s@taniraya.co.id | Staf Gudang |
| sari.w@taniraya.co.id | Staf Pembelian |
| agus.p@taniraya.co.id | Staf Lapangan (nonaktif, contoh user nonaktif) |

## Struktur penting

- `routes/web.php` — semua route
- `app/Http/Controllers/` — 1 controller per modul
- `app/Models/` — 1 model per tabel
- `resources/views/layouts/app.blade.php` — layout utama, meng-include:
  - `resources/views/partials/header.blade.php` — **header terpisah**, satu file untuk semua halaman (otomatis beda tampilan antara Dashboard vs halaman modul lain)
  - `resources/views/partials/footer.blade.php` — **footer terpisah**, satu file untuk semua halaman
- Halaman modul lain (`resources/views/{modul}/index.blade.php`) hanya berisi `@section('content')` — tidak perlu sentuh header/footer sama sekali kalau mau ubah tampilan tengah halaman
- `public/css/style.css` & `public/js/main.js` — desain sama persis seperti prototype sebelumnya
- `database/taniraya_erp.sql` — seluruh schema + seed data (lihat dokumen "Rencana Modul & Struktur Database" sebelumnya untuk penjelasan tiap tabel)

## Yang belum diimplementasi (lanjutan)

- Reset password lewat email sungguhan (saat ini form di `/lupa-password` hanya menampilkan
  pesan sukses tanpa kirim email — perlu setup `MAIL_*` di `.env` + `Illuminate\Auth\Passwords`
  kalau mau diaktifkan penuh)
- Halaman edit terpisah untuk Master Sparepart / Armada / Supplier / User (saat ini hanya
  create + delete lewat halaman ini; route `update` sudah tersedia di `web.php`, tinggal
  ditambahkan tombol "Edit" yang membuka form terisi data lama)
- Approval pre-order saat ini 1 tingkat (belum berjenjang)
