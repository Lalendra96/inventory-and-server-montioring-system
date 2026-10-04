<?php

return [
    'connection_timeout' => (int) env('SERVER_MONITOR_SSH_TIMEOUT', 8),
    'command_timeout' => (int) env('SERVER_MONITOR_COMMAND_TIMEOUT', 12),
    'remote_helper' => env('SERVER_MONITOR_REMOTE_HELPER', '/usr/local/sbin/himu-opsctl'),
];
