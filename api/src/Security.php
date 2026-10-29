<?php
declare(strict_types=1);

final class Security
{
    /** Header ความปลอดภัยของทุก response */
    public static function sendSecurityHeaders(): void
    {
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('Referrer-Policy: no-referrer');
        header('Cache-Control: no-store');
        header_remove('X-Powered-By');
        if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
        }
    }

    /** CORS แบบ allowlist — โดเมนที่ไม่อยู่ในลิสต์จะอ่าน response ไม่ได้ */
    public static function applyCors(array $allowedOrigins): void
    {
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
        if ($origin !== '' && in_array($origin, $allowedOrigins, true)) {
            header('Access-Control-Allow-Origin: ' . $origin);
            header('Vary: Origin');
            header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS');
            header('Access-Control-Allow-Headers: Content-Type, X-Owner-Token');
            header('Access-Control-Max-Age: 86400');
        }
    }

    /**
     * IP ของผู้ใช้ — อ่าน header ของ proxy เฉพาะเมื่อเชื่อถือ proxy ได้
     * (ถ้าเปิด trust ตอนไม่มี proxy จริง ผู้โจมตีปลอม header ข้าม rate limit ได้)
     */
    public static function clientIp(bool $trustProxy): string
    {
        if ($trustProxy) {
            if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
                return trim($_SERVER['HTTP_CF_CONNECTING_IP']);
            }
            if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                $first = trim(explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0]);
                if (filter_var($first, FILTER_VALIDATE_IP)) return $first;
            }
        }
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    /** hash ทางเดียว (HMAC) — ใช้ทำ rate limit / เช็คเจ้าของ โดยไม่ต้องเก็บ IP ดิบ */
    public static function ipHash(string $ip, string $secret): string
    {
        return hash_hmac('sha256', 'ip:' . $ip, $secret);
    }

    /** token พิสูจน์ความเป็นเจ้าของกระทง (stateless — คำนวณจาก secret ได้อย่างเดียว) */
    public static function ownerToken(string $id, string $secret): string
    {
        return hash_hmac('sha256', 'krathong-delete:' . $id, $secret);
    }

    public static function ownerTokenValid(string $id, string $token, string $secret): bool
    {
        return hash_equals(self::ownerToken($id, $secret), $token); // timing-safe
    }
}