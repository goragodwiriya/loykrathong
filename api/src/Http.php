<?php
declare(strict_types=1);

final class Http
{
    public static function json(int $status, array $data): never
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    public static function error(int $status, string $message, string $errorCode = 'ERROR'): never
    {
        self::json($status, ['status' => $status, 'code' => $errorCode, 'message' => $message]);
    }

    /** อ่าน JSON body แบบจำกัดขนาดและความลึก */
    public static function readJsonBody(int $maxBytes): array
    {
        $declared = (int)($_SERVER['CONTENT_LENGTH'] ?? 0);
        if ($declared > $maxBytes) {
            self::error(413, 'ข้อมูลส่งมาใหญ่เกินกำหนด', 'BODY_TOO_LARGE');
        }
        $raw = file_get_contents('php://input') ?: '';
        if (strlen($raw) > $maxBytes) {
            self::error(413, 'ข้อมูลส่งมาใหญ่เกินกำหนด', 'BODY_TOO_LARGE');
        }
        if (trim($raw) === '') {
            self::error(400, 'ไม่พบข้อมูล JSON', 'EMPTY_BODY');
        }
        $data = json_decode($raw, true, 4);
        if (!is_array($data)) {
            self::error(400, 'รูปแบบ JSON ไม่ถูกต้อง', 'INVALID_JSON');
        }
        return $data;
    }

    /** อ่านตัวเลขจาก query string อย่างปลอดภัย */
    public static function intQuery(string $key, int $default, int $min, int $max): int
    {
        $v = $_GET[$key] ?? null;
        if (!is_string($v) || !preg_match('/^\d{1,4}$/', $v)) return $default;
        return max($min, min($max, (int)$v));
    }
}