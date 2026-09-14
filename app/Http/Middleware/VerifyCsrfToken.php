<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        // Logout harus selalu bisa diakses meski token CSRF sudah kedaluwarsa
        // (mis. tab dibiarkan terbuka melewati SESSION_LIFETIME atau double-submit
        // setelah logout di tab lain). Tanpa ini pengguna mendapat 419 Page Expired.
        'logout',
    ];
}
