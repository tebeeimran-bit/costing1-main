<?php

return [
    'slow_request_ms' => (int) env('SLOW_REQUEST_MS', 750),
    'retention_days' => (int) env('SYSTEM_EVENT_RETENTION_DAYS', 30),
    'backup' => [
        'mysqldump_path' => env('MYSQLDUMP_PATH', 'mysqldump'),
        'mysql_path' => env('MYSQL_PATH', 'mysql'),
        'timeout_seconds' => (int) env('BACKUP_TIMEOUT_SECONDS', 600),
    ],
];
