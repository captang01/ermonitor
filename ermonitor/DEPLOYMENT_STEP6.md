# ERMonitor Step 6 — HTTPS, Domain & Installable App

This package is prepared for two deployment modes:

1. **LAN**: `http://PC-IP/ermonitor/`
2. **Internet / HTTPS**: a real domain routed to Apache through a secure tunnel.

## Recommended architecture for internet access

```text
Phone / Laptop
      |
   HTTPS
      |
   Domain
      |
 Cloudflare
      |
 cloudflared (Windows server)
      |
  Apache :80
      |
 ERMonitor PHP
   |        |
 MySQL   Prometheus :9090
              |
       windows_exporter :9182
```

The browser never needs direct access to MySQL, Prometheus, or windows_exporter.

## Option A — Cloudflare Tunnel (recommended)

Cloudflare Tunnel can publish the local Apache service without opening an inbound port on the server. Create a tunnel in Cloudflare, install `cloudflared` on the Windows PC, then publish a hostname such as:

`monitor.example.com` -> `http://127.0.0.1:80`

The Cloudflare dashboard generates the exact Windows installation command and tunnel token for your account.

### Windows flow

1. Buy/use a domain that you control.
2. Add the domain to Cloudflare.
3. In Cloudflare Dashboard, create a Tunnel.
4. Select Windows as the connector OS.
5. Install `cloudflared` on the ERMonitor PC.
6. Add a Published Application route:
   - Hostname: `monitor.example.com`
   - Service: `http://127.0.0.1:80`
7. Verify the tunnel is Healthy.
8. Open `https://monitor.example.com/ermonitor/`.
9. Log in and test Dashboard, Refresh, Profile, Servers, Alerts and Reports.
10. Install ERMonitor from the browser's "Install app" / "Add to Home Screen" action.

Cloudflare documents Windows service installation and recommends running cloudflared as a service so it starts with the machine.

## Option B — Apache HTTPS directly

If you already have a public domain and a trusted certificate, Apache can terminate HTTPS on TCP 443.

The certificate must match the real hostname. Do not use a self-signed certificate for normal end-user deployment because phones and browsers will show trust warnings.

Configure an Apache HTTPS VirtualHost for the ERMonitor document root, then redirect HTTP to HTTPS.

## PWA

The package already contains:
- `manifest.webmanifest`
- ERMonitor logo/icon
- service worker
- standalone display metadata

For reliable installation on phones, use HTTPS with a real hostname.

## Security boundaries

Keep these services private to the monitoring machine:

- MySQL: `3306`
- Prometheus: `9090`
- windows_exporter: `9182`

Only the web application should be reachable by end-user browsers.

## Important operational note

The Windows PC hosting XAMPP/Prometheus must remain powered on and connected to the network for ERMonitor to show live telemetry. If the PC sleeps, shuts down, or loses network access, the web application and monitoring data will be unavailable.

## Quick tests

Local:

`http://127.0.0.1/ermonitor/`

LAN:

`http://<SERVER-LAN-IP>/ermonitor/`

Production:

`https://<YOUR-DOMAIN>/ermonitor/`

Run `tools\check-https.bat` or `tools\check-https.ps1` on Windows for a quick readiness check.
