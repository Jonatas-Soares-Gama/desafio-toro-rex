<?php

declare(strict_types=1);

use App\Infrastructure\Database\ConnectionFactory;

require dirname(__DIR__) . '/vendor/autoload.php';

$pdo = (new ConnectionFactory())->create();
$schema = file_get_contents(dirname(__DIR__) . '/database/schema.sql');

if ($schema === false) {
    throw new RuntimeException('Unable to read database schema.');
}

$pdo->exec($schema);

$adminPassword = password_hash('admin123', PASSWORD_DEFAULT);
$sellerPassword = password_hash('seller123', PASSWORD_DEFAULT);

$userStatement = $pdo->prepare(
    'INSERT INTO users (name, email, password_hash, role) VALUES (:name, :email, :password_hash, :role)
     ON DUPLICATE KEY UPDATE name = VALUES(name), password_hash = VALUES(password_hash), role = VALUES(role)',
);

foreach ([
    ['Administrador', 'admin@toro.local', $adminPassword, 'admin'],
    ['Vendedor Um', 'seller1@toro.local', $sellerPassword, 'seller'],
    ['Vendedor Dois', 'seller2@toro.local', $sellerPassword, 'seller'],
] as [$name, $email, $password, $role]) {
    $userStatement->execute([
        'name' => $name,
        'email' => $email,
        'password_hash' => $password,
        'role' => $role,
    ]);
}

$productStatement = $pdo->prepare(
    'INSERT INTO products (name, sku, points_per_unit) VALUES (:name, :sku, :points_per_unit)
     ON DUPLICATE KEY UPDATE name = VALUES(name), points_per_unit = VALUES(points_per_unit), active = TRUE',
);

foreach ([
    ['Produto A', 'PROD-A', 100],
    ['Produto B', 'PROD-B', 250],
] as [$name, $sku, $points]) {
    $productStatement->execute(['name' => $name, 'sku' => $sku, 'points_per_unit' => $points]);
}

$campaignStatement = $pdo->prepare(
    'INSERT INTO campaigns (name, budget_total, starts_at, ends_at, status)
     SELECT :name, :budget, NOW(), DATE_ADD(NOW(), INTERVAL 30 DAY), :status
     WHERE NOT EXISTS (SELECT 1 FROM campaigns WHERE name = :existing_name)',
);
$campaignStatement->execute([
    'name' => 'Campanha Inicial',
    'budget' => 10000,
    'status' => 'active',
    'existing_name' => 'Campanha Inicial',
]);

fwrite(STDOUT, "Database schema and seed applied.\n");
