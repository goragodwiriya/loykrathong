<?php
declare(strict_types=1);

/**
 * Rate limiter แบบ fixed window เก็บใน MySQL
 * bucket key = HMAC(ip + action) จึงไม่ผูกกับ IP ดิบ
 */
final class RateLimiter
{
    public function __construct(private PDO $pdo) {}

    public function enforce(string $bucketKey, int $limit, int $windowSeconds): void
    {
        $bucketKey = hash('sha256', $bucketKey); // ยาวคงที่ 64 ตัว พอดีคอลัมน์ CHAR(64)
        $nowMs  = (int)(microtime(true) * 1000);
        $nowSql = self::msToSql($nowMs);
        $expSql = self::msToSql($nowMs + $windowSeconds * 1000);

        // atomic UPSERT: เริ่ม window ใหม่ถ้าหมดอายุ / นับเพิ่มถ้ายังไม่หมด
        $stmt = $this->pdo->prepare(
            'INSERT INTO api_rate_limits (bucket, hits, expires)
             VALUES (:b, 1, :exp)
             ON DUPLICATE KEY UPDATE
                hits    = IF(expires <= :now,  1, hits + 1),
                expires = IF(expires <= :now2, :exp2, expires)'
        );
        $stmt->execute([
            ':b' => $bucketKey, ':exp' => $expSql,
            ':now' => $nowSql, ':now2' => $nowSql, ':exp2' => $expSql,
        ]);

        $check = $this->pdo->prepare(
            'SELECT hits,
                    GREATEST(1, TIMESTAMPDIFF(SECOND, :now3, expires)) AS retry_after
             FROM api_rate_limits
             WHERE bucket = :b2'
        );
        $check->execute([':now3' => $nowSql, ':b2' => $bucketKey]);
        $row = $check->fetch();

        if ($row && (int)$row['hits'] > $limit) {
            header('Retry-After: ' . (int)$row['retry_after']);
            throw new ApiException(429, 'ส่งรายการถี่เกินไป พักสักครู่แล้วลองใหม่นะ', 'RATE_LIMITED');
        }

        // ล้างแถวขยะแบบสุ่ม 1% ของ request (ไม่ต้องตั้ง cron)
        if (random_int(1, 100) === 1) {
            $this->pdo->exec("DELETE FROM api_rate_limits WHERE expires < '" . self::msToSql($nowMs - 86400000) . "'");
        }
    }

    private static function msToSql(int $ms): string
    {
        return gmdate('Y-m-d H:i:s', intdiv($ms, 1000)) . sprintf('.%03d', $ms % 1000);
    }
}