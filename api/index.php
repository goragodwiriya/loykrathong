<?php
declare (strict_types = 1);

/**
 * ลอยกระทงออนไลน์ — REST API entry point เดียว
 * ทุก request วิ่งเข้าไฟล์นี้ผ่าน .htaccess / nginx / php -S
 */

ini_set('display_errors', '0'); // ห้าม spit error ออกทาง response
error_reporting(E_ALL);

require __DIR__.'/src/ApiException.php';
require __DIR__.'/src/Database.php';
require __DIR__.'/src/Http.php';
require __DIR__.'/src/Security.php';
require __DIR__.'/src/Validator.php';
require __DIR__.'/src/RateLimiter.php';
require __DIR__.'/src/KrathongRepository.php';

/* ยังไม่ได้ตั้งค่า = ไม่ทำงานเลย ดีกว่ารันด้วย secret ที่ทุกคนรู้ (frontend โหมด auto จะสลับไปใช้ Mock เอง) */
$configFile = __DIR__.'/config/config.php';
if (!is_file($configFile)) {
    Http::error(500, 'ยังไม่ได้ตั้งค่า API — คัดลอก config/config.example.php เป็น config/config.php', 'NOT_CONFIGURED');
}
$config = require $configFile;
if (!preg_match('/^[0-9a-f]{64}$/i', (string)($config['app_secret'] ?? ''))) {
    Http::error(500, 'app_secret ใน config.php ต้องเป็น hex 64 ตัว (สุ่มด้วย bin2hex(random_bytes(32)))', 'NOT_CONFIGURED');
}

Security::sendSecurityHeaders();
Security::applyCors($config['allowed_origins']);

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if ($method === 'OPTIONS') { // CORS preflight
    http_response_code(204);
    exit;
}

/* แยก path — base คือโฟลเดอร์ของไฟล์นี้ (/api, /krathong/api, ...) จึงวางที่ไหนก็ได้ไม่ต้องแก้ */
$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$uri = rawurldecode($uri);
$base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
if ($base !== '' && ($uri === $base || str_starts_with($uri, $base.'/'))) {
    $uri = substr($uri, strlen($base));
}

$uri = '/'.trim($uri, '/');

try {
    $pdo = Database::pdo($config['db']);
    $repo = new KrathongRepository($pdo);
    $limiter = new RateLimiter($pdo);

    $ipHash = Security::ipHash(
        Security::clientIp($config['trust_proxy_headers']),
        $config['app_secret']
    );

    // rate limit ชุดอ่าน — ครอบทุก endpoint
    $limiter->enforce(
        'read:'.$ipHash,
        $config['rate_limits']['read']['limit'],
        $config['rate_limits']['read']['window']
    );

    $matched = preg_match('#^/krathongs(?:/([A-Za-z0-9]{10,64}))?$#', $uri, $m);

    if ($matched && isset($m[1])) {
        /* ===== DELETE /krathongs/{id} ===== */
        if ($method !== 'DELETE') {
            Http::error(405, 'Method Not Allowed', 'METHOD');
        }

        $limiter->enforce('del:'.$ipHash,
            $config['rate_limits']['delete']['limit'],
            $config['rate_limits']['delete']['window']);

        $id = $m[1];
        $token = $_SERVER['HTTP_X_OWNER_TOKEN'] ?? '';
        $tokenValid = is_string($token) && $token !== ''
        && Security::ownerTokenValid($id, $token, $config['app_secret']);

        if (!$repo->exists($id)) {
            Http::error(404, 'ไม่พบกระทงลำนี้', 'NOT_FOUND');
        }
        $ok = $repo->delete($id, $ipHash, $config['delete_window_hours'], $tokenValid);
        if (!$ok) {
            Http::error(403, 'ลบได้เฉพาะกระทงที่คุณลอยเองเท่านั้น', 'FORBIDDEN');
        }
        http_response_code(204);
        exit;
    } elseif ($matched) {

        /* ===== /krathongs ===== */
        if ($method === 'GET') {
            $limit = Http::intQuery('limit', 12, 1, 50);
            $cursor = isset($_GET['cursor']) && is_string($_GET['cursor']) ? $_GET['cursor'] : null;
            [$items, $nextCursor, $total] = $repo->list($limit, $cursor);
            Http::json(200, ['items' => $items, 'nextCursor' => $nextCursor, 'total' => $total]);
        }

        if ($method === 'POST') {
            $limiter->enforce('write:'.$ipHash,
                $config['rate_limits']['write']['limit'],
                $config['rate_limits']['write']['window']);

            $in = Http::readJsonBody($config['max_body_bytes']);
            $data = Validator::krathong($in);
            $item = $repo->create($data, $ipHash);

            Http::json(201, [
                'item' => $item,
                'ownerToken' => Security::ownerToken($item['id'], $config['app_secret'])
            ]);
        }

        Http::error(405, 'Method Not Allowed', 'METHOD');
    } elseif ($uri === '/krathongs/latest' && $method === 'GET') {

        $sinceSql = KrathongRepository::toSqlDate($_GET['since'] ?? '') ?? gmdate('Y-m-d H:i:s.v');
        $items = $repo->latest($sinceSql, Http::intQuery('limit', 10, 1, 20));
        Http::json(200, ['items' => $items]);
    } elseif ($uri === '/krathongs/stats' && $method === 'GET') {

        Http::json(200, $repo->stats($config['stats_timezone']));
    } else {
        Http::error(404, 'Not Found', 'NOT_FOUND');
    }
} catch (ApiException $e) {
    Http::error($e->status, $e->getMessage(), $e->errorCode);
} catch (PDOException $e) {
    error_log('[krathong] DB: '.$e->getMessage());
    Http::error(500, 'เกิดข้อผิดพลาดภายในเซิร์ฟเวอร์', 'INTERNAL');
} catch (Throwable $e) {
    error_log('[krathong] '.$e->getMessage());
    Http::error(500, 'เกิดข้อผิดพลาดภายในเซิร์ฟเวอร์', 'INTERNAL');
}