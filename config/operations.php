<?php

return ['slow_request_ms' => (int) env('SLOW_REQUEST_MS', 750), 'retention_days' => (int) env('SYSTEM_EVENT_RETENTION_DAYS', 30)];
