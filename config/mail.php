<?php
// config/mail.php
return [
    'host'     => 'smtp.gmail.com',          // e.g., smtp.sendgrid.net
    'port'     => 587,                       // 465 for SSL, 587 for TLS
    'secure'   => 'tls',                     // 'ssl' or 'tls'
    'username' => 'nemadodzinaki@gmail.com',    // SMTP username
    'password' => 'your-app-password',       // SMTP password (Gmail: app password)
    'from'     => 'no-reply@dipselgroup.com', // From email address
    'fromName' => 'Dipsel Auto System',      // From name
];