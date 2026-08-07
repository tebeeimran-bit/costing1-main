<?php

return [
    /*
    | demo       = instalasi lokal kantor dengan SQLite
    | production = instalasi VPS bersama dengan MySQL
    */
    'mode' => env('APP_MODE', env('APP_ENV') === 'production' ? 'production' : 'demo'),
];
