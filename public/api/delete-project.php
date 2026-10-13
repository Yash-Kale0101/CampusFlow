<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    header('Allow: DELETE');
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

    if ($id === false || $id === null || $id < 1) {
        throw new InvalidArgumentException('A valid project ID is required.');
    }

    require __DIR__ . '/../../config/database.php';

    $statement = $pdo->prepare(
        'DELETE FROM projects WHERE id = :id'
    );

    $statement->execute(['id' => $id]);

    if ($statement->rowCount() === 0) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'error' => 'Project not found.',
        ]);
        exit;
    }

    echo json_encode([
        'success' => true,
        'message' => 'Project deleted successfully.',
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
    error_log('CampusFlow delete-project API error: ' . $exception->getMessage());

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Unable to delete project.',
    ]);
}
