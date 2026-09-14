# Panduan Pengguna ERMonitor

**Versi:** Step 7  
**Sistem:** ERMonitor — Server Monitoring Control Room

## 1. Tujuan
ERMonitor digunakan untuk melihat kondisi server secara terpusat, terutama status node, penggunaan CPU, penggunaan RAM, dan kejadian Warning/Offline.

## 2. Memulai
1. Pastikan Apache, MySQL, Prometheus, dan exporter monitoring berjalan.
2. Buka alamat ERMonitor yang diberikan administrator.
3. Login.
4. Jika belum memiliki akun, gunakan Register. Akun baru dibuat sebagai User.

## 3. Dashboard
Dashboard menampilkan Fleet, Online, Warning, Offline, CPU, Memory, indikator LIVE, dan Last Update. Telemetry diperbarui otomatis; tombol Refresh dapat digunakan untuk pembaruan segera.

## 4. Status
- **Online:** probe aktif dan CPU/RAM belum mencapai ambang Warning.
- **Warning:** CPU atau RAM mencapai 75% atau lebih.
- **Offline:** target/probe monitoring tidak aktif atau tidak dapat dipantau.

Status adalah indikator operasional dan bukan diagnosis akar masalah.

## 5. Alerts
Buka Alerts ketika Warning/Offline bertambah. Catat server dan waktunya, periksa host serta Prometheus/exporter, kemudian konfirmasi recovery melalui Dashboard. Step 7 menyimpan perubahan status sebagai alert event.

## 6. Servers
Semua pengguna dapat melihat inventory. Administrator dapat Add Node, Edit, dan Delete. Server name dan IP address wajib diisi saat menambah node.

## 7. Users
Users hanya untuk Administrator. Administrator dapat mengubah role User/Administrator dan menghapus akun lain. Akun administrator yang sedang digunakan tidak dapat diturunkan atau dihapus sendiri.

## 8. Reports
Reports berisi ringkasan fleet dan event alert. Gunakan Print report untuk mencetak atau menyimpan melalui dialog print browser.

## 9. My Profile
Pengguna dapat mengubah display name dan password. Password baru minimal 8 karakter.

## 10. Audit Log
Audit Log hanya untuk Administrator dan mencatat aktivitas akun serta administrasi.

## 11. Logout
Gunakan tombol Sign out di sidebar.

## 12. LAN dan HTTPS
Mode LAN menggunakan alamat seperti `http://192.168.x.x/ermonitor/`. Gunakan IP LAN server, bukan `localhost`. Untuk akses publik gunakan URL HTTPS yang diberikan administrator.

## 13. Troubleshooting
**Dashboard tidak menampilkan telemetry:** pastikan Prometheus dan exporter aktif, periksa koneksi aplikasi ke Prometheus, lalu Refresh.

**Offline:** periksa target exporter, host, dan konektivitas monitoring.

**HP tidak bisa membuka:** pastikan HP dan server berada di LAN yang sama dan gunakan IP LAN server.

**Menu Administrator tidak terlihat:** akun memiliki role User; hubungi administrator bila membutuhkan hak admin.

## 14. Keamanan
Jangan membagikan password. Batasi role Administrator. Backup database secara berkala. Jangan mengekspos MySQL 3306, Prometheus 9090, atau exporter 9182 langsung ke Internet.
