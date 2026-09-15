<?php

// =====================================================================
// Tambahkan blok ini ke dalam array yang di-return oleh config/services.php
// yang sudah ada di project Laravel kamu (jangan timpa file aslinya).
// Lalu isi MIDTRANS_SERVER_KEY & MIDTRANS_CLIENT_KEY di file .env.
// =====================================================================

'midtrans' => [
    'server_key'     => env('MIDTRANS_SERVER_KEY'),
    'client_key'     => env('MIDTRANS_CLIENT_KEY'),
    'is_production'  => env('MIDTRANS_IS_PRODUCTION', false),
],

// .env yang perlu ditambahkan:
// MIDTRANS_SERVER_KEY=SB-Mid-server-xxxxxxxxxxxxxxxxxxxxxxxx
// MIDTRANS_CLIENT_KEY=SB-Mid-client-xxxxxxxxxxxxxxxxxxxxxxxx
// MIDTRANS_IS_PRODUCTION=false

// Install SDK resmi via composer:
// composer require midtrans/midtrans-php
