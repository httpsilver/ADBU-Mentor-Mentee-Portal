<?php

namespace ADMentorConnect;

class Mailer
{
    private string $host       = '';
    private int    $port       = 587;
    private string $encryption = 'tls';  
    private string $username   = '';
    private string $password   = '';
    private string $fromEmail  = '';
    private string $fromName   = '';
    private int    $timeout    = 20;

    private string $lastError  = '';
    private array  $debugLog   = [];
    /** @var resource|null */
    private $socket = null;

    public function configure(
        string $host, int $port, string $encryption,
        string $user, string $pass,
        string $fromEmail, string $fromName = ''
    ): void {
        $this->host       = $host;
        $this->port       = $port;
        $this->encryption = strtolower($encryption);
        $this->username   = $user;
        $this->password   = $pass;
        $this->fromEmail  = $fromEmail;
        $this->fromName   = $fromName;
    }

    public function getLastError(): string { return $this->lastError; }
    public function getDebugLog(): array   { return $this->debugLog;  }


    public function send(
        string $toEmail, string $toName,
        string $subject, string $htmlBody,
        string $plainBody = ''
    ): bool {
        $this->lastError = '';
        $this->debugLog  = [];

        try {
            $this->openSocket();
            $this->readExpect('220', 'greeting');

            $this->ehlo();

            if ($this->encryption === 'tls') {
                $this->cmd('STARTTLS', '220', 'STARTTLS');
                $this->upgradeToTLS();
                $this->ehlo(); 
            }

            $this->login();
            $this->envelope($toEmail, $toName, $subject, $htmlBody, $plainBody);
            $this->quit();
            return true;

        } catch (\Exception $e) {
            $this->lastError = $e->getMessage();
            $this->log('ERROR: ' . $e->getMessage());
            $this->closeSocket();
            return false;
        }
    }

    // ── Socket 
    private function openSocket(): void
    {
        $host = $this->encryption === 'ssl'
            ? 'ssl://' . $this->host
            : $this->host;

        $ctx = stream_context_create([
            'ssl' => [
                'verify_peer'       => false,
                'verify_peer_name'  => false,
                'allow_self_signed' => true,
            ],
        ]);

        $this->log("Connecting to {$host}:{$this->port}");
        $sock = @stream_socket_client(
            "{$host}:{$this->port}",
            $errno, $errstr,
            $this->timeout,
            STREAM_CLIENT_CONNECT,
            $ctx
        );

        if (!$sock) {
            throw new \Exception("Connection failed to {$this->host}:{$this->port} — {$errstr} (#{$errno})");
        }
        stream_set_timeout($sock, $this->timeout);
        $this->socket = $sock;
    }

    private function upgradeToTLS(): void
    {
        $this->log('Upgrading to TLS...');
        $ok = stream_socket_enable_crypto(
            $this->socket, true,
            STREAM_CRYPTO_METHOD_TLS_CLIENT
        );
        if (!$ok) {
            throw new \Exception('STARTTLS crypto upgrade failed. Check that OpenSSL is enabled in PHP.');
        }
        $this->log('TLS active.');
    }

    private function closeSocket(): void
    {
        if ($this->socket) {
            @fclose($this->socket);
            $this->socket = null;
        }
    }

    // ── SMTP commands 
    private function ehlo(): void
    {
        $domain = gethostname() ?: 'localhost';
        $this->cmd("EHLO {$domain}", '250', 'EHLO');
    }

    private function login(): void
    {
        $this->cmd('AUTH LOGIN', '334', 'AUTH LOGIN');
        $this->cmd(base64_encode($this->username), '334', 'username');
        $this->cmd(base64_encode($this->password), '235', 'password');
    }

    private function envelope(
        string $toEmail, string $toName,
        string $subject, string $html, string $plain
    ): void {
        $this->cmd("MAIL FROM:<{$this->fromEmail}>", '250', 'MAIL FROM');
        $this->cmd("RCPT TO:<{$toEmail}>",           '250', 'RCPT TO');
        $this->cmd('DATA',                            '354', 'DATA');

        $raw = $this->buildMessage($toEmail, $toName, $subject, $html, $plain);
        
        $raw = preg_replace('/^\.$/m', '..', $raw);

        $this->write($raw . "\r\n.");
        $this->readExpect('250', 'end of DATA');
    }

    private function quit(): void
    {
        $this->write('QUIT');
        $this->readLine(); 
        $this->closeSocket();
    }

    // ── Message builder 
    private function buildMessage(
        string $toEmail, string $toName,
        string $subject, string $html, string $plain
    ): string {
        $boundary = 'MP_' . bin2hex(random_bytes(8));
        $msgId    = '<' . bin2hex(random_bytes(16)) . '@mentorportal>';
        $date     = date('r');

        $from = $this->fromName
            ? $this->mimeEncode($this->fromName) . " <{$this->fromEmail}>"
            : $this->fromEmail;
        $to   = $toName
            ? $this->mimeEncode($toName) . " <{$toEmail}>"
            : $toEmail;

        $plain = $plain ?: strip_tags(str_replace(['</p>', '<br>', '<br/>'], "\n", $html));

        $msg  = "Date: {$date}\r\n";
        $msg .= "Message-ID: {$msgId}\r\n";
        $msg .= "From: {$from}\r\n";
        $msg .= "To: {$to}\r\n";
        $msg .= "Subject: " . $this->mimeEncode($subject) . "\r\n";
        $msg .= "MIME-Version: 1.0\r\n";
        $msg .= "Content-Type: multipart/alternative; boundary=\"{$boundary}\"\r\n";
        $msg .= "X-Mailer: ADMentorConnect\r\n";
        $msg .= "\r\n";

        // Plain text part
        $msg .= "--{$boundary}\r\n";
        $msg .= "Content-Type: text/plain; charset=UTF-8\r\n";
        $msg .= "Content-Transfer-Encoding: base64\r\n\r\n";
        $msg .= chunk_split(base64_encode($plain)) . "\r\n";

        // HTML part
        $msg .= "--{$boundary}\r\n";
        $msg .= "Content-Type: text/html; charset=UTF-8\r\n";
        $msg .= "Content-Transfer-Encoding: base64\r\n\r\n";
        $msg .= chunk_split(base64_encode($html)) . "\r\n";

        $msg .= "--{$boundary}--\r\n";
        return $msg;
    }

    private function mimeEncode(string $str): string
    {
        
        if (preg_match('/[^\x20-\x7E]/', $str) || mb_strlen($str) > 60) {
            return '=?UTF-8?B?' . base64_encode($str) . '?=';
        }
        return $str;
    }

    // ── I/O helpers 
    private function cmd(string $cmd, string $expectCode, string $label): string
    {
        
        $logCmd = (strpos($cmd, base64_encode($this->password)) !== false)
            ? '[password hidden]'
            : $cmd;
        $this->write($cmd);
        return $this->readExpect($expectCode, $label);
    }

    private function write(string $data): void
    {
        $this->log('>>> ' . (strlen($data) > 200 ? substr($data, 0, 200) . '...' : $data));
        fwrite($this->socket, $data . "\r\n");
    }

    private function readExpect(string $code, string $context): string
    {
        $response = $this->readResponse();
        if (substr($response, 0, strlen($code)) !== $code) {
            throw new \Exception(
                "SMTP error at [{$context}]: expected {$code}, got: " .
                substr($response, 0, 120)
            );
        }
        return $response;
    }

    private function readResponse(): string
    {
        $full = '';
        while (true) {
            $line = $this->readLine();
            $full .= $line . "\n";
            if (!isset($line[3]) || $line[3] === ' ' || $line[3] === "\r") break;
        }
        return trim($full);
    }

    private function readLine(): string
    {
        $line = fgets($this->socket, 1024);
        if ($line === false) $line = '';
        $line = rtrim($line);
        $this->log('<<< ' . $line);
        return $line;
    }

    private function log(string $msg): void
    {
        $this->debugLog[] = date('H:i:s') . ' ' . $msg;
    }
}
