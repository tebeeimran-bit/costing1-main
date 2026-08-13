<?php

return [
    /*
    | demo       = instalasi lokal kantor dengan SQLite
    | production = instalasi server tetap (on-premise/VPS) dengan MySQL
    */
    'mode' => env('APP_MODE', env('APP_ENV') === 'production' ? 'production' : 'demo'),
];
