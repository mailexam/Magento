<?php

declare(strict_types=1);

namespace Mailexam\Smtp\Controller\Mail;

use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultInterface;
use Mailexam\Smtp\Model\SmtpSender;

class Test implements HttpPostActionInterface, CsrfAwareActionInterface
{
    public function __construct(
        private readonly RequestInterface $request,
        private readonly JsonFactory $resultJsonFactory,
        private readonly SmtpSender $smtpSender
    ) {
    }

    public function execute(): ResultInterface
    {
        $payload = json_decode($this->request->getContent(), true) ?: [];

        $to = (string) ($payload['to'] ?? 'user@example.test');
        $subject = (string) ($payload['subject'] ?? 'Magento + Mailexam');
        $body = (string) ($payload['body'] ?? $payload['text'] ?? 'Mailexam test from Magento');

        try {
            $this->smtpSender->send($to, $subject, $body);
        } catch (\Throwable $exception) {
            return $this->resultJsonFactory->create()
                ->setHttpResponseCode(500)
                ->setData(['error' => $exception->getMessage()]);
        }

        return $this->resultJsonFactory->create()->setData(['status' => 'ok']);
    }

    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }

    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }
}
