<?php

declare(strict_types=1);

namespace Mailexam\Smtp\Model;

final class Config
{
    public function isConfigured(): bool
    {
        return $this->getLogin() !== '' && $this->getPassword() !== '';
    }

    public function getLogin(): string
    {
        return (string) $this->env('MAILEXAM_LOGIN');
    }

    public function getPassword(): string
    {
        return (string) $this->env('MAILEXAM_PASSWORD');
    }

    public function getPort(): int
    {
        $port = (int) $this->env('MAILEXAM_PORT');

        return $port > 0 ? $port : 587;
    }

    public function getFrom(): string
    {
        $from = (string) $this->env('MAIL_FROM');

        return $from !== '' ? $from : 'noreply@example.test';
    }

    public function getHost(): string
    {
        return $this->getLogin() . '.mailexam.io';
    }

    private function env(string $name): string|false
    {
        $value = getenv($name);

        if ($value !== false && $value !== '') {
            return $value;
        }

        return $_ENV[$name] ?? $_SERVER[$name] ?? '';
    }
}
