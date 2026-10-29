<?php
declare (strict_types = 1);

/**
 * คัดลอกไฟล์นี้เป็น config.php แล้วแก้ค่าให้ตรงกับเครื่อง/โฮสต์ของคุณ
 * config.php อยู่ใน .gitignore — ห้าม commit เพราะมีรหัสผ่าน DB และ app_secret
 */
return [
    'db' => [
        'host' => '127.0.0.1',
        'port' => 3306,
        'name' => 'krathong_db',
        'user' => 'krathong_app',
        'pass' => 'CHANGE_ME_STRONG_PASSWORD',
        'charset' => 'utf8mb4'
    ],

    // สุ่มครั้งเดียว:  php -r "echo bin2hex(random_bytes(32));"
    // API จะไม่ทำงานถ้ายังเป็นค่านี้ (secret ที่เดาได้ = ปลอม owner token ลบกระทงคนอื่นได้)
    'app_secret' => 'CHANGE_ME_64_HEX_CHARS',

    // CORS allowlist — ใส่เฉพาะเมื่อหน้าเว็บอยู่คนละ origin กับ API (เช่น GitHub Pages + API บนโฮสต์อื่น)
    // ห้ามใช้ * กับ API สาธารณะ
    'allowed_origins' => [
        'https://yourdomain.com',
        'https://www.yourdomain.com',
        // 'https://your-username.github.io',
        'http://localhost:5500', // VS Code Live Server ตอน dev
        'http://127.0.0.1:5500'
    ],

    // true เฉพาะเมื่ออยู่หลัง reverse proxy/CDN ที่เชื่อถือได้ (เช่น Cloudflare)
    'trust_proxy_headers' => false,

    'rate_limits' => [
        'read' => ['limit' => 120, 'window' => 60], // ครั้ง / วินาทีของวินโดว์
        'write' => ['limit' => 10, 'window' => 3600],
        'delete' => ['limit' => 10, 'window' => 3600]
    ],

    'max_body_bytes' => 2048,
    'delete_window_hours' => 24, // ลบได้ด้วย IP เดิมภายในกี่ชั่วโมง (สำรองเมื่อไม่มี token)
    'stats_timezone' => 'Asia/Bangkok'
];
