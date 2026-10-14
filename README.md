# Runjau Haus To Haus Management System - MVP Demo

Aplikasi web demonstrasi MVP untuk manajemen acara lari dan pencatatan putaran (loop counter) berbasis pemindaian kartu proximity RFID. Dirancang khusus untuk memvalidasi alur sistem, visualisasi layar operator, dan arsitektur data sebelum aturan kompetisi resmi dikunci.

---

## 1. Prasyarat Sistem

- **PHP**: >= 8.2 (Teruji pada PHP 8.4)
  - Ekstensi wajib: `pdo`, `pdo_sqlite` (atau `pdo_mysql`), `mbstring`, `openssl`, `curl`
- **Composer**: >= 2.x
- **Node.js & NPM**: Node >= 18.x, NPM >= 9.x (untuk build aset Tailwind CSS & Vite)
- **Database**:
  - **SQLite** (Default: langsung siap pakai tanpa perlu instalasi server database lokal terpisah)
  - **MySQL / MariaDB** (Dapat dikonfigurasi melalui `.env`)

---

## 2. Instalasi dan Setup

1. **Clone / Buka Direktori Proyek**:
   ```bash
   cd C:/Users/Aryaaa/Documents/Project-Event
   ```

2. **Instal Dependensi Backend (PHP/Composer)**:
   ```bash
   composer install
   ```

3. **Instal Dependensi Frontend (Node/NPM) & Build Aset**:
   ```bash
   npm install
   npm run build
   ```

4. **Konfigurasi Lingkungan (`.env`)**:
   Salin berkas contoh konfigurasi jika belum ada:
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

   *Konfigurasi default menggunakan SQLite:*
   ```ini
   DB_CONNECTION=sqlite
   # DB_DATABASE otomatis mengarah ke database/database.sqlite
   ```

   *Opsi menggunakan MySQL (jika tersedia):*
   ```ini
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=rfid_running_event
   DB_USERNAME=root
   DB_PASSWORD=
   ```

   *Konfigurasi Opsional RFID:*
   ```ini
   # Waktu debounce untuk mencegah double-tap tidak sengaja (detik)
   RFID_DEBOUNCE_SECONDS=3

   # UID contoh untuk pelari Rio Millen saat seeder dijalankan
   SAMPLE_RFID_UID=0007352925
   ```

5. **Jalankan Migrasi & Data Seeder**:
   ```bash
   php artisan migrate:fresh --seed
   ```

---

## 3. Menjalankan Aplikasi

Jalankan server pengembangan Laravel:
```bash
php artisan serve
```
Akses aplikasi melalui browser di: **`http://localhost:8000`**

---

## 4. Akun Login Administrator Demo

Untuk mengakses dashboard panitia dan manajemen peserta:
- **URL Login**: `http://localhost:8000/login`
- **Email**: `admin@runningevent.test`
- **Password**: `password123`

*(Catatan: Kredensial di atas khusus lingkungan demo lokal dan tidak memuat secret produksi).*

---

## 5. Cara Mendemonstrasikan Pemindaian RFID

Buka layar pemindaian operator di **`http://localhost:8000/scan`** (atau halaman utama `/`).

### Kasus A: Menggunakan Alat Scanner RFID Fisik (USB Keyboard Emulation)
1. Hubungkan alat USB RFID Reader ke laptop/komputer.
2. Buka halaman `/scan`. Kursor otomatis terfokus pada field input pemindaian kartu.
3. Dekatkan kartu RFID ke alat pembaca.
4. Reader akan mengetikkan UID kartu (termasuk *leading zeros* seperti `0007352925`) dan mengirimkan karakter `Enter`.
5. Sistem langsung memproses scan, mencatat putaran baru di database, dan memperbarui tampilan:
   - **Nama Peserta**: (misal: RIO MILLEN)
   - **Nomor BIB**: (misal: 420)
   - **Jumlah Loop**: Dinamis bertambah dari data transaksi database
   - **Tabel Aktivitas Terakhir**: Ter-update secara langsung tanpa reload halaman.

### Kasus B: Mode Demo Manual (Jika Alat Reader Belum Terhubung)
Halaman scan menyediakan panel **Mode Demo Manual**:
- Klik tombol **"Tap Kartu RIO MILLEN (0007352925)"** untuk menyimulasikan tap kartu pertama (loop bertambah dari 0 ke 1).
- Klik tombol **"Tap Kartu DEWI LESTARI (0008412034)"** untuk menyimulasikan peserta lain.
- Klik tombol **"Uji Reject (0009999999)"** untuk membuktikan bahwa kartu yang belum terdaftar ditolak oleh backend dan tidak menambah putaran.
- Lakukan klik dua kali berturut-turut pada kartu yang sama untuk melihat proteksi **Double-Tap Debounce (< 3 detik)** beraksi.

---

## 6. Cara Mendaftarkan / Memperbarui UID Kartu Fisik

Jika guru atau panitia memiliki kartu RFID fisik baru:
1. Login sebagai Admin di `/login`.
2. Masuk ke menu **Peserta** (`/admin/participants`).
3. Cari peserta (misal: **RIO MILLEN**) lalu klik **Edit**.
4. Dekatkan kartu ke scanner pada kolom **UID Kartu RFID** (atau ketik UID fisiknya).
5. Klik **Simpan Perubahan**. Kartu fisik tersebut sekarang terhubung langsung ke peserta tersebut.

---

## 7. Endpoint API Backend

| Metode | Endpoint | Deskripsi |
|---|---|---|
| `POST` | `/api/rfid/scan` | Memproses scan RFID, validasi debounce, dan pencatatan loop transaksional |
| `GET` | `/api/participants/by-card/{uid}` | Mengambil data peserta berdasarkan UID kartu RFID |
| `GET` | `/api/participants/{participant}/loops` | Mengambil seluruh riwayat loop peserta |
| `GET` | `/api/dashboard/summary` | Mengambil ringkasan metrik statistik (peserta, total loop, total scan) |
| `GET` | `/api/dashboard/recent-scans` | Mengambil 15 data scan terbaru untuk live display |

---

## 8. Menjalankan Pengujian Otomatis (Automated Tests)

Jalankan test suite menggunakan PHPUnit bawaan Laravel:
```bash
php artisan test
```

Suite pengujian mencakup 16 skenario pengujian (66 assertions) yang memverifikasi:
1. Pendaftaran peserta oleh admin.
2. Penolakan nomor BIB duplikat.
3. Penolakan UID kartu duplikat.
4. Pemeliharaan *leading zeros* pada UID string (contoh: `0007352925`).
5. Pencarian peserta terdaftar via UID.
6. Penolakan kartu tidak dikenal & pencatatan log rejected tanpa membuat loop record.
7. Keberhasilan scan & pencatatan loop pertama.
8. Akurasi kalkulasi loop setelah multiple valid scans.
9. Bukti scan rejected tidak meningkatkan jumlah loop.
10. Konsistensi riwayat loop dengan jumlah loop yang ditampilkan.
11. Proteksi keamanan transaksi & debounce double-tap (< 3 detik).
12. Proteksi rute admin dari akses unauthenticated.
13. Penanganan input kosong/spasi/tidak valid.

---

## 9. Batasan Demo dan Asumsi yang Belum Dikonfirmasi

1. **Sumber Waktu Resmi**: Waktu yang dicatat adalah *server-received timestamp* saat request diterima aplikasi, bukan *photo-finish timing* fisik tingkat milidetik di garis lintasan.
2. **Karakter UID**: UID RFID diperlakukan sebagai nomor identifikasi kartu, bukan bukti autentikasi biometrik pemegang kartu (kartu dapat dipinjamkan atau diduplikasi secara fisik).
3. **Integrasi Hardware**: Demo dioptimalkan untuk pembaca USB *Keyboard Emulation* (standar reader proximity umum). Jika nantinya menggunakan reader berbasis Serial/RS485 atau SDK vendor tertutup, lapisan adapter reader dapat diintegrasikan ke `ScanService`.
4. **Aturan Perlombaan**: Aturan resmi seperti interval minimum putaran, diskualifikasi, titik checkpoint ganda (split checkpoints), dan kategori lomba masih bersifat asumsi demo dan menunggu konfirmasi guru/penyelenggara.
