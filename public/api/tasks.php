<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    header('Allow: GET');
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'error' => 'Method not allowed.',
    ]);
    exit;
}

try {
    require __DIR__ . '/../../config/database.php';

    $sql = <<<'SQL'
        SELECT
            t.id,
            t.project_id,
            t.title,
            t.description,
            t.status,
            t.priority,
            t.due_date,
            t.created_at,
            t.updated_at,
            p.name AS project_name
        FROM tasks t
        INNER JOIN projects p ON p.id = t.project_id
        ORDER BY
            CASE t.status
                WHEN 'todo' THEN 1
                WHEN 'in_progress' THEN 2
                WHEN 'completed' THEN 3
            END,
            t.due_date IS NULL,
            t.due_date ASC,
            t.id DESC
    SQL;

    $statement = $pdo->query($sql);

    echo json_encode([
        'success' => true,
        'data' => $statement->fetchAll(),
    ], JSON_THROW_ON_ERROR);
} catch (Throwable $exception) {
    error_log('CampusFlow tasks API error: ' . $exception->getMessage());

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Unable to retrieve tasks.',
    ]);
}
