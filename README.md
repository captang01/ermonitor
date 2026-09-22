# 🚀 ERMonitor - System & Server Monitoring Tool

**ERMonitor** adalah aplikasi pemantauan server dan sistem berbasis web yang dirancang untuk memantau performa server, mendeteksi gangguan secara *real-time*, memberikan notifikasi peringatan (*alerts*), serta menyediakan laporan log dan audit sistem secara komprehensif.

---

## 🌟 Fitur Utama

* **Dashboard Interaktif**: Menampilkan ringkasan status server, performa sistem, dan indikator kesehatan secara *real-time*.
* **Manajemen Server**: Tambah, edit, hapus, dan kelola daftar server yang dipantau dengan mudah.
* **Sistem Notifikasi & Alerting**: Memantau batasan ambang batas (*thresholds*) dan memicu peringatan jika terjadi kegagalan atau gangguan pada server.
* **REST API Lengkap**: Menyediakan endpoint API untuk *health check*, statistik, pemantauan (*monitoring*), *alerts*, dan *servers*.
* **Manajemen Pengguna & Autentikasi**: Fitur pendaftaran, *login*, *logout*, *session management*, dan pengaturan hak akses pengguna.
* **Audit & Logging**: Pencatatan riwayat aktivitas pengguna dan perubahan sistem secara detail melalui fitur audit log.
* **Progressive Web App (PWA)**: Dilengkapi dengan file `manifest.webmanifest` dan *service worker* (`sw.js`) untuk akses cepat dan pengalaman layaknya aplikasi native.
* **Perkakas & Alat Bantu (Tools)**: Script pendukung untuk *setup* LAN, diagnostik jaringan, pengujian HTTPS/Cloudflare, verifikasi upgrade, serta *backup* database otomatis.

---

## 🛠️ Persyaratan Sistem
Web Server: Apache / Nginx

PHP: Versi 7.4 atau lebih baru (dengan ekstensi pdo_mysql, json, curl aktif)

Database: MySQL / MariaDB

OS: Windows / Linux / macOS

## 📦 Langkah Instalasi
Clone Repository

Bash
git clone [https://github.com/username/ermonitor.git](https://github.com/username/ermonitor.git)
cd ermonitor
Konfigurasi Database

Buat database baru di MySQL/MariaDB (misal: ermonitor_db).

Impor skema dasar database dari direktori database/schema.sql:

Bash
mysql -u root -p ermonitor_db < database/schema.sql
Jika melakukan pembaruan versi, jalankan skrip migrasi tambahan seperti database/upgrade_step7.sql.

Atur Konfigurasi Aplikasi

Buka file config/database.php dan sesuaikan kredensial database Anda (host, username, password, nama database).

Sesuaikan parameter aplikasi utama pada config/app.php.

Konfigurasi Web Server

Arahkan Document Root web server Anda ke folder ermonitor/ atau letakkan direktori ermonitor/ di dalam folder htdocs / www.

Pastikan file .htaccess aktif jika menggunakan Apache.

## 🔧 Tools & Otomasi (Windows)
Di dalam folder tools/, tersedia berbagai skrip utilitas untuk mempermudah operasional:

Penyetelan Jaringan: Jalankan setup-lan.bat atau diagnose-lan.bat untuk mengonfigurasi dan mendiagnosis akses aplikasi via jaringan lokal (LAN).

Pemeriksaan HTTPS & Cloudflare: Gunakan check-https.bat atau check-https.ps1 untuk memverifikasi sertifikat SSL/HTTPS, serta manfaatkan cloudflared-config.yml.example untuk pengaturan tunneling.

Backup & Verifikasi: Jalankan backup-database.bat untuk mencadangkan database secara berkala, serta upgrade-step7.bat / verify-step7.bat saat melakukan pembaruan versi aplikasi.

## 📖 Dokumentasi Pengguna
Panduan penggunaan aplikasi lengkap bagi pengelola dan pengguna dapat diakses di folder docs/:

docs/PANDUAN_PENGGUNA_ERMONITOR.md

docs/PANDUAN_PENGGUNA_ERMONITOR.pdf

## 📄 Lisensi
Proyek ini dilisensikan di bawah MIT License.

---

# 📁 Struktur Direktori

```text
ermonitor/
├── admin/                     # Modul Administrasi
│   ├── alerts.php             # Kelola peringatan
│   ├── audit.php              # Log audit aktivitas
│   ├── dashboard.php          # Dashboard utama admin
│   ├── reports.php            # Laporan pemantauan
│   ├── users.php              # Kelola pengguna
│   └── servers/               # CRUD Manajemen Server
│       ├── add.php
│       ├── delete.php
│       ├── edit.php
│       └── index.php
├── api/                       # REST API Endpoints
│   ├── alerts.php
│   ├── health.php
│   ├── monitoring.php
│   ├── servers.php
│   └── stats.php
├── assets/                    # Aset Statis (CSS, JS, Gambar)
│   ├── css/style.css
│   ├── img/
│   └── js/ (icons.js, script.js)
├── auth/                      # Otentikasi & Sesi
│   ├── check.php
│   ├── login.php
│   ├── logout.php
│   ├── register.php
│   └── session.php
├── config/                    # Konfigurasi Aplikasi & Database
│   ├── app.php
│   └── database.php
├── database/                  # Skema & Migration SQL
│   ├── schema.sql
│   └── upgrade_step7.sql
├── docs/                      # Dokumentasi Panduan Pengguna
│   ├── PANDUAN_PENGGUNA_ERMONITOR.md
│   └── PANDUAN_PENGGUNA_ERMONITOR.pdf
├── includes/                  # Komponen Layout & Reusable Scripts
│   ├── audit.php
│   ├── footer.php
│   ├── head.php
│   ├── sidebar.php
│   └── topbar.php
├── tools/                     # Utility & Script Otomasi (.bat, .ps1, .yml)
│   ├── backup-database.bat
│   ├── check-https.bat / .ps1
│   ├── cloudflared-config.yml.example
│   ├── diagnose-lan.bat
│   ├── setup-lan.bat
│   ├── upgrade-step7.bat
│   └── verify-step7.bat
├── index.php                  # Halaman Utama
├── login.php / register.php   # Akses Gerbang Pengguna
├── manifest.webmanifest / sw.js # PWA Configuration
└── README.md                  # Dokumentasi Proyek
