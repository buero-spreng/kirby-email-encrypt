<?php
use Kirby\Cms\App as Kirby;

Kirby::plugin('kesabr/email-token', [
  'options' => [
    // configure this in site/config/config.php
    // 'kesabr.emailTokenKey' => 'a-long-random-secret',
  ],
  'snippets' => [
    'emailToken' => __DIR__ . '/snippets/email-token_js.php'
  ],

  // Public API endpoint: /api/mailto?token=...
  'api' => [
    'routes' => [
      [
        'pattern' => 'mailto',
        'method'  => 'GET',
        'auth'    => false,
        'action'  => function () {
          $token = get('token');
          if (!$token) {
            return ['error' => 'missing token'];
          }
          $email = email_token_verify($token);
          if (!$email) {
            return ['error' => 'invalid token'];
          }
          return ['email' => $email];
        }
      ],
    ]
  ],

  // Optional: add tiny helpers as Kirby “methods” if you like
  'helpers' => [
    'emailToken' => function (string $email) {
      return email_token_make($email);
    },
    'emailTokenLink' => function (string $email, string $label = 'E-Mail', array $attrs = []) {
      $token = email_token_make($email);
      $attrs['class'] = trim(($attrs['class'] ?? '') . ' js-mailto');
      $attrs['data-mailto-token'] = $token;
      $attrStr = '';
      foreach ($attrs as $k => $v) $attrStr .= ' ' . $k . '="' . htmlspecialchars($v, ENT_QUOTES) . '"';
      return '<a href="#"' . $attrStr . '>' . htmlspecialchars($label, ENT_QUOTES) . '</a>';
    },
  ],
]);

// ---------------- Helpers ----------------
function email_token_key(): string {
  $key = option('kesabr.emailTokenKey');
  if (!$key) {
    throw new Exception('Missing option: kesabr.emailTokenKey');
  }
  return hash('sha256', $key, true); // derive 32-byte key
}

function base64url_encode(string $bin): string {
  return rtrim(strtr(base64_encode($bin), '+/', '-_'), '=');
}
function base64url_decode(string $txt): string {
  $txt = strtr($txt, '-_', '+/');
  return base64_decode($txt);
}

/** Create token: base64url(email) + "." + base64url(hmac) */
function email_token_make(string $email): string {
  $email = trim(strtolower($email));
  $payload = base64url_encode($email);
  $sig = hash_hmac('sha256', $payload, email_token_key(), true);
  return $payload . '.' . base64url_encode($sig);
}

/** Verify token → email or false */
function email_token_verify(string $token) {
  if (!str_contains($token, '.')) return false;
  [$payload, $sig] = explode('.', $token, 2);
  if ($payload === '' || $sig === '') return false;

  $calc  = hash_hmac('sha256', $payload, email_token_key(), true);
  $given = base64url_decode($sig);
  if (!hash_equals($calc, $given)) return false;

  $email = base64url_decode($payload);
  if (!filter_var($email, FILTER_VALIDATE_EMAIL)) return false;
  return $email;
}