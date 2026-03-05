<?php
/**
 * firebase.php
 * Thin wrapper around Firebase REST API + Firebase Auth REST API.
 * No Composer required — works on plain XAMPP / shared hosting.
 */

// ── Your Firebase project credentials ────────────────────
define('FB_API_KEY',   'AIzaSyDC6VPrEg-TN8Jcvj0g16WOHXn6eqMiR6U');
define('FB_PROJECT',   'ass-tracker-f86bf');
define('FB_FIRESTORE', 'https://firestore.googleapis.com/v1/projects/' . FB_PROJECT . '/databases/(default)/documents');
define('FB_AUTH_URL',  'https://identitytoolkit.googleapis.com/v1/accounts');

// ── Low-level HTTP helper ────────────────────────────────
function fb_http(string $method, string $url, array $payload = [], string $idToken = ''): array {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST  => $method,
        CURLOPT_HTTPHEADER     => array_filter([
            'Content-Type: application/json',
            $idToken ? "Authorization: Bearer $idToken" : null,
        ]),
    ]);
    if ($payload) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    }
    $body = curl_exec($ch);
    curl_close($ch);
    return json_decode($body, true) ?? [];
}

// ════════════════════════════════════════════════════════
//  AUTH helpers
// ════════════════════════════════════════════════════════

function fb_register(string $email, string $pass, string $name): array {
    $res = fb_http('POST', FB_AUTH_URL . ':signUp?key=' . FB_API_KEY, [
        'email' => $email, 'password' => $pass, 'returnSecureToken' => true,
    ]);
    if (isset($res['error'])) return ['error' => $res['error']['message']];

    $idToken = $res['idToken'];
    $uid     = $res['localId'];

    // Save display name
    fb_http('POST', FB_AUTH_URL . ':update?key=' . FB_API_KEY, [
        'idToken' => $idToken, 'displayName' => $name, 'returnSecureToken' => true,
    ]);

    // Save user document in Firestore
    fb_firestore_set("users/$uid", ['name' => $name, 'email' => $email], $idToken);

    return ['idToken' => $idToken, 'uid' => $uid, 'name' => $name, 'email' => $email];
}

function fb_login(string $email, string $pass): array {
    $res = fb_http('POST', FB_AUTH_URL . ':signInWithPassword?key=' . FB_API_KEY, [
        'email' => $email, 'password' => $pass, 'returnSecureToken' => true,
    ]);
    if (isset($res['error'])) return ['error' => $res['error']['message']];

    return [
        'idToken' => $res['idToken'],
        'uid'     => $res['localId'],
        'name'    => $res['displayName'] ?? explode('@', $email)[0],
        'email'   => $res['email'],
    ];
}

function fb_forgot(string $email): bool {
    $res = fb_http('POST', FB_AUTH_URL . ':sendOobCode?key=' . FB_API_KEY, [
        'requestType' => 'PASSWORD_RESET', 'email' => $email,
    ]);
    return !isset($res['error']);
}

// ════════════════════════════════════════════════════════
//  FIRESTORE helpers
// ════════════════════════════════════════════════════════

/** Convert a PHP value into a Firestore field value object */
function fb_val($v): array {
    if (is_bool($v))   return ['booleanValue'  => $v];
    if (is_int($v))    return ['integerValue'  => (string)$v];
    if (is_float($v))  return ['doubleValue'   => $v];
    if (is_null($v))   return ['nullValue'     => null];
    return                    ['stringValue'   => (string)$v];
}

/** Convert a PHP assoc array into Firestore fields map */
function fb_fields(array $data): array {
    $fields = [];
    foreach ($data as $k => $v) $fields[$k] = fb_val($v);
    return ['fields' => $fields];
}

/** Extract PHP value from a Firestore field value object */
function fb_extract(array $field) {
    return $field['stringValue']
        ?? $field['integerValue']
        ?? $field['booleanValue']
        ?? $field['doubleValue']
        ?? null;
}

/** Convert a Firestore document to a plain PHP array */
function fb_doc_to_array(array $doc): array {
    $out = [];
    $nameParts = explode('/', $doc['name'] ?? '');
    $out['id'] = end($nameParts);
    foreach ($doc['fields'] ?? [] as $k => $v) {
        $out[$k] = fb_extract($v);
    }
    return $out;
}

/** GET a single document */
function fb_firestore_get(string $path, string $idToken): ?array {
    $res = fb_http('GET', FB_FIRESTORE . "/$path", [], $idToken);
    if (isset($res['error']) || !isset($res['fields'])) return null;
    return fb_doc_to_array($res);
}

/** CREATE or OVERWRITE a document at an explicit path */
function fb_firestore_set(string $path, array $data, string $idToken): array {
    return fb_http('PATCH', FB_FIRESTORE . "/$path", fb_fields($data), $idToken);
}

/** CREATE a document with auto-generated ID */
function fb_firestore_add(string $collection, array $data, string $idToken): ?string {
    $res = fb_http('POST', FB_FIRESTORE . "/$collection", fb_fields($data), $idToken);
    if (!isset($res['name'])) return null;
    $parts = explode('/', $res['name']);
    return end($parts);
}

/** UPDATE specific fields on a document */
function fb_firestore_update(string $path, array $data, string $idToken): array {
    $fieldPaths = implode('&', array_map(fn($k) => 'updateMask.fieldPaths=' . urlencode($k), array_keys($data)));
    $url = FB_FIRESTORE . "/$path?" . $fieldPaths;
    return fb_http('PATCH', $url, fb_fields($data), $idToken);
}

/** DELETE a document */
function fb_firestore_delete(string $path, string $idToken): void {
    fb_http('DELETE', FB_FIRESTORE . "/$path", [], $idToken);
}

/**
 * QUERY a collection with simple equality filters.
 * $filters = [['field','op','value'], ...]  op: EQUAL | LESS_THAN | GREATER_THAN etc.
 */
function fb_firestore_query(string $collection, array $filters, string $idToken): array {
    $where = [];
    foreach ($filters as [$field, $op, $value]) {
        $where[] = [
            'fieldFilter' => [
                'field'    => ['fieldPath' => $field],
                'op'       => $op,
                'value'    => fb_val($value),
            ],
        ];
    }

    $body = ['structuredQuery' => [
        'from'  => [['collectionId' => $collection]],
        'where' => count($where) === 1
            ? $where[0]
            : ['compositeFilter' => ['op' => 'AND', 'filters' => $where]],
    ]];

    $url  = 'https://firestore.googleapis.com/v1/projects/' . FB_PROJECT . '/databases/(default)/documents:runQuery';
    $res  = fb_http('POST', $url, $body, $idToken);

    $docs = [];
    foreach ((array)$res as $item) {
        if (!empty($item['document'])) {
            $docs[] = fb_doc_to_array($item['document']);
        }
    }
    return $docs;
}
