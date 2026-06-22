# Magento + Mailexam

Minimal [Magento 2](https://business.adobe.com/products/magento/magento-commerce.html) Open Source integration that sends test mail through [Mailexam](https://mailexam.io/) SMTP via a custom module.

Based on the [Mailexam Magento guide](https://wiki.mailexam.ru/en/examples/magento/).

## What you need

- A Mailexam account and a project with SMTP credentials.
- An existing Magento 2.4+ store (Open Source or Commerce) with PHP 8.1+.

From your Mailexam welcome email or dashboard:

| Variable | Description |
|----------|-------------|
| `MAILEXAM_LOGIN` | SMTP login (for example, `xxxxx`) |
| `MAILEXAM_PASSWORD` | SMTP password (paired with the login) |
| Host | `{MAILEXAM_LOGIN}.mailexam.io` (built automatically in code) |

## Quick start (host)

Use this on an **existing** Magento installation.

1. Copy the module into your store:

```bash
cp -R app/code/Mailexam/Smtp /path/to/magento/app/code/Mailexam/Smtp
```

2. Add Mailexam variables to `app/etc/env.php` (see `env.php.example`):

```php
'env' => [
    'MAILEXAM_LOGIN' => 'YOUR_LOGIN',
    'MAILEXAM_PASSWORD' => 'YOUR_PASSWORD',
    'MAILEXAM_PORT' => '587',
    'MAIL_FROM' => 'noreply@example.test',
],
```

Do not commit real passwords to git.

3. Enable the module:

```bash
cd /path/to/magento
bin/magento module:enable Mailexam_Smtp
bin/magento setup:upgrade
bin/magento cache:flush
```

4. Send a test message:

```bash
curl -X POST https://your-store.test/mailexam/mail/test \
  -H 'Content-Type: application/json' \
  -d '{"to":"user@example.test","subject":"Test","body":"Hello"}'
```

The message appears in the Mailexam dashboard → your project → inbox.

When configured, the module also routes all Magento transactional mail (`TransportInterface`) through Mailexam SMTP.

## Environment variables

| Variable | Required | Default | Description |
|----------|----------|---------|-------------|
| `MAILEXAM_LOGIN` | yes | — | SMTP login; host becomes `{login}.mailexam.io` |
| `MAILEXAM_PASSWORD` | yes | — | SMTP password |
| `MAILEXAM_PORT` | no | `587` | SMTP port (`587`, `2525`, or `25`) |
| `MAIL_FROM` | no | `noreply@example.test` | Sender address for test requests |

For port **587** sending uses STARTTLS. For port **25** it uses plain SMTP without STARTTLS.

Variables are read from Magento `env.php` (`env` section), `getenv()`, or the shell environment.

## Project layout

```
.
├── app/code/Mailexam/Smtp/
│   ├── Controller/Mail/Test.php      # POST /mailexam/mail/test
│   ├── Model/Config.php              # reads MAILEXAM_* variables
│   ├── Model/SmtpSender.php          # SMTP client with STARTTLS
│   ├── Plugin/Mail/TransportPlugin.php
│   └── etc/                          # module, routes, di.xml
├── env.php.example
└── .env.example                      # reference for CI/shell exports
```

## CI

Set these secrets in your CI environment or export them before integration tests:

```yaml
variables:
  MAILEXAM_LOGIN: $MAILEXAM_LOGIN
  MAILEXAM_PASSWORD: $MAILEXAM_PASSWORD
  MAILEXAM_PORT: "587"
  MAIL_FROM: "noreply@example.test"
```

After sending a message in a test, verify delivery via the [Mailexam API](https://mailexam.io/api).

## Troubleshooting

**Module not found after copy**

- Path must be `app/code/Mailexam/Smtp`, then run `bin/magento setup:upgrade`.

**TLS or authentication failed**

- Host must be `{login}.mailexam.io`, username the same login from the email.
- Login and password must come from the same Mailexam project.

**Test endpoint returns 404**

- Run `bin/magento cache:flush`.
- Confirm the store front URL and that the module is enabled.

**Message not in the dashboard**

- Open the inbox of the same Mailexam project.
- Check Magento logs in `var/log/` and the JSON error body from the test endpoint.

## See also

- [Mailexam Magento guide (wiki)](https://wiki.mailexam.ru/en/examples/magento/)
- [WordPress](https://github.com/mailexam/WordPress), [Laravel](https://github.com/mailexam/Laravel), [Symfony](https://github.com/mailexam/Symfony) — other PHP stacks
- [Magento DevDocs — Email](https://developer.adobe.com/commerce/php/development/components/email/)
- [Mailexam API documentation](https://mailexam.io/api)

## License

Apache 2.0
