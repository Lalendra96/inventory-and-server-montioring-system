# HIMU Server Monitoring Remote Setup

Install these prerequisites on each monitored Ubuntu 16.04-22.04 server. Use a dedicated account such as `himu-monitor`; do not use root SSH login.

## 1. Monitoring account

```bash
sudo adduser --disabled-password --gecos "" himu-monitor
sudo mkdir -p /home/himu-monitor/.ssh
sudo chmod 700 /home/himu-monitor/.ssh
sudo nano /home/himu-monitor/.ssh/authorized_keys
sudo chmod 600 /home/himu-monitor/.ssh/authorized_keys
sudo chown -R himu-monitor:himu-monitor /home/himu-monitor/.ssh
```

Place only the **public key** generated on the Laravel monitoring host into `authorized_keys`.

## 2. Install the governed control helper

Copy `himu-opsctl` from this directory to the remote server:

```bash
sudo install -o root -g root -m 0755 himu-opsctl /usr/local/sbin/himu-opsctl
sudo mkdir -p /etc/himu-monitor
sudo touch /etc/himu-monitor/services.allow
sudo chown root:root /etc/himu-monitor/services.allow
sudo chmod 0644 /etc/himu-monitor/services.allow
```

Add only services HIMU is permitted to control, one exact systemd unit name per line, for example:

```text
nginx
php7.4-fpm
postgresql
```

The application has a second allow-list. A service must be approved in **both** places before it can be controlled.

## 3. Minimal sudo policy

Create `/etc/sudoers.d/himu-monitor` using `visudo -f /etc/sudoers.d/himu-monitor`:

```text
himu-monitor ALL=(root) NOPASSWD: /usr/local/sbin/himu-opsctl *
```

This does not permit arbitrary sudo commands. The root-owned helper accepts only governed process signals and allow-listed systemd operations.

## 4. Verify SSH host keys on the Laravel server

Run SSH interactively as the same Linux account that runs PHP-FPM (commonly `www-data`) and verify the presented host key fingerprint with the server administrator before accepting it. Strict host-key checking is mandatory; the application does not use `StrictHostKeyChecking=no`.

## 5. Private key permissions on Laravel host

The configured private key path must be readable only by the PHP-FPM/Laravel account. Example:

```bash
sudo chown www-data:www-data /var/www/.ssh/himu_monitor_ed25519
sudo chmod 600 /var/www/.ssh/himu_monitor_ed25519
```

Never place private-key contents or SSH passwords in the HIMU database.
