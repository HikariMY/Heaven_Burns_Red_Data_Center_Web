<?php
// db.php — PDO connector (host: localhost friendly)
// ใช้ได้ทั้ง localhost และ shared hosting ที่ DB host = "localhost"

class DB {
    // ค่าเริ่มต้น (ปรับได้)
    private $cfg = [
        'host'    => 'localhost',   // บน hosting ส่วนใหญ่ใช้ "localhost"
        'port'    => 3306,
        'name'    => 'hbr_web_db',  // ชื่อ DB
        'user'    => 'root',        // ใส่ผู้ใช้จริงตอนขึ้นโฮส
        'pass'    => '',            // ใส่รหัสจริงตอนขึ้นโฮส
        'charset' => 'utf8mb4',
        'timezone'=> 'Asia/Bangkok',
        'persistent' => false
    ];

    private static $pdo = null;

    public function __construct(array $override = []) {
        // 1) ถ้ามี config.db.php (return array) ให้ใช้ก่อน
        if (is_readable(__DIR__ . '/config.db.php')) {
            $fileCfg = include __DIR__ . '/config.db.php';
            if (is_array($fileCfg)) $this->cfg = array_replace($this->cfg, $fileCfg);
        }
        // 2) ถ้ามี db.ini
        elseif (is_readable(__DIR__ . '/db.ini')) {
            $ini = parse_ini_file(__DIR__ . '/db.ini', false, INI_SCANNER_TYPED);
            if ($ini) $this->cfg = array_replace($this->cfg, $ini);
        }
        // 3) ENV (ใส่ใน cPanel → Cron/Environment หรือ .htaccess SetEnv ก็ได้)
        $env = [
            'host' => getenv('DB_HOST') ?: null,
            'port' => getenv('DB_PORT') ?: null,
            'name' => getenv('DB_NAME') ?: null,
            'user' => getenv('DB_USER') ?: null,
            'pass' => getenv('DB_PASS') ?: null,
            'charset' => getenv('DB_CHARSET') ?: null,
        ];
        $this->cfg = array_replace($this->cfg, array_filter($env, fn($v)=>$v!==null));

        // 4) โค้ดเรียกส่ง override มาก็ได้
        if ($override) $this->cfg = array_replace($this->cfg, $override);
    }

    public function connect() {
        if (self::$pdo instanceof PDO) return self::$pdo;

        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            $this->cfg['host'],
            (int)$this->cfg['port'],
            $this->cfg['name'],
            $this->cfg['charset']
        );

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::ATTR_PERSISTENT         => (bool)$this->cfg['persistent'],
            PDO::ATTR_TIMEOUT            => 10,
            // ตั้งค่า charset ในบางโฮสต์ที่ต้องการ
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$this->cfg['charset']}"
        ];

        try {
            self::$pdo = new PDO($dsn, $this->cfg['user'], $this->cfg['pass'], $options);
            // ตั้ง timezone ของ session (ถ้าฐานข้อมูลรองรับ)
            if (!empty($this->cfg['timezone'])) {
                $tz = $this->cfg['timezone'];
                self::$pdo->exec("SET time_zone = '".addslashes(self::phpTzToMysqlOffset($tz))."'");
            }
            return self::$pdo;
        } catch (PDOException $e) {
            // fallback: ลองใช้ 127.0.0.1 แทน localhost ในบางโฮสต์
            if ($this->cfg['host'] === 'localhost') {
                try {
                    $altDsn = sprintf(
                        'mysql:host=127.0.0.1;port=%d;dbname=%s;charset=%s',
                        (int)$this->cfg['port'], $this->cfg['name'], $this->cfg['charset']
                    );
                    self::$pdo = new PDO($altDsn, $this->cfg['user'], $this->cfg['pass'], $options);
                    return self::$pdo;
                } catch (PDOException $e2) {
                    exit('DB connect failed (fallback): ' . $e2->getMessage());
                }
            }
            exit('DB connect failed: ' . $e->getMessage());
        }
    }

    // helper: แปลง PHP timezone → offset สำหรับ MySQL (เช่น +07:00)
    private static function phpTzToMysqlOffset(string $tz): string {
        try {
            $dt = new DateTime('now', new DateTimeZone($tz));
            return $dt->format('P'); // +07:00
        } catch (Throwable $e) {
            return '+00:00';
        }
    }
}
