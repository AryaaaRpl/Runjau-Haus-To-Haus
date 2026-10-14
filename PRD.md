# PRD --- RFID Running Event Demo

**Versi:** 0.1 --- Demo awal\
**Status:** Draft untuk validasi kebutuhan\
**Tujuan dokumen:** Menjadi acuan pembuatan demo yang dapat ditunjukkan
kepada guru/pemilik proyek sebelum system design dimatangkan.

------------------------------------------------------------------------

## 1. Ringkasan Produk

RFID Running Event adalah demo aplikasi web untuk membantu penyelenggara
kegiatan lari mencatat peserta dan progres lomba menggunakan kartu
peserta yang memiliki QR Code. Untuk demo awal, QR digunakan sebagai
cara membuka halaman peserta melalui kamera HP; sistem belum
mengandalkan pembacaan RFID/NFC secara langsung.

Demo berfokus pada alur yang mudah diperlihatkan: 1. Panitia melihat dan
mengelola data peserta demo. 2. Setiap peserta memiliki QR Code unik
yang membuka halaman peserta. 3. Peserta membuka halaman melalui QR,
lalu melakukan aksi **Mulai**, **Catat Putaran**, dan **Selesai** sesuai
simulasi. 4. Sistem menampilkan waktu event, jumlah putaran, status
peserta, dan dashboard panitia.

> **Penting:** Demo ini membuktikan alur produk dan UX, bukan
> membuktikan timing lomba profesional atau integrasi hardware RFID.
> Waktu yang tercatat adalah waktu server menerima aksi, bukan
> pengukuran otomatis saat peserta melintasi garis start/finish.

## 2. Latar Belakang dan Masalah

Penyelenggara membutuhkan cara sederhana untuk melihat identitas peserta
dan progres lari. Detail format acara, jumlah peserta, aturan lap, serta
tingkat akurasi timing yang diperlukan belum dikonfirmasi. Karena guru
meminta demo terlebih dahulu, dokumen ini memakai asumsi sementara dan
tidak menganggapnya sebagai keputusan final.

## 3. Tujuan Demo

-   Memperlihatkan alur peserta dari membuka QR hingga selesai.
-   Menunjukkan dashboard panitia yang menampilkan status, putaran, dan
    waktu.
-   Mendemonstrasikan bagaimana sistem menolak atau menandai aksi yang
    tidak sesuai urutan.
-   Mendapatkan feedback guru sebelum arsitektur dan aturan lomba
    dikunci.

### 3.1 Bukan tujuan demo

-   Integrasi USB RFID reader, NFC, atau hardware timing otomatis.
-   Membuktikan akurasi waktu kompetisi.
-   Menangani ribuan peserta atau high availability.
-   Membuat aplikasi native Android/iOS.
-   Menjamin QR tidak bisa difoto, disalin, atau dibagikan.
-   Menetapkan aturan perlombaan resmi yang belum diberikan guru.
-   Implementasi microservices, message broker, atau infrastruktur
    kompleks.

## 4. Pengguna dan Hak Akses

### 4.1 Panitia/Admin

-   Masuk ke dashboard demo.
-   Melihat daftar peserta dan detail progres.
-   Melihat event tap dan status peserta.
-   Melakukan reset data demo atau simulasi ulang jika fitur tersebut
    dibuat.
-   Tidak boleh mengubah event secara diam-diam; jika ada koreksi
    manual, perubahan harus tercatat.

### 4.2 Peserta

-   Membuka halaman peserta lewat QR unik.
-   Melihat identitas minimum yang diperlukan.
-   Melihat status lomba, waktu mulai, jumlah putaran, dan waktu
    terakhir tercatat.
-   Melakukan aksi mulai, catat putaran, dan selesai sesuai status
    peserta.

## 5. Asumsi Sementara

Semua butir berikut harus diberi label **asumsi demo**, bukan fakta
final: 1. QR Code menjadi mekanisme akses awal; NFC/RFID belum
diintegrasikan. 2. Setiap peserta demo mempunyai QR unik yang berisi URL
dengan token acak. 3. Demo menggunakan satu event dan satu titik
pencatatan putaran secara konseptual. 4. Peserta melakukan aksi melalui
HP sendiri. 5. Internet tersedia dan aplikasi berjalan online. 6. Waktu
event ditentukan server ketika aksi diterima. 7. Untuk demo, peserta
menekan tombol pada website; QR tidak otomatis mencatat putaran setiap
kali kartu dipindai. 8. Aturan lap, durasi event, batas peserta, dan
definisi hasil resmi belum ditetapkan. 9. Data peserta dan hasil yang
digunakan adalah data fiktif.

## 6. Alur Utama Demo

### 6.1 Alur peserta

1.  Peserta memindai QR pada kartu menggunakan kamera/QR scanner HP.
2.  Browser membuka URL unik untuk peserta tersebut.
3.  Website menampilkan identitas peserta dan status event.
4.  Peserta menekan **Mulai**.
5.  Ketika menyelesaikan putaran, peserta menekan **Catat Putaran**.
6.  Website memperbarui jumlah putaran dan daftar waktu putaran.
7.  Setelah selesai, peserta menekan **Selesai**.
8.  Website menampilkan ringkasan hasil demo.

### 6.2 Alur panitia

1.  Admin login.
2.  Admin membuka dashboard event.
3.  Dashboard menampilkan jumlah peserta, belum mulai, sedang berlari,
    selesai, total putaran, dan aktivitas terbaru.
4.  Admin membuka detail peserta untuk melihat urutan event dan waktu.
5.  Admin dapat membuka halaman/fitur QR peserta untuk demo pemindaian.

## 7. Status Peserta dan Aturan Transisi

Status awal: - `registered` --- terdaftar, belum mulai. - `running` ---
telah memulai dan belum selesai. - `finished` --- peserta telah menekan
selesai.

Aturan demo: - `registered` → `running` hanya melalui aksi Mulai. -
`running` → `running` melalui Catat Putaran. - `running` → `finished`
melalui aksi Selesai. - Peserta `finished` tidak boleh menambah putaran
atau memulai lagi tanpa reset admin/demo. - Aksi yang tidak sesuai
status ditolak oleh server. - Jumlah putaran hanya bertambah saat server
menerima aksi Catat Putaran yang valid. - Batas minimum waktu lap belum
diberlakukan sebagai aturan resmi. Jika ditampilkan sebagai proteksi
demo, nilainya harus dapat diubah dan diberi label konfigurasi demo,
bukan aturan lomba final.

## 8. Fitur MVP Demo

### P0 --- Wajib

-   Dashboard admin sederhana.
-   Login admin demo.
-   Daftar peserta dengan data fiktif.
-   Halaman peserta berdasarkan token QR unik.
-   Tombol Mulai, Catat Putaran, Selesai.
-   Penyimpanan waktu mulai, waktu setiap putaran, dan waktu selesai.
-   Riwayat event per peserta.
-   Status peserta dan jumlah putaran.
-   QR Code unik per peserta yang dapat ditampilkan/diunduh atau dicetak
    untuk demo.
-   Validasi status di backend.
-   Tampilan responsif untuk HP dan desktop.
-   Empty state, loading state, error state, dan konfirmasi aksi
    penting.
-   Seed data dan cara menjalankan demo yang terdokumentasi.

### P1 --- Jika waktu cukup

-   Dashboard diperbarui otomatis melalui polling sederhana.
-   Filter peserta berdasarkan nama, nomor, dan status.
-   Halaman detail peserta dengan timeline.
-   Fitur reset simulasi dengan konfirmasi.
-   Ekspor data demo ke CSV.

### Di luar scope

-   Integrasi RFID/NFC reader.
-   Verifikasi identitas peserta secara kuat.
-   GPS tracking.
-   Timing otomatis di garis finis.
-   Pembayaran/registrasi publik.
-   Offline-first sync.
-   Multi-event, multi-checkpoint, atau multi-tenant.
-   Notifikasi SMS/WhatsApp.

## 9. Data dan Entitas Konseptual

### `events`

-   `id`
-   `name`
-   `status` --- draft, active, completed
-   `started_at` (opsional jika event memakai jadwal global)
-   `created_at`, `updated_at`

### `participants`

-   `id`
-   `event_id`
-   `bib_number` --- nomor peserta yang ditampilkan
-   `name` --- nama fiktif untuk demo
-   `qr_token` --- token acak unik, jangan memakai ID berurutan sebagai
    token publik
-   `status` --- registered, running, finished
-   `started_at` nullable
-   `finished_at` nullable
-   `created_at`, `updated_at`

### `tap_events`

Catatan aksi mentah yang diterima server: - `id` - `participant_id` -
`event_type` --- start, lap, finish - `event_uuid` --- UUID unik yang
dibuat klien untuk mencegah penggandaan request yang sama -
`client_occurred_at` nullable --- informasi diagnostik, bukan sumber
waktu resmi - `received_at` --- waktu server menerima aksi - `metadata`
nullable --- data diagnostik minimum yang tidak sensitif - `created_at`,
`updated_at`

Aturan penyimpanan: - Unique constraint pada `event_uuid` untuk mencegah
satu request yang sama tercatat dua kali. - Request ulang dengan UUID
sama mengembalikan hasil event yang sudah ada, bukan membuat event
baru. - Dua aksi fisik yang berbeda memiliki UUID berbeda; validasi
status tetap menentukan apakah keduanya sah. - `received_at` adalah
waktu penerimaan server, bukan jaminan waktu lintasan fisik. - Untuk
demo, `tap_events` dapat menjadi sumber timeline dan jumlah lap; tabel
terpisah `lap_records` hanya ditambahkan jika diperlukan untuk menyimpan
hasil validasi yang terpisah dari raw event.

### Relasi konseptual

-   Satu event memiliki banyak peserta.
-   Satu peserta memiliki banyak `tap_events`.
-   Satu peserta hanya memiliki satu token QR aktif pada satu waktu
    dalam demo.

## 10. Prinsip Waktu dan Hasil

-   Semua waktu resmi demo dihasilkan server.
-   Tampilkan zona waktu secara konsisten, misalnya WIB atau zona event
    yang dikonfirmasi.
-   Durasi lap dihitung dari selisih waktu server antar event lap yang
    diterima.
-   Jika peserta menekan tombol terlambat, waktu yang tercatat juga
    terlambat. UI harus menjelaskan bahwa ini adalah pencatatan mandiri.
-   Jangan menyebut hasil demo sebagai timing resmi atau otomatis.
-   Definisi apakah lap pertama dimulai dari waktu start atau dihitung
    setelah satu putaran harus dikonfirmasi. Untuk demo, pilih satu
    perilaku dan jelaskan di UI.

## 11. API Konseptual

Endpoint boleh disesuaikan dengan gaya Laravel yang dipilih: -
`GET /api/demo/participants/{token}` --- detail peserta yang boleh
ditampilkan. - `POST /api/demo/participants/{token}/start` --- mulai
event peserta. - `POST /api/demo/participants/{token}/laps` --- catat
satu putaran. - `POST /api/demo/participants/{token}/finish` ---
selesaikan event. - `GET /api/demo/admin/dashboard` --- metrik dashboard
admin. - `GET /api/demo/admin/participants` --- daftar peserta. -
`GET /api/demo/admin/participants/{id}/events` --- riwayat event
peserta.

Setiap endpoint mutasi harus memvalidasi token, event, status peserta,
dan UUID idempotensi. Endpoint admin harus memerlukan autentikasi.

## 12. UI/UX

### Halaman peserta

-   Mobile-first, tombol besar yang mudah ditekan.
-   Identitas peserta dan nomor bib terlihat jelas.
-   Status lomba jelas: Belum mulai, Sedang berlari, Selesai.
-   Tampilkan waktu mulai, jumlah putaran, daftar waktu putaran, dan
    waktu selesai.
-   Minta konfirmasi sebelum menyelesaikan lomba.
-   Tampilkan pesan sukses/gagal yang jelas setelah aksi server.
-   Hindari tombol yang dapat membuat event ganda ketika request masih
    diproses.

### Dashboard admin

-   Ringkasan jumlah peserta per status.
-   Total putaran tercatat.
-   Daftar peserta dan status terbaru.
-   Detail peserta dengan timeline start/lap/finish.
-   Indikator waktu pembaruan terakhir.
-   QR untuk membuka halaman peserta demo.
-   Gunakan data fiktif dan tandai lingkungan sebagai DEMO.

## 13. Keamanan dan Privasi Demo

-   Gunakan HTTPS pada deployment.
-   Token QR harus acak dan tidak mudah ditebak.
-   QR/token tidak boleh mengandung nama lengkap atau informasi sensitif
    secara langsung.
-   Data peserta demo harus fiktif.
-   Admin dilindungi autentikasi.
-   Batasi informasi peserta yang ditampilkan secara publik.
-   Jangan menyimpan informasi perangkat atau lokasi yang tidak
    diperlukan.
-   QR yang disalin dapat digunakan orang lain; demo tidak mengklaim
    autentikasi pemilik kartu.
-   Tambahkan rate limiting dasar pada endpoint mutasi jika
    memungkinkan.
-   Jangan mengandalkan validasi frontend saja.

## 14. Kegagalan yang Perlu Ditangani

  -----------------------------------------------------------------------
  Skenario                            Perilaku yang diharapkan
  ----------------------------------- -----------------------------------
  Token QR tidak valid                Tampilkan halaman tidak ditemukan
                                      tanpa membocorkan data

  Peserta belum mulai lalu menekan    Tolak aksi dan tampilkan instruksi
  Catat Putaran                       

  Peserta menekan Mulai dua kali      Tolak atau kembalikan status yang
                                      sama tanpa membuat start baru

  Request terkirim dua kali           UUID yang sama hanya menghasilkan
                                      satu event

  Peserta selesai lalu menekan Catat  Tolak aksi
  Putaran                             

  Koneksi terputus                    Tampilkan gagal dan minta pengguna
                                      mencoba ulang; jangan mengklaim
                                      tersimpan

  Admin membuka dashboard tanpa login Arahkan ke login

  Data peserta kosong                 Tampilkan empty state

  Reset demo                          Minta konfirmasi dan jelaskan bahwa
                                      data simulasi akan dihapus
  -----------------------------------------------------------------------

## 15. Acceptance Criteria

Demo dapat diterima jika: 1. Admin dapat login dan melihat daftar
peserta demo. 2. Setiap peserta memiliki QR unik yang membuka halaman
peserta yang tepat. 3. Peserta dapat menjalankan urutan start → lap →
lap → finish. 4. Waktu server tercatat untuk setiap aksi valid. 5.
Jumlah putaran dan status diperbarui dengan benar. 6. Urutan aksi yang
salah ditolak oleh backend. 7. Pengiriman ulang event dengan UUID yang
sama tidak membuat catatan ganda. 8. Dashboard menampilkan data yang
konsisten dengan timeline peserta. 9. Halaman nyaman digunakan di HP dan
dashboard nyaman di desktop. 10. README menjelaskan instalasi,
konfigurasi, seeding, login demo, dan batasan demo. 11. UI menyatakan
bahwa waktu berasal dari aksi peserta dan belum merupakan timing
otomatis. 12. Tidak ada klaim bahwa NFC/RFID atau hardware reader sudah
terintegrasi.

## 16. Data Seed Demo

Gunakan 8--12 peserta fiktif dengan variasi status: - Beberapa peserta
`registered`. - Beberapa peserta `running` dengan 1--3 lap. - Beberapa
peserta `finished`.

Gunakan nama dan nomor peserta fiktif. Buat data timeline yang konsisten
secara kronologis. Jangan menggunakan data pribadi nyata.

## 17. Stack Teknis

Stack final mengikuti arahan pemilik proyek. Default provisional: -
Backend: Laravel. - Database: MySQL. - Frontend: Laravel Blade +
Tailwind CSS + JavaScript ringan. - QR generation: library Laravel/PHP
yang kompatibel dan dipelihara. - Authentication: mekanisme autentikasi
Laravel yang sesuai dengan versi proyek. - Deployment: lingkungan demo
yang mendukung HTTPS.

Jangan menambahkan React, microservices, Redis, queue broker, atau
infrastruktur tambahan tanpa alasan yang diperlukan untuk demo.

## 18. Keputusan Terbuka

Hal berikut tidak boleh dianggap final: 1. Nama dan branding event. 2.
Jumlah peserta dan kapasitas yang dibutuhkan. 3. Definisi satu putaran
dan apakah putaran pertama dihitung sejak start. 4. Apakah peserta harus
mencatat setiap lap sendiri. 5. Apakah hasil hanya untuk monitoring atau
menentukan peringkat resmi. 6. Toleransi tap ganda/minimum lap time. 7.
Hosting dan domain demo. 8. Apakah admin dapat melakukan koreksi manual.
9. Apakah peserta wajib login atau cukup token QR. 10. Apakah nantinya
akan ada integrasi reader RFID/NFC terpusat.

## 19. Tahapan Implementasi Demo

1.  Buat skeleton Laravel dan konfigurasi database.
2.  Implementasikan migration, model, factory/seed data.
3.  Implementasikan autentikasi admin dan data peserta.
4.  Implementasikan halaman QR/token peserta.
5.  Implementasikan aksi start/lap/finish beserta validasi server dan
    idempotensi.
6.  Implementasikan dashboard dan timeline.
7.  Implementasikan QR generator.
8.  Jalankan acceptance test dan skenario kegagalan.
9.  Tulis README serta batasan demo.
10. Presentasikan demo dan kumpulkan feedback guru sebelum merevisi
    system design.

------------------------------------------------------------------------

## 20. Catatan Engineering

Demo ini adalah *prototype untuk validasi alur*. Setelah guru menyetujui
tujuan, aturan lomba, dan cara peserta melakukan pencatatan, revisi PRD
dan system design sebelum menjadikan aplikasi sebagai sistem operasional
lomba.
