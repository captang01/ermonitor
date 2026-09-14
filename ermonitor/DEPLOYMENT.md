# ERMonitor — multi-device deployment

## 1. Local/LAN access
ERMonitor uses relative URLs, so browsers do not need `localhost` in the application links.

On the Windows PC that runs XAMPP, find the LAN IPv4 address with `ipconfig`, then open from another device:

`http://<IP-PC>/ermonitor/`

Apache must listen on the LAN interface and Windows Firewall must allow inbound TCP 80. MySQL and Prometheus should remain bound to the server PC; browsers do not need direct access to port 3306 or 9090.

## 2. Monitoring architecture
The browser calls `api/monitoring.php`. PHP runs on the monitoring PC and queries Prometheus locally at `127.0.0.1:9090`, so other devices never need to reach Prometheus directly.

For a second Windows server, install windows_exporter there and add its target to Prometheus. The server can then be registered in **Servers**.

## 3. User roles
- **User**: login/register, dashboard, alerts, reports, profile and password change.
- **Administrator**: all User features plus server create/edit/delete and user/role management.
- Public registration can only create `user` accounts.

## 4. PWA / app installation
A web app manifest and service worker are included. For reliable install-to-home-screen behavior on phones and PCs, deploy ERMonitor over HTTPS with a real hostname/domain. Plain LAN HTTP is still usable as a normal website, but browser PWA installation rules vary.

## 5. Database
Registration expects the existing `users` table to contain: `id`, `name`, `username`, `password`, `role`. The supplied login already uses these fields. New accounts are inserted with `role = 'user'` only.
