<?php

declare(strict_types=1);

require __DIR__ . '/../config/database.php';

$version = $pdo->query('SELECT VERSION()')->fetchColumn();

echo "CampusFlow database connection successful.\n";
echo "MariaDB version: " . $version . "\n";
