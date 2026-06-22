<?php

declare(strict_types=1);

namespace Mailexam\Smtp\Model;

use Magento\Framework\Mail\EmailMessageInterface;

final class SmtpSender
{
    public function __construct(
        private readonly Config $config
    ) {
    }

    public function send(string $to, string $subject, string $body, ?string $from = null): void
    {
        if (!$this->config->isConfigured()) {
            throw new \RuntimeException('MAILEXAM_LOGIN and MAILEXAM_PASSWORD must be set');
        }

        if ($to === '') {
            $to = 'user@example.test';
        }
        if ($subject === '') {
            $subject = 'Magento + Mailexam';
        }
        if ($body === '') {
            $body = 'Mailexam test from Magento';
        }

        $fromAddress = $from ?: $this->config->getFrom();
        $host = $this->config->getHost();
        $port = $this->config->getPort();
        $login = $this->config->getLogin();
        $password = $this->config->getPassword();
        $addr = $host . ':' . $port;

        $message = $this->buildMessage($fromAddress, $to, $subject, $body);

        if (in_array($port, [587, 2525], true)) {
            $this->sendWithStartTls($addr, $host, $login, $password, $fromAddress, $to, $message);

            return;
        }

        $this->sendPlain($addr, $host, $login, $password, $fromAddress, $to, $message);
    }

    public function sendMagentoMessage(EmailMessageInterface $email): void
    {
        $to = array_key_first($email->getTo()) ?: 'user@example.test';
        $from = array_key_first($email->getFrom()) ?: $this->config->getFrom();
        $subject = $email->getSubject();
        $body = $this->extractBody($email);

        $this->send($to, $subject, $body, $from);
    }

    private function extractBody(EmailMessageInterface $email): string
    {
        $body = $email->getBody();

        if (method_exists($body, 'getParts')) {
            $parts = $body->getParts();
            if (!empty($parts) && method_exists($parts[0], 'getContent')) {
                return (string) $parts[0]->getContent();
            }
        }

        if (method_exists($email, 'getBodyText')) {
            return (string) $email->getBodyText();
        }

        return '';
    }

    private function buildMessage(string $from, string $to, string $subject, string $body): string
    {
        return implode("\r\n", [
            'From: ' . $from,
            'To: ' . $to,
            'Subject: ' . $subject,
            'MIME-Version: 1.0',
            'Content-Type: text/plain; charset=UTF-8',
            '',
            $body,
        ]);
    }

    private function sendWithStartTls(
        string $addr,
        string $host,
        string $login,
        string $password,
        string $from,
        string $to,
        string $message
    ): void {
        $socket = stream_socket_client('tcp://' . $addr, $errno, $errstr, 30);
        if ($socket === false) {
            throw new \RuntimeException('SMTP connection failed: ' . $errstr);
        }

        try {
            $this->expect($socket, 220);
            $this->command($socket, 'EHLO localhost');
            $this->command($socket, 'STARTTLS');
            stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
            $this->command($socket, 'EHLO localhost');
            $this->authenticate($socket, $login, $password);
            $this->command($socket, 'MAIL FROM:<' . $from . '>');
            $this->command($socket, 'RCPT TO:<' . $to . '>');
            $this->command($socket, 'DATA');
            fwrite($socket, $message . "\r\n.\r\n");
            $this->expect($socket, 250);
            $this->command($socket, 'QUIT');
        } finally {
            fclose($socket);
        }
    }

    private function sendPlain(
        string $addr,
        string $host,
        string $login,
        string $password,
        string $from,
        string $to,
        string $message
    ): void {
        $socket = stream_socket_client('tcp://' . $addr, $errno, $errstr, 30);
        if ($socket === false) {
            throw new \RuntimeException('SMTP connection failed: ' . $errstr);
        }

        try {
            $this->expect($socket, 220);
            $this->command($socket, 'EHLO localhost');
            $this->authenticate($socket, $login, $password);
            $this->command($socket, 'MAIL FROM:<' . $from . '>');
            $this->command($socket, 'RCPT TO:<' . $to . '>');
            $this->command($socket, 'DATA');
            fwrite($socket, $message . "\r\n.\r\n");
            $this->expect($socket, 250);
            $this->command($socket, 'QUIT');
        } finally {
            fclose($socket);
        }
    }

    private function authenticate($socket, string $login, string $password): void
    {
        $this->command($socket, 'AUTH LOGIN');
        $this->expect($socket, 334);
        fwrite($socket, base64_encode($login) . "\r\n");
        $this->expect($socket, 334);
        fwrite($socket, base64_encode($password) . "\r\n");
        $this->expect($socket, 235);
    }

    private function command($socket, string $command): void
    {
        fwrite($socket, $command . "\r\n");
        $this->expect($socket, null);
    }

    private function expect($socket, ?int $code): void
    {
        $response = '';
        while (($line = fgets($socket, 515)) !== false) {
            $response .= $line;
            if (isset($line[3]) && $line[3] === ' ') {
                break;
            }
        }

        if ($code !== null && !str_starts_with($response, (string) $code)) {
            throw new \RuntimeException('Unexpected SMTP response: ' . trim($response));
        }
    }
}
