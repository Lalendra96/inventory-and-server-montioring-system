# Teaching Hospital Peradeniya — HIMU ICT Operations, Support & Maintenance

Laravel 12 + PostgreSQL operational platform built from the supplied clean Laravel skeleton. The interface is based on the supplied THP ICT dashboard concept and uses only locally served CSS/JavaScript; there are **no CDN dependencies**.

## Implemented in this build

- Mandatory session-based login with username/email and password.
- Multi-role user levels: System Administrator, HIMU Administrator, ICT Manager/Consultant, Senior ICT Officer, ICT Officer, Technician, Software Developer, System/DB Officer, Night Shift Officer, Auditor/Verifier, Department/Ward User and Reporting User.
- Admin-only Users & Roles management, password reset, enable/disable and feature controls.
- Governance rule: operational records have no delete routes. Assets/users are disabled with actor, reason and timestamp; historical records remain available.
- HMAC-SHA-256 tamper-evident audit trail with previous-hash chaining and verification UI.
- Support tickets with priorities, SLA targets, assignment, status workflow, internal comments, private attachments, SHA-256 attachment hashes and timeline events.
- Automatic SLA escalation command and database notification alerts.
- Asset/inventory register with location, purchase/warranty fields, fault history, transfer history and replacement linkage.
- Recurring preventive inspection schema/worklist and scheduled work-item generator.
- System downtime capture, root-cause/resolution and verification.
- Software request workflow from request through review/development/UAT/deployment/verification.
- Procurement/replacement request and approval workflow.
- Night-shift incident and handover capture.
- Notification centre.
- Audit viewer and HMAC verification.
- Reports/print summary.
- Local HTML5 Canvas charts (no Chart.js/CDN/network dependency).

## PostgreSQL deployment

1. Create a PostgreSQL database and a least-privilege application account.
2. Copy `.env.example` to `.env` and set the PostgreSQL values.
3. Set a strong `APP_KEY` (`php artisan key:generate`).
4. Set a separate high-entropy `AUDIT_HMAC_KEY`. Do not casually rotate it because existing audit signatures depend on the key.
5. Before the first seed, set `SEED_ADMIN_PASSWORD` to a strong temporary password.
6. Run:

```bash
php artisan migrate --seed
php artisan storage:link
php artisan optimize
```

The current attachment implementation stores ticket attachments on Laravel's **private local disk**; downloads go through an authenticated controller. The `storage:link` command is not required for those private attachments, but is safe if other public files are later added.

## Scheduler / SLA / recurring inspections

Configure the normal Laravel scheduler cron on the server:

```cron
* * * * * cd /var/www/html/example-app && php artisan schedule:run >> /dev/null 2>&1
```

Scheduled jobs included:

- `himu:escalate-sla` — every five minutes.
- `himu:generate-inspections` — daily at 00:05.

They can also be run manually:

```bash
php artisan himu:escalate-sla
php artisan himu:generate-inspections
```

## Queue worker

Database notifications can run synchronously today. For queued tasks added later, keep a supervised worker running:

```bash
php artisan queue:work --sleep=3 --tries=3
```

## Offline frontend

Runtime UI assets are in:

```text
public/assets/css/app.css
public/assets/js/app.js
public/assets/js/charts.js
```

No Bootstrap CDN, Chart.js CDN, Google Fonts, Font Awesome CDN or remote JavaScript is used. The application UI therefore continues working on an isolated hospital LAN.

## Realtime notification path

This build keeps the notification source of truth in PostgreSQL so no cloud service is required. The architecture is compatible with adding **Laravel Reverb** as a locally hosted WebSocket server later. Reverb should be installed and bundled on the deployment network rather than loaded from a CDN. Until then the notification centre and scheduled alerts remain fully functional without internet access.

## Security / governance notes

- Avoid putting patient-identifiable clinical data into routine ICT tickets. The UI explicitly reminds users of data minimisation.
- Authentication is rate-limited; disabled users are logged out by middleware.
- Sensitive file downloads are authorised and not publicly exposed.
- Search fields use exact matching; user-supplied wildcard searches are not implemented.
- Audit logs have no application delete route.
- Feature disabling is administrative configuration only and does not remove stored records.
- Set `SESSION_SECURE_COOKIE=true` after HTTPS is enabled. HTTPS is strongly recommended for deployment even on the hospital LAN.
- Production file permissions should allow the web-service account to write only to Laravel's required `storage/` and `bootstrap/cache/` locations.

## First login

The seeded administrator uses the values from:

```text
SEED_ADMIN_USERNAME
SEED_ADMIN_EMAIL
SEED_ADMIN_PASSWORD
```

Do **not** deploy using the placeholder password from `.env.example`.

## Validation performed in this package

- PHP syntax lint on application/database/route PHP files.
- Laravel route registration via `php artisan route:list`.
- Full database migration execution could not be performed in the build environment because its PHP runtime does not include a PDO database driver. Migrations are authored for PostgreSQL and must be run on the target host with `pdo_pgsql` installed.


## Server Monitoring and Live Process Management

Build 2 adds an offline LAN server-monitoring module for Ubuntu 16.04-22.04 servers. It uses the operating system `ssh` client through Symfony Process, so no CDN or browser-side remote administration dependency is required.

Features include:

- live CPU, memory, root-disk, load, uptime and process-count metrics;
- live top-process table with PID, parent PID, user, state, CPU, memory, elapsed time and command;
- governed TERM and KILL actions for non-protected processes;
- allow-listed systemd service start, stop and restart;
- 24-hour metric history using PostgreSQL snapshots;
- retained action history with actor, reason, outcome and timestamp;
- HMAC audit events for remote control actions;
- strict SSH host-key checking and public-key authentication only;
- per-server process/service control switches; and
- no record deletion: server profiles are disabled and historical metrics/actions are retained.

### Laravel host requirements

The Laravel server needs the OpenSSH client (`ssh`) and a private key readable by the PHP-FPM account. Never store private-key contents or SSH passwords in PostgreSQL. Configure only the path to the key in Administration → Server Configuration.

The application intentionally uses `StrictHostKeyChecking=yes`. Verify each remote server fingerprint administratively before accepting it into the PHP-FPM account's `known_hosts` file.

### Remote server helper

Install `deployment/server-monitor/himu-opsctl` on each monitored server and follow `deployment/server-monitor/README.md`. The root-owned helper provides a narrow command surface for process signals and allow-listed service operations; the monitoring SSH account should not receive general sudo access.

### Historical snapshots

The scheduler executes:

```bash
php artisan himu:collect-server-metrics
```

every minute. Keep the standard Laravel scheduler cron running:

```cron
* * * * * cd /var/www/html/example-app && php artisan schedule:run >> /dev/null 2>&1
```

### Environment options

```env
SERVER_MONITOR_SSH_TIMEOUT=8
SERVER_MONITOR_COMMAND_TIMEOUT=12
SERVER_MONITOR_REMOTE_HELPER=/usr/local/sbin/himu-opsctl
```

Live page refresh intervals are configured per server from 5 to 60 seconds. Historical collection remains once per minute to avoid unnecessary load.

## Build 3 - Assets / Inventory route and source formatting

Build 3 explicitly verifies and exposes the Asset / Inventory module through the canonical `/assets` route and an additional `/inventory` alias.

Available Asset / Inventory routes include:

```text
GET       /assets
GET       /assets/create
POST      /assets
GET       /assets/{asset}
GET       /assets/{asset}/edit
PUT       /assets/{asset}
POST      /assets/{asset}/move
POST      /assets/{asset}/faults
POST      /assets/{asset}/faults/{fault}/resolve
POST      /assets/{asset}/disable
GET       /inventory   -> redirects to /assets
```

The module remains protected by the `assets` feature flag. If an administrator disables **Asset / Inventory Management** under Administration → Features & Options, the operational routes intentionally return 404 while records remain intact.

After replacing an older build, clear Laravel route/configuration caches:

```bash
php artisan optimize:clear
php artisan route:list --path=assets
php artisan route:list --path=inventory
```

Build 3 also reformats the application source for maintainability. Compact/single-line application PHP classes, controllers and Blade templates were expanded into conventional readable formatting. Blade templates were compiled and the resulting PHP was syntax-checked. No application delete routes were introduced; the disable-only governance rule remains unchanged.
