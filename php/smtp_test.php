<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>SMTP Test – Mentor Portal</title>
<style>
  body{font-family:monospace;background:#111;color:#eee;padding:24px;margin:0}
  h1{color:#6366f1;margin-bottom:8px}
  .card{background:#1f2937;border-radius:10px;padding:20px;margin-bottom:16px;border:1px solid #374151}
  label{display:block;font-size:13px;color:#9ca3af;margin-bottom:4px}
  input{width:100%;padding:8px 12px;background:#111827;border:1px solid #374151;color:#fff;border-radius:6px;font-family:monospace;font-size:14px;box-sizing:border-box;margin-bottom:12px}
  button{background:#4f46e5;color:#fff;border:none;padding:10px 24px;border-radius:8px;font-size:14px;font-weight:600;cursor:pointer}
  button:hover{background:#4338ca}
  .ok{color:#10b981} .fail{color:#ef4444} .info{color:#fbbf24}
  pre{background:#0f172a;padding:16px;border-radius:8px;overflow-x:auto;font-size:12px;line-height:1.6;white-space:pre-wrap;word-break:break-all;border:1px solid #374151}
  .row{display:grid;grid-template-columns:1fr 1fr;gap:12px}
  @media(max-width:600px){.row{grid-template-columns:1fr}}
</style>
</head>
<body>

<h1>🔧 SMTP Diagnostic Tool</h1>
<p style="color:#9ca3af;margin-bottom:20px;font-size:13px">
  Tests your SMTP settings and shows the full connection log. <strong style="color:#ef4444">Delete this file after testing.</strong>
</p>

<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';

// ── Show current config ──────────────────────────────────────
echo '<div class="card">';
echo '<h3 style="color:#6366f1;margin:0 0 12px">Current SMTP Config (from config.php)</h3>';
echo '<pre>';
echo "SMTP_HOST       = " . SMTP_HOST . "\n";
echo "SMTP_PORT       = " . SMTP_PORT . "\n";
echo "SMTP_ENCRYPTION = " . SMTP_ENCRYPTION . "\n";
echo "SMTP_USER       = " . SMTP_USER . "\n";
echo "SMTP_PASS       = " . (SMTP_PASS !== 'xxxx xxxx xxxx xxxx' ? str_repeat('*', strlen(SMTP_PASS)) : '⚠ NOT SET (still placeholder)') . "\n";
echo "MAIL_FROM       = " . MAIL_FROM . "\n";
echo "MAIL_FROM_NAME  = " . MAIL_FROM_NAME . "\n";
echo "CAPTCHA_ENABLED = " . (CAPTCHA_ENABLED ? 'true' : 'false') . "\n";
echo '</pre>';

// ── PHP environment checks ───────────────────────────────────
echo '<h3 style="color:#6366f1;margin:12px 0 8px">PHP Environment</h3><pre>';
echo "PHP version     = " . PHP_VERSION . "\n";
echo "OpenSSL         = " . (extension_loaded('openssl') ? '<span class=ok>✓ enabled</span>' : '<span class=fail>✗ missing — TLS will not work</span>') . "\n";
echo "cURL            = " . (extension_loaded('curl')    ? '<span class=ok>✓ enabled</span>' : '<span class=fail>✗ missing — captcha verify will fail</span>') . "\n";
echo "stream_socket   = " . (function_exists('stream_socket_client') ? '<span class=ok>✓ available</span>' : '<span class=fail>✗ missing</span>') . "\n";
echo "allow_url_fopen = " . (ini_get('allow_url_fopen')  ? '<span class=ok>✓ on</span>' : '<span class=info>⚠ off (cURL used instead)</span>') . "\n";
echo '</pre></div>';

// ── Send test email form ─────────────────────────────────────
$sent = false;
$result = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['test_to'])) {
    $testTo   = trim($_POST['test_to']);
    $result   = sendMailDebug(
        $testTo,
        'Test Recipient',
        'Mentor Portal – SMTP Test Email',
        '<h2>✅ SMTP is working!</h2><p>If you received this, your password reset emails will be sent successfully.</p><p style="color:#666;font-size:12px">Sent from Mentor Portal SMTP Test at ' . date('Y-m-d H:i:s') . '</p>'
    );
    $sent = true;
}
?>

<div class="card">
  <h3 style="color:#6366f1;margin:0 0 12px">Send Test Email</h3>
  <form method="POST">
    <label>Send test email to:</label>
    <input type="email" name="test_to"
           value="<?= htmlspecialchars($_POST['test_to'] ?? SMTP_USER) ?>"
           placeholder="recipient@example.com" required>
    <button type="submit">▶ Send Test Email Now</button>
  </form>
</div>

<?php if ($sent && $result): ?>
<div class="card">
  <h3 style="margin:0 0 12px" class="<?= $result['success'] ? 'ok' : 'fail' ?>">
    <?= $result['success'] ? '✅ Email sent successfully!' : '❌ Email FAILED' ?>
  </h3>

  <?php if (!$result['success']): ?>
  <p style="color:#ef4444;margin-bottom:12px;font-size:14px">
    <strong>Error:</strong> <?= htmlspecialchars($result['error']) ?>
  </p>
  <p style="color:#9ca3af;font-size:13px;margin-bottom:12px">
    <?php
    $err = $result['error'];
    if (str_contains($err, 'Connection failed'))
        echo '💡 Cannot reach the SMTP server. Check SMTP_HOST and SMTP_PORT, or your firewall/antivirus may be blocking outbound port 587.';
    elseif (str_contains($err, '535') || str_contains($err, '534') || str_contains($err, 'password'))
        echo '💡 Authentication failed. For Gmail, you must use an <strong>App Password</strong>, not your normal Gmail password. Enable 2FA first, then generate an App Password at myaccount.google.com → Security → App Passwords.';
    elseif (str_contains($err, 'TLS') || str_contains($err, 'crypto'))
        echo '💡 TLS handshake failed. Make sure OpenSSL is enabled in php.ini (extension=openssl).';
    elseif (str_contains($err, '550') || str_contains($err, 'relay'))
        echo '💡 The SMTP server refused to relay. Make sure MAIL_FROM matches your authenticated Gmail address.';
    else
        echo '💡 Check the debug log below for the exact SMTP conversation.';
    ?>
  </p>
  <?php endif; ?>

  <h4 style="color:#9ca3af;font-size:12px;margin-bottom:6px">SMTP Debug Log:</h4>
  <pre><?= htmlspecialchars(implode("\n", $result['debug_log'])) ?></pre>
</div>
<?php endif; ?>

<div class="card" style="border-color:#991b1b">
  <p style="color:#ef4444;font-size:13px;margin:0">
    ⚠️ <strong>Security reminder:</strong> Delete <code>php/smtp_test.php</code> once you've confirmed email is working.
  </p>
</div>

</body>
</html>
