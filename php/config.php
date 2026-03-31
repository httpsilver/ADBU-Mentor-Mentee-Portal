<?php

// DATABASE
define('DB_HOST',    'localhost');
define('DB_USER',    'root');
define('DB_PASS',    '');
define('DB_NAME',    'mentor_portal');
define('DB_CHARSET', 'utf8mb4');


// APP

define('APP_NAME', 'ADBU MentorConnect');
define('APP_URL',  'http://localhost/mentor-portal');
define('UPLOAD_PATH', __DIR__ . '/../assets/uploads/');
define('UPLOAD_URL',  APP_URL . '/assets/uploads/');
define('SESSION_TIMEOUT', 3600);


// SMTP EMAIL  

define('SMTP_HOST',       'smtp.gmail.com');
define('SMTP_PORT',       587);
define('SMTP_ENCRYPTION', 'tls');

define('SMTP_USER',       'youremailserver@gmail.com');
define('SMTP_PASS',       'app_password_here'); // Use an app password for Gmail
define('MAIL_FROM',       'youremailserver@gmail.com');
define('MAIL_FROM_NAME',  'ADBU MentorConnect');


define('SMTP_CONFIGURED', SMTP_USER !== 'ENTER_YOUR_GMAIL@gmail.com');


// hCAPTCHA

define('HCAPTCHA_SECRET',   'secret_key_here');
define('HCAPTCHA_SITE_KEY', 'site_key_here');
define('CAPTCHA_ENABLED',   true);


// DATABASE CONNECTION

function getDB() {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            die(json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]));
        }
    }
    return $pdo;
}
?>
