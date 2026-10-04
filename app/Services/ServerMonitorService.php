<?php

namespace App\Services;

use App\Models\ServerNode;
use RuntimeException;
use Symfony\Component\Process\Process;

class ServerMonitorService
{
    private const PROTECTED_DEFAULTS = [
        'systemd', 'init', 'sshd', 'ssh', 'dbus-daemon', 'systemd-journald',
    ];

    /**
     * Collect a compatibility-focused snapshot using commands available on Ubuntu 16.04-22.04.
     * No remote shell text is supplied by the browser; all commands are generated server-side.
     */
    public function metrics(ServerNode $server): array
    {
        $script = <<<'SH'
set -u
printf 'HOSTNAME='; hostname 2>/dev/null || true
printf 'KERNEL='; uname -r 2>/dev/null || true
printf 'OS='; (grep '^PRETTY_NAME=' /etc/os-release 2>/dev/null | cut -d= -f2- | tr -d '"') || true
printf 'UPTIME='; awk '{printf "%.0f\n", $1}' /proc/uptime 2>/dev/null || echo 0
printf 'LOAD='; cat /proc/loadavg 2>/dev/null | awk '{print $1" "$2" "$3}' || echo '0 0 0'
printf 'CPU='; top -bn1 2>/dev/null | awk '/Cpu\(s\)|%Cpu/{for(i=1;i<=NF;i++){if($i ~ /id,?/){gsub(/,/,"",$(i-1)); printf "%.2f\n", 100-$(i-1); exit}}}'
printf 'MEM='; awk '/MemTotal:/{t=$2*1024}/MemAvailable:/{a=$2*1024}END{if(!a){a=0} u=t-a; printf "%.0f %.0f\n",t,u}' /proc/meminfo
printf 'DISK='; df -B1 -P / 2>/dev/null | awk 'NR==2{gsub(/%/,"",$5); print $2" "$3" "$5}'
printf 'PROCS='; ps -e --no-headers 2>/dev/null | wc -l | tr -d ' '
SH;

        $result = $this->run($server, $script);
        $lines = preg_split('/\r?\n/', trim($result['output']));
        $data = [];

        foreach ($lines as $line) {
            if (!str_contains($line, '=')) {
                continue;
            }
            [$key, $value] = explode('=', $line, 2);
            $data[trim($key)] = trim($value);
        }

        [$load1, $load5, $load15] = array_pad(preg_split('/\s+/', $data['LOAD'] ?? ''), 3, 0);
        [$memoryTotal, $memoryUsed] = array_pad(preg_split('/\s+/', $data['MEM'] ?? ''), 2, 0);
        [$diskTotal, $diskUsed, $diskPercent] = array_pad(preg_split('/\s+/', $data['DISK'] ?? ''), 3, 0);

        $memoryTotal = (int) $memoryTotal;
        $memoryUsed = (int) $memoryUsed;

        return [
            'reachable' => true,
            'hostname' => $data['HOSTNAME'] ?? $server->name,
            'kernel' => $data['KERNEL'] ?? null,
            'os' => $data['OS'] ?? $server->os_hint,
            'uptime_seconds' => (int) ($data['UPTIME'] ?? 0),
            'cpu_percent' => round((float) ($data['CPU'] ?? 0), 2),
            'memory_total_bytes' => $memoryTotal,
            'memory_used_bytes' => $memoryUsed,
            'memory_percent' => $memoryTotal > 0 ? round(($memoryUsed / $memoryTotal) * 100, 2) : 0,
            'disk_total_bytes' => (int) $diskTotal,
            'disk_used_bytes' => (int) $diskUsed,
            'disk_percent' => round((float) $diskPercent, 2),
            'load_1' => round((float) $load1, 2),
            'load_5' => round((float) $load5, 2),
            'load_15' => round((float) $load15, 2),
            'process_count' => (int) ($data['PROCS'] ?? 0),
            'captured_at' => now()->toIso8601String(),
        ];
    }

    public function processes(ServerNode $server, int $limit = 100): array
    {
        $limit = max(10, min($limit, 250));
        $command = "ps -eo pid=,ppid=,user=,stat=,%cpu=,%mem=,etime=,comm=,args= --sort=-%cpu | head -n {$limit}";
        $result = $this->run($server, $command);
        $rows = [];

        foreach (preg_split('/\r?\n/', trim($result['output'])) as $line) {
            if (trim($line) === '') {
                continue;
            }

            $parts = preg_split('/\s+/', trim($line), 9);
            if (count($parts) < 8) {
                continue;
            }

            $rows[] = [
                'pid' => (int) ($parts[0] ?? 0),
                'ppid' => (int) ($parts[1] ?? 0),
                'user' => $parts[2] ?? '',
                'state' => $parts[3] ?? '',
                'cpu' => (float) ($parts[4] ?? 0),
                'memory' => (float) ($parts[5] ?? 0),
                'elapsed' => $parts[6] ?? '',
                'command' => $parts[7] ?? '',
                'args' => $parts[8] ?? ($parts[7] ?? ''),
                'protected' => $this->isProtectedProcess($server, (int) ($parts[0] ?? 0), (string) ($parts[7] ?? ''), (string) ($parts[8] ?? '')),
            ];
        }

        return $rows;
    }

    public function services(ServerNode $server): array
    {
        $services = $this->normalisedAllowedServices($server);
        $result = [];

        foreach ($services as $service) {
            $name = $service['name'];
            $label = $service['label'];
            $check = $this->run($server, 'systemctl is-active '.escapeshellarg($name).' 2>/dev/null || true', false);
            $state = trim($check['output']) ?: 'unknown';
            $result[] = ['name' => $name, 'label' => $label, 'state' => $state];
        }

        return $result;
    }

    public function signalProcess(ServerNode $server, int $pid, string $signal): array
    {
        if (!$server->allow_process_control) {
            throw new RuntimeException('Process control is disabled for this server.');
        }
        if ($pid <= 100) {
            throw new RuntimeException('Protected operating-system PID range cannot be controlled from the dashboard.');
        }
        if (!in_array($signal, ['TERM', 'KILL'], true)) {
            throw new RuntimeException('Unsupported process signal.');
        }

        $details = $this->run($server, 'ps -p '.((int) $pid).' -o comm=,args= 2>/dev/null || true', false);
        $detailText = trim($details['output']);
        if ($detailText === '') {
            throw new RuntimeException('The selected process is no longer running.');
        }

        $first = preg_split('/\s+/', $detailText, 2);
        if ($this->isProtectedProcess($server, $pid, $first[0] ?? '', $detailText)) {
            throw new RuntimeException('This process is protected. Manage the related allow-listed service instead.');
        }

        return $this->run($server, 'sudo -n '.escapeshellarg((string) config('server-monitor.remote_helper')).' process '.((int) $pid).' '.$signal);
    }

    public function serviceAction(ServerNode $server, string $service, string $operation): array
    {
        if (!$server->allow_service_control) {
            throw new RuntimeException('Service control is disabled for this server.');
        }
        if (!in_array($operation, ['start', 'stop', 'restart'], true)) {
            throw new RuntimeException('Unsupported service operation.');
        }
        if (!preg_match('/^[A-Za-z0-9_.@:-]+$/', $service)) {
            throw new RuntimeException('Invalid service name.');
        }

        $allowed = collect($this->normalisedAllowedServices($server))->pluck('name')->all();
        if (!in_array($service, $allowed, true)) {
            throw new RuntimeException('The service is not on this server\'s control allow-list.');
        }

        return $this->run(
            $server,
            'sudo -n '.escapeshellarg((string) config('server-monitor.remote_helper')).' service '.escapeshellarg($service).' '.$operation
        );
    }

    private function run(ServerNode $server, string $remoteCommand, bool $throw = true): array
    {
        $this->validateServer($server);

        $process = new Process([
            'ssh',
            '-i', $server->ssh_key_path,
            '-p', (string) $server->ssh_port,
            '-o', 'BatchMode=yes',
            '-o', 'PasswordAuthentication=no',
            '-o', 'StrictHostKeyChecking=yes',
            '-o', 'ConnectTimeout='.(int) config('server-monitor.connection_timeout', 8),
            $server->ssh_user.'@'.$server->host,
            $remoteCommand,
        ]);

        $process->setTimeout((float) config('server-monitor.command_timeout', 12));
        $process->run();

        $result = [
            'successful' => $process->isSuccessful(),
            'exit_code' => $process->getExitCode(),
            'output' => trim($process->getOutput()),
            'error' => trim($process->getErrorOutput()),
        ];

        if ($throw && !$process->isSuccessful()) {
            $message = $result['error'] ?: $result['output'] ?: 'Remote command failed.';
            throw new RuntimeException($this->safeError($message));
        }

        return $result;
    }

    private function validateServer(ServerNode $server): void
    {
        if (!$server->is_active) {
            throw new RuntimeException('This server is disabled.');
        }
        if (!preg_match('/^[A-Za-z0-9._:-]+$/', $server->host)) {
            throw new RuntimeException('Server host contains unsupported characters.');
        }
        if (!preg_match('/^[A-Za-z_][A-Za-z0-9_-]*$/', $server->ssh_user)) {
            throw new RuntimeException('SSH user is invalid.');
        }
        if (!is_file($server->ssh_key_path)) {
            throw new RuntimeException('The configured SSH private key is not available to the Laravel server.');
        }
    }

    private function normalisedAllowedServices(ServerNode $server): array
    {
        $raw = $server->allowed_services ?: [];
        $result = [];

        foreach ($raw as $item) {
            if (is_string($item)) {
                $name = trim($item);
                $label = $name;
            } else {
                $name = trim((string) ($item['name'] ?? ''));
                $label = trim((string) ($item['label'] ?? $name));
            }
            if ($name !== '' && preg_match('/^[A-Za-z0-9_.@:-]+$/', $name)) {
                $result[] = ['name' => $name, 'label' => $label ?: $name];
            }
        }

        return $result;
    }

    private function isProtectedProcess(ServerNode $server, int $pid, string $command, string $args): bool
    {
        if ($pid <= 100) {
            return true;
        }

        $patterns = array_merge(self::PROTECTED_DEFAULTS, $server->protected_process_patterns ?: []);
        $haystack = strtolower($command.' '.$args);

        foreach ($patterns as $pattern) {
            $pattern = strtolower(trim((string) $pattern));
            if ($pattern !== '' && str_contains($haystack, $pattern)) {
                return true;
            }
        }

        return false;
    }

    private function safeError(string $message): string
    {
        $message = preg_replace('/\s+/', ' ', trim($message));
        return mb_substr($message, 0, 500);
    }
}
