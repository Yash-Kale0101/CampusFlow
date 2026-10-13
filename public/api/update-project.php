<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
    header('Allow: PUT');
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'error' => 'Method not allowed.',
    ]);
    exit;
}

try {
    $input = json_decode(
        file_get_contents('php://input'),
        true,
        512,
        JSON_THROW_ON_ERROR
    );

    if (!is_array($input)) {
        throw new InvalidArgumentException('Invalid request body.');
    }

    $id = filter_var($input['id'] ?? null, FILTER_VALIDATE_INT);
    $name = trim((string) ($input['name'] ?? ''));
    $description = trim((string) ($input['description'] ?? ''));
    $status = $input['status'] ?? 'planning';

    if ($id === false || $id === null || $id < 1) {
        throw new InvalidArgumentException('A valid project ID is required.');
    }

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

    require __DIR__ . '/../../config/database.php';

    $statement = $pdo->prepare(
        'UPDATE projects
         SET name = :name,
             description = :description,
             status = :status
         WHERE id = :id'
    );

    $statement->execute([
        'name' => $name,
        'description' => $description !== '' ? $description : null,
        'status' => $status,
        'id' => $id,
    ]);

    $check = $pdo->prepare('SELECT id FROM projects WHERE id = :id');
    $check->execute(['id' => $id]);

    if (!$check->fetch()) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'error' => 'Project not found.',
        ]);
        exit;
    }

    echo json_encode([
        'success' => true,
        'message' => 'Project updated successfully.',
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
    error_log('CampusFlow update-project API error: ' . $exception->getMessage());

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Unable to update project.',
    ]);
}
