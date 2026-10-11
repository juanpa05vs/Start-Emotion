<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Proxies de confianza
    |--------------------------------------------------------------------------
    |
    | Lista de proxies (IPs o rangos CIDR separados por comas) en los que la
    | aplicación confiará para leer las cabeceras X-Forwarded-* (IP real,
    | esquema HTTPS, host, etc.).
    |
    | Por defecto está VACÍO: no se confía en ningún proxy. Antes se usaba '*'
    | en bootstrap/app.php, lo que permitía que un cliente falsificara su IP o
    | el esquema de la petición.
    |
    | Si la aplicación va detrás de un balanceador que termina el TLS, define
    | la variable de entorno TRUSTED_PROXIES, p. ej.:
    |   TRUSTED_PROXIES=10.0.0.0/8,172.16.0.0/12
    | El valor especial '*' confía únicamente en el par que realiza la petición
    | (el proxy inmediato), no en cualquier origen.
    |
    */

    'proxies' => env('TRUSTED_PROXIES'),

];
