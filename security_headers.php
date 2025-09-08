<?php
header('X-Frame-Options: SAMEORIGIN');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');
header("Permissions-Policy: geolocation=(), microphone=(), camera=()");
header("Content-Security-Policy:
  default-src 'self' https:;
  img-src 'self' https: data: blob:;
  style-src 'self' 'unsafe-inline' https:;
  script-src 'self' 'unsafe-inline' https:;
  frame-ancestors 'self';
  base-uri 'self';
  form-action 'self';");
