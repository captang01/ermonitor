# ERMonitor Step 7 — Production Hardening

Step 7 menambahkan hardening operasional dan keamanan di atas Step 6.

## Fitur baru

- Audit Log untuk login, logout, registrasi, perubahan profil/password, perubahan role, dan administrasi server.
- Alert transition history untuk event Warning/Offline dan recovery ke Online.
- Halaman Alerts menampilkan incident aktif dan riwayat event.
- Halaman Audit Log khusus Administrator.
- Reports menampilkan ringkasan event alert terbaru.
- Logout menggunakan POST + CSRF.
- Service worker tidak menyimpan halaman authenticated `/admin/`, `/profile.php`, `/auth/`, atau API ke cache.
- Backup database melalui `tools\backup-database.bat`.

## Upgrade database

Jalankan `database/schema.sql` pada database `ermonitor`. Tabel lama `users` dan `servers` tetap digunakan; tabel berikut akan dibuat bila belum ada:

- `audit_logs`
- `alert_events`

## Backup

Di Windows/XAMPP, jalankan:

`tools\backup-database.bat`

File backup SQL disimpan ke folder `backups\`.

## Alur operator

1. Login.
2. Periksa Dashboard dan indikator LIVE.
3. Periksa Fleet / Online / Warning / Offline.
4. Buka Alerts jika terdapat Warning atau Offline.
5. Periksa Servers untuk inventory node.
6. Administrator dapat mengelola node dan akun.
7. Gunakan Reports untuk snapshot operasional.
8. Administrator dapat memeriksa Audit Log.
9. Gunakan My Profile untuk profil/password.
10. Sign out setelah selesai.

## Deployment

LAN:

`http://PC-IP/ermonitor/`

HTTPS publik dapat menggunakan arsitektur Step 6/7 dengan Cloudflare Tunnel atau konfigurasi HTTPS Apache.

Jangan mengekspos MySQL 3306, Prometheus 9090, atau windows_exporter 9182 langsung ke Internet.

## Jika muncul error `Table 'ermonitor.alert_events' doesn't exist`

Artinya database dari Step 6 belum menjalankan struktur tabel Step 7.

Cara paling mudah di Windows/XAMPP:

1. Pastikan MySQL aktif di XAMPP.
2. Jalankan `tools\upgrade-step7.bat` sebagai user Windows yang dapat menjalankan XAMPP.
3. Buka kembali ERMonitor.

Alternatif melalui phpMyAdmin: buka database `ermonitor`, pilih tab **Import**, lalu import `database\upgrade_step7.sql`.
