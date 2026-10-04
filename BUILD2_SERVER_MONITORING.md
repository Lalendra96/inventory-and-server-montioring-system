# Build 2 - Server Monitoring

This build adds governed live monitoring and process/service management for the hospital's Ubuntu 16.04-22.04 servers.

## Upgrade

After replacing the application files:

```bash
php artisan migrate
php artisan config:clear
php artisan cache:clear
```

The migration automatically adds the **Server Monitoring** feature option, so an existing installation does not need to re-run the full database seeder.

Ensure the Laravel scheduler is active because historical server snapshots are collected once per minute.

## Configure the five servers

Sign in with System Administrator or HIMU Administrator, then open:

**Administration → Server Configuration**

For each server configure its code, display name, IP/hostname, SSH user, private-key path, Ubuntu version hint, refresh interval, allowed services, and whether process/service control is enabled.

Do not store passwords or private-key contents in the application.

## Remote control security

Follow `deployment/server-monitor/README.md` on each monitored Ubuntu server. The included `himu-opsctl` helper is intended to be the only sudo command exposed to the monitoring account. It independently validates process signals and service allow-lists.

## Live behaviour

The server detail page refreshes metrics every 5-60 seconds according to the server profile. Process and service data is refreshed independently. Remote control actions require a human-entered reason and are retained in both the server action log and HMAC audit chain.
