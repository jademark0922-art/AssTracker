<?php
/**
 * firebase.php
 * Thin wrapper around Firebase REST API + Firebase Auth REST API.
 * No Composer required — works on plain XAMPP / shared hosting.
 *
 * FIXES:
 *  - idToken auto-refresh using stored refreshToken (tokens expire after 1 hr)
 *  - All Firestore calls retry once on auth failure after refreshing token
 *  - fb_firestore_add surfaces errors and returns null properly
 *  - fb_firestore_query returns [] on error instead of crashing
 */

// ── Start session if not already started ────────────────
if (session_status() === PHP_SESSION_NONE) { session_start(); }

// ── Your Firebase project credentials ────────────────────
define('FB_API_KEY',     'AIzaSyDC6VPrEg-TN8Jcvj0g16WOHXn6eqMiR6U');
define('FB_PROJECT',     'ass-tracker-f86bf');
define('FB_FIRESTORE',   'https://firestore.googleapis.com/v1/projects/' . FB_PROJECT . '/databases/(default)/documents');
define('FB_AUTH_URL',    'https://identitytoolkit.googleapis.com/v1/accounts');
define('FB_REFRESH_URL', 'https://securetoken.googleapis.com/v1/token');

// ── Low-level HTTP helper ─────────────────────────────────
function fb_http(string $method, string $url, array $payload = [], string $idToken = ''): array {
    $ch      = curl_init($url);
    $headers = ['Content-Type: application/json'];
    if ($idToken) $headers[] = "Authorization: Bearer $idToken";
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST  => $method,
        CURLOPT_HTTPHEADER     => $headers,
        CURLOPT_TIMEOUT        => 15,
    ]);
    if ($payload) curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    $body  = curl_exec($ch);
    $errno = curl_errno($ch);
    curl_close($ch);
    if ($errno || $body === false) return ['error' => ['message' => 'NETWORK_ERROR']];
    return json_decode($body, true) ?? ['error' => ['message' => 'INVALID_JSON']];
}

// ════════════════════════════════════════════════════════
//  TOKEN REFRESH — Firebase idTokens expire after 1 hour
// ════════════════════════════════════════════════════════

function fb_refresh_token(string $refreshToken): ?string {
    $res = fb_http('POST', FB_REFRESH_URL . '?key=' . FB_API_KEY, [
        'grant_type'    => 'refresh_token',
        'refresh_token' => $refreshToken,
    ]);
    if (empty($res['id_token'])) return null;
    $_SESSION['id_token']      = $res['id_token'];
    $_SESSION['refresh_token'] = $res['refresh_token'] ?? $refreshToken;
    return $res['id_token'];
}

function fb_get_valid_token(): string {
    $token   = $_SESSION['id_token']      ?? '';
    $refresh = $_SESSION['refresh_token'] ?? '';
    if (!$token && $refresh) $token = fb_refresh_token($refresh) ?? '';
    return $token;
}

/**
 * Execute a Firestore HTTP call with automatic token refresh on auth failure.
 */
function fb_call(string $method, string $url, array $payload = []): array {
    $token = fb_get_valid_token();
    $res   = fb_http($method, $url, $payload, $token);

    $errMsg = $res['error']['status'] ?? ($res['error']['message'] ?? '');
    if (in_array($errMsg, ['UNAUTHENTICATED', 'PERMISSION_DENIED', 'TOKEN_EXPIRED'], true)) {
        $refresh = $_SESSION['refresh_token'] ?? '';
        if ($refresh) {
            $newToken = fb_refresh_token($refresh);
            if ($newToken) $res = fb_http($method, $url, $payload, $newToken);
        }
    }
    return $res;
}

// ════════════════════════════════════════════════════════
//  AUTH helpers
// ════════════════════════════════════════════════════════

function fb_register(string $email, string $pass, string $name, string $username): array {
    $res = fb_http('POST', FB_AUTH_URL . ':signUp?key=' . FB_API_KEY, [
        'email' => $email, 'password' => $pass, 'returnSecureToken' => true,
    ]);
    if (isset($res['error'])) return ['error' => $res['error']['message'] ?? 'UNKNOWN_ERROR'];

    $idToken      = $res['idToken'];
    $refreshToken = $res['refreshToken'];
    $uid          = $res['localId'];

    // Save display name
    fb_http('POST', FB_AUTH_URL . ':update?key=' . FB_API_KEY, [
        'idToken' => $idToken, 'displayName' => $name, 'returnSecureToken' => true,
    ]);

    // Save user doc in Firestore
    fb_http('PATCH', FB_FIRESTORE . "/users/$uid", fb_fields([
        'name' => $name, 'email' => $email, 'username' => $username,
    ]), $idToken);

    // Save username → email mapping
    fb_http('PATCH', FB_FIRESTORE . "/usernames/$username", fb_fields([
        'email' => $email, 'uid' => $uid,
    ]), $idToken);

    return [
        'idToken'      => $idToken,
        'refreshToken' => $refreshToken,
        'uid'          => $uid,
        'name'         => $name,
        'email'        => $email,
        'username'     => $username,
    ];
}

function fb_get_email_by_username(string $username): ?string {
    $url = FB_FIRESTORE . "/usernames/$username?key=" . FB_API_KEY;
    $ch  = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        CURLOPT_TIMEOUT        => 10,
    ]);
    $body = curl_exec($ch);
    curl_close($ch);
    $doc = json_decode($body, true) ?? [];
    if (isset($doc['error']) || !isset($doc['fields'])) return null;
    return $doc['fields']['email']['stringValue'] ?? null;
}

function fb_login(string $username, string $pass): array {
    $email = fb_get_email_by_username($username)
          ?? strtolower($username) . '@asstracker.app';

    $res = fb_http('POST', FB_AUTH_URL . ':signInWithPassword?key=' . FB_API_KEY, [
        'email' => $email, 'password' => $pass, 'returnSecureToken' => true,
    ]);
    if (isset($res['error'])) return ['error' => $res['error']['message'] ?? 'LOGIN_FAILED'];

    return [
        'idToken'      => $res['idToken'],
        'refreshToken' => $res['refreshToken'],
        'uid'          => $res['localId'],
        'name'         => $res['displayName'] ?? $username,
        'email'        => $res['email'],
        'username'     => $username,
    ];
}

function fb_forgot(string $email): bool {
    $res = fb_http('POST', FB_AUTH_URL . ':sendOobCode?key=' . FB_API_KEY, [
        'requestType' => 'PASSWORD_RESET', 'email' => $email,
    ]);
    return !isset($res['error']);
}

// ════════════════════════════════════════════════════════
//  FIRESTORE value helpers
// ════════════════════════════════════════════════════════

function fb_val($v): array {
    if (is_bool($v))  return ['booleanValue' => $v];
    if (is_int($v))   return ['integerValue' => (string)$v];
    if (is_float($v)) return ['doubleValue'  => $v];
    if (is_null($v))  return ['nullValue'    => null];
    return                   ['stringValue'  => (string)$v];
}

function fb_fields(array $data): array {
    $fields = [];
    foreach ($data as $k => $v) $fields[$k] = fb_val($v);
    return ['fields' => $fields];
}

function fb_extract(array $field) {
    return $field['stringValue']
        ?? $field['integerValue']
        ?? $field['booleanValue']
        ?? $field['doubleValue']
        ?? null;
}

function fb_doc_to_array(array $doc): array {
    $out       = [];
    $nameParts = explode('/', $doc['name'] ?? '');
    $out['id'] = end($nameParts);
    foreach ($doc['fields'] ?? [] as $k => $v) {
        $out[$k] = fb_extract($v);
    }
    return $out;
}

// ════════════════════════════════════════════════════════
//  FIRESTORE CRUD — all use fb_call() for auto token refresh
// ════════════════════════════════════════════════════════

function fb_firestore_get(string $path, string $idToken = ''): ?array {
    $res = fb_call('GET', FB_FIRESTORE . "/$path");
    if (isset($res['error']) || !isset($res['fields'])) return null;
    return fb_doc_to_array($res);
}

function fb_firestore_set(string $path, array $data, string $idToken = ''): array {
    return fb_call('PATCH', FB_FIRESTORE . "/$path", fb_fields($data));
}

/** Returns the new document ID, or null on failure */
function fb_firestore_add(string $collection, array $data, string $idToken = ''): ?string {
    $res = fb_call('POST', FB_FIRESTORE . "/$collection", fb_fields($data));
    if (isset($res['error']) || !isset($res['name'])) {
        error_log('[AssTracker] fb_firestore_add failed: ' . json_encode($res));
        return null;
    }
    $parts = explode('/', $res['name']);
    return end($parts);
}

function fb_firestore_update(string $path, array $data, string $idToken = ''): array {
    $fieldPaths = implode('&', array_map(
        fn($k) => 'updateMask.fieldPaths=' . urlencode($k),
        array_keys($data)
    ));
    return fb_call('PATCH', FB_FIRESTORE . "/$path?" . $fieldPaths, fb_fields($data));
}

function fb_firestore_delete(string $path, string $idToken = ''): void {
    fb_call('DELETE', FB_FIRESTORE . "/$path");
}

function fb_firestore_query(string $collection, array $filters, string $idToken = ''): array {
    $where = [];
    foreach ($filters as [$field, $op, $value]) {
        $where[] = ['fieldFilter' => [
            'field' => ['fieldPath' => $field],
            'op'    => $op,
            'value' => fb_val($value),
        ]];
    }

    $body = ['structuredQuery' => [
        'from'  => [['collectionId' => $collection]],
        'where' => count($where) === 1
            ? $where[0]
            : ['compositeFilter' => ['op' => 'AND', 'filters' => $where]],
    ]];

    $url = 'https://firestore.googleapis.com/v1/projects/' . FB_PROJECT . '/databases/(default)/documents:runQuery';
    $res = fb_call('POST', $url, $body);

    if (isset($res['error'])) {
        error_log('[AssTracker] fb_firestore_query failed: ' . json_encode($res));
        return [];
    }

    $docs = [];
    foreach ((array)$res as $item) {
        if (!empty($item['document'])) {
            $docs[] = fb_doc_to_array($item['document']);
        }
    }
    return $docs;
}