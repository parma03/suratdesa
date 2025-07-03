<?php
// config/email_config.php
// Konfigurasi Email - Sesuaikan dengan provider email Anda

return [
    // Gmail SMTP Configuration
    'gmail' => [
        'host' => 'smtp.gmail.com',
        'port' => 587,
        'encryption' => 'tls',
        'username' => 'poseidonseal03@gmail.com', // Ganti dengan email Anda
        'password' => 'mdnduaoqlwyrgawg',     // Ganti dengan App Password Gmail bukan password gmail
        'from_email' => 'poseidonseal03@gmail.com', // Ganti dengan email Anda
        'from_name' => 'Kantor Camat Sutera'
    ],

    // Yahoo SMTP Configuration
    'yahoo' => [
        'host' => 'smtp.mail.yahoo.com',
        'port' => 587,
        'encryption' => 'tls',
        'username' => 'your-email@yahoo.com',
        'password' => 'your-password',
        'from_email' => 'your-email@yahoo.com',
        'from_name' => 'Kantor Camat Sutera'
    ],

    // Outlook/Hotmail SMTP Configuration
    'outlook' => [
        'host' => 'smtp-mail.outlook.com',
        'port' => 587,
        'encryption' => 'tls',
        'username' => 'your-email@outlook.com',
        'password' => 'your-password',
        'from_email' => 'your-email@outlook.com',
        'from_name' => 'Kantor Camat Sutera'
    ],

    // Custom SMTP Configuration
    'custom' => [
        'host' => 'your-smtp-server.com',
        'port' => 587,
        'encryption' => 'tls', // atau 'ssl'
        'username' => 'your-email@domain.com',
        'password' => 'your-password',
        'from_email' => 'noreply@domain.com',
        'from_name' => 'Kantor Camat Sutera'
    ],

    // Pilih provider yang akan digunakan
    'active_provider' => 'gmail' // Ganti dengan: gmail, yahoo, outlook, atau custom
];
?>