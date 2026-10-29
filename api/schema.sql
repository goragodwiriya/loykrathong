-- ============================================================
--  ลอยกระทงออนไลน์ — MySQL Schema
--  ใช้งาน:  mysql -u root -p < schema.sql
-- ============================================================

CREATE DATABASE IF NOT EXISTS krathong_db
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE krathong_db;

-- API เก็บเวลาเป็น UTC (ดู Database.php) — seed ด้านล่างต้องใช้ UTC ด้วย
SET time_zone = '+00:00';

-- กระทงทั้งหมด (เก็บ IP แบบ hash เท่านั้น — privacy by design)
CREATE TABLE IF NOT EXISTS krathongs (
  id          CHAR(24)     NOT NULL,
  base        ENUM('tong','stem','bread')                          NOT NULL,
  flower      ENUM('lotus','rose','marigold','jasmine','orchid')   NOT NULL,
  candle      ENUM('gold','coral','pink','blue','lavender','mint') NOT NULL,
  incense     TINYINT(1)   NOT NULL DEFAULT 1,
  message     VARCHAR(140) NOT NULL,
  author_name VARCHAR(30)  NOT NULL DEFAULT '',
  ip_hash     CHAR(64)     NOT NULL,
  created_at  DATETIME(3)  NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
  PRIMARY KEY (id),
  KEY idx_created    (created_at),
  KEY idx_ip_created (ip_hash, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ตารางสำหรับ rate limiting
CREATE TABLE IF NOT EXISTS api_rate_limits (
  bucket  CHAR(64)     NOT NULL,
  hits    INT UNSIGNED NOT NULL DEFAULT 0,
  expires DATETIME(3)  NOT NULL,
  PRIMARY KEY (bucket),
  KEY idx_expires (expires)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- บัญชี DB แบบ least privilege สำหรับ host จริง — เปลี่ยนรหัสผ่าน เอา comment ออก
-- แล้วใส่ user/pass นี้ใน config/config.php แทน root
-- CREATE USER IF NOT EXISTS 'krathong_app'@'localhost'
--   IDENTIFIED BY 'CHANGE_ME_STRONG_PASSWORD';
-- GRANT SELECT, INSERT, UPDATE, DELETE ON krathong_db.* TO 'krathong_app'@'localhost';
-- FLUSH PRIVILEGES;

-- ข้อมูลตั้งต้น (ลบออกได้)
INSERT INTO krathongs (id, base, flower, candle, incense, message, author_name, ip_hash, created_at) VALUES
(LEFT(REPLACE(UUID(),'-',''),24),'tong','lotus','gold',1,'ขอให้ความทุกข์ทั้งหลายจมหายไปกับสายน้ำ','พิมพ์ชนก',SHA2('seed1',256),NOW(3)-INTERVAL 2 HOUR),
(LEFT(REPLACE(UUID(),'-',''),24),'bread','marigold','coral',0,'ขอโทษนะแม่น้ำ ที่เคยทิ้งขยะให้เธอ','ต้นน้ำ',SHA2('seed2',256),NOW(3)-INTERVAL 5 HOUR),
(LEFT(REPLACE(UUID(),'-',''),24),'stem','orchid','lavender',1,'ขอให้เราสองคนรักกันไปนาน ๆ','ภูมิ',SHA2('seed3',256),NOW(3)-INTERVAL 9 HOUR),
(LEFT(REPLACE(UUID(),'-',''),24),'tong','rose','pink',1,'ขอให้สอบผ่านทุกวิชา สมหวังทุกเรื่อง','แพรวา',SHA2('seed4',256),NOW(3)-INTERVAL 15 HOUR),
(LEFT(REPLACE(UUID(),'-',''),24),'stem','jasmine','blue',1,'ขอให้คนอ่านข้อความนี้มีความสุขมาก ๆ','มายด์',SHA2('seed5',256),NOW(3)-INTERVAL 26 HOUR);