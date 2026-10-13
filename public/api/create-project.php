<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'error' => 'Method not allowed.',
    ]);
    exit;
}

try {
    require __DIR__ . '/../../config/database.php';

    $input = json_decode(
        file_get_contents('php://input'),
        true,
        512,
        JSON_THROW_ON_ERROR
    );

    if (!is_array($input)) {
        throw new InvalidArgumentException('Invalid request body.');
    }

    $name = trim((string) ($input['name'] ?? ''));
    $description = trim((string) ($input['description'] ?? ''));
    $status = $input['status'] ?? 'planning';

    if ($name === '' || mb_strlen($name) > 150) {
        throw new InvalidArgumentException(
            'Project name is required and must be at most 150 characters.'
        );
    }

    if (mb_strlen($description) > 5000) {
        throw new InvalidArgumentException(
            'Description must be at most 5000 characters.'
        );
    }

    $allowedStatuses = ['planning', 'in_progress', 'completed', 'on_hold'];

    if (!is_string($status) || !in_array($status, $allowedStatuses, true)) {
        throw new InvalidArgumentException('Invalid project status.');
    }

    $statement = $pdo->prepare(
        'INSERT INTO projects (name, description, status)
         VALUES (:name, :description, :status)'
    );

    $statement->execute([
        'name' => $name,
        'description' => $description !== '' ? $description : null,
        'status' => $status,
    ]);

    http_response_code(201);

    echo json_encode([
        'success' => true,
        'message' => 'Project created successfully.',
        'data' => [
            'id' => (int) $pdo->lastInsertId(),
            'name' => $name,
            'description' => $description,
            'status' => $status,
        ],
    ], JSON_THROW_ON_ERROR);
} catch (InvalidArgumentException $exception) {
    http_response_code(422);
    echo json_encode([
        'success' => false,
        'error' => $exception->getMessage(),
    ]);
} catch (JsonException $exception) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Invalid JSON request.',
    ]);
} catch (Throwable $exception) {
    error_log('CampusFlow create-project API error: ' . $exception->getMessage());

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Unable to create project.',
    ]);
}
