# ERMonitor — LAN / multi-device deployment

ERMonitor is designed so the browser only talks to Apache/PHP. Prometheus and MySQL stay on the monitoring PC.

## Quick start on Windows + XAMPP

1. Extract this folder to `C:\xampp\htdocs\ermonitor`.
2. Start **Apache** and **MySQL** in XAMPP.
3. Start **Prometheus** and `windows_exporter` on the monitoring PC.
4. Import `database/schema.sql` if the database has not been created.
5. Open `http://localhost/ermonitor/` on the monitoring PC.
6. Register a standard user, then have an existing administrator promote the account if needed.

## Open from phone / another laptop

On the monitoring PC run:

```bat
ipconfig
```

Find the active Wi-Fi/Ethernet **IPv4 Address**, for example `192.168.1.20`.

From another device on the same network open:

`http://192.168.1.20/ermonitor/`

You can also run `tools\setup-lan.bat` **as Administrator** to create the Windows Firewall rule for Apache TCP 80.

## What must be reachable

- Browser → Apache/PHP: **TCP 80** (LAN)
- PHP → MySQL: **TCP 3306**, local/private only
- PHP → Prometheus: **TCP 9090**, local/private only
- Prometheus → exporters: exporter ports such as **9182**, private only

Do **not** expose MySQL, Prometheus, or windows_exporter to the internet.

## Troubleshooting

Run `tools\diagnose-lan.bat` on the monitoring PC. It checks IP addresses and listening ports.

The authenticated endpoint `api/health.php` checks MySQL and Prometheus from the server side.

If the phone gets a timeout:
- make sure both devices are on the same Wi-Fi/LAN;
- make sure the network profile is Private/appropriate for the firewall rule;
- allow Apache through Windows Defender Firewall;
- make sure Apache is not configured to listen only on `127.0.0.1`;
- test `http://PC-IP/` first, then `/ermonitor/`.

## HTTPS / install as an app

LAN HTTP works as a normal web application. Browser install/PWA behavior is more restrictive on non-secure origins. For production or internet access, use HTTPS with a proper hostname/domain and a reverse proxy or Apache SSL configuration.

Never publish the XAMPP development stack directly to the public internet.

## Step 6 — HTTPS / public deployment

For internet access and reliable PWA installation, use a real domain over HTTPS. The recommended architecture is:

`HTTPS domain -> Cloudflare -> cloudflared -> Apache :80 -> ERMonitor`

See `DEPLOYMENT_STEP6.md` for the setup flow. Example files are in `tools/`.

Do not expose MySQL 3306, Prometheus 9090, or windows_exporter 9182 to the public internet.
