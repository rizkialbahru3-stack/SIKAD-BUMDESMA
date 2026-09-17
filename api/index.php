<?php

/**
 * Serverless entrypoint untuk deploy Vercel (runtime vercel-php).
 * Semua request diteruskan ke front controller Laravel.
 */
require __DIR__.'/../public/index.php';
