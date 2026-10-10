<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Dilempar ketika komunikasi dengan payment gateway (Midtrans) gagal atau
 * gateway belum dikonfigurasi. Dipisahkan dari RuntimeException biasa agar
 * controller bisa membalas 502 (bukan 409 "slot bentrok").
 */
class PaymentGatewayException extends RuntimeException
{
}
