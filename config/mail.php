<?php
// config/mail.php
return [
    'host'     => getenv('SMTP_HOST') ?: 'smtp.gmail.com',
    'port'     => (int) (getenv('SMTP_PORT') ?: 587),
    'secure'   => getenv('SMTP_SECURE') ?: 'tls',
    'username' => getenv('SMTP_USERNAME') ?: '',
    'password' => getenv('SMTP_PASSWORD') ?: '',
    'from'     => getenv('MAIL_FROM_ADDRESS') ?: 'no-reply@dipselgroup.com',
    'fromName' => getenv('MAIL_FROM_NAME') ?: 'Dipsel Auto System',
];
