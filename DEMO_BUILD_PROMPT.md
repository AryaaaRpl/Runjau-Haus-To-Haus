# Prompt Implementasi Demo --- RFID Running Event dengan QR

Salin seluruh prompt ini ke AI coding assistant yang memiliki akses ke
repository proyek.

------------------------------------------------------------------------

## Peran

Bertindak sebagai senior Laravel engineer dan product-minded software
engineer. Bangun **demo MVP** berdasarkan `PRD.md` yang tersedia di
repository. Tujuan saat ini adalah menghasilkan demo yang bisa
dipresentasikan kepada guru, bukan sistem timing lomba produksi.

## Aturan kerja wajib

1.  Baca `PRD.md` sampai selesai sebelum mengubah file.
2.  Periksa repository terlebih dahulu: framework/version, struktur
    direktori, dependensi, autentikasi, database, dan test yang sudah
    ada.
3.  Jika repository sudah berisi aplikasi, adaptasikan implementasi
    tanpa merusak fitur yang tidak terkait.
4.  Jika informasi penting tidak tersedia, pilih default paling
    sederhana yang konsisten dengan PRD, dokumentasikan asumsi, dan
    jangan mengarang requirement resmi.
5.  Jangan mengintegrasikan hardware RFID/NFC. QR hanya membuka halaman
    peserta berbasis token.
6.  Jangan mengklaim waktu yang dicatat sebagai waktu fisik saat peserta
    melewati garis start/finish. Waktu demo adalah waktu server menerima
    aksi.
7.  Jangan menambahkan microservices, React, Redis, queue broker, atau
    dependency berat tanpa kebutuhan yang jelas.
8.  Utamakan kode yang mudah dipahami, struktur Laravel konvensional,
    validasi server, authorization, dan test.
9.  Jangan hanya menghasilkan rencana atau pseudocode. Implementasikan
    fitur dalam repository, jalankan test yang tersedia, dan perbaiki
    kegagalan yang disebabkan perubahanmu.
10. Jangan menghapus data atau menjalankan operasi destruktif terhadap
    database tanpa memeriksa environment dan meminta persetujuan bila
    ada risiko kehilangan data.
11. Jangan pernah menaruh secret, password, atau token admin di
    repository.
12. Jangan mengklaim test berhasil jika belum benar-benar
    menjalankannya.

## Target fitur

### A. Admin

-   Login admin menggunakan mekanisme autentikasi Laravel yang sesuai
    dengan repository.
-   Dashboard ringkasan peserta: registered, running, finished, total
    lap tercatat, dan aktivitas terbaru.
-   Daftar peserta dengan pencarian/filter sederhana bila sesuai scope.
-   Detail peserta dan timeline start/lap/finish.
-   Tampilkan QR peserta atau tautan yang dapat dibuka untuk demo.
-   Sediakan cara aman untuk reset data simulasi hanya jika sesuai PRD;
    konfirmasi sebelum reset.

### B. Peserta

-   Halaman mobile-first dari URL dengan token acak.
-   Tampilkan nama demo, nomor bib, status, waktu mulai, daftar lap, dan
    waktu selesai.
-   Tombol Mulai, Catat Putaran, dan Selesai.
-   Disable tombol saat request sedang diproses untuk mengurangi double
    submit.
-   Tampilkan feedback sukses/gagal berdasarkan respons server.
-   Minta konfirmasi sebelum Selesai.
-   Tolak aksi yang tidak sesuai status.

### C. Data dan event

-   Implementasikan entitas dan relasi sesuai PRD.
-   Buat 8--12 peserta fiktif dengan status/timeline yang bervariasi.
-   Setiap aksi mutasi memiliki UUID idempotensi. Jika retry mengirim
    UUID yang sama, jangan membuat event kedua.
-   Simpan waktu penerimaan dari server sebagai waktu demo resmi.
-   Simpan client timestamp hanya jika diperlukan untuk diagnostik;
    jangan percaya sebagai waktu resmi.
-   Pastikan validasi status dilakukan di server, bukan hanya di
    frontend.
-   Gunakan transaction dan unique constraint yang sesuai untuk mencegah
    event ganda atau perubahan status yang tidak konsisten.
-   Jangan menghapus raw event hanya karena suatu aksi tidak valid; jika
    event ditolak sebelum disimpan, dokumentasikan perilakunya. Pilih
    desain yang konsisten dengan PRD dan jelaskan di README.

### D. QR

-   Generate QR yang mengarah ke URL HTTPS/token peserta pada lingkungan
    deployment, atau URL lokal yang benar untuk development.
-   Jangan menaruh nama lengkap atau data sensitif di dalam QR.
-   Jangan menggunakan ID berurutan sebagai token publik.
-   QR tidak dianggap bukti autentikasi pemilik kartu; dokumentasikan
    batasan ini.

### E. UI/UX

-   Gunakan stack yang sudah ada. Default jika repository baru: Laravel
    Blade, Tailwind CSS, dan JavaScript ringan.
-   Desain rapi, responsif, mudah didemokan di HP dan desktop.
-   Visualisasikan status peserta dengan jelas.
-   Sertakan empty, loading, success, dan error states.
-   Beri label DEMO dan keterangan bahwa pencatatan waktu berasal dari
    aksi peserta/server, bukan reader timing otomatis.
-   Hindari elemen dekoratif yang mengganggu keterbacaan saat
    presentasi.

## Pengujian minimum

Tambahkan dan jalankan test yang sesuai untuk: 1. Admin yang belum login
tidak dapat mengakses dashboard. 2. Token peserta valid membuka data
peserta yang benar. 3. Token tidak valid tidak membocorkan data. 4.
Urutan start → lap → finish berhasil. 5. Aksi lap sebelum start ditolak.
6. Start kedua tidak membuat start event kedua. 7. Lap setelah finish
ditolak. 8. UUID event yang sama tidak menghasilkan dua event. 9. Data
dashboard sesuai dengan data peserta. 10. Akses peserta tidak dapat
mengubah peserta lain hanya dengan mengganti parameter yang mudah
ditebak.

Jika test membutuhkan database yang belum tersedia, jelaskan
prasyaratnya dan tetap jalankan pemeriksaan lain yang memungkinkan.

## Dokumentasi wajib

Buat atau perbarui `README.md` yang memuat: - Prasyarat versi PHP,
Composer, Node bila diperlukan, dan MySQL. - Instalasi dependency. -
Konfigurasi `.env` tanpa memasukkan secret. - Perintah migration dan
seed. - Cara menjalankan aplikasi. - Cara login demo dengan kredensial
lokal yang aman; jangan hardcode password produksi. - Cara membuka QR
peserta. - Cara menjalankan test. - Batasan demo dan asumsi yang belum
dikonfirmasi.

## Cara bekerja

Lakukan langkah berikut secara berurutan: 1. Inspeksi repository dan
rangkum temuan singkat. 2. Tunjukkan rencana implementasi singkat yang
sesuai dengan struktur repository. 3. Implementasikan fitur dalam urutan
yang menjaga aplikasi tetap bisa dijalankan. 4. Jalankan
formatter/linter/test yang tersedia. 5. Perbaiki regresi yang berasal
dari perubahan. 6. Periksa diff akhir untuk file yang tidak terkait,
secret, migrasi berbahaya, dan fitur yang tidak diminta. 7. Berikan
laporan akhir: fitur yang selesai, file penting yang berubah, perintah
menjalankan demo, hasil test aktual, asumsi, dan batasan yang masih ada.

Mulai dengan membaca `PRD.md` dan menginspeksi repository. Jangan mulai
dengan menulis ulang PRD atau menambah fitur di luar scope.
