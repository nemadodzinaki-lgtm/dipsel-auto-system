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

$host     = '127.0.0.1';
$database = 'dipsel_motors';
$username = 'root';
$password = '';
$charset  = 'utf8mb4';

$dsn = "mysql:host={$host};dbname={$database};charset={$charset}";

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