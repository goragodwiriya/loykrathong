<?php
declare(strict_types=1);

final class KrathongRepository
{
    private const COLS = 'id, base, flower, candle, incense, message, author_name, created_at';

    public function __construct(private PDO $pdo) {}

    /** @return array{0: array, 1: ?string, 2: int} [items, nextCursor, total] */
    public function list(int $limit, ?string $cursorRaw): array
    {
        $cursor = $cursorRaw !== null ? self::decodeCursor($cursorRaw) : null;

        $sql    = 'SELECT ' . self::COLS . ' FROM krathongs';
        $params = [];
        if ($cursor !== null) {
            if ($cursor['id'] !== null) {
                $sql .= ' WHERE (created_at < :ts OR (created_at = :ts2 AND id < :cid))';
                $params = [':ts' => $cursor['ts'], ':ts2' => $cursor['ts'], ':cid' => $cursor['id']];
            } else {
                $sql .= ' WHERE created_at < :ts';
                $params = [':ts' => $cursor['ts']];
            }
        }
        $sql .= ' ORDER BY created_at DESC, id DESC LIMIT ' . ($limit + 1); // +1 เพื่อเช็คหน้าถัดไป

        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $k => $v) $stmt->bindValue($k, $v);
        $stmt->execute();
        $rows = $stmt->fetchAll();

        $hasMore = count($rows) > $limit;
        if ($hasMore) $rows = array_slice($rows, 0, $limit);

        $items = array_map(self::toItem(...), $rows);
        $last  = $rows ? end($rows) : null;

        return [
            $items,
            ($hasMore && $last) ? self::encodeCursor($last['created_at'], $last['id']) : null,
            $this->countAll(),
        ];
    }

    public function latest(string $sinceSql, int $limit): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT ' . self::COLS . ' FROM krathongs
             WHERE created_at > :since
             ORDER BY created_at DESC
             LIMIT :lim'
        );
        $stmt->bindValue(':since', $sinceSql);
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return array_map(self::toItem(...), $stmt->fetchAll());
    }

    /** @return array{total:int, today:int} — "วันนี้" คิดตาม timezone ที่ตั้ง เทียบกับเวลา UTC ใน DB */
    public function stats(string $tz): array
    {
        $total = $this->countAll();

        $startUtc = (new DateTime('now', new DateTimeZone($tz)))
            ->setTime(0, 0)
            ->setTimezone(new DateTimeZone('UTC'))
            ->format('Y-m-d H:i:s.v');

        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM krathongs WHERE created_at >= :d');
        $stmt->execute([':d' => $startUtc]);

        return ['total' => $total, 'today' => (int)$stmt->fetchColumn()];
    }

    public function create(array $d, string $ipHash): array
    {
        $id  = bin2hex(random_bytes(12)); // 24 ตัวอักษร สุ่มแบบ cryptographic
        $ins = $this->pdo->prepare(
            'INSERT INTO krathongs
                (id, base, flower, candle, incense, message, author_name, ip_hash)
             VALUES (:id, :base, :flower, :candle, :incense, :message, :name, :iph)'
        );
        $ins->execute([
            ':id'      => $id,
            ':base'    => $d['base'],
            ':flower'  => $d['flower'],
            ':candle'  => $d['candle'],
            ':incense' => $d['incense'],
            ':message' => $d['message'],
            ':name'    => $d['name'],
            ':iph'     => $ipHash,
        ]);

        $stmt = $this->pdo->prepare('SELECT ' . self::COLS . ' FROM krathongs WHERE id = :id');
        $stmt->execute([':id' => $id]);
        return self::toItem($stmt->fetch());
    }

    public function exists(string $id): bool
    {
        $stmt = $this->pdo->prepare('SELECT 1 FROM krathongs WHERE id = :id');
        $stmt->execute([':id' => $id]);
        return $stmt->fetchColumn() !== false;
    }

    /** ลบได้เฉพาะเจ้าของ: token ถูกต้อง หรือ IP เดิมภายใน window */
    public function delete(string $id, string $ipHash, int $windowHours, bool $tokenVerified): bool
    {
        if ($tokenVerified) {
            $stmt = $this->pdo->prepare('DELETE FROM krathongs WHERE id = :id');
            $stmt->execute([':id' => $id]);
        } else {
            $stmt = $this->pdo->prepare(
                'DELETE FROM krathongs
                 WHERE id = :id
                   AND ip_hash = :iph
                   AND created_at >= DATE_SUB(NOW(3), INTERVAL :win HOUR)'
            );
            $stmt->execute([':id' => $id, ':iph' => $ipHash, ':win' => $windowHours]);
        }
        return $stmt->rowCount() === 1;
    }

    public function countAll(): int
    {
        return (int)$this->pdo->query('SELECT COUNT(*) FROM krathongs')->fetchColumn();
    }

    /* ---------------- helpers ---------------- */

    private static function toItem(array $r): array
    {
        return [
            'id'        => $r['id'],
            'base'      => $r['base'],
            'flower'    => $r['flower'],
            'candle'    => $r['candle'],
            'incense'   => (bool)$r['incense'],
            'message'   => $r['message'],
            'name'      => $r['author_name'],
            'createdAt' => self::toIso($r['created_at']),
        ];
    }

    private static function toIso(string $sqlDate): string
    {
        $d = DateTime::createFromFormat('Y-m-d H:i:s.v', $sqlDate, new DateTimeZone('UTC'))
            ?: DateTime::createFromFormat('Y-m-d H:i:s', $sqlDate, new DateTimeZone('UTC'));
        return $d !== false ? $d->format('Y-m-d\TH:i:s.v\Z') : $sqlDate;
    }

    private static function encodeCursor(string $sqlDate, string $id): string
    {
        return rtrim(strtr(base64_encode($sqlDate . '|' . $id), '+/', '-_'), '=');
    }

    /** @return ?array{ts:string, id:?string} */
    private static function decodeCursor(string $cursor): ?array
    {
        $cursor = trim($cursor);
        if ($cursor === '' || strlen($cursor) > 120) return null;

        // 1) รูปแบบของเรา: base64url("Y-m-d H:i:s.v|id")
        if (preg_match('/^[A-Za-z0-9_-]+$/', $cursor)) {
            $pad = str_repeat('=', (4 - strlen($cursor) % 4) % 4);
            $dec = base64_decode(strtr($cursor, '-_', '+/') . $pad, true);
            if (is_string($dec)
                && preg_match('/^(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}(?:\.\d{1,6})?)\|([A-Za-z0-9]{24})$/', $dec, $m)) {
                return ['ts' => $m[1], 'id' => $m[2]];
            }
        }

        // 2) fallback: ISO 8601 ตรง ๆ (ใช้กับ frontend เดิมได้)
        $ts = self::toSqlDate($cursor);
        return $ts === null ? null : ['ts' => $ts, 'id' => null];
    }

    /** แปลง ISO 8601 เป็น "Y-m-d H:i:s.v" แบบ UTC ตรงกับที่เก็บใน DB (คืน null ถ้าใช้ไม่ได้) */
    public static function toSqlDate(mixed $iso): ?string
    {
        if (!is_string($iso) || $iso === '' || strlen($iso) > 40) return null;
        try {
            $utc = new DateTimeZone('UTC');
            return (new DateTime($iso, $utc))->setTimezone($utc)->format('Y-m-d H:i:s.v');
        } catch (Throwable) {
            return null;
        }
    }
}