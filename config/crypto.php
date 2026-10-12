<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Deterministic Encryption Key
    |--------------------------------------------------------------------------
    |
    | Kunci rahasia untuk enkripsi deterministik dua arah (AES-256-CBC).
    | Menghasilkan ciphertext yang konsisten (identik) untuk plaintext yang sama,
    | sehingga mendukung pencarian langsung dan penegakan constraint unik.
    | Wajib diisi di berkas .env (DETERMINISTIC_ENCRYPTION_KEY).
    |
    */

    'deterministic_key' => env('DETERMINISTIC_ENCRYPTION_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Cipher Algorithm
    |--------------------------------------------------------------------------
    |
    | Algoritma cipher yang digunakan untuk enkripsi deterministik.
    | AES-256-CBC dipilih karena mendukung IV deterministik.
    |
    */

    'cipher' => 'aes-256-cbc',

];
