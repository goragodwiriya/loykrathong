<?php
declare(strict_types=1);

final class Validator
{
    private const BASES   = ['tong', 'stem', 'bread'];
    private const FLOWERS = ['lotus', 'rose', 'marigold', 'jasmine', 'orchid'];
    private const CANDLES = ['gold', 'coral', 'pink', 'blue', 'lavender', 'mint'];

    /** ตรวจ + ทำความสะอาด payload ของ POST /krathongs */
    public static function krathong(array $in): array
    {
        // honeypot — ฟิลด์ที่คนจริงไม่มีทางกรอก แต่บอทชอบกรอกทุกช่อง
        $honeypot = $in['website'] ?? '';
        if (is_string($honeypot) && trim($honeypot) !== '') {
            throw new ApiException(400, 'ข้อมูลไม่ถูกต้อง', 'VALIDATION');
        }

        $errors = [];
        $base   = $in['base']   ?? '';
        $flower = $in['flower'] ?? '';
        $candle = $in['candle'] ?? '';

        if (!in_array($base,   self::BASES,   true)) $errors[] = 'base ไม่ถูกต้อง';
        if (!in_array($flower, self::FLOWERS, true)) $errors[] = 'flower ไม่ถูกต้อง';
        if (!in_array($candle, self::CANDLES, true)) $errors[] = 'candle ไม่ถูกต้อง';

        $message = self::cleanText(self::str($in, 'message'));
        if ($message === '') {
            $errors[] = 'ต้องเขียนคำอวยพรก่อนลอยกระทงนะ';
        } elseif (mb_strlen($message) > 140) {
            $errors[] = 'คำอวยพรยาวเกิน 140 ตัวอักษร';
        }

        $name = self::cleanText(self::str($in, 'name'));
        if (mb_strlen($name) > 30) $errors[] = 'ชื่อยาวเกิน 30 ตัวอักษร';

        if ($errors) {
            throw new ApiException(400, implode(' / ', $errors), 'VALIDATION');
        }

        return [
            'base'    => $base,
            'flower'  => $flower,
            'candle'  => $candle,
            'incense' => !empty($in['incense']) ? 1 : 0,
            'message' => $message,
            'name'    => $name,
        ];
    }

    private static function str(array $in, string $key): string
    {
        return is_string($in[$key] ?? null) ? $in[$key] : '';
    }

    /** ตัด null byte / อักขระควบคุม / UTF-8 เสีย แล้วบีบช่องว่าง */
    private static function cleanText(string $s): string
    {
        if (!mb_check_encoding($s, 'UTF-8')) {
            $s = mb_convert_encoding($s, 'UTF-8', 'UTF-8');
        }
        $s = str_replace("\0", '', $s);
        $s = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $s) ?? '';
        $s = preg_replace('/\s+/u', ' ', $s) ?? '';
        return trim($s);
    }
}