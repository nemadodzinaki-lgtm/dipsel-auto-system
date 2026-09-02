<?php
/*
$host = '127.0.0.1';
$db   = 'dipsel_motors';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    die('Database connection failed: ' . $e->getMessage());
}

*/

/**
 * -------------------------------------------------------
 * Dipsel Auto System
 * Database Configuration
 * -------------------------------------------------------
 */

declare(strict_types=1);

$readEnv = static function (string $name, string $default = ''): string {
    $value = getenv($name);

    return $value === false ? $default : $value;
};

$host     = $readEnv('DB_HOST', '127.0.0.1');
$port     = $readEnv('DB_PORT', '3306');
$database = $readEnv('DB_NAME', 'dipsel_motors');
$username = $readEnv('DB_USER', 'root');
$password = $readEnv('DB_PASSWORD');
$charset  = $readEnv('DB_CHARSET', 'utf8mb4');

$dsn = "mysql:host={$host};port={$port};dbname={$database};charset={$charset}";

$options = [

    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,

    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,

    PDO::ATTR_EMULATE_PREPARES   => false,

    PDO::ATTR_PERSISTENT         => false,

    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"

];

try {

    $pdo = new PDO($dsn, $username, $password, $options);

} catch (PDOException $e) {

    exit(
        '<h2 style="font-family:Arial;color:#dc3545;">
            Database Connection Failed
        </h2>
        <p style="font-family:Arial;">
            Please check your database configuration.
        </p>'
    );

}
