<?php

echo "START<br>";

require_once 'config/database.php';

echo "DATABASE OK<br>";

$total = $pdo->query("SELECT COUNT(*) FROM customers")->fetchColumn();

echo "Customers: $total<br>";

echo "FINISHED";