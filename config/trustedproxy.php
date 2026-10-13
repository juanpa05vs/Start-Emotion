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
    | Por defecto es '*': Laravel confía únicamente en el proxy que conecta
    | directamente con el contenedor (el de Render), NO en cualquier origen.
    | En Laravel 11, '*' se traduce a "confiar solo en REMOTE_ADDR", no a
    | "confiar en todos". Sin esta confianza la app ignora X-Forwarded-Proto
    | detrás del proxy TLS y genera URLs con esquema http://.
    |
    | Para fijar las IPs/rangos del balanceador, define TRUSTED_PROXIES, p. ej.:
    |   TRUSTED_PROXIES=10.0.0.0/8,172.16.0.0/12
    |
    */

    'proxies' => env('TRUSTED_PROXIES') ?: '*',

];
