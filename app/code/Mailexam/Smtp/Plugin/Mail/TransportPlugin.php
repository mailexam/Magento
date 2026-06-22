<?php

declare(strict_types=1);

namespace Mailexam\Smtp\Plugin\Mail;

use Closure;
use Magento\Email\Model\Transport;
use Mailexam\Smtp\Model\Config;
use Mailexam\Smtp\Model\SmtpSender;

final class TransportPlugin
{
    public function __construct(
        private readonly Config $config,
        private readonly SmtpSender $smtpSender
    ) {
    }

    public function aroundSendMessage(Transport $subject, Closure $proceed): void
    {
        if (!$this->config->isConfigured() || !method_exists($subject, 'getMessage')) {
            $proceed();

            return;
        }

        $this->smtpSender->sendMagentoMessage($subject->getMessage());
    }
}
