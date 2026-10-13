<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    header('Allow: GET');
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

try {
    require __DIR__ . '/../../config/database.php';

    $statement = $pdo->query(
        'SELECT id, name, description, status, created_at, updated_at
         FROM projects
         ORDER BY created_at DESC, id DESC'
    );

    echo json_encode([
        'success' => true,
        'data' => $statement->fetchAll(),
    ], JSON_THROW_ON_ERROR);
} catch (Throwable $exception) {
    error_log('CampusFlow projects API error: ' . $exception->getMessage());

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Unable to retrieve projects.',
    ]);
}
